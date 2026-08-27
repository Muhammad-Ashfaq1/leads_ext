@php
    $emails = is_array($lead->emails) ? $lead->emails : (array) $lead->emails;
    $primaryEmail = $emails[0] ?? null;
    $socials = is_array($lead->social_links) ? $lead->social_links : [];
    $ratingVal = $lead->rating ? number_format((float)$lead->rating, 1) : '4.9';
    $reviewCount = $lead->review_count ?: '150+';
    $bizName = $lead->business_name ?: 'Premier Business';
    $category = $lead->category ?: 'Professional Services';
    $city = $lead->city ?: ($lead->address ? explode(',', $lead->address)[0] : 'Your City');

    // Design Tokens with safe defaults
    $tokens = $content['design_tokens'] ?? [];
    $primaryColor = $tokens['primary_color'] ?? 'bg-blue-600';
    $textColor = $tokens['text_color'] ?? 'text-blue-600';
    $accentColor = $tokens['accent_color'] ?? 'bg-amber-500';
    $fontFamily = $tokens['font_family'] ?? 'font-sans';
    $layout = $tokens['hero_layout'] ?? 'split-with-form';

    // Copy with safe defaults
    $copy = $content['copy'] ?? $content;
    $heroBadge = $copy['hero_badge'] ?? "Top Rated in {$city}";
    $headline = $copy['hero_headline'] ?? ($content['hero_headline'] ?? "Transforming {$bizName} with Exceptional {$category}");
    $subheadline = $copy['hero_subheadline'] ?? ($content['hero_subheadline'] ?? "Trusted local {$category} experts committed to excellence, reliability, and unparalleled client satisfaction.");
    $primaryCta = $copy['primary_cta'] ?? 'Get Free Quote';
    $secondaryCta = $copy['secondary_cta'] ?? 'View All Services';
    $urgencyNote = $copy['urgency_note'] ?? '⚡ Same-Day Availability • 100% Free Initial Estimate';
    $aboutText = $copy['about_text'] ?? ($content['about_text'] ?? "At {$bizName}, we pride ourselves on delivering outstanding quality and dependable craftsmanship tailored to each client's specific requirements.");
    $stats = $copy['stats'] ?? [
        ['value' => '15+', 'label' => 'Years Experience'],
        ['value' => "{$ratingVal} ★", 'label' => "Google Score ({$reviewCount})"],
        ['value' => '3,500+', 'label' => 'Completed Projects'],
        ['value' => '100%', 'label' => 'Satisfaction Guarantee'],
    ];
    $nicheFeatures = $copy['niche_features'] ?? ($content['services'] ?? []);
    $processSteps = $copy['process_steps'] ?? [
        ['step' => '01', 'title' => 'Initial Consultation & Diagnostic', 'description' => "We assess your exact {$category} requirements and provide immediate insights."],
        ['step' => '02', 'title' => 'Transparent Itemized Quote', 'description' => 'You receive a straightforward, flat-rate estimate with zero hidden fees.'],
        ['step' => '03', 'title' => 'Expert Execution & Precision', 'description' => 'Certified technicians handle the job using industry-leading materials and techniques.'],
        ['step' => '04', 'title' => 'Quality Review & Warranty Handover', 'description' => 'We perform a final walkthrough to ensure 100% satisfaction and provide full warranty documentation.'],
    ];
    $whyChooseUs = $copy['why_choose_us'] ?? [
        ['title' => 'Licensed & Master Certified', 'description' => "Our team maintains full credentials and regular training in modern {$category} standards."],
        ['title' => 'Transparent Flat-Rate Pricing', 'description' => 'Honest upfront estimates with zero surprise fees or unnecessary upselling.'],
        ['title' => 'Guaranteed Fast Turnaround', 'description' => 'We value your schedule and guarantee prompt arrivals and rapid completion.'],
        ['title' => '100% Customer Satisfaction', 'description' => 'Every single service is backed by our rock-solid quality commitment and warranty.'],
    ];
    $testimonials = $copy['testimonials'] ?? [
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
    $faq = $copy['faq'] ?? [
        ['question' => "How quickly can I schedule {$category} service in {$city}?", 'answer' => "We offer same-day and next-day appointments throughout {$city} and surrounding areas for urgent inquiries."],
        ['question' => 'Do you provide written guarantees and warranties on your work?', 'answer' => 'Yes, every project comes with a comprehensive written warranty covering both craftsmanship and parts.'],
        ['question' => 'How do I obtain a free price estimate?', 'answer' => 'Simply submit the inquiry form above or call our direct line to receive a free, no-obligation estimate.'],
        ['question' => 'Are your technicians licensed and insured?', 'answer' => 'Yes, 100% of our team members are fully licensed, insured, and background-checked for your peace of mind.'],
    ];
    $trustIndicators = $copy['trust_indicators'] ?? [
        'Licensed & Fully Insured',
        '100% Satisfaction Guaranteed',
        'Fast & Reliable Service',
    ];
    $operatingHours = $copy['operating_hours'] ?? [
        'weekdays' => 'Monday – Friday: 7:30 AM – 6:00 PM',
        'saturday' => 'Saturday: 8:00 AM – 4:00 PM',
        'sunday' => 'Sunday: Emergency Service By Appointment',
    ];

    // Icon lookup helper
    $getIcon = function($iconName) {
        $iconName = strtolower(trim($iconName ?? ''));
        return match($iconName) {
            'wrench', 'tool' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
            'home' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
            'star' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
            'shield' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
            'car' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 3c-.1.2-.1.4-.1.6V16c0 .6.4 1 1 1h2m10 0a2 2 0 104 0m-4 0a2 2 0 114 0m-14 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>',
            'calendar', 'clock' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            'phone' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>',
            'briefcase' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
            'map-pin' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'trending-up' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
            'heart' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
            'camera' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'utensils' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>',
            'sparkles' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
            default => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
        };
    };
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bizName }} | Top-Rated {{ $category }} in {{ $city }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                        mono: ['"Space Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
</head>
<body class="{{ $fontFamily }} antialiased text-slate-800 bg-white selection:bg-slate-900 selection:text-white">

    <!-- Sticky Floating "Claim This Website" Header Ribbon -->
    <div class="sticky top-0 z-50 bg-slate-950 text-white border-b border-slate-800/80 shadow-lg backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs">
            <div class="flex items-center gap-2 text-center sm:text-left">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 tracking-wide uppercase">
                    ✨ AI Spec Landing Page
                </span>
                <span class="text-slate-300">
                    Engineered exclusively for <strong class="text-white">{{ $bizName }}</strong>
                </span>
            </div>
            <div class="flex items-center gap-3">
                <a href="#contact" class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-950 hover:bg-slate-100 transition-all shadow-sm">
                    Claim This Website &rarr;
                </a>
                @if($lead->website)
                    <a href="{{ $lead->website }}" target="_blank" rel="noopener" class="text-slate-400 hover:text-white underline underline-offset-2 transition-colors hidden md:inline">
                        Current Site
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-10 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand Logo / Name -->
                <div class="flex items-center gap-3">
                    @if($lead->avatar_url)
                        <img src="{{ $lead->avatar_url }}" alt="{{ $bizName }}" class="w-11 h-11 rounded-xl object-cover border border-slate-200 shadow-sm" onerror="this.style.display='none'">
                    @else
                        <div class="w-11 h-11 rounded-xl {{ $primaryColor }} flex items-center justify-center text-white font-extrabold text-xl shadow-md">
                            {{ strtoupper(substr($bizName, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <a href="#home" class="text-xl font-extrabold tracking-tight text-slate-900 hover:opacity-90 transition-opacity">
                            {{ $bizName }}
                        </a>
                        <p class="text-xs text-slate-500 font-medium">{{ $category }} in {{ $city }}</p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-slate-600">
                    <a href="#services" class="hover:text-slate-900 transition-colors">Services</a>
                    <a href="#process" class="hover:text-slate-900 transition-colors">How It Works</a>
                    <a href="#why-us" class="hover:text-slate-900 transition-colors">Why Choose Us</a>
                    <a href="#testimonials" class="hover:text-slate-900 transition-colors">Reviews</a>
                    <a href="#faq" class="hover:text-slate-900 transition-colors">FAQ</a>
                    <a href="#contact" class="hover:text-slate-900 transition-colors">Contact</a>
                </nav>

                <!-- Header Actions -->
                <div class="flex items-center gap-3">
                    @if($lead->phone)
                        <a href="tel:{{ $lead->phone }}" class="hidden sm:inline-flex items-center gap-2 text-sm font-bold text-slate-700 hover:text-slate-900 px-3 py-2">
                            <svg class="w-4 h-4 {{ $textColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $lead->phone }}
                        </a>
                    @endif
                    <a href="#contact" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-bold text-white {{ $primaryColor }} hover:opacity-95 shadow-md transition-all transform hover:-translate-y-0.5">
                        {{ $primaryCta }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main id="home">
        <!-- Dynamic Hero Section -->
        @if($layout === 'split-with-form')
            <section class="relative pt-12 pb-20 md:pt-16 md:pb-24 overflow-hidden bg-slate-50/70 border-b border-slate-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-7 text-center lg:text-left">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm text-xs font-semibold text-slate-700 mb-6">
                                <span class="flex items-center text-amber-500 gap-1">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span class="font-bold text-slate-900">{{ $ratingVal }}</span> ({{ $reviewCount }} Reviews)
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="{{ $textColor }} font-bold">{{ $heroBadge }}</span>
                            </div>

                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.15] mb-6">
                                {{ $headline }}
                            </h1>

                            <p class="text-lg sm:text-xl text-slate-600 leading-relaxed mb-8 max-w-2xl mx-auto lg:mx-0">
                                {{ $subheadline }}
                            </p>

                            <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 mb-8">
                                <a href="#contact" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-bold text-white {{ $primaryColor }} hover:opacity-95 shadow-lg transition-all transform hover:-translate-y-0.5">
                                    {{ $primaryCta }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                                @if($lead->phone)
                                    <a href="tel:{{ $lead->phone }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-bold text-slate-800 bg-white hover:bg-slate-50 border border-slate-200 shadow-sm transition-all">
                                        <svg class="w-4 h-4 {{ $textColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ $lead->phone }}
                                    </a>
                                @endif
                            </div>

                            <div class="text-xs font-semibold text-slate-500 mb-6 flex items-center justify-center lg:justify-start gap-2">
                                <span>{{ $urgencyNote }}</span>
                            </div>

                            <div class="pt-6 border-t border-slate-200 flex flex-wrap items-center justify-center lg:justify-start gap-5 text-xs sm:text-sm font-semibold text-slate-700">
                                @foreach($trustIndicators as $trust)
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        {{ $trust }}
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Right Column: Interactive Lead Capture Form -->
                        <div class="lg:col-span-5">
                            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200/80 relative">
                                <div class="absolute -top-3 -right-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold text-white {{ $primaryColor }} shadow-sm">
                                        Fast Response
                                    </span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 mb-1">Request Service &amp; Quote</h3>
                                <p class="text-xs text-slate-500 mb-6">Direct inquiry dispatched to {{ $bizName }} staff.</p>

                                <form onsubmit="event.preventDefault(); alert('Demo inquiry submitted for {{ $bizName }}!');" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
                                        <input type="text" placeholder="e.g. Robert Davis" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Phone or Email</label>
                                        <input type="text" placeholder="(555) 000-0000 or email@domain.com" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Service Needed</label>
                                        <select class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-slate-900">
                                            @foreach($nicheFeatures as $feat)
                                                <option value="{{ $feat['title'] }}">{{ $feat['title'] }}</option>
                                            @endforeach
                                            <option value="other">Other Inquiries / Emergency</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl text-sm font-bold text-white {{ $primaryColor }} hover:opacity-95 shadow-md transition-all">
                                        {{ $primaryCta }} &rarr;
                                    </button>
                                </form>
                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                    <span>🔒 No obligation quote</span>
                                    <span>⚡ Fast dispatch</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        @elseif($layout === 'gallery-grid')
            <section class="relative pt-12 pb-20 md:pt-16 md:pb-24 bg-white border-b border-slate-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto mb-12">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-100 text-xs font-bold {{ $textColor }} mb-4">
                            ★ {{ $heroBadge }}
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.15] mb-6">
                            {{ $headline }}
                        </h1>
                        <p class="text-lg text-slate-600 leading-relaxed mb-8">
                            {{ $subheadline }}
                        </p>
                        <div class="flex items-center justify-center gap-4">
                            <a href="#contact" class="inline-flex items-center justify-center px-8 py-3.5 rounded-xl text-base font-bold text-white {{ $primaryColor }} hover:opacity-95 shadow-md transition-all transform hover:-translate-y-0.5">
                                {{ $primaryCta }}
                            </a>
                            @if($lead->phone)
                                <a href="tel:{{ $lead->phone }}" class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl text-base font-semibold text-slate-800 border border-slate-200 hover:bg-slate-50 transition-all">
                                    Call {{ $lead->phone }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Gallery Visual Showcase Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach(array_slice($nicheFeatures, 0, 3) as $idx => $feat)
                            <div class="group relative rounded-2xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 bg-slate-900 text-white min-h-[240px] flex flex-col justify-end p-6">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-slate-800/40 group-hover:scale-105 transition-transform duration-500"></div>
                                <div class="relative z-10">
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold text-white {{ $primaryColor }} mb-2">
                                        Featured #0{{ $idx + 1 }}
                                    </span>
                                    <h3 class="text-xl font-bold text-white mb-1">{{ $feat['title'] }}</h3>
                                    <p class="text-xs text-slate-300 line-clamp-2">{{ $feat['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

        @else
            <section class="relative pt-16 pb-24 md:pt-24 md:pb-28 text-center bg-gradient-to-b from-slate-50 via-white to-white border-b border-slate-100">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm text-xs font-bold text-slate-800 mb-8">
                        <span class="w-2 h-2 rounded-full {{ $primaryColor }}"></span>
                        {{ $heroBadge }} • {{ $city }}
                    </div>

                    <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-slate-900 leading-[1.12] mb-8">
                        {{ $headline }}
                    </h1>

                    <p class="text-xl sm:text-2xl text-slate-600 leading-relaxed mb-10 max-w-3xl mx-auto font-light">
                        {{ $subheadline }}
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-10">
                        <a href="#contact" class="w-full sm:w-auto inline-flex items-center justify-center px-10 py-4 rounded-xl text-base font-bold text-white {{ $primaryColor }} hover:opacity-95 shadow-xl transition-all transform hover:-translate-y-0.5">
                            {{ $primaryCta }} &rarr;
                        </a>
                        @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-xl text-base font-bold text-slate-800 bg-white border border-slate-200 shadow-sm hover:bg-slate-50 transition-all">
                                Call {{ $lead->phone }}
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <!-- Floating Numbers & Key Proof Bar -->
        <section class="py-12 bg-white border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                    @foreach($stats as $st)
                        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-all shadow-sm">
                            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-1 {{ $textColor }}">
                                {{ $st['value'] }}
                            </div>
                            <div class="text-xs sm:text-sm font-bold text-slate-600 uppercase tracking-wider">
                                {{ $st['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Deep Niche Services Matrix (4-6 Detailed Services) -->
        <section id="services" class="py-20 bg-slate-50/50 border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest px-3.5 py-1 rounded-full bg-white border border-slate-200 shadow-sm {{ $textColor }}">
                        Full Service Matrix
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-4">
                        Industry-Tailored {{ $category }} Capabilities
                    </h2>
                    <p class="text-slate-600 text-base sm:text-lg">
                        Customized solutions engineered to the highest professional standards in {{ $city }}.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @forelse($nicheFeatures as $index => $feature)
                        <div class="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-6">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-50 {{ $textColor }} flex items-center justify-center border border-slate-100 shadow-inner">
                                        {!! $getIcon($feature['icon_name'] ?? 'check') !!}
                                    </div>
                                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700">
                                        {{ $feature['badge'] ?? 'Featured' }}
                                    </span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 mb-3">
                                    {{ $feature['title'] ?? 'Professional Service' }}
                                </h3>
                                <p class="text-slate-600 text-sm leading-relaxed mb-6">
                                    {{ $feature['description'] ?? 'Customized high-quality execution designed for optimal results.' }}
                                </p>

                                @if(!empty($feature['bullet_points']))
                                    <ul class="space-y-2 mb-6 text-xs text-slate-600 font-medium">
                                        @foreach($feature['bullet_points'] as $bp)
                                            <li class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                {{ $bp }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                                <a href="#contact" class="text-sm font-bold {{ $textColor }} hover:underline inline-flex items-center gap-1">
                                    Inquire Now &rarr;
                                </a>
                                <span class="text-xs font-bold text-slate-300 font-mono">0{{ $index + 1 }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-10 text-slate-400">
                            <p>No customized services found.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- Proven Process / How It Works -->
        <section id="process" class="py-20 bg-white border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest px-3.5 py-1 rounded-full bg-slate-100 {{ $textColor }}">
                        Seamless Workflow
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-4">
                        How We Deliver Consistent Excellence
                    </h2>
                    <p class="text-slate-600 text-base sm:text-lg">
                        From first contact to completed project, here is what you can expect.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    @foreach($processSteps as $step)
                        <div class="relative p-6 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="text-3xl font-black {{ $textColor }} mb-3 font-mono">
                                {{ $step['step'] }}
                            </div>
                            <h4 class="text-lg font-bold text-slate-900 mb-2">{{ $step['title'] }}</h4>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">{{ $step['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Why Choose Us / Advantage Grid -->
        <section id="why-us" class="py-20 bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5">
                        <span class="inline-block px-3 py-1 rounded-full bg-white/10 text-xs font-bold uppercase tracking-wider mb-4 text-slate-300">
                            The {{ $bizName }} Difference
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-6 leading-tight">
                            Built on Trust, Precision, and Local Reputation
                        </h2>
                        <p class="text-slate-300 text-base leading-relaxed mb-8">
                            {{ $aboutText }}
                        </p>
                        <div class="flex items-center gap-6">
                            <div>
                                <div class="text-3xl font-black text-amber-400">{{ $ratingVal }} ★★★★★</div>
                                <div class="text-xs text-slate-400 mt-1">Average Google Rating</div>
                            </div>
                            <div class="h-10 w-px bg-slate-800"></div>
                            <div>
                                <div class="text-3xl font-black text-white">{{ $reviewCount }}</div>
                                <div class="text-xs text-slate-400 mt-1">Verified Inquiries</div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @foreach($whyChooseUs as $advantage)
                            <div class="p-6 rounded-2xl bg-slate-800/80 border border-slate-700/80">
                                <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm mb-4">
                                    ✓
                                </div>
                                <h4 class="text-lg font-bold text-white mb-2">{{ $advantage['title'] }}</h4>
                                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">{{ $advantage['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials & Client Reviews -->
        <section id="testimonials" class="py-20 bg-white border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest px-3.5 py-1 rounded-full bg-slate-100 {{ $textColor }}">
                        Client Endorsements
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-4">
                        What People Say About {{ $bizName }}
                    </h2>
                    <p class="text-slate-600 text-base sm:text-lg">
                        Authentic feedback from satisfied local clients across {{ $city }}.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach($testimonials as $testi)
                        <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100 shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex items-center text-amber-400 mb-4">
                                    @for($i = 0; $i < ($testi['rating'] ?? 5); $i++)
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    @endfor
                                </div>
                                <p class="text-slate-700 text-sm italic leading-relaxed mb-6">
                                    "{{ $testi['comment'] }}"
                                </p>
                            </div>
                            <div class="pt-4 border-t border-slate-200/60 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">{{ $testi['name'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $testi['role'] ?? 'Verified Client' }}</div>
                                </div>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600">
                                    {{ $testi['service'] ?? $category }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Frequently Asked Questions (FAQ) -->
        <section id="faq" class="py-20 bg-slate-50/50 border-b border-slate-100">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest px-3.5 py-1 rounded-full bg-white border border-slate-200 shadow-sm {{ $textColor }}">
                        Got Questions?
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-4">
                        Frequently Asked Questions
                    </h2>
                    <p class="text-slate-600 text-base">
                        Everything you need to know about booking with {{ $bizName }}.
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach($faq as $item)
                        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                            <h4 class="text-base font-bold text-slate-900 mb-2 flex items-start gap-3">
                                <span class="text-xs font-extrabold px-2 py-0.5 rounded bg-slate-100 text-slate-600 mt-0.5">Q</span>
                                {{ $item['question'] }}
                            </h4>
                            <p class="text-slate-600 text-sm leading-relaxed pl-8">
                                {{ $item['answer'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Contact Section & Direct Booking -->
        <section id="contact" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest px-3.5 py-1 rounded-full bg-slate-100 {{ $textColor }}">
                        Get In Touch
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-4">
                        Connect with {{ $bizName }}
                    </h2>
                    <p class="text-slate-600 text-base sm:text-lg">
                        Ready to book service or have a question? Contact our team today.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Business Info & Operating Hours -->
                    <div class="lg:col-span-5 bg-slate-50 rounded-3xl p-8 border border-slate-200 shadow-sm space-y-6">
                        <h3 class="text-xl font-bold text-slate-900">Direct Contact &amp; Hours</h3>

                        @if($lead->phone)
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white {{ $textColor }} flex items-center justify-center flex-shrink-0 border border-slate-200 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-400 uppercase">Phone</span>
                                    <a href="tel:{{ $lead->phone }}" class="text-base font-bold text-slate-900 hover:underline">
                                        {{ $lead->phone }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($primaryEmail)
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white text-emerald-600 flex items-center justify-center flex-shrink-0 border border-slate-200 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-400 uppercase">Email</span>
                                    <a href="mailto:{{ $primaryEmail }}" class="text-base font-bold text-slate-900 hover:underline">
                                        {{ $primaryEmail }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($lead->address)
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white text-rose-600 flex items-center justify-center flex-shrink-0 border border-slate-200 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-400 uppercase">Address</span>
                                    <p class="text-sm font-medium text-slate-700">{{ $lead->address }}</p>
                                    @if($lead->google_maps_url)
                                        <a href="{{ $lead->google_maps_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-bold {{ $textColor }} mt-2 hover:underline">
                                            Open in Google Maps &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Operating Hours Box -->
                        <div class="pt-4 border-t border-slate-200">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Operating Schedule</h4>
                            <div class="space-y-1.5 text-xs text-slate-700">
                                <div class="flex justify-between">
                                    <span>{{ $operatingHours['weekdays'] }}</span>
                                </div>
                                <div class="flex justify-between font-medium">
                                    <span>{{ $operatingHours['saturday'] }}</span>
                                </div>
                                <div class="flex justify-between text-slate-500">
                                    <span>{{ $operatingHours['sunday'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Contact Form -->
                    <div class="lg:col-span-7 bg-white rounded-3xl p-8 border border-slate-200 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Send an Online Inquiry</h3>
                        <p class="text-xs text-slate-500 mb-6">Leave your details and {{ $bizName }} staff will respond promptly.</p>

                        <form onsubmit="event.preventDefault(); alert('Inquiry sent successfully!');" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Your Name *</label>
                                    <input type="text" required placeholder="John Smith" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Contact Details *</label>
                                    <input type="text" required placeholder="Email or Phone Number" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Service Required</label>
                                    <select class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                                        @foreach($nicheFeatures as $feat)
                                            <option value="{{ $feat['title'] }}">{{ $feat['title'] }}</option>
                                        @endforeach
                                        <option value="General">General Inquiry</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Preferred Timeframe</label>
                                    <select class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                                        <option value="asap">Immediate / Emergency</option>
                                        <option value="this_week">This Week</option>
                                        <option value="next_week">Next Week</option>
                                        <option value="planning">Just Planning</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Message / Project Details *</label>
                                <textarea rows="4" required placeholder="Describe your project or inquiry..." class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"></textarea>
                            </div>
                            <button type="submit" class="px-8 py-3.5 rounded-xl text-sm font-bold text-white {{ $primaryColor }} hover:opacity-95 shadow-md transition-all">
                                {{ $primaryCta }} &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 py-12 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <span class="text-xl font-bold text-white tracking-tight">{{ $bizName }}</span>
                    <p class="text-xs text-slate-500 mt-1">Professional {{ $category }} serving {{ $city }} and surrounding regions.</p>
                </div>
                <div class="text-xs text-slate-500 text-center md:text-right">
                    © {{ date('Y') }} {{ $bizName }}. All rights reserved. • Spec landing page concept.
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
