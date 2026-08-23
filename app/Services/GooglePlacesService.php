<?php

namespace App\Services;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GooglePlacesService
{
    private const PLACES_API_URL = 'https://places.googleapis.com/v1/places:searchText';

    public function stream(ExtractionJob $job, ?string $apiKey = null): StreamedResponse
    {
        $key = $apiKey ?: config('services.google.maps_api_key');

        return response()->stream(function () use ($job, $key): void {
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
            $pageToken = null;

            try {
                do {
                    $payload = [
                        'textQuery' => $job->query,
                        'pageSize' => min(20, $limit - $extractedCount),
                    ];
                    if ($pageToken) {
                        $payload['pageToken'] = $pageToken;
                    }

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Goog-Api-Key' => $key,
                        'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.rating,places.userRatingCount,places.googleMapsUri,places.primaryTypeDisplayName,nextPageToken',
                    ])->timeout(15)->post(self::PLACES_API_URL, $payload);

                    if ($response->failed()) {
                        $errorBody = $response->json();
                        $errorMessage = $errorBody['error']['message'] ?? 'Google Places API request failed with HTTP '.$response->status();
                        Log::error('Google Places API error', ['status' => $response->status(), 'body' => $errorBody]);

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

                        $seenCount++;
                        $name = $place['displayName']['text'] ?? null;
                        if (! $name) {
                            continue;
                        }

                        $phone = $place['nationalPhoneNumber'] ?? ($place['internationalPhoneNumber'] ?? null);
                        $website = $place['websiteUri'] ?? null;
                        $emails = [];

                        if ($website) {
                            $websitesCount++;
                            $emails = $this->quickEnrichWebsiteEmails($website);
                            if (! empty($emails)) {
                                $emailsCount += count($emails);
                            }
                        }

                        $leadData = [
                            'business_name' => $name,
                            'address' => $place['formattedAddress'] ?? null,
                            'phone' => $phone,
                            'emails' => $emails,
                            'website' => $website,
                            'google_maps_url' => $place['googleMapsUri'] ?? null,
                            'place_id' => $place['id'] ?? null,
                            'category' => $place['primaryTypeDisplayName']['text'] ?? null,
                            'rating' => isset($place['rating']) ? (float) $place['rating'] : null,
                            'review_count' => isset($place['userRatingCount']) ? (int) $place['userRatingCount'] : null,
                            'source' => 'Google Places API',
                            'extracted_at' => now(),
                        ];

                        $exists = $job->leads()
                            ->when(! empty($leadData['place_id']), fn ($q) => $q->where('place_id', $leadData['place_id']))
                            ->when(empty($leadData['place_id']), fn ($q) => $q->where('business_name', $name)->where('address', $leadData['address']))
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

                    if ($pageToken) {
                        // Google Places API recommends a short delay before querying nextPageToken
                        usleep(1500000);
                    }
                } while ($pageToken && $extractedCount < $limit);

                $job->forceFill([
                    'status' => ExtractionJob::STATUS_COMPLETED,
                    'businesses_seen' => $seenCount,
                    'leads_extracted' => $extractedCount,
                    'emails_found' => $emailsCount,
                    'websites_found' => $websitesCount,
                    'current_activity' => 'Extraction completed.',
                    'completed_at' => now(),
                ])->save();

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
     * Quickly scan the homepage and contact page of a website for emails.
     * Safe, non-intrusive, 2.5 second timeout.
     */
    private function quickEnrichWebsiteEmails(string $websiteUrl): array
    {
        if (empty($websiteUrl) || ! filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        $host = parse_url($websiteUrl, PHP_URL_HOST);
        if (! $host || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return [];
        }

        $foundEmails = [];

        try {
            $resp = Http::timeout(2.5)
                ->withUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
                ->get($websiteUrl);

            if ($resp->successful()) {
                $html = $resp->body();
                $foundEmails = $this->extractEmailsFromHtml($html);
            }
        } catch (Throwable) {
            // Non-blocking enrichment
        }

        return array_values(array_unique($foundEmails));
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
        echo 'data: '.json_encode($data)."\n\n";
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
