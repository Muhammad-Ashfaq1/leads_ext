<?php

namespace App\Services;

use App\Models\ExtractedLead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiWebsiteService
{
    protected string $systemPrompt = 'You are an expert copywriter. The user will provide local business data. Generate JSON for a landing page containing: hero_headline, hero_subheadline, about_text, and a services array (3 items with title/description). Output valid JSON only.';

    public function generateSite(ExtractedLead $lead): array
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $primaryModel = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));
        $models = array_values(array_unique([$primaryModel, 'gemini-3.6-flash', 'gemini-flash-latest', 'gemini-2.5-flash']));

        if (empty($apiKey)) {
            throw new RuntimeException('Gemini API key is not configured. Please set GEMINI_API_KEY in your environment.');
        }

        $businessContext = [
            'business_name' => $lead->business_name ?? 'Local Business',
            'category' => $lead->category ?? 'Professional Services',
            'city' => $lead->city ?: (explode(',', $lead->address ?? '')[0] ?? ''),
            'country' => $lead->country ?? '',
            'address' => $lead->address ?? '',
            'phone' => $lead->phone ?? '',
            'website' => $lead->website ?? '',
            'rating' => $lead->rating ?? null,
            'review_count' => $lead->review_count ?? null,
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $this->systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => "Business Profile:\n" . json_encode($businessContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.7,
            ],
        ];

        $response = null;
        $lastException = null;
        $lastStatus = 500;
        $lastErrorMessage = 'Gemini API error';

        foreach ($models as $index => $currentModel) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";

            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, $payload);

                if ($response->successful()) {
                    break;
                }

                $lastStatus = $response->status();
                $jsonError = $response->json();
                $lastErrorMessage = $jsonError['error']['message'] ?? "Gemini API error (HTTP {$lastStatus})";

                // If 404 (model not found / deprecated for this API key) and more models exist, try next
                if ($lastStatus === 404 && $index < count($models) - 1) {
                    Log::warning("Gemini model {$currentModel} returned 404, attempting fallback model.", [
                        'lead_id' => $lead->id,
                    ]);
                    continue;
                }

                // For non-404 errors (429 rate limit, 401 auth, 400 bad request, etc.), stop and report
                break;
            } catch (Throwable $e) {
                $lastException = $e;
                Log::error("Gemini API HTTP call failed with model {$currentModel}", [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $response || ! $response->successful()) {
            if ($lastException && (! $response || $response->status() >= 500)) {
                throw new RuntimeException('Failed to communicate with Gemini API: ' . $lastException->getMessage(), 0, $lastException);
            }

            throw new RuntimeException($lastErrorMessage, $lastStatus);
        }

        $responseData = $response->json();
        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($rawText)) {
            throw new RuntimeException('Empty or malformed content received from Gemini API.');
        }

        $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($rawText));
        $parsed = json_decode($cleanJson, true);

        if (! is_array($parsed)) {
            Log::warning('Failed to parse JSON from Gemini, attempting fallback parsing', [
                'lead_id' => $lead->id,
                'raw' => $rawText,
            ]);
            throw new RuntimeException('Gemini API returned non-JSON format.');
        }

        // Validate and normalize expected schema
        $content = $this->normalizeContent($parsed, $lead);

        // Save generated content to the lead
        $lead->generated_website_content = $content;
        $lead->save();

        return $content;
    }

    protected function normalizeContent(array $parsed, ExtractedLead $lead): array
    {
        $bizName = $lead->business_name ?: 'Our Business';
        $category = $lead->category ?: 'Services';
        $city = $lead->city ?: 'your area';

        $headline = ! empty($parsed['hero_headline']) ? trim($parsed['hero_headline']) : "Premium {$category} Solutions by {$bizName}";
        $subheadline = ! empty($parsed['hero_subheadline']) ? trim($parsed['hero_subheadline']) : "Delivering trusted, top-rated {$category} expertise in {$city} and beyond.";
        $aboutText = ! empty($parsed['about_text']) ? trim($parsed['about_text']) : "At {$bizName}, we are dedicated to providing superior {$category} with unmatched customer dedication, reliable craftsmanship, and a proven track record.";

        $services = [];
        if (! empty($parsed['services']) && is_array($parsed['services'])) {
            foreach ($parsed['services'] as $svc) {
                if (is_array($svc) && ! empty($svc['title'])) {
                    $services[] = [
                        'title' => trim($svc['title']),
                        'description' => trim($svc['description'] ?? "High quality {$svc['title']} tailored to your unique requirements."),
                    ];
                }
            }
        }

        if (count($services) === 0) {
            $services = [
                [
                    'title' => 'Customized Solutions',
                    'description' => "Tailored {$category} solutions designed specifically for your individual needs.",
                ],
                [
                    'title' => 'Professional Execution',
                    'description' => "Experienced specialists providing prompt, detail-oriented service you can trust.",
                ],
                [
                    'title' => 'Customer Care & Quality',
                    'description' => 'Dedicated follow-up, transparent pricing, and 100% satisfaction commitment.',
                ],
            ];
        }

        return [
            'hero_headline' => $headline,
            'hero_subheadline' => $subheadline,
            'about_text' => $aboutText,
            'services' => array_slice($services, 0, 3),
        ];
    }
}

