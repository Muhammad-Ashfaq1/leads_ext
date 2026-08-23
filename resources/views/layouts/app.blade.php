<!doctype html>
<html
    lang="en"
    class="layout-navbar-fixed layout-menu-fixed layout-compact"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="{{ asset('assets') }}/"
    data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Dashboard') | Leads Engine</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/extractor.css') }}" />
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <style>
        .app-brand-logo-custom {
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #696cff, #4338ca);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.15rem;
            box-shadow: 0 3px 8px rgba(105, 108, 255, 0.35);
        }
        .user-avatar-badge {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(105, 108, 255, 0.15), rgba(37, 185, 214, 0.15));
            color: #696cff;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            border: 1px solid rgba(105, 108, 255, 0.2);
        }
        .layout-menu .menu-item.active > .menu-link {
            background: linear-gradient(72.47deg, #696cff 22.16%, rgba(105, 108, 255, 0.7) 76.47%) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px 0 rgba(105, 108, 255, 0.48);
        }
        .layout-menu .menu-item.active > .menu-link i {
            color: #ffffff !important;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Sidebar / Vertical Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo py-3">
                    <a href="{{ route('dashboard') }}" class="app-brand-link text-decoration-none d-flex align-items-center gap-2">
                        <span class="app-brand-logo-custom">
                            <i class="icon-base ti tabler-radar"></i>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold fs-5 text-heading">Leads Engine</span>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <!-- Main Apps Section -->
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Lead Generation</span>
                    </li>

                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-smart-home"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('extractor.index') ? 'active' : '' }}">
                        <a href="{{ route('extractor.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-map-pin-search"></i>
                            <div data-i18n="Lead Extractor">Lead Extractor</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('leads.index') ? 'active' : '' }}">
                        <a href="{{ route('leads.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-users-group"></i>
                            <div data-i18n="All Extracted Leads">All Extracted Leads</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('jobs.index') ? 'active' : '' }}">
                        <a href="{{ route('jobs.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-history"></i>
                            <div data-i18n="Extraction History">Extraction History</div>
                        </a>
                    </li>

                    <!-- Administration Section -->
                    <li class="menu-header small text-uppercase mt-3">
                        <span class="menu-header-text">Organization</span>
                    </li>

                    <li class="menu-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <a href="{{ route('users.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-user-cog"></i>
                            <div data-i18n="Team Members">Team Members</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('profile.index') ? 'active' : '' }}">
                        <a href="{{ route('profile.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-user"></i>
                            <div data-i18n="My Profile">My Profile</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <a href="{{ route('settings.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-settings"></i>
                            <div data-i18n="Extractor Settings">Extractor Settings</div>
                        </a>
                    </li>

                    @if (Auth::user()?->isSuperAdmin())
                        <!-- Super Admin Section -->
                        <li class="menu-header small text-uppercase mt-3">
                            <span class="menu-header-text">Super Admin</span>
                        </li>

                        <li class="menu-item {{ request()->routeIs('tenants.index') ? 'active' : '' }}">
                            <a href="{{ route('tenants.index') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-building"></i>
                                <div data-i18n="Tenants / Clients">Tenants / Clients</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </aside>
            <!-- / Sidebar -->

            <!-- Layout Page -->
            <div class="layout-page">
                <!-- Top Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme shadow-sm" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="icon-base ti tabler-menu-2"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100" id="navbar-collapse">
                        <!-- Left: Tenant Indicator & Quick Actions -->
                        <div class="d-flex align-items-center gap-2">
                            @if (Auth::user()?->tenant)
                                <span class="badge bg-label-primary px-3 py-2 d-flex align-items-center gap-1">
                                    <i class="icon-base ti tabler-building me-1"></i>
                                    <span class="fw-semibold">{{ Auth::user()->tenant->name }}</span>
                                    <span class="badge bg-primary text-white ms-1" style="font-size: 0.65rem;">{{ strtoupper(Auth::user()->tenant->plan) }}</span>
                                </span>
                            @elseif (Auth::user()?->isSuperAdmin())
                                <span class="badge bg-label-danger px-3 py-2 d-flex align-items-center gap-1">
                                    <i class="icon-base ti tabler-shield-check me-1"></i>
                                    <span class="fw-semibold">Super Admin Console</span>
                                </span>
                            @endif

                            <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary d-none d-md-inline-flex align-items-center ms-2">
                                <i class="icon-base ti tabler-plus me-1"></i> New Extraction
                            </a>
                        </div>

                        <!-- Right: User Profile Dropdown -->
                        <ul class="navbar-nav flex-row align-items-center ms-auto gap-2">
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-avatar-badge">
                                        {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <div class="dropdown-item py-2">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="user-avatar-badge">
                                                        {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 fw-semibold">{{ Auth::user()->name }}</h6>
                                                    <small class="badge {{ Auth::user()->getRoleBadgeClass() }} mt-1" style="font-size: 0.65rem;">
                                                        {{ Auth::user()->getRoleLabel() }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li><div class="dropdown-divider"></div></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.index') }}">
                                            <i class="icon-base ti tabler-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.index') }}">
                                            <i class="icon-base ti tabler-settings me-2"></i>
                                            <span class="align-middle">Extractor Settings</span>
                                        </a>
                                    </li>
                                    @if (Auth::user()?->isSuperAdmin())
                                        <li>
                                            <a class="dropdown-item" href="{{ route('tenants.index') }}">
                                                <i class="icon-base ti tabler-building me-2"></i>
                                                <span class="align-middle">Manage Tenants</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li><div class="dropdown-divider"></div></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="icon-base ti tabler-logout me-2"></i>
                                                <span class="align-middle">Sign Out</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- / Top Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible py-2 px-3 mb-3" role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="icon-base ti tabler-circle-check"></i>
                                    <div>{{ session('success') }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible py-2 px-3 mb-3" role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="icon-base ti tabler-alert-circle"></i>
                                    <div>{{ session('error') }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme border-top py-3">
                        <div class="container-xxl d-flex flex-wrap justify-content-between align-items-center py-2 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0 text-muted small">
                                © 2026 <strong>Leads Engine</strong> — SaaS Lead Generation &amp; Enrichment Platform
                            </div>
                            <div class="d-none d-lg-inline-block text-muted small">
                                <span class="badge bg-label-secondary">v2.5.0</span>
                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->
                </div>
                <!-- / Content wrapper -->
            </div>
            <!-- / Layout Page -->
        </div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    @stack('scripts')
</body>
</html>
