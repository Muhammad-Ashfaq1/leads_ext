<?php

namespace App\Services;

use App\Models\ExtractedLead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GeminiWebsiteService
{
    protected string $systemPrompt = <<<PROMPT
You are an expert Art Director and Conversion Copywriter. You will receive local business profile data including its specific industry category.
Your task is to analyze the business niche and generate dynamic Tailwind CSS design tokens and high-converting, industry-specific copywriting.

You MUST return a valid JSON object ONLY matching this schema:
{
  "design_tokens": {
    "primary_color": "bg-red-600",
    "text_color": "text-red-600",
    "font_family": "font-sans",
    "hero_layout": "split-with-form"
  },
  "copy": {
    "hero_badge": "Family Owned & Operated",
    "hero_headline": "Headline tailored to industry and location",
    "hero_subheadline": "Persuasive subheadline highlighting key customer benefits",
    "primary_cta": "Book Service",
    "about_text": "Engaging 2-3 sentence narrative about business quality and reliability",
    "niche_features": [
      {
        "title": "Service 1",
        "description": "Clear description tailored to this specific niche",
        "icon_name": "wrench"
      },
      {
        "title": "Service 2",
        "description": "Clear description tailored to this specific niche",
        "icon_name": "shield"
      },
      {
        "title": "Service 3",
        "description": "Clear description tailored to this specific niche",
        "icon_name": "check"
      }
    ],
    "trust_indicators": [
      "Licensed & Insured",
      "Over 500+ Satisfied Clients",
      "100% Satisfaction Guarantee"
    ]
  }
}

Guidelines for Design Tokens:
- primary_color: Choose a valid Tailwind 600-900 background class fitting the niche (e.g. bg-red-600 for automotive/garage, bg-slate-900 for real estate/corporate/luxury, bg-emerald-600 for landscaping/health, bg-blue-600 for medical/tech, bg-amber-800 for lawyers/fine dining, bg-cyan-600 for plumbing/pools, bg-orange-600 for restaurants/food).
- text_color: Matching Tailwind text class (e.g. text-red-600, text-slate-900, text-emerald-600, text-blue-600, text-amber-800, text-cyan-600, text-orange-600).
- font_family: "font-sans" (modern/tech/services), "font-serif" (luxury/lawyers/real estate/hospitality), or "font-mono" (technical/industrial).
- hero_layout: "split-with-form" (contractors/garages/home services/appointment bookings), "gallery-grid" (real estate/restaurants/salons/venues), or "centered-bold" (consultants/agencies/general).
- icon_name: One of: wrench, home, star, shield, car, sparkles, calendar, phone, check, briefcase, map-pin, trending-up, heart, tool, camera, utensils, clock.
PROMPT;

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
                            'text' => "Target Business Profile (Industry: {$businessContext['category']}):\n" . json_encode($businessContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
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

        // Validate and normalize expected schema with design tokens and copy
        $content = $this->normalizeContent($parsed, $lead);

        // Save generated content to the lead
        $lead->generated_website_content = $content;
        $lead->save();

        return $content;
    }

    public function getDefaultDesignTokensForCategory(?string $category): array
    {
        $cat = strtolower($category ?? '');

        if (str_contains($cat, 'auto') || str_contains($cat, 'garage') || str_contains($cat, 'car') || str_contains($cat, 'mechanic') || str_contains($cat, 'tire') || str_contains($cat, 'repair') || str_contains($cat, 'towing')) {
            return [
                'primary_color' => 'bg-red-600',
                'text_color' => 'text-red-600',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'real estate') || str_contains($cat, 'realtor') || str_contains($cat, 'property') || str_contains($cat, 'luxury') || str_contains($cat, 'architect')) {
            return [
                'primary_color' => 'bg-slate-900',
                'text_color' => 'text-slate-900',
                'font_family' => 'font-serif',
                'hero_layout' => 'gallery-grid',
            ];
        }

        if (str_contains($cat, 'law') || str_contains($cat, 'legal') || str_contains($cat, 'attorney') || str_contains($cat, 'lawyer')) {
            return [
                'primary_color' => 'bg-amber-800',
                'text_color' => 'text-amber-800',
                'font_family' => 'font-serif',
                'hero_layout' => 'centered-bold',
            ];
        }

        if (str_contains($cat, 'dental') || str_contains($cat, 'doctor') || str_contains($cat, 'clinic') || str_contains($cat, 'medical') || str_contains($cat, 'health') || str_contains($cat, 'hospital')) {
            return [
                'primary_color' => 'bg-blue-600',
                'text_color' => 'text-blue-600',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'landscap') || str_contains($cat, 'tree') || str_contains($cat, 'garden') || str_contains($cat, 'green') || str_contains($cat, 'lawn')) {
            return [
                'primary_color' => 'bg-emerald-600',
                'text_color' => 'text-emerald-600',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'plumb') || str_contains($cat, 'pool') || str_contains($cat, 'hvac') || str_contains($cat, 'water')) {
            return [
                'primary_color' => 'bg-cyan-600',
                'text_color' => 'text-cyan-600',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'restaurant') || str_contains($cat, 'food') || str_contains($cat, 'cafe') || str_contains($cat, 'bakery') || str_contains($cat, 'pizza') || str_contains($cat, 'bar')) {
            return [
                'primary_color' => 'bg-orange-600',
                'text_color' => 'text-orange-600',
                'font_family' => 'font-sans',
                'hero_layout' => 'gallery-grid',
            ];
        }

        return [
            'primary_color' => 'bg-blue-600',
            'text_color' => 'text-blue-600',
            'font_family' => 'font-sans',
            'hero_layout' => 'split-with-form',
        ];
    }

    public function normalizeContent(array $parsed, ExtractedLead $lead): array
    {
        $defaultTokens = $this->getDefaultDesignTokensForCategory($lead->category);

        // Normalize design tokens
        $rawTokens = is_array($parsed['design_tokens'] ?? null) ? $parsed['design_tokens'] : [];

        $primaryColor = ! empty($rawTokens['primary_color']) && preg_match('/^bg-[a-z]+-[0-9]{2,3}$/', trim($rawTokens['primary_color']))
            ? trim($rawTokens['primary_color'])
            : $defaultTokens['primary_color'];

        $textColor = ! empty($rawTokens['text_color']) && preg_match('/^text-[a-z]+-[0-9]{2,3}$/', trim($rawTokens['text_color']))
            ? trim($rawTokens['text_color'])
            : str_replace('bg-', 'text-', $primaryColor);

        $allowedFonts = ['font-sans', 'font-serif', 'font-mono'];
        $fontFamily = ! empty($rawTokens['font_family']) && in_array(trim($rawTokens['font_family']), $allowedFonts, true)
            ? trim($rawTokens['font_family'])
            : $defaultTokens['font_family'];

        $allowedLayouts = ['split-with-form', 'gallery-grid', 'centered-bold'];
        $heroLayout = ! empty($rawTokens['hero_layout']) && in_array(trim($rawTokens['hero_layout']), $allowedLayouts, true)
            ? trim($rawTokens['hero_layout'])
            : $defaultTokens['hero_layout'];

        $designTokens = [
            'primary_color' => $primaryColor,
            'text_color' => $textColor,
            'font_family' => $fontFamily,
            'hero_layout' => $heroLayout,
        ];

        // Normalize copy
        $rawCopy = is_array($parsed['copy'] ?? null) ? $parsed['copy'] : $parsed;

        $bizName = $lead->business_name ?: 'Our Business';
        $category = $lead->category ?: 'Services';
        $city = $lead->city ?: ($lead->address ? explode(',', $lead->address)[0] : 'your area');

        $heroBadge = ! empty($rawCopy['hero_badge']) ? trim($rawCopy['hero_badge']) : "Top Rated in {$city}";
        $heroHeadline = ! empty($rawCopy['hero_headline']) ? trim($rawCopy['hero_headline']) : "Premium {$category} Solutions by {$bizName}";
        $heroSubheadline = ! empty($rawCopy['hero_subheadline']) ? trim($rawCopy['hero_subheadline']) : "Delivering trusted, top-rated {$category} expertise in {$city} and beyond.";
        $primaryCta = ! empty($rawCopy['primary_cta']) ? trim($rawCopy['primary_cta']) : 'Get Free Estimate';
        $aboutText = ! empty($rawCopy['about_text']) ? trim($rawCopy['about_text']) : "At {$bizName}, we are dedicated to providing superior {$category} with unmatched customer dedication, reliable craftsmanship, and a proven track record.";

        // Niche features
        $rawFeatures = $rawCopy['niche_features'] ?? ($rawCopy['services'] ?? []);
        $nicheFeatures = [];
        if (is_array($rawFeatures)) {
            foreach ($rawFeatures as $feat) {
                if (is_array($feat) && ! empty($feat['title'])) {
                    $nicheFeatures[] = [
                        'title' => trim($feat['title']),
                        'description' => trim($feat['description'] ?? "High quality {$feat['title']} tailored to your unique requirements."),
                        'icon_name' => trim($feat['icon_name'] ?? 'check'),
                    ];
                }
            }
        }

        if (count($nicheFeatures) === 0) {
            $nicheFeatures = [
                [
                    'title' => 'Customized Solutions',
                    'description' => "Tailored {$category} solutions designed specifically for your individual needs.",
                    'icon_name' => 'wrench',
                ],
                [
                    'title' => 'Professional Execution',
                    'description' => "Experienced specialists providing prompt, detail-oriented service you can trust.",
                    'icon_name' => 'shield',
                ],
                [
                    'title' => 'Customer Care & Quality',
                    'description' => 'Dedicated follow-up, transparent pricing, and 100% satisfaction commitment.',
                    'icon_name' => 'star',
                ],
            ];
        }

        // Trust indicators
        $rawTrust = $rawCopy['trust_indicators'] ?? [];
        $trustIndicators = [];
        if (is_array($rawTrust)) {
            foreach ($rawTrust as $t) {
                if (is_string($t) && trim($t) !== '') {
                    $trustIndicators[] = trim($t);
                }
            }
        }

        if (count($trustIndicators) === 0) {
            $trustIndicators = [
                'Licensed & Insured',
                '100% Satisfaction Guaranteed',
                'Fast & Reliable Service',
            ];
        }

        $copy = [
            'hero_badge' => $heroBadge,
            'hero_headline' => $heroHeadline,
            'hero_subheadline' => $heroSubheadline,
            'primary_cta' => $primaryCta,
            'about_text' => $aboutText,
            'niche_features' => array_slice($nicheFeatures, 0, 4),
            'trust_indicators' => array_slice($trustIndicators, 0, 3),
        ];

        return [
            'design_tokens' => $designTokens,
            'copy' => $copy,
            // Backwards compatibility keys
            'hero_headline' => $heroHeadline,
            'hero_subheadline' => $heroSubheadline,
            'about_text' => $aboutText,
            'services' => $copy['niche_features'],
        ];
    }
}
