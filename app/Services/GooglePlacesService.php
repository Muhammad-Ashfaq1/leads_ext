<?php

namespace App\Services;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Support\PromptNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GooglePlacesService
{
    private const PLACES_API_URL = 'https://places.googleapis.com/v1/places:searchText';

    public function __construct(
        private readonly GeospatialGridService $gridService,
        private readonly SocialMediaExtractor $socialExtractor,
        private readonly EmailVerifier $emailVerifier,
    ) {}

    public function stream(ExtractionJob $job, ?string $apiKey = null, array $filters = [], ?string $location = null): StreamedResponse
    {
        $key = $apiKey ?: config('services.google.maps_api_key');

        return response()->stream(function () use ($job, $key, $filters, $location): void {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            if (! app()->environment('testing')) {
                while (ob_get_level() > 0) {
                    @ob_end_flush();
                }
            }
            echo "retry: 2000\n\n";
            $this->flush();

            if (empty($key)) {
                $this->sendSseEvent('error', [
                    'type' => 'error',
                    'status' => ExtractionJob::STATUS_ERROR,
                    'message' => 'Google Maps API key is missing. Please add GOOGLE_MAPS_API_KEY in .env or enter it in the search bar.',
                ]);
                $job->forceFill([
                    'status' => ExtractionJob::STATUS_ERROR,
                    'error' => 'Google Maps API key is missing.',
                    'completed_at' => now(),
                ])->save();

                return;
            }

            $job->forceFill([
                'status' => ExtractionJob::STATUS_STARTING,
                'started_at' => now(),
                'current_activity' => 'Starting Google Places API extraction...',
            ])->save();

            $this->sendSseEvent('started', [
                'type' => 'started',
                'status' => ExtractionJob::STATUS_STARTING,
                'message' => 'Connected to Google Places API.',
            ]);

            [$searchTerm, $resolvedLocation] = $this->resolveSearchQueryAndLocation($job, $location);

            $this->sendSseEvent('searching', [
                'type' => 'searching',
                'status' => ExtractionJob::STATUS_SEARCHING,
                'query' => $job->query,
                'message' => "Searching Google Places for '{$job->query}'...",
            ]);

            $job->forceFill([
                'status' => ExtractionJob::STATUS_SEARCHING,
                'current_activity' => "Searching: {$job->query}",
            ])->save();

            $limit = $job->limit ?: 100;
            $extractedCount = 0;
            $seenCount = 0;
            $emailsCount = 0;
            $websitesCount = 0;

            $reqWebsite = (bool) ($filters['require_website'] ?? false);
            $reqPhone = (bool) ($filters['require_phone'] ?? false);
            $reqEmail = (bool) ($filters['require_email'] ?? false);
            $minRating = (float) ($filters['min_rating'] ?? 0);
            $minReviews = (int) ($filters['min_reviews'] ?? 0);

            // In-memory deduplication tracking
            $seenPlaceIds = [];
            $seenSignatures = [];

            // Attempt Geospatial Grid Subdivision
            $gridCells = [];
            if (! empty($resolvedLocation)) {
                $this->sendSseEvent('progress', [
                    'type' => 'progress',
                    'status' => ExtractionJob::STATUS_SEARCHING,
                    'current_activity' => "Geocoding location '{$resolvedLocation}' for geospatial grid partition...",
                    'businesses_seen' => $seenCount,
                    'leads_extracted' => $extractedCount,
                    'emails_found' => $emailsCount,
                    'websites_found' => $websitesCount,
                ]);

                $bounds = $this->gridService->geocode($resolvedLocation, $key);
                if ($bounds) {
                    $gridCells = $this->gridService->generateGrid($bounds, stepKm: 0.0, targetLimit: $limit);
                    $cellCount = count($gridCells);

                    $this->sendSseEvent('progress', [
                        'type' => 'progress',
                        'status' => ExtractionJob::STATUS_SEARCHING,
                        'current_activity' => "Partitioned '{$resolvedLocation}' into {$cellCount} search grid cells.",
                        'businesses_seen' => $seenCount,
                        'leads_extracted' => $extractedCount,
                        'emails_found' => $emailsCount,
                        'websites_found' => $websitesCount,
                    ]);
                }
            }

            // Fallback to single unrestricted query if no grid cells generated
            if (empty($gridCells)) {
                $gridCells = [null];
            }

            try {
                $totalCells = count($gridCells);

                foreach ($gridCells as $cellIdx => $cell) {
                    if ($extractedCount >= $limit) {
                        break;
                    }

                    // Check if job was cancelled externally
                    $job->refresh();
                    if ($job->isTerminal()) {
                        break;
                    }

                    if (connection_aborted()) {
                        break;
                    }

                    if ($totalCells > 1 && $cell !== null) {
                        $cellNum = $cellIdx + 1;
                        $this->sendSseEvent('progress', [
                            'type' => 'progress',
                            'status' => ExtractionJob::STATUS_EXTRACTING,
                            'current_activity' => "Scanning grid cell {$cellNum} of {$totalCells} in {$resolvedLocation}...",
                            'businesses_seen' => $seenCount,
                            'leads_extracted' => $extractedCount,
                            'emails_found' => $emailsCount,
                            'websites_found' => $websitesCount,
                        ]);
                    }

                    $pageToken = null;

                    do {
                        if ($extractedCount >= $limit) {
                            break 2;
                        }

                        $payload = [
                            'textQuery' => $searchTerm ?: $job->query,
                            'pageSize' => min(20, max(20, $limit - $extractedCount)),
                        ];

                        if ($pageToken) {
                            $payload['pageToken'] = $pageToken;
                        }

                        if ($cell !== null) {
                            $payload['locationRestriction'] = [
                                'rectangle' => [
                                    'low' => $cell['low'],
                                    'high' => $cell['high'],
                                ],
                            ];
                        }

                        $response = Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'X-Goog-Api-Key' => $key,
                            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.rating,places.userRatingCount,places.googleMapsUri,places.primaryTypeDisplayName,places.photos,places.location,nextPageToken',
                        ])->timeout(15)->post(self::PLACES_API_URL, $payload);

                        if ($response->failed()) {
                            $errorBody = $response->json();
                            $errorMessage = $errorBody['error']['message'] ?? 'Google Places API request failed with HTTP '.$response->status();
                            Log::error('Google Places API error', ['status' => $response->status(), 'body' => $errorBody]);

                            // If single cell or auth error, terminate with error
                            if ($totalCells === 1 || in_array($response->status(), [401, 403], true)) {
                                $this->sendSseEvent('error', [
                                    'type' => 'error',
                                    'status' => ExtractionJob::STATUS_ERROR,
                                    'message' => 'Google Maps API Error: '.$errorMessage,
                                ]);

                                $job->forceFill([
                                    'status' => ExtractionJob::STATUS_ERROR,
                                    'error' => $errorMessage,
                                    'completed_at' => now(),
                                ])->save();

                                return;
                            }

                            // Otherwise log warning and proceed to next cell
                            Log::warning("Grid cell {$cellIdx} request failed, skipping to next cell", ['error' => $errorMessage]);
                            break;
                        }

                        $data = $response->json();
                        $places = $data['places'] ?? [];
                        $pageToken = $data['nextPageToken'] ?? null;

                        if (empty($places)) {
                            break;
                        }

                        foreach ($places as $place) {
                            if ($extractedCount >= $limit) {
                                break 2;
                            }

                            if (connection_aborted()) {
                                break 2;
                            }

                            $seenCount++;
                            $name = $place['displayName']['text'] ?? null;
                            if (! $name) {
                                continue;
                            }

                            $placeId = $place['id'] ?? null;
                            $address = $place['formattedAddress'] ?? null;

                            // In-memory deduplication check
                            if ($placeId) {
                                if (isset($seenPlaceIds[$placeId])) {
                                    continue;
                                }
                                $seenPlaceIds[$placeId] = true;
                            } else {
                                $signature = strtolower(trim($name)).'|'.strtolower(trim((string) $address));
                                if (isset($seenSignatures[$signature])) {
                                    continue;
                                }
                                $seenSignatures[$signature] = true;
                            }

                            $phone = $place['nationalPhoneNumber'] ?? ($place['internationalPhoneNumber'] ?? null);
                            $website = $place['websiteUri'] ?? null;
                            $rating = isset($place['rating']) ? (float) $place['rating'] : null;
                            $reviews = isset($place['userRatingCount']) ? (int) $place['userRatingCount'] : null;
                            $lat = isset($place['location']['latitude']) ? (float) $place['location']['latitude'] : null;
                            $lng = isset($place['location']['longitude']) ? (float) $place['location']['longitude'] : null;

                            // Pre-extraction filter: require website
                            if ($reqWebsite && empty($website)) {
                                continue;
                            }

                            // Pre-extraction filter: require phone
                            if ($reqPhone && empty($phone)) {
                                continue;
                            }

                            // Pre-extraction filter: min rating
                            if ($minRating > 0 && ($rating === null || $rating < $minRating)) {
                                continue;
                            }

                            // Pre-extraction filter: min reviews
                            if ($minReviews > 0 && ($reviews === null || $reviews < $minReviews)) {
                                continue;
                            }

                            $emails = [];
                            $socialLinks = [];
                            $emailVerificationStatus = [];

                            if ($website) {
                                $websitesCount++;
                                $enrichment = $this->quickEnrichWebsite($website);
                                $emails = $enrichment['emails'];
                                $socialLinks = $enrichment['social_links'];
                                $emailVerificationStatus = $enrichment['email_verification_status'];

                                if (! empty($emails)) {
                                    $emailsCount += count($emails);
                                }
                            }

                            // Pre-extraction filter: require email
                            if ($reqEmail && empty($emails)) {
                                continue;
                            }

                            // Avatar image resolution
                            $avatarUrl = null;
                            if (! empty($place['photos'][0]['name'])) {
                                $photoName = $place['photos'][0]['name'];
                                $avatarUrl = "https://places.googleapis.com/v1/{$photoName}/media?maxHeightPx=160&maxWidthPx=160&key={$key}";
                            } elseif (! empty($website)) {
                                $domain = parse_url($website, PHP_URL_HOST) ?: $website;
                                $avatarUrl = "https://www.google.com/s2/favicons?domain=".urlencode($domain)."&sz=128";
                            }

                            $leadData = [
                                'business_name' => $name,
                                'address' => $address,
                                'phone' => $phone,
                                'emails' => $emails,
                                'social_links' => $socialLinks,
                                'email_verification_status' => $emailVerificationStatus,
                                'avatar_url' => $avatarUrl,
                                'website' => $website,
                                'google_maps_url' => $place['googleMapsUri'] ?? null,
                                'place_id' => $placeId,
                                'category' => $place['primaryTypeDisplayName']['text'] ?? null,
                                'rating' => $rating,
                                'review_count' => $reviews,
                                'latitude' => $lat,
                                'longitude' => $lng,
                                'source' => 'Google Places API',
                                'metadata' => [
                                    'place_id' => $placeId,
                                    'business_status' => $place['businessStatus'] ?? null,
                                    'grid_cell' => $cell ? ['row' => $cell['row'] ?? null, 'col' => $cell['col'] ?? null] : null,
                                ],
                                'extracted_at' => now(),
                                'tenant_id' => $job->tenant_id,
                                'user_id' => $job->user_id,
                            ];

                            // Database deduplication check for current job
                            $exists = $job->leads()
                                ->when(! empty($placeId), fn ($q) => $q->where('place_id', $placeId))
                                ->when(empty($placeId), fn ($q) => $q->where('business_name', $name)->where('address', $address))
                                ->exists();

                            if (! $exists) {
                                $createdLead = $job->leads()->create($leadData);
                                $leadData['id'] = $createdLead->id;
                                $extractedCount++;
                            }

                            $job->forceFill([
                                'status' => ExtractionJob::STATUS_EXTRACTING,
                                'businesses_seen' => $seenCount,
                                'leads_extracted' => $extractedCount,
                                'emails_found' => $emailsCount,
                                'websites_found' => $websitesCount,
                                'current_activity' => $name,
                            ])->save();

                            $this->sendSseEvent('lead', [
                                'type' => 'lead',
                                'status' => ExtractionJob::STATUS_EXTRACTING,
                                'lead' => $leadData,
                                'businesses_seen' => $seenCount,
                                'leads_extracted' => $extractedCount,
                                'emails_found' => $emailsCount,
                                'websites_found' => $websitesCount,
                            ]);

                            // Micro-delay between stream yields for smooth visual streaming
                            usleep(60000);
                        }

                        if ($pageToken && $extractedCount < $limit) {
                            // Google Places API recommends a short delay before querying nextPageToken
                            usleep(1000000);
                        }
                    } while ($pageToken && $extractedCount < $limit);
                }

                $job->forceFill([
                    'status' => ExtractionJob::STATUS_COMPLETED,
                    'businesses_seen' => $seenCount,
                    'leads_extracted' => $extractedCount,
                    'emails_found' => $emailsCount,
                    'websites_found' => $websitesCount,
                    'current_activity' => 'Extraction completed.',
                    'completed_at' => now(),
                ])->save();

                if ($job->tenant_id && $extractedCount > 0) {
                    $job->tenant?->incrementLeadsCount($extractedCount);
                }

                $this->sendSseEvent('completed', [
                    'type' => 'completed',
                    'status' => ExtractionJob::STATUS_COMPLETED,
                    'message' => "Extraction completed. Extracted {$extractedCount} leads.",
                    'businesses_seen' => $seenCount,
                    'leads_extracted' => $extractedCount,
                    'emails_found' => $emailsCount,
                    'websites_found' => $websitesCount,
                ]);

            } catch (Throwable $e) {
                Log::error('Google Places Service stream error', ['error' => $e->getMessage()]);

                $job->forceFill([
                    'status' => ExtractionJob::STATUS_ERROR,
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                ])->save();

                $this->sendSseEvent('error', [
                    'type' => 'error',
                    'status' => ExtractionJob::STATUS_ERROR,
                    'message' => 'Error during Places API extraction: '.$e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Resolve search query keyword and location from ExtractionJob and optional explicit location.
     *
     * @return array{0: string, 1: string|null} [searchTerm, location]
     */
    public function resolveSearchQueryAndLocation(ExtractionJob $job, ?string $explicitLocation = null): array
    {
        $location = $explicitLocation ? trim($explicitLocation) : null;
        $searchTerm = null;

        // If location not explicitly passed, try extracting from prompt or query
        if (empty($location)) {
            if (preg_match('/^(.*?)\s*\((.*?)\)$/', $job->prompt, $matches)) {
                $searchTerm = trim($matches[1]);
                $location = trim($matches[2]);
            } elseif (preg_match('/^(.*?)\s+in\s+(.*)$/i', $job->query, $matches)) {
                $searchTerm = trim($matches[1]);
                $location = trim($matches[2]);
            }
        }

        if (empty($searchTerm)) {
            if (! empty($location) && preg_match('/^(.*?)\s+in\s+'.preg_quote($location, '/').'$/i', $job->query, $matches)) {
                $searchTerm = trim($matches[1]);
            } else {
                $searchTerm = PromptNormalizer::toSearchQuery($job->prompt);
            }
        }

        return [
            PromptNormalizer::toSearchQuery($searchTerm ?: $job->query),
            $location ?: null,
        ];
    }

    /**
     * Single-pass website enrichment: extracts emails and social media links from HTML,
     * and validates discovered emails with multi-tier verification (RFC, disposable, DNS MX cache).
     * Safe, non-intrusive, 2.5 second timeout.
     *
     * @return array{emails: array<string>, social_links: array<string, string>, email_verification_status: array<string, array>}
     */
    public function quickEnrichWebsite(string $websiteUrl): array
    {
        if (empty($websiteUrl) || ! filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            return [
                'emails' => [],
                'social_links' => [],
                'email_verification_status' => [],
            ];
        }

        $host = parse_url($websiteUrl, PHP_URL_HOST);
        if (! $host || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return [
                'emails' => [],
                'social_links' => [],
                'email_verification_status' => [],
            ];
        }

        $foundEmails = [];
        $socialLinks = [];
        $verificationStatus = [];

        try {
            $resp = Http::timeout(2.5)
                ->withUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
                ->get($websiteUrl);

            if ($resp->successful()) {
                $html = $resp->body();
                $foundEmails = $this->extractEmailsFromHtml($html);
                $socialLinks = $this->socialExtractor->extract($html);
                $verificationStatus = $this->emailVerifier->verifyBatch($foundEmails);
            }
        } catch (Throwable) {
            // Non-blocking enrichment
        }

        return [
            'emails' => array_values(array_unique($foundEmails)),
            'social_links' => $socialLinks,
            'email_verification_status' => $verificationStatus,
        ];
    }

    /**
     * Convenience method to extract emails from a website.
     *
     * @return array<string>
     */
    public function quickEnrichWebsiteEmails(string $websiteUrl): array
    {
        return $this->quickEnrichWebsite($websiteUrl)['emails'];
    }

    private function extractEmailsFromHtml(string $html): array
    {
        $emails = [];

        // Match mailto: links
        if (preg_match_all('/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $html, $matches)) {
            foreach ($matches[1] as $email) {
                $clean = strtolower(trim($email));
                if (! str_ends_with($clean, '.png') && ! str_ends_with($clean, '.jpg') && ! str_ends_with($clean, '.svg')) {
                    $emails[] = $clean;
                }
            }
        }

        // Match any email pattern in the document
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i', $html, $matches)) {
            foreach ($matches[0] as $email) {
                $clean = strtolower(trim($email));
                if (! str_ends_with($clean, '.png') && ! str_ends_with($clean, '.jpg') && ! str_ends_with($clean, '.svg') && ! str_ends_with($clean, '.webp')) {
                    $emails[] = $clean;
                }
            }
        }

        return array_slice(array_values(array_unique($emails)), 0, 5);
    }

    private function sendSseEvent(string $event, array $data): void
    {
        echo 'data: '.json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n\n";
        $this->flush();
    }

    private function flush(): void
    {
        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        flush();
    }
}
