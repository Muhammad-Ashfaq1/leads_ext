<?php

namespace App\Services;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Support\PromptNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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
            $reqNoWebsite = (bool) ($filters['without_website'] ?? ($filters['require_no_website'] ?? false));
            if (isset($filters['website_status'])) {
                if (in_array($filters['website_status'], ['has_website', 'yes'], true)) {
                    $reqWebsite = true;
                    $reqNoWebsite = false;
                } elseif (in_array($filters['website_status'], ['without_website', 'no_website', 'no'], true)) {
                    $reqNoWebsite = true;
                    $reqWebsite = false;
                }
            }
            $reqPhone = (bool) ($filters['require_phone'] ?? false);
            $reqEmail = (bool) ($filters['require_email'] ?? false);
            $minRating = (float) ($filters['min_rating'] ?? 0);
            $minReviews = (int) ($filters['min_reviews'] ?? 0);

            // In-memory deduplication tracking
            $seenPlaceIds = [];
            $seenSignatures = [];

            $textQuery = $this->buildTextQuery($searchTerm, $resolvedLocation, $job->query);
            $regionCode = $this->inferRegionCode($resolvedLocation);
            $searchBounds = null;

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
                $geocodeFailure = $this->gridService->lastFailure();
                if ($this->isBillingFailure($geocodeFailure['error'] ?? null)) {
                    $this->failJob($job, $this->formatGoogleCloudError($geocodeFailure['error'] ?? $geocodeFailure['status'] ?? null));

                    return;
                }
                if ($bounds) {
                    $searchBounds = $bounds;
                    $gridCells = $this->gridService->generateGrid($bounds, stepKm: 0.0, targetLimit: $limit);
                    if (empty($gridCells)) {
                        $gridCells = [$this->cellFromBounds($bounds)];
                    }
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

            $tasks = $this->buildSearchTasks($searchTerm, $resolvedLocation, $job->query, $searchBounds, $gridCells, $limit);
            $totalTasks = count($tasks);

            try {
                foreach ($tasks as $taskIdx => $task) {
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

                    $taskNum = $taskIdx + 1;
                    $currentQueryText = $task['query'];
                    $cell = $task['cell'] ?? null;
                    $taskLabel = $task['label'] ?? $currentQueryText;

                    $this->sendSseEvent('progress', [
                        'type' => 'progress',
                        'status' => ExtractionJob::STATUS_EXTRACTING,
                        'current_activity' => $totalTasks > 1 ? "Target {$taskNum}/{$totalTasks}: {$taskLabel} ({$extractedCount}/{$limit} leads)..." : "Searching {$currentQueryText}...",
                        'businesses_seen' => $seenCount,
                        'leads_extracted' => $extractedCount,
                        'emails_found' => $emailsCount,
                        'websites_found' => $websitesCount,
                    ]);

                    $pageToken = null;
                    $taskPage = 1;

                    try {
                        do {
                            if ($extractedCount >= $limit) {
                                break 2;
                            }

                            $payload = [
                                'textQuery' => $currentQueryText,
                                'pageSize' => min(20, max(20, $limit - $extractedCount)),
                                'rankPreference' => 'RELEVANCE',
                            ];

                            if ($regionCode) {
                                $payload['regionCode'] = $regionCode;
                            }

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
                            } elseif ($searchBounds) {
                                $cityCell = $this->cellFromBounds($searchBounds);
                                $payload['locationRestriction'] = [
                                    'rectangle' => [
                                        'low' => $cityCell['low'],
                                        'high' => $cityCell['high'],
                                    ],
                                ];
                            }

                            $response = Http::withHeaders([
                                'Content-Type' => 'application/json',
                                'X-Goog-Api-Key' => $key,
                                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.rating,places.userRatingCount,places.primaryTypeDisplayName,nextPageToken',
                            ])
                                ->timeout(35)
                                ->connectTimeout(10)
                                ->retry(3, 500, function ($exception, $request) {
                                    return $exception instanceof ConnectionException;
                                }, throw: false)
                                ->post(self::PLACES_API_URL, $payload);

                            if ($response->failed()) {
                                $errorBody = $response->json();
                                $errorMessage = $errorBody['error']['message'] ?? 'Google Places API request failed with HTTP '.$response->status();
                                Log::error('Google Places API error', ['status' => $response->status(), 'body' => $errorBody]);

                                // If auth error, terminate with error
                                if (in_array($response->status(), [401, 403], true)) {
                                    $this->failJob($job, $this->formatGoogleCloudError($errorMessage));

                                    return;
                                }

                                if ($totalTasks === 1) {
                                    $this->failJob($job, $this->formatGoogleCloudError($errorMessage));

                                    return;
                                }

                                // Otherwise log warning, emit non-fatal SSE event and proceed to next target
                                Log::warning("Target {$taskNum} request failed with HTTP {$response->status()}, skipping to next target", ['error' => $errorMessage]);
                                $this->sendSseEvent('warning', [
                                    'type' => 'warning',
                                    'status' => ExtractionJob::STATUS_EXTRACTING,
                                    'message' => "Target {$taskNum} request failed (HTTP {$response->status()}), continuing to next area...",
                                    'businesses_seen' => $seenCount,
                                    'leads_extracted' => $extractedCount,
                                    'emails_found' => $emailsCount,
                                    'websites_found' => $websitesCount,
                                ]);
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

                            $job->refresh();
                            if ($job->isTerminal()) {
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

                            if (! $this->placeMatchesTargetLocation($place, $resolvedLocation, $searchBounds)) {
                                Log::info('Skipped place outside target location', [
                                    'name' => $name,
                                    'address' => $address,
                                    'target' => $resolvedLocation,
                                ]);
                                continue;
                            }

                            // Pre-extraction filter: require website
                            if ($reqWebsite && empty($website)) {
                                continue;
                            }

                            // Pre-extraction filter: require NO website (without website only)
                            if ($reqNoWebsite && ! empty($website)) {
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

                            // Avatar image resolution (use verified Google Place photo if present)
                            $avatarUrl = null;
                            if (! empty($place['photos'][0]['name'])) {
                                $photoName = $place['photos'][0]['name'];
                                $avatarUrl = "https://places.googleapis.com/v1/{$photoName}/media?maxHeightPx=160&maxWidthPx=160&key={$key}";
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
                                'google_maps_url' => $place['googleMapsUri'] ?? ($placeId ? "https://www.google.com/maps/place/?q=place_id:{$placeId}" : null),
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
                                'tenant_id' => $job->tenant_id ?? \App\Models\Tenant::first()?->id,
                                'user_id' => $job->user_id ?? \App\Models\User::where('email', 'admin@obtainsolutions.com')->value('id'),
                                'status' => 'saved',
                                'is_saved' => true,
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
                } catch (RequestException $cellException) {
                    $status = $cellException->response?->status();
                    $errorBody = $cellException->response?->json();
                    $errorMessage = $errorBody['error']['message'] ?? $cellException->getMessage();
                    Log::warning("Grid cell {$cellNum} request failed: {$errorMessage}", [
                        'cell_index' => $cellIdx,
                        'cell' => $cell,
                        'status' => $status,
                        'error' => $errorMessage,
                    ]);

                    if (in_array($status, [401, 403], true) || $this->isFatalGoogleCloudFailure($errorMessage, (string) $status)) {
                        $this->failJob($job, $this->formatGoogleCloudError($errorMessage));

                        return;
                    }

                    if ($totalCells === 1) {
                        $this->failJob($job, $this->formatGoogleCloudError($errorMessage));

                        return;
                    }

                    $this->sendSseEvent('warning', [
                        'type' => 'warning',
                        'status' => ExtractionJob::STATUS_EXTRACTING,
                        'message' => "Grid cell {$cellNum} request failed (HTTP {$status}), continuing to next area...",
                        'businesses_seen' => $seenCount,
                        'leads_extracted' => $extractedCount,
                        'emails_found' => $emailsCount,
                        'websites_found' => $websitesCount,
                    ]);
                } catch (Throwable $cellException) {
                    Log::warning("Grid cell {$cellNum} request failed or timed out: {$cellException->getMessage()}", [
                        'cell_index' => $cellIdx,
                        'cell' => $cell,
                        'error' => $cellException->getMessage(),
                    ]);

                    if ($this->isFatalGoogleCloudFailure($cellException->getMessage())) {
                        $this->failJob($job, $this->formatGoogleCloudError($cellException->getMessage()));

                        return;
                    }

                    $warningMessage = $totalCells > 1
                        ? "Grid cell {$cellNum} timed out, continuing to next area..."
                        : 'Grid cell timed out, continuing to next area...';

                    $this->sendSseEvent('warning', [
                        'type' => 'warning',
                        'status' => ExtractionJob::STATUS_EXTRACTING,
                        'message' => $warningMessage,
                        'businesses_seen' => $seenCount,
                        'leads_extracted' => $extractedCount,
                        'emails_found' => $emailsCount,
                        'websites_found' => $websitesCount,
                    ]);
                }
            }

                $job->refresh();
                $isCancelled = ($job->status === ExtractionJob::STATUS_CANCELLED || connection_aborted());

                if ($isCancelled) {
                    $job->forceFill([
                        'status' => ExtractionJob::STATUS_CANCELLED,
                        'businesses_seen' => $seenCount,
                        'leads_extracted' => $extractedCount,
                        'emails_found' => $emailsCount,
                        'websites_found' => $websitesCount,
                        'current_activity' => 'Extraction stopped.',
                        'completed_at' => $job->completed_at ?? now(),
                    ])->save();

                    if ($job->tenant_id && $extractedCount > 0) {
                        $job->tenant?->incrementLeadsCount($extractedCount);
                    }

                    $this->sendSseEvent('cancelled', [
                        'type' => 'cancelled',
                        'status' => ExtractionJob::STATUS_CANCELLED,
                        'message' => 'Extraction stopped. Previously extracted leads have been preserved.',
                        'businesses_seen' => $seenCount,
                        'leads_extracted' => $extractedCount,
                        'emails_found' => $emailsCount,
                        'websites_found' => $websitesCount,
                    ]);
                } else {
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
                }

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

    private function failJob(ExtractionJob $job, string $message): void
    {
        Log::error('Google Places API job failed', ['job_id' => $job->uuid, 'error' => $message]);

        $this->sendSseEvent('error', [
            'type' => 'error',
            'status' => ExtractionJob::STATUS_ERROR,
            'message' => $message,
        ]);

        $job->forceFill([
            'status' => ExtractionJob::STATUS_ERROR,
            'error' => $message,
            'completed_at' => now(),
        ])->save();
    }

    private function formatGoogleCloudError(?string $googleMessage): string
    {
        $googleMessage = trim((string) $googleMessage);

        if ($this->isBillingFailure($googleMessage)) {
            return 'Google Cloud billing is not enabled on the project that owns this API key. Open that exact project → Billing → Link a billing account. Then enable Places API (New) and Geocoding API. Adding a card on a different Google project will not work.';
        }

        if (
            $googleMessage === ''
            || stripos($googleMessage, 'does not have permission') !== false
            || stripos($googleMessage, 'PERMISSION_DENIED') !== false
            || stripos($googleMessage, 'REQUEST_DENIED') !== false
            || stripos($googleMessage, 'API has not been used') !== false
            || stripos($googleMessage, 'is not authorized') !== false
        ) {
            $detail = $googleMessage !== '' && stripos($googleMessage, 'does not have permission') === false
                ? ' Google said: '.$googleMessage
                : '';

            return 'Google rejected this API key (PERMISSION_DENIED). On the same Cloud project that created the key: (1) link a billing account, (2) enable Places API (New), (3) enable Geocoding API. If the key has restrictions, allow those two APIs and do not use HTTP referrers — this app calls Google from the server.'.$detail;
        }

        return 'Google Maps API Error: '.$googleMessage;
    }

    private function isBillingFailure(?string $message): bool
    {
        return is_string($message) && $message !== '' && stripos($message, 'billing') !== false;
    }

    private function buildTextQuery(string $searchTerm, ?string $location, string $fallbackQuery): string
    {
        $searchTerm = trim($searchTerm);
        $location = $location ? trim($location) : '';
        $fallbackQuery = trim($fallbackQuery);

        if ($location === '') {
            return $searchTerm !== '' ? $searchTerm : $fallbackQuery;
        }

        if ($searchTerm === '') {
            return $fallbackQuery !== '' ? $fallbackQuery : $location;
        }

        if (stripos($searchTerm, $location) !== false) {
            return $searchTerm;
        }

        return $searchTerm.' in '.$location;
    }

    /**
     * Build an expanded list of search targets to fulfill high limits (100, 500, 1000, 1500+).
     *
     * @return array<int, array{query: string, cell: array|null, label: string}>
     */
    public function buildSearchTasks(string $searchTerm, ?string $resolvedLocation, string $fallbackQuery, ?array $searchBounds, array $gridCells, int $limit): array
    {
        $tasks = [];
        $primaryQuery = $this->buildTextQuery($searchTerm, $resolvedLocation, $fallbackQuery);

        // 1. If grid cells exist from geocoding and more than 1 cell, add each grid cell
        if (! empty($gridCells) && count($gridCells) > 1) {
            foreach ($gridCells as $idx => $cell) {
                if ($cell !== null) {
                    $tasks[] = [
                        'query' => $primaryQuery,
                        'cell' => $cell,
                        'label' => "Grid Cell #".($idx + 1)." in {$resolvedLocation}",
                    ];
                }
            }
        } else {
            // 2. Primary full text search query
            $tasks[] = [
                'query' => $primaryQuery,
                'cell' => ! empty($gridCells[0]) ? $gridCells[0] : null,
                'label' => $primaryQuery,
            ];
        }

        // If requested limit <= 60 and we have tasks, no need to expand yet
        if ($limit <= 60 && ! empty($tasks)) {
            return $tasks;
        }

        // 3. Sub-locality expansion (cities, districts, boroughs)
        $subLocalities = $this->getSubLocalities($resolvedLocation);
        foreach ($subLocalities as $subLoc) {
            $subQuery = $this->buildTextQuery($searchTerm, $subLoc, "{$searchTerm} in {$subLoc}");
            $tasks[] = [
                'query' => $subQuery,
                'cell' => null,
                'label' => $subQuery,
            ];
        }

        // 4. Niche synonym / keyword variation expansion (if limit > 150)
        if ($limit > 150) {
            $synonyms = $this->getNicheSynonyms($searchTerm);
            foreach ($synonyms as $synonym) {
                $synQuery = $this->buildTextQuery($synonym, $resolvedLocation, "{$synonym} in {$resolvedLocation}");
                $tasks[] = [
                    'query' => $synQuery,
                    'cell' => null,
                    'label' => $synQuery,
                ];

                // If high limit (>= 400), combine top sub-localities with synonyms
                if ($limit >= 400 && ! empty($subLocalities)) {
                    foreach (array_slice($subLocalities, 0, 10) as $subLoc) {
                        $synSubQuery = $this->buildTextQuery($synonym, $subLoc, "{$synonym} in {$subLoc}");
                        $tasks[] = [
                            'query' => $synSubQuery,
                            'cell' => null,
                            'label' => $synSubQuery,
                        ];
                    }
                }
            }
        }

        // Deduplicate task queries
        $seenQueries = [];
        $uniqueTasks = [];
        foreach ($tasks as $t) {
            $k = strtolower(trim($t['query'])).($t['cell'] ? json_encode($t['cell']['low']) : '');
            if (! isset($seenQueries[$k])) {
                $seenQueries[$k] = true;
                $uniqueTasks[] = $t;
            }
        }

        return $uniqueTasks;
    }

    /**
     * Get sub-localities, boroughs, or regional cities for a given location.
     *
     * @return array<string>
     */
    public function getSubLocalities(?string $location): array
    {
        if (! $location) {
            return [];
        }

        $loc = strtolower(trim($location));

        // UAE Sub-localities & Major Hubs
        if (in_array($loc, ['uae', 'united arab emirates', 'emirates'], true)) {
            return [
                'Dubai, UAE', 'Abu Dhabi, UAE', 'Sharjah, UAE', 'Ajman, UAE', 'Ras Al Khaimah, UAE',
                'Al Ain, UAE', 'Fujairah, UAE', 'Umm Al Quwain, UAE', 'Al Quoz, Dubai', 'Deira, Dubai',
                'Bur Dubai, Dubai', 'Musaffah, Abu Dhabi', 'Al Barsha, Dubai', 'Jumeirah, Dubai',
                'Industrial Area, Sharjah', 'Mirdif, Dubai', 'Motor City, Dubai', 'Al Karama, Dubai',
                'Business Bay, Dubai', 'Al Qusais, Dubai', 'Al Nahda, Sharjah', 'Khalidiya, Abu Dhabi',
                'Yas Island, Abu Dhabi', 'Mohamed Bin Zayed City, Abu Dhabi',
            ];
        }

        // UK Sub-localities & Major Cities
        if (in_array($loc, ['uk', 'united kingdom', 'great britain', 'england'], true)) {
            return [
                'London, UK', 'Manchester, UK', 'Birmingham, UK', 'Leeds, UK', 'Glasgow, UK',
                'Liverpool, UK', 'Newcastle, UK', 'Sheffield, UK', 'Bristol, UK', 'Nottingham, UK',
                'Leicester, UK', 'Edinburgh, UK', 'Belfast, UK', 'Cardiff, UK', 'Central London, UK',
                'East London, UK', 'North London, UK', 'South London, UK', 'West London, UK', 'Croydon, UK',
            ];
        }

        // US Nationwide
        if (in_array($loc, ['usa', 'us', 'united states', 'america'], true)) {
            return [
                'New York, NY', 'Los Angeles, CA', 'Chicago, IL', 'Houston, TX', 'Phoenix, AZ',
                'Philadelphia, PA', 'San Antonio, TX', 'San Diego, CA', 'Dallas, TX', 'Austin, TX',
                'Jacksonville, FL', 'San Jose, CA', 'Fort Worth, TX', 'Columbus, OH', 'Charlotte, NC',
                'Indianapolis, IN', 'San Francisco, CA', 'Seattle, WA', 'Denver, CO', 'Washington, DC',
                'Boston, MA', 'Miami, FL', 'Atlanta, GA', 'Nashville, TN', 'Las Vegas, NV',
            ];
        }

        // Texas Sub-localities
        if (str_contains($loc, 'texas') || str_contains($loc, 'tx')) {
            return [
                'Houston, TX', 'Dallas, TX', 'Austin, TX', 'San Antonio, TX', 'Fort Worth, TX',
                'El Paso, TX', 'Arlington, TX', 'Corpus Christi, TX', 'Plano, TX', 'Lubbock, TX',
                'Irving, TX', 'Laredo, TX', 'Garland, TX', 'Frisco, TX', 'McKinney, TX',
                'Downtown Dallas, TX', 'North Dallas, TX', 'Uptown Dallas, TX', 'Addison, TX', 'Richardson, TX',
            ];
        }

        // California Sub-localities
        if (str_contains($loc, 'california') || str_contains($loc, 'ca')) {
            return [
                'Los Angeles, CA', 'San Francisco, CA', 'San Diego, CA', 'San Jose, CA', 'Sacramento, CA',
                'Fresno, CA', 'Long Beach, CA', 'Oakland, CA', 'Bakersfield, CA', 'Anaheim, CA',
                'Santa Ana, CA', 'Riverside, CA', 'Irvine, CA', 'Stockton, CA', 'Chula Vista, CA',
            ];
        }

        // Florida Sub-localities
        if (str_contains($loc, 'florida') || str_contains($loc, 'fl')) {
            return [
                'Miami, FL', 'Orlando, FL', 'Tampa, FL', 'Jacksonville, FL', 'St. Petersburg, FL',
                'Hialeah, FL', 'Port St. Lucie, FL', 'Cape Coral, FL', 'Tallahassee, FL', 'Fort Lauderdale, FL',
                'Pembroke Pines, FL', 'Hollywood, FL', 'Gainesville, FL', 'Miramar, FL', 'Coral Springs, FL',
            ];
        }

        // Saudi Arabia Sub-localities
        if (in_array($loc, ['ksa', 'saudi', 'saudi arabia'], true)) {
            return [
                'Riyadh, Saudi Arabia', 'Jeddah, Saudi Arabia', 'Dammam, Saudi Arabia', 'Khobar, Saudi Arabia',
                'Mecca, Saudi Arabia', 'Medina, Saudi Arabia', 'Tabuk, Saudi Arabia', 'Taif, Saudi Arabia',
                'Buraidah, Saudi Arabia', 'Abha, Saudi Arabia', 'Khamis Mushait, Saudi Arabia', 'Jubail, Saudi Arabia',
            ];
        }

        // Canada Sub-localities
        if (in_array($loc, ['canada', 'ca'], true)) {
            return [
                'Toronto, Canada', 'Montreal, Canada', 'Vancouver, Canada', 'Calgary, Canada', 'Edmonton, Canada',
                'Ottawa, Canada', 'Winnipeg, Canada', 'Quebec City, Canada', 'Hamilton, Canada', 'Kitchener, Canada',
                'London ON, Canada', 'Victoria BC, Canada', 'Halifax, Canada', 'Mississauga, Canada', 'Brampton, Canada',
            ];
        }

        // Australia Sub-localities
        if (in_array($loc, ['australia', 'au'], true)) {
            return [
                'Sydney, Australia', 'Melbourne, Australia', 'Brisbane, Australia', 'Perth, Australia', 'Adelaide, Australia',
                'Gold Coast, Australia', 'Newcastle, Australia', 'Canberra, Australia', 'Sunshine Coast, Australia', 'Wollongong, Australia',
            ];
        }

        // Pakistan Sub-localities
        if (in_array($loc, ['pakistan', 'pk'], true)) {
            return [
                'Karachi, Pakistan', 'Lahore, Pakistan', 'Faisalabad, Pakistan', 'Rawalpindi, Pakistan', 'Gujranwala, Pakistan',
                'Peshawar, Pakistan', 'Multan, Pakistan', 'Hyderabad, Pakistan', 'Islamabad, Pakistan', 'Quetta, Pakistan',
            ];
        }

        // India Sub-localities
        if (in_array($loc, ['india', 'in'], true)) {
            return [
                'Mumbai, India', 'Delhi, India', 'Bangalore, India', 'Hyderabad, India', 'Ahmedabad, India',
                'Chennai, India', 'Kolkata, India', 'Surat, India', 'Pune, India', 'Jaipur, India',
            ];
        }

        // Generic City Sub-areas (Downtown, North, South, East, West, Central, Metro)
        $cleanLoc = ucwords(trim($location));

        return [
            "Downtown {$cleanLoc}",
            "North {$cleanLoc}",
            "South {$cleanLoc}",
            "East {$cleanLoc}",
            "West {$cleanLoc}",
            "Central {$cleanLoc}",
            "Metro {$cleanLoc}",
            "Greater {$cleanLoc}",
        ];
    }

    /**
     * Get common industry keyword variations and synonyms.
     *
     * @return array<string>
     */
    public function getNicheSynonyms(string $searchTerm): array
    {
        $term = strtolower(trim($searchTerm));

        if (str_contains($term, 'oil') || str_contains($term, 'auto') || str_contains($term, 'car') || str_contains($term, 'mechanic') || str_contains($term, 'repair')) {
            return [
                'Auto Repair',
                'Car Service',
                'Auto Workshop',
                'Car Mechanic',
                'Lube Express',
                'Tire and Auto Repair',
                'Car Maintenance',
                'Brake and Oil Service',
            ];
        }

        if (str_contains($term, 'plumb') || str_contains($term, 'drain') || str_contains($term, 'pipe')) {
            return [
                'Emergency Plumbing',
                'Drain Cleaning',
                'Water Heater Repair',
                'Commercial Plumbing',
                'Plumber and Gas Fitter',
                'Leak Detection',
            ];
        }

        if (str_contains($term, 'dent') || str_contains($term, 'teeth') || str_contains($term, 'ortho')) {
            return [
                'Dental Clinic',
                'Family Dentist',
                'Cosmetic Dentistry',
                'Orthodontics',
                'Dental Care',
                'Teeth Whitening',
            ];
        }

        if (str_contains($term, 'law') || str_contains($term, 'attorney') || str_contains($term, 'legal')) {
            return [
                'Law Firm',
                'Attorneys at Law',
                'Legal Services',
                'Solicitors',
                'Advocate',
                'Legal Counsel',
            ];
        }

        if (str_contains($term, 'roof')) {
            return [
                'Roofing Contractor',
                'Roof Repair',
                'Commercial Roofing',
                'Residential Roofing',
                'Roof Replacement',
            ];
        }

        if (str_contains($term, 'real estate') || str_contains($term, 'realt') || str_contains($term, 'propert')) {
            return [
                'Real Estate Agency',
                'Property Management',
                'Realtors',
                'Commercial Real Estate',
                'Real Estate Brokers',
            ];
        }

        if (str_contains($term, 'clean') || str_contains($term, 'maid')) {
            return [
                'Commercial Cleaning',
                'Janitorial Services',
                'House Cleaning Service',
                'Deep Cleaning',
                'Carpet Cleaning',
            ];
        }

        if (str_contains($term, 'electric')) {
            return [
                'Electrical Contractor',
                'Licensed Electrician',
                'Emergency Electrician',
                'Commercial Electrician',
            ];
        }

        return [
            "{$searchTerm} Services",
            "{$searchTerm} Specialists",
            "{$searchTerm} Company",
            "{$searchTerm} Experts",
        ];
    }

    /**
     * @return array{index?: int, row: int, col: int, low: array{latitude: float, longitude: float}, high: array{latitude: float, longitude: float}, center: array{latitude: float, longitude: float}}
     */
    private function cellFromBounds(array $bounds): array
    {
        $south = (float) ($bounds['southwest']['lat'] ?? 0);
        $west = (float) ($bounds['southwest']['lng'] ?? 0);
        $north = (float) ($bounds['northeast']['lat'] ?? 0);
        $east = (float) ($bounds['northeast']['lng'] ?? 0);

        return [
            'row' => 0,
            'col' => 0,
            'low' => [
                'latitude' => $south,
                'longitude' => $west,
            ],
            'high' => [
                'latitude' => $north,
                'longitude' => $east,
            ],
            'center' => [
                'latitude' => ($south + $north) / 2,
                'longitude' => ($west + $east) / 2,
            ],
        ];
    }

    private function inferRegionCode(?string $location): ?string
    {
        if (! $location) {
            return null;
        }

        $lower = strtolower($location);
        $countries = [
            'pakistan' => 'PK',
            'united arab emirates' => 'AE',
            'uae' => 'AE',
            'saudi arabia' => 'SA',
            'united kingdom' => 'GB',
            'united states' => 'US',
            'canada' => 'CA',
            'australia' => 'AU',
            'india' => 'IN',
        ];
        foreach ($countries as $name => $code) {
            if (preg_match('/\b'.preg_quote($name, '/').'\b/', $lower)) {
                return $code;
            }
        }

        if (preg_match('/,\s*([A-Za-z]{2})\s*(,|$)/', $location, $matches)) {
            $abbr = strtoupper($matches[1]);
            $usStates = [
                'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA',
                'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
                'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT',
                'VA', 'WA', 'WV', 'WI', 'WY', 'DC',
            ];
            if (in_array($abbr, $usStates, true)) {
                return 'US';
            }
        }

        return null;
    }

    /**
     * Drop Google results that are clearly outside the user-selected city/region.
     *
     * @param  array<string, mixed>  $place
     * @param  array{northeast: array{lat: float, lng: float}, southwest: array{lat: float, lng: float}}|null  $bounds
     */
    public function placeMatchesTargetLocation(array $place, ?string $location, ?array $bounds): bool
    {
        if (empty($location) && empty($bounds)) {
            return true;
        }

        $lat = isset($place['location']['latitude']) ? (float) $place['location']['latitude'] : null;
        $lng = isset($place['location']['longitude']) ? (float) $place['location']['longitude'] : null;

        if ($bounds && $lat !== null && $lng !== null) {
            $north = (float) $bounds['northeast']['lat'];
            $south = (float) $bounds['southwest']['lat'];
            $east = (float) $bounds['northeast']['lng'];
            $west = (float) $bounds['southwest']['lng'];
            $pad = 0.12;

            $inBounds = ! ($lat < ($south - $pad) || $lat > ($north + $pad) || $lng < ($west - $pad) || $lng > ($east + $pad));
            if (! $inBounds) {
                return false;
            }

            return true;
        }

        if (! $location) {
            return true;
        }

        $address = strtolower(trim((string) ($place['formattedAddress'] ?? '')));
        $name = strtolower(trim((string) ($place['displayName']['text'] ?? '')));
        $haystack = trim($address.' '.$name);
        if ($haystack === '') {
            return false;
        }

        $locLower = strtolower(trim($location));

        // Comprehensive country & region alias directory
        $countryAliases = [
            'uae' => ['uae', 'u.a.e', 'u.a.e.', 'united arab emirates', 'emirates', 'dubai', 'abu dhabi', 'sharjah', 'ajman', 'ras al khaimah', 'fujairah', 'umm al quwain', 'al ain', 'دبي', 'أبو ظبي', 'الشارقة', 'عجمان', 'الإمارات'],
            'united arab emirates' => ['uae', 'u.a.e', 'u.a.e.', 'united arab emirates', 'emirates', 'dubai', 'abu dhabi', 'sharjah', 'ajman', 'ras al khaimah', 'fujairah', 'umm al quwain', 'al ain', 'دبي', 'أبو ظبي', 'الشارقة', 'عجمان', 'الإمارات'],
            'uk' => ['uk', 'u.k', 'u.k.', 'united kingdom', 'great britain', 'britain', 'england', 'scotland', 'wales', 'northern ireland', 'london', 'manchester', 'birmingham', 'leeds', 'glasgow', 'liverpool'],
            'united kingdom' => ['uk', 'u.k', 'united kingdom', 'great britain', 'britain', 'england', 'scotland', 'wales', 'london', 'manchester'],
            'usa' => ['usa', 'u.s.a', 'u.s.', 'us', 'united states', 'america'],
            'us' => ['usa', 'u.s.a', 'u.s.', 'us', 'united states', 'america'],
            'united states' => ['usa', 'u.s.a', 'u.s.', 'us', 'united states', 'america'],
            'ksa' => ['ksa', 'saudi', 'saudi arabia', 'riyadh', 'jeddah', 'dammam', 'khobar', 'mecca', 'medina'],
            'saudi arabia' => ['ksa', 'saudi', 'saudi arabia', 'riyadh', 'jeddah', 'dammam', 'khobar'],
            'canada' => ['canada', 'ca', 'ontario', 'quebec', 'toronto', 'vancouver', 'montreal', 'calgary', 'ottawa', 'alberta', 'british columbia'],
            'australia' => ['australia', 'au', 'sydney', 'melbourne', 'brisbane', 'perth', 'adelaide', 'nsw', 'queensland', 'victoria'],
            'pakistan' => ['pakistan', 'pk', 'karachi', 'lahore', 'islamabad', 'rawalpindi', 'faisalabad', 'multan', 'peshawar'],
            'india' => ['india', 'in', 'mumbai', 'delhi', 'bangalore', 'bengaluru', 'hyderabad', 'chennai', 'kolkata', 'pune', 'ahmedabad'],
            'germany' => ['germany', 'deutschland', 'berlin', 'munich', 'münchen', 'frankfurt', 'hamburg', 'cologne', 'köln'],
            'france' => ['france', 'paris', 'marseille', 'lyon', 'toulouse', 'nice', 'nantes'],
        ];

        // If entire location is a recognized country
        if (isset($countryAliases[$locLower])) {
            foreach ($countryAliases[$locLower] as $alias) {
                if (mb_stripos($haystack, $alias) !== false) {
                    return true;
                }
            }

            return false;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
        if ($parts === []) {
            return true;
        }

        $city = strtolower($parts[0]);
        if (isset($countryAliases[$city])) {
            foreach ($countryAliases[$city] as $alias) {
                if (mb_stripos($haystack, $alias) !== false) {
                    return true;
                }
            }

            return false;
        }

        // State / Region mapping
        $regionAliases = [
            'il' => ['il', 'illinois', 'chicago'],
            'tx' => ['tx', 'texas', 'dallas', 'houston', 'austin', 'san antonio', 'fort worth'],
            'ca' => ['ca', 'california', 'los angeles', 'san francisco', 'san diego', 'san jose'],
            'ny' => ['ny', 'new york', 'nyc', 'brooklyn', 'manhattan', 'queens'],
            'fl' => ['fl', 'florida', 'miami', 'orlando', 'tampa', 'jacksonville'],
            'wa' => ['wa', 'washington', 'seattle'],
            'oh' => ['oh', 'ohio', 'columbus', 'cleveland', 'cincinnati'],
            'ga' => ['ga', 'georgia', 'atlanta'],
            'nc' => ['nc', 'north carolina', 'charlotte', 'raleigh'],
            'pa' => ['pa', 'pennsylvania', 'philadelphia', 'pittsburgh'],
        ];

        if ($city !== '') {
            $cityNeedles = $regionAliases[$city] ?? [$city];
            $matchedCity = false;
            foreach ($cityNeedles as $cNeedle) {
                if (mb_stripos($haystack, $cNeedle) !== false) {
                    $matchedCity = true;
                    break;
                }
            }

            if ($matchedCity) {
                return true;
            }
        }

        if (isset($parts[1])) {
            $region = strtolower($parts[1]);
            $needles = $regionAliases[$region] ?? [$region];
            foreach ($needles as $needle) {
                if (mb_stripos($haystack, $needle) !== false) {
                    return true;
                }
            }

            // Reject if clearly from a different country/region
            $conflictingCountries = ['pakistan', 'india', 'germany', 'france', 'china', 'russia', 'brazil', 'japan'];
            foreach ($conflictingCountries as $conflict) {
                if (mb_stripos($haystack, $conflict) !== false) {
                    return false;
                }
            }

            return (bool) preg_match('/\b(usa|united states|us)\b/i', $haystack);
        }

        // If user searched for a generic term without strict bounds, keep Google's result unless conflicting
        return true;
    }

    private function isFatalGoogleCloudFailure(?string $message, ?string $status = null): bool
    {
        $haystack = trim($message.' '.$status);

        return $haystack !== '' && (
            $this->isBillingFailure($haystack)
            || stripos($haystack, 'PERMISSION_DENIED') !== false
            || stripos($haystack, 'does not have permission') !== false
            || stripos($haystack, 'API has not been used') !== false
            || stripos($haystack, 'is not authorized') !== false
            || $status === '403'
            || $status === '401'
        );
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
