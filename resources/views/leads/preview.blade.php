@php
    $emails = is_array($lead->emails) ? $lead->emails : (array) $lead->emails;
    $primaryEmail = $emails[0] ?? null;
    $socials = is_array($lead->social_links) ? $lead->social_links : [];
    $ratingVal = $lead->rating ? number_format((float)$lead->rating, 1) : '4.9';
    $reviewCount = $lead->review_count ?: '95+';
    $bizName = $lead->business_name ?: 'Premier Business';
    $category = $lead->category ?: 'Professional Services';
    $city = $lead->city ?: ($lead->address ? explode(',', $lead->address)[0] : 'Your City');
    $headline = $content['hero_headline'] ?? "Transforming {$bizName} with Exceptional {$category}";
    $subheadline = $content['hero_subheadline'] ?? "Trusted local {$category} experts committed to excellence, reliability, and unparalleled client satisfaction.";
    $aboutText = $content['about_text'] ?? "At {$bizName}, we pride ourselves on delivering outstanding quality and dependable craftsmanship tailored to each client's specific requirements.";
    $services = $content['services'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bizName }} | Spec Landing Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased text-slate-800 bg-white selection:bg-indigo-500 selection:text-white">

    <!-- Top Demo Notification Ribbon -->
    <div class="bg-slate-900 text-white text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                    AI Spec Preview
                </span>
                <span class="text-slate-300">
                    Live demo landing page generated for <strong class="text-white">{{ $bizName }}</strong>
                </span>
            </div>
            <div class="flex items-center gap-3">
                @if($lead->website)
                    <a href="{{ $lead->website }}" target="_blank" rel="noopener" class="text-slate-400 hover:text-white underline underline-offset-2 transition-colors">
                        Current Website &rarr;
                    </a>
                @endif
                <span class="text-slate-500">•</span>
                <span class="text-slate-400">Powered by Gemini AI</span>
            </div>
        </div>
    </div>

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-100 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand Logo / Name -->
                <div class="flex items-center gap-3">
                    @if($lead->avatar_url)
                        <img src="{{ $lead->avatar_url }}" alt="{{ $bizName }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-sm" onerror="this.style.display='none'">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 flex items-center justify-center text-white font-extrabold text-lg shadow-md shadow-indigo-500/20">
                            {{ strtoupper(substr($bizName, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <a href="#home" class="text-xl font-bold tracking-tight text-slate-900 hover:text-indigo-600 transition-colors">
                            {{ $bizName }}
                        </a>
                        <p class="text-xs text-slate-500 font-medium">{{ $category }} in {{ $city }}</p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                    <a href="#services" class="hover:text-indigo-600 transition-colors">Services</a>
                    <a href="#about" class="hover:text-indigo-600 transition-colors">About Us</a>
                    <a href="#why-us" class="hover:text-indigo-600 transition-colors">Why Choose Us</a>
                    <a href="#contact" class="hover:text-indigo-600 transition-colors">Contact</a>
                </nav>

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    @if($lead->phone)
                        <a href="tel:{{ $lead->phone }}" class="hidden sm:inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-indigo-600 px-3 py-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $lead->phone }}
                        </a>
                    @endif
                    <a href="#contact" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                        Get a Free Quote
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main id="home">
        <!-- Hero Section -->
        <section class="relative pt-12 pb-20 md:pt-20 md:pb-28 overflow-hidden bg-gradient-to-b from-indigo-50/50 via-white to-white">
            <div class="absolute inset-0 z-0 opacity-30 pointer-events-none" style="background-image: radial-gradient(#6366f1 0.75px, transparent 0.75px); background-size: 24px 24px;"></div>
            
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    
                    <div class="lg:col-span-7 text-center lg:text-left">
                        <!-- Rating & Category Pill -->
                        <div class="inline-flex flex-wrap items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-indigo-100 shadow-sm text-xs font-semibold text-slate-700 mb-6">
                            <span class="flex items-center text-amber-500 gap-1">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span class="font-bold text-slate-900">{{ $ratingVal }}</span> ({{ $reviewCount }} Reviews)
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="text-indigo-600 font-medium">{{ $category }} in {{ $city }}</span>
                        </div>

                        <!-- Main Headline -->
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.15] mb-6">
                            {{ $headline }}
                        </h1>

                        <!-- Subheadline -->
                        <p class="text-lg sm:text-xl text-slate-600 leading-relaxed mb-8 max-w-2xl mx-auto lg:mx-0">
                            {{ $subheadline }}
                        </p>

                        <!-- Hero CTA Buttons -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            <a href="#contact" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                                Schedule Free Consultation
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                            @if($lead->phone)
                                <a href="tel:{{ $lead->phone }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-sm transition-all">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    Call Now: {{ $lead->phone }}
                                </a>
                            @endif
                        </div>

                        <!-- Trust Checkmarks -->
                        <div class="mt-10 pt-8 border-t border-slate-200/70 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs sm:text-sm font-semibold text-slate-600">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Licensed & Certified
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                100% Satisfaction Guaranteed
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Fast & Reliable
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Interactive Quick Card -->
                    <div class="lg:col-span-5">
                        <div class="relative mx-auto max-w-md bg-white rounded-3xl p-6 sm:p-8 shadow-2xl shadow-slate-200/80 border border-slate-100">
                            <div class="absolute -top-3 -right-3">
                                <span class="flex h-6 w-6 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-6 w-6 bg-emerald-500 text-white items-center justify-center text-[10px] font-bold">✓</span>
                                </span>
                            </div>

                            <h3 class="text-xl font-bold text-slate-900 mb-2">Request an Estimate</h3>
                            <p class="text-sm text-slate-500 mb-6">Leave your details and our team will get back to you within 24 hours.</p>

                            <form onsubmit="event.preventDefault(); alert('Thank you! Your estimate request has been received.');" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Your Full Name</label>
                                    <input type="text" placeholder="Jane Doe" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-slate-50/50">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone or Email</label>
                                    <input type="text" placeholder="you@example.com or (555) 000-0000" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-slate-50/50">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Service Needed</label>
                                    <select class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-slate-50/50">
                                        @foreach($services as $s)
                                            <option value="{{ $s['title'] ?? 'General Inquiry' }}">{{ $s['title'] ?? 'General Inquiry' }}</option>
                                        @endforeach
                                        <option value="other">Other Inquiry</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full py-3.5 px-4 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-md">
                                    Submit Request &rarr;
                                </button>
                            </form>

                            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                                <span>🔒 Strictly confidential</span>
                                <span>⚡ No spam guarantee</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="py-20 bg-slate-50 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100">
                        Our Specialized Offerings
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-4">
                        Tailored Services by {{ $bizName }}
                    </h2>
                    <p class="text-slate-600 text-base sm:text-lg">
                        We offer dedicated solutions designed to deliver superior outcomes with precision and care.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @php
                        $icons = [
                            '<svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                            '<svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                            '<svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                        ];
                    @endphp

                    @forelse($services as $index => $service)
                        <div class="bg-white rounded-2xl p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-6">
                                    {!! $icons[$index % 3] !!}
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 mb-3">
                                    {{ $service['title'] ?? 'Professional Service' }}
                                </h3>
                                <p class="text-slate-600 text-sm leading-relaxed mb-6">
                                    {{ $service['description'] ?? 'High quality solutions crafted with precision and dedicated customer support.' }}
                                </p>
                            </div>
                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                                <a href="#contact" class="text-sm font-bold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1.5 group">
                                    Learn More <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </a>
                                <span class="text-xs font-semibold text-slate-400">0{{ $index + 1 }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-10 text-slate-400">
                            <p>No customized service entries found.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- About Us Section -->
        <section id="about" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    
                    <div class="lg:col-span-6 order-2 lg:order-1">
                        <div class="relative">
                            <div class="aspect-4/3 rounded-3xl bg-gradient-to-tr from-indigo-900 via-indigo-700 to-indigo-500 p-8 sm:p-12 text-white flex flex-col justify-between shadow-2xl">
                                <div>
                                    <span class="inline-block px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider mb-4">
                                        Established Excellence
                                    </span>
                                    <h3 class="text-2xl sm:text-3xl font-extrabold leading-snug">
                                        Building Long-Term Trust in {{ $city }}
                                    </h3>
                                </div>
                                <div class="grid grid-cols-2 gap-4 pt-8 border-t border-white/20">
                                    <div>
                                        <div class="text-3xl font-black">{{ $ratingVal }} ★</div>
                                        <div class="text-xs text-indigo-200 mt-1">Average Google Rating</div>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black">{{ $reviewCount }}</div>
                                        <div class="text-xs text-indigo-200 mt-1">Happy Clients</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-6 order-1 lg:order-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100">
                            About {{ $bizName }}
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-6">
                            Dedicated to Delivering the Highest Standard of {{ $category }}
                        </h2>
                        <div class="prose prose-slate text-slate-600 leading-relaxed text-base sm:text-lg mb-8">
                            <p>{{ $aboutText }}</p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    ✓
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Dedicated local team with in-depth industry experience</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    ✓
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Transparent communication, clear pricing, and no hidden surprises</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    ✓
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Prompt turnaround with end-to-end satisfaction guarantee</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Why Choose Us Banner -->
        <section id="why-us" class="py-16 bg-slate-900 text-white relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-slate-800">
                    <div class="p-4">
                        <div class="text-3xl font-extrabold text-indigo-400 mb-1">100%</div>
                        <div class="text-sm text-slate-300 font-medium">Customer Centric</div>
                    </div>
                    <div class="p-4">
                        <div class="text-3xl font-extrabold text-indigo-400 mb-1">{{ $ratingVal }} ★</div>
                        <div class="text-sm text-slate-300 font-medium">Top Quality Score</div>
                    </div>
                    <div class="p-4">
                        <div class="text-3xl font-extrabold text-indigo-400 mb-1">Fast</div>
                        <div class="text-sm text-slate-300 font-medium">Responsive Service</div>
                    </div>
                    <div class="p-4">
                        <div class="text-3xl font-extrabold text-indigo-400 mb-1">Verified</div>
                        <div class="text-sm text-slate-300 font-medium">Authentic Craftsmanship</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact & Location Section -->
        <section id="contact" class="py-20 bg-slate-50 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center mb-16">
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100">
                        Get In Touch
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 mb-4">
                        Contact {{ $bizName }} Today
                    </h2>
                    <p class="text-slate-600 text-base sm:text-lg">
                        Have questions or ready to get started? We are here to help you every step of the way.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Business Info Card -->
                    <div class="lg:col-span-5 bg-white rounded-2xl p-8 border border-slate-200 shadow-sm space-y-6">
                        <h3 class="text-xl font-bold text-slate-900">Direct Contact Details</h3>

                        @if($lead->phone)
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-400 uppercase">Telephone</span>
                                    <a href="tel:{{ $lead->phone }}" class="text-base font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ $lead->phone }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($primaryEmail)
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-400 uppercase">Email Address</span>
                                    <a href="mailto:{{ $primaryEmail }}" class="text-base font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                                        {{ $primaryEmail }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($lead->address)
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-400 uppercase">Location & Address</span>
                                    <p class="text-sm font-medium text-slate-700">{{ $lead->address }}</p>
                                    @if($lead->google_maps_url)
                                        <a href="{{ $lead->google_maps_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 mt-2 hover:underline">
                                            Open in Google Maps &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($lead->business_hours)
                            <div class="pt-4 border-t border-slate-100">
                                <span class="block text-xs font-semibold text-slate-400 uppercase mb-2">Operating Hours</span>
                                <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg font-mono">
                                    {{ $lead->business_hours }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Message Form -->
                    <div class="lg:col-span-7 bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Send an Online Inquiry</h3>
                        <p class="text-sm text-slate-500 mb-6">Fill out the form below and we will get back to you promptly.</p>

                        <form onsubmit="event.preventDefault(); alert('Message submitted successfully!');" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Your Name *</label>
                                    <input type="text" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Email Address *</label>
                                    <input type="email" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Subject</label>
                                <input type="text" placeholder="Project Inquiry / Estimate Request" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Message *</label>
                                <textarea rows="4" required placeholder="How can we assist you?" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>
                            <button type="submit" class="px-8 py-3.5 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <span class="text-xl font-bold text-white tracking-tight">{{ $bizName }}</span>
                    <p class="text-xs text-slate-500 mt-1">Professional {{ $category }} serving {{ $city }} and surrounding areas.</p>
                </div>

                <div class="flex items-center gap-6 text-sm">
                    <a href="#services" class="hover:text-white transition-colors">Services</a>
                    <a href="#about" class="hover:text-white transition-colors">About</a>
                    <a href="#contact" class="hover:text-white transition-colors">Contact</a>
                </div>

                <div class="flex items-center gap-4">
                    @if(!empty($socials['linkedin']))
                        <a href="{{ $socials['linkedin'] }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" title="LinkedIn">LinkedIn</a>
                    @endif
                    @if(!empty($socials['facebook']))
                        <a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" title="Facebook">Facebook</a>
                    @endif
                    @if(!empty($socials['instagram']))
                        <a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" title="Instagram">Instagram</a>
                    @endif
                    @if(!empty($socials['twitter']))
                        <a href="{{ $socials['twitter'] }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" title="Twitter / X">Twitter</a>
                    @endif
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-slate-800 text-xs text-center text-slate-500">
                <p>© {{ date('Y') }} {{ $bizName }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>

