<!doctype html>
<html
    lang="en"
    class="layout-navbar-fixed layout-wide awt-theme-lake"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-awt-theme="lake"
    data-awt-theme-mode="light"
    data-assets-path="{{ asset('assets') }}/"
    data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Lead Extractor') | {{ config('app.name', 'AWT Phone') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/img/icons/brands/alwtrade.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/awt-themes.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/awt-glass.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/tenant-dashboard.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/awt-table.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/awt-responsive.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/extractor.css') }}" />
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>
<body>
    <div class="layout-wrapper layout-navbar-full extractor-shell">
        <nav class="layout-navbar extractor-navbar">
            <div class="container-xxl d-flex align-items-center gap-3 py-2">
                <a href="{{ url('/') }}" class="app-brand-link d-flex align-items-center text-decoration-none">
                    <span class="app-brand-logo demo">
                        <img src="{{ asset('assets/img/icons/brands/alwtrade.png') }}" alt="AWT Phone" class="extractor-logo">
                    </span>
                    <span class="app-brand-text demo menu-text fw-bold ms-2">AWT Phone</span>
                </a>
                <span class="extractor-nav-divider"></span>
                <span class="extractor-nav-title">Lead Extractor</span>
                <span class="badge bg-label-primary ms-auto">Local</span>
            </div>
        </nav>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y extractor-page awt-dashboard">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    @stack('scripts')
</body>
</html>
