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
You are an elite Art Director, Conversion Copywriter, and UI/UX Designer. You will receive local business profile data including its specific industry category, location, ratings, and phone.
Your goal is to build an exhaustive, high-converting, professional, feature-rich landing page concept tailored specifically to this business niche.

You MUST return a valid JSON object ONLY with this comprehensive schema:
{
  "design_tokens": {
    "primary_color": "bg-red-600", // Valid Tailwind background class suited for the niche (e.g. bg-red-600 for automotive/garage, bg-slate-900 for real estate/corporate/luxury, bg-emerald-600 for landscaping/health, bg-blue-600 for medical/tech, bg-amber-800 for legal/lawyers, bg-cyan-600 for plumbing/pool, bg-orange-600 for restaurant/food)
    "text_color": "text-red-600", // Matching Tailwind text class (e.g. text-red-600, text-slate-900, text-emerald-600, text-blue-600, text-amber-800, text-cyan-600, text-orange-600)
    "accent_color": "bg-amber-500", // Tailwind accent color class
    "font_family": "font-sans", // "font-sans" (modern/tech/services), "font-serif" (luxury/lawyers/real estate/fine dining), or "font-mono" (industrial/technical)
    "hero_layout": "split-with-form" // "split-with-form" (contractors/garages/clinics), "gallery-grid" (real estate/restaurants/salons), or "centered-bold" (consultants/agencies/corporate)
  },
  "copy": {
    "hero_badge": "Family Owned & Operated Since 2012",
    "hero_headline": "High-converting headline tailored to industry and city",
    "hero_subheadline": "Persuasive 2-sentence subheadline highlighting customer benefits, speed, and trust",
    "primary_cta": "Book Service",
    "secondary_cta": "View All Services",
    "urgency_note": "⚡ Same-Day Availability • 100% Free Initial Estimate",
    "stats": [
      {"value": "15+", "label": "Years Experience"},
      {"value": "4.9 ★", "label": "Google Rating"},
      {"value": "2,800+", "label": "Satisfied Clients"},
      {"value": "100%", "label": "Satisfaction Guaranteed"}
    ],
    "about_text": "Detailed 3-4 sentence brand story highlighting craftsmanship, experienced personnel, local commitment, and state-of-the-art methods.",
    "niche_features": [
      {
        "title": "Comprehensive Service 1",
        "description": "Specific 2-sentence description of the service and customer value.",
        "icon_name": "wrench", // One of: wrench, home, star, shield, car, sparkles, calendar, phone, check, briefcase, map-pin, trending-up, heart, tool, camera, utensils, clock
        "badge": "Most Popular",
        "bullet_points": ["Key feature A", "Key feature B", "Key feature C"]
      },
      {
        "title": "Comprehensive Service 2",
        "description": "Specific 2-sentence description of the service and customer value.",
        "icon_name": "shield",
        "badge": "Certified",
        "bullet_points": ["Key feature A", "Key feature B", "Key feature C"]
      },
      {
        "title": "Comprehensive Service 3",
        "description": "Specific 2-sentence description of the service and customer value.",
        "icon_name": "check",
        "badge": "Fast Turnaround",
        "bullet_points": ["Key feature A", "Key feature B", "Key feature C"]
      },
      {
        "title": "Comprehensive Service 4",
        "description": "Specific 2-sentence description of the service and customer value.",
        "icon_name": "tool",
        "badge": "Guaranteed",
        "bullet_points": ["Key feature A", "Key feature B", "Key feature C"]
      }
    ],
    "process_steps": [
      {"step": "01", "title": "Initial Consultation & Inspection", "description": "Thorough assessment and transparent discovery of your exact needs."},
      {"step": "02", "title": "Customized Upfront Proposal", "description": "Clear, itemized quote with competitive pricing and zero hidden costs."},
      {"step": "03", "title": "Precision Execution", "description": "Certified specialists carry out the work with high-grade materials and tools."},
      {"step": "04", "title": "Final Quality Review & Warranty", "description": "Complete client walkthrough, satisfaction sign-off, and warranty handover."}
    ],
    "why_choose_us": [
      {"title": "Licensed & Master Certified", "description": "Our technicians undergo rigorous industry certification and continuous training."},
      {"title": "Transparent Flat-Rate Pricing", "description": "Clear estimates upfront with no surprise charges or hidden fees."},
      {"title": "Rapid Response & Punctuality", "description": "We respect your valuable time with guaranteed on-time arrivals and fast turnarounds."},
      {"title": "100% Satisfaction Guarantee", "description": "We stand firmly behind our craftsmanship with comprehensive warranty backing."}
    ],
    "testimonials": [
      {
        "name": "Sarah M.",
        "rating": 5,
        "role": "Verified Customer",
        "service": "Primary Service",
        "comment": "Exceptional experience! Prompt communication, incredible attention to detail, and fair pricing."
      },
      {
        "name": "David R.",
        "rating": 5,
        "role": "Local Resident",
        "service": "Specialized Service",
        "comment": "Hands down the best service in the area. Fixed our issue quickly and explained everything thoroughly."
      },
      {
        "name": "Elena T.",
        "rating": 5,
        "role": "Commercial Client",
        "service": "Full Project Execution",
        "comment": "Professional, reliable, and courteous. We will definitely be partnering with them for all future needs."
      }
    ],
    "faq": [
      {"question": "How quickly can I schedule an appointment?", "answer": "We provide prompt same-day or next-day appointments depending on urgency and location."},
      {"question": "Do you provide written guarantees and warranties?", "answer": "Yes, all our services include full craftsmanship warranties and manufacturer guarantees on parts."},
      {"question": "What payment methods and financing do you accept?", "answer": "We accept all major credit cards, bank transfers, checks, and offer flexible payment plans."},
      {"question": "How do you provide estimates?", "answer": "We provide transparent, no-obligation upfront quotes after evaluating your exact requirements."}
    ],
    "trust_indicators": [
      "Licensed & Fully Insured",
      "Over 10+ Years of Local Service",
      "100% Craftsmanship Guarantee"
    ],
    "operating_hours": {
      "weekdays": "Monday – Friday: 7:30 AM – 6:00 PM",
      "saturday": "Saturday: 8:00 AM – 4:00 PM",
      "sunday": "Sunday: Emergency Dispatch By Appointment"
    }
  }
}
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
                            'text' => "Target Business Profile (Industry: {$businessContext['category']}, City: {$businessContext['city']}):\n" . json_encode($businessContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
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

        // Validate and normalize expected schema with design tokens and extensive copy
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
                'accent_color' => 'bg-amber-500',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'real estate') || str_contains($cat, 'realtor') || str_contains($cat, 'property') || str_contains($cat, 'luxury') || str_contains($cat, 'architect')) {
            return [
                'primary_color' => 'bg-slate-900',
                'text_color' => 'text-slate-900',
                'accent_color' => 'bg-amber-600',
                'font_family' => 'font-serif',
                'hero_layout' => 'gallery-grid',
            ];
        }

        if (str_contains($cat, 'law') || str_contains($cat, 'legal') || str_contains($cat, 'attorney') || str_contains($cat, 'lawyer')) {
            return [
                'primary_color' => 'bg-amber-800',
                'text_color' => 'text-amber-800',
                'accent_color' => 'bg-slate-900',
                'font_family' => 'font-serif',
                'hero_layout' => 'centered-bold',
            ];
        }

        if (str_contains($cat, 'dental') || str_contains($cat, 'doctor') || str_contains($cat, 'clinic') || str_contains($cat, 'medical') || str_contains($cat, 'health') || str_contains($cat, 'hospital')) {
            return [
                'primary_color' => 'bg-blue-600',
                'text_color' => 'text-blue-600',
                'accent_color' => 'bg-cyan-500',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'landscap') || str_contains($cat, 'tree') || str_contains($cat, 'garden') || str_contains($cat, 'green') || str_contains($cat, 'lawn')) {
            return [
                'primary_color' => 'bg-emerald-600',
                'text_color' => 'text-emerald-600',
                'accent_color' => 'bg-lime-500',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'plumb') || str_contains($cat, 'pool') || str_contains($cat, 'hvac') || str_contains($cat, 'water')) {
            return [
                'primary_color' => 'bg-cyan-600',
                'text_color' => 'text-cyan-600',
                'accent_color' => 'bg-blue-500',
                'font_family' => 'font-sans',
                'hero_layout' => 'split-with-form',
            ];
        }

        if (str_contains($cat, 'restaurant') || str_contains($cat, 'food') || str_contains($cat, 'cafe') || str_contains($cat, 'bakery') || str_contains($cat, 'pizza') || str_contains($cat, 'bar')) {
            return [
                'primary_color' => 'bg-orange-600',
                'text_color' => 'text-orange-600',
                'accent_color' => 'bg-amber-500',
                'font_family' => 'font-sans',
                'hero_layout' => 'gallery-grid',
            ];
        }

        return [
            'primary_color' => 'bg-blue-600',
            'text_color' => 'text-blue-600',
            'accent_color' => 'bg-indigo-500',
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

        $accentColor = ! empty($rawTokens['accent_color']) && preg_match('/^bg-[a-z]+-[0-9]{2,3}$/', trim($rawTokens['accent_color']))
            ? trim($rawTokens['accent_color'])
            : ($defaultTokens['accent_color'] ?? 'bg-amber-500');

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
            'accent_color' => $accentColor,
            'font_family' => $fontFamily,
            'hero_layout' => $heroLayout,
        ];

        // Normalize copy
        $rawCopy = is_array($parsed['copy'] ?? null) ? $parsed['copy'] : $parsed;

        $bizName = $lead->business_name ?: 'Our Business';
        $category = $lead->category ?: 'Services';
        $city = $lead->city ?: ($lead->address ? explode(',', $lead->address)[0] : 'your area');
        $rating = $lead->rating ? number_format((float)$lead->rating, 1) : '4.9';
        $reviewCount = $lead->review_count ?: '150+';

        $heroBadge = ! empty($rawCopy['hero_badge']) ? trim($rawCopy['hero_badge']) : "Top Rated in {$city}";
        $heroHeadline = ! empty($rawCopy['hero_headline']) ? trim($rawCopy['hero_headline']) : "Premium {$category} Solutions by {$bizName}";
        $heroSubheadline = ! empty($rawCopy['hero_subheadline']) ? trim($rawCopy['hero_subheadline']) : "Delivering trusted, top-rated {$category} expertise in {$city} and beyond.";
        $primaryCta = ! empty($rawCopy['primary_cta']) ? trim($rawCopy['primary_cta']) : 'Get Free Quote';
        $secondaryCta = ! empty($rawCopy['secondary_cta']) ? trim($rawCopy['secondary_cta']) : 'Explore Services';
        $urgencyNote = ! empty($rawCopy['urgency_note']) ? trim($rawCopy['urgency_note']) : '⚡ Same-Day Availability • 100% Free Initial Consultation';
        $aboutText = ! empty($rawCopy['about_text']) ? trim($rawCopy['about_text']) : "At {$bizName}, we are dedicated to providing superior {$category} with unmatched customer dedication, reliable craftsmanship, and a proven track record across {$city}.";

        // Stats
        $rawStats = $rawCopy['stats'] ?? [];
        $stats = [];
        if (is_array($rawStats) && count($rawStats) > 0) {
            foreach ($rawStats as $st) {
                if (is_array($st) && ! empty($st['value'])) {
                    $stats[] = [
                        'value' => trim($st['value']),
                        'label' => trim($st['label'] ?? 'Standard'),
                    ];
                }
            }
        }
        if (count($stats) === 0) {
            $stats = [
                ['value' => '15+', 'label' => 'Years Experience'],
                ['value' => "{$rating} ★", 'label' => "Google Score ({$reviewCount})"],
                ['value' => '3,500+', 'label' => 'Completed Projects'],
                ['value' => '100%', 'label' => 'Satisfaction Guarantee'],
            ];
        }

        // Niche features / services
        $rawFeatures = $rawCopy['niche_features'] ?? ($rawCopy['services'] ?? []);
        $nicheFeatures = [];
        if (is_array($rawFeatures)) {
            foreach ($rawFeatures as $feat) {
                if (is_array($feat) && ! empty($feat['title'])) {
                    $bullets = [];
                    if (! empty($feat['bullet_points']) && is_array($feat['bullet_points'])) {
                        foreach ($feat['bullet_points'] as $b) {
                            if (is_string($b) && trim($b) !== '') {
                                $bullets[] = trim($b);
                            }
                        }
                    }
                    if (count($bullets) === 0) {
                        $bullets = ['Certified specialists', 'Comprehensive guarantee', 'Fast turnaround'];
                    }

                    $nicheFeatures[] = [
                        'title' => trim($feat['title']),
                        'description' => trim($feat['description'] ?? "High quality {$feat['title']} tailored to your unique requirements."),
                        'icon_name' => trim($feat['icon_name'] ?? 'check'),
                        'badge' => trim($feat['badge'] ?? 'Featured'),
                        'bullet_points' => array_slice($bullets, 0, 3),
                    ];
                }
            }
        }

        if (count($nicheFeatures) === 0) {
            $nicheFeatures = [
                [
                    'title' => 'Customized Solutions',
                    'description' => "Tailored {$category} solutions engineered specifically for your individual needs.",
                    'icon_name' => 'wrench',
                    'badge' => 'Most Popular',
                    'bullet_points' => ['Personalized approach', 'Accredited specialists', 'Transparent pricing'],
                ],
                [
                    'title' => 'Emergency & Rapid Service',
                    'description' => "Swift dispatch and immediate attention when you need {$category} support most.",
                    'icon_name' => 'clock',
                    'badge' => 'Fast Dispatch',
                    'bullet_points' => ['Priority scheduling', 'Same-day available', 'Direct hotline'],
                ],
                [
                    'title' => 'Precision Maintenance',
                    'description' => "Preventative upkeep and full diagnostics to keep everything functioning at peak performance.",
                    'icon_name' => 'shield',
                    'badge' => 'Preventative',
                    'bullet_points' => ['Complete inspection', 'OEM standards', 'Extended durability'],
                ],
                [
                    'title' => 'Premium Consultation',
                    'description' => "Expert advisory, detailed planning, and upfront transparent quotes for all clients.",
                    'icon_name' => 'star',
                    'badge' => 'Guaranteed',
                    'bullet_points' => ['1-on-1 consultation', 'Written estimates', 'No obligations'],
                ],
            ];
        }

        // Process Steps (How It Works)
        $rawSteps = $rawCopy['process_steps'] ?? [];
        $processSteps = [];
        if (is_array($rawSteps)) {
            foreach ($rawSteps as $i => $s) {
                if (is_array($s) && ! empty($s['title'])) {
                    $processSteps[] = [
                        'step' => trim($s['step'] ?? sprintf('%02d', $i + 1)),
                        'title' => trim($s['title']),
                        'description' => trim($s['description'] ?? 'Detailed execution step ensuring quality results.'),
                    ];
                }
            }
        }
        if (count($processSteps) === 0) {
            $processSteps = [
                ['step' => '01', 'title' => 'Initial Consultation & Diagnostic', 'description' => "We assess your exact {$category} requirements and provide immediate insights."],
                ['step' => '02', 'title' => 'Transparent Itemized Quote', 'description' => 'You receive a straightforward, flat-rate estimate with zero hidden fees.'],
                ['step' => '03', 'title' => 'Expert Execution & Precision', 'description' => 'Certified technicians handle the job using industry-leading materials and techniques.'],
                ['step' => '04', 'title' => 'Quality Review & Warranty Handover', 'description' => 'We perform a final walkthrough to ensure 100% satisfaction and provide full warranty documentation.'],
            ];
        }

        // Why Choose Us
        $rawWhy = $rawCopy['why_choose_us'] ?? [];
        $whyChooseUs = [];
        if (is_array($rawWhy)) {
            foreach ($rawWhy as $w) {
                if (is_array($w) && ! empty($w['title'])) {
                    $whyChooseUs[] = [
                        'title' => trim($w['title']),
                        'description' => trim($w['description'] ?? 'Proven commitment to exceptional craftsmanship.'),
                    ];
                }
            }
        }
        if (count($whyChooseUs) === 0) {
            $whyChooseUs = [
                ['title' => 'Licensed & Master Certified', 'description' => "Our team maintains full credentials and regular training in modern {$category} standards."],
                ['title' => 'Transparent Flat-Rate Pricing', 'description' => 'Honest upfront estimates with zero surprise fees or unnecessary upselling.'],
                ['title' => 'Guaranteed Fast Turnaround', 'description' => 'We value your schedule and guarantee prompt arrivals and rapid completion.'],
                ['title' => '100% Customer Satisfaction', 'description' => 'Every single service is backed by our rock-solid quality commitment and warranty.'],
            ];
        }

        // Testimonials
        $rawTestimonials = $rawCopy['testimonials'] ?? [];
        $testimonials = [];
        if (is_array($rawTestimonials)) {
            foreach ($rawTestimonials as $t) {
                if (is_array($t) && ! empty($t['name'])) {
                    $testimonials[] = [
                        'name' => trim($t['name']),
                        'rating' => (int) ($t['rating'] ?? 5),
                        'role' => trim($t['role'] ?? 'Verified Client'),
                        'service' => trim($t['service'] ?? "{$category} Service"),
                        'comment' => trim($t['comment'] ?? 'Outstanding service, highly recommended!'),
                    ];
                }
            }
        }
        if (count($testimonials) === 0) {
            $testimonials = [
                [
                    'name' => 'Sarah Jenkins',
                    'rating' => 5,
                    'role' => "Local Resident in {$city}",
                    'service' => "Full {$category} Overhaul",
                    'comment' => "{$bizName} exceeded every expectation. Fast, extremely professional, and honest pricing!",
                ],
                [
                    'name' => 'Michael Torres',
                    'rating' => 5,
                    'role' => 'Verified Homeowner',
                    'service' => 'Emergency Diagnostics & Repair',
                    'comment' => 'They responded within minutes and resolved the issue the exact same afternoon. Truly a top-tier team.',
                ],
                [
                    'name' => 'Amanda Vance',
                    'rating' => 5,
                    'role' => 'Business Owner',
                    'service' => 'Commercial Maintenance Contract',
                    'comment' => 'The absolute best in town. We will never use anyone else for our ongoing requirements.',
                ],
            ];
        }

        // FAQ
        $rawFaq = $rawCopy['faq'] ?? [];
        $faq = [];
        if (is_array($rawFaq)) {
            foreach ($rawFaq as $f) {
                if (is_array($f) && ! empty($f['question'])) {
                    $faq[] = [
                        'question' => trim($f['question']),
                        'answer' => trim($f['answer'] ?? 'Please contact us directly for personalized answers to your inquiry.'),
                    ];
                }
            }
        }
        if (count($faq) === 0) {
            $faq = [
                ['question' => "How quickly can I schedule {$category} service in {$city}?", 'answer' => "We offer same-day and next-day appointments throughout {$city} and surrounding areas for urgent inquiries."],
                ['question' => 'Do you provide written guarantees and warranties on your work?', 'answer' => 'Yes, every project comes with a comprehensive written warranty covering both craftsmanship and parts.'],
                ['question' => 'How do I obtain a free price estimate?', 'answer' => 'Simply submit the inquiry form above or call our direct line to receive a free, no-obligation estimate.'],
                ['question' => 'Are your technicians licensed and insured?', 'answer' => 'Yes, 100% of our team members are fully licensed, insured, and background-checked for your peace of mind.'],
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

        // Operating hours
        $rawHours = is_array($rawCopy['operating_hours'] ?? null) ? $rawCopy['operating_hours'] : [];
        $operatingHours = [
            'weekdays' => $rawHours['weekdays'] ?? 'Monday – Friday: 7:30 AM – 6:00 PM',
            'saturday' => $rawHours['saturday'] ?? 'Saturday: 8:00 AM – 4:00 PM',
            'sunday' => $rawHours['sunday'] ?? 'Sunday: Emergency Service By Appointment',
        ];

        $copy = [
            'hero_badge' => $heroBadge,
            'hero_headline' => $heroHeadline,
            'hero_subheadline' => $heroSubheadline,
            'primary_cta' => $primaryCta,
            'secondary_cta' => $secondaryCta,
            'urgency_note' => $urgencyNote,
            'stats' => array_slice($stats, 0, 4),
            'about_text' => $aboutText,
            'niche_features' => array_slice($nicheFeatures, 0, 6),
            'process_steps' => array_slice($processSteps, 0, 4),
            'why_choose_us' => array_slice($whyChooseUs, 0, 4),
            'testimonials' => array_slice($testimonials, 0, 3),
            'faq' => array_slice($faq, 0, 4),
            'trust_indicators' => array_slice($trustIndicators, 0, 4),
            'operating_hours' => $operatingHours,
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
