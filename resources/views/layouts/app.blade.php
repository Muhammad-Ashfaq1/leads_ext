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
    <script>
      (function () {
        const templateName = 'vertical-menu-template';
        const collapsed = localStorage.getItem('templateCustomizer-' + templateName + '--LayoutCollapsed');
        if (collapsed !== null) {
          if (collapsed === 'true') {
            document.documentElement.classList.add('layout-menu-collapsed');
          } else {
            document.documentElement.classList.remove('layout-menu-collapsed');
          }
        }
      })();
    </script>
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
    <link rel="stylesheet" href="{{ asset('assets/css/pos-glass.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-navbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-menu.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-table.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/extractor.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-notifications.css') }}" />
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
        #layout-menu .menu-inner.menu-layout-column {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        #layout-menu .menu-item-settings-bottom {
            margin-top: auto;
        }
        #layout-menu .menu-copyright {
            pointer-events: none;
        }
        #layout-menu .menu-copyright .menu-link {
            cursor: default;
            padding-top: 0.25rem;
            padding-bottom: 0.85rem;
            color: var(--bs-secondary-color);
            font-size: 0.8125rem;
        }
        #layout-menu .menu-copyright .menu-link:hover {
            background: transparent !important;
            color: var(--bs-secondary-color) !important;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Sidebar / Vertical Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="{{ route('dashboard') }}" class="app-brand-link text-decoration-none d-flex align-items-center gap-2">
                        <span class="app-brand-logo-custom">
                            <i class="icon-base ti tabler-radar"></i>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold fs-5 text-heading">Leads Engine</span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                        <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                        <i class="icon-base ti tabler-x d-block d-xl-none"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1 menu-layout-column">
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

                    <li class="menu-item {{ request()->routeIs('email-templates.*') ? 'active' : '' }}">
                        <a href="{{ route('email-templates.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-template"></i>
                            <div data-i18n="Email Templates">Email Templates</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('jobs.index') ? 'active' : '' }}">
                        <a href="{{ route('jobs.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-history"></i>
                            <div data-i18n="Extraction History">Extraction History</div>
                        </a>
                    </li>

                    <!-- Account Section -->
                    <li class="menu-header small text-uppercase mt-3">
                        <span class="menu-header-text">Account</span>
                    </li>

                    <li class="menu-item {{ request()->routeIs('profile.index') ? 'active' : '' }}">
                        <a href="{{ route('profile.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-user"></i>
                            <div data-i18n="My Profile">My Profile</div>
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

                    <!-- Bottom Settings & Copyright (Matching POS) -->
                    <li class="menu-item menu-item-settings-bottom {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <a href="{{ route('settings.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-settings-cog"></i>
                            <div data-i18n="Settings">Settings</div>
                        </a>
                    </li>

                    <li class="menu-item menu-copyright">
                        <div class="menu-link">
                            <div>&copy; {{ date('Y') }} Leads Engine</div>
                        </div>
                    </li>
                </ul>
            </aside>
            <!-- / Sidebar -->

            <!-- Layout Page -->
            <div class="layout-page">
                <!-- Top Navbar matching POS structure -->
                @php
                    $authUser = Auth::user();
                    $isSuperAdmin = $authUser?->isSuperAdmin();
                    $contextLabel = $isSuperAdmin ? 'Super Admin Console' : ($authUser?->tenant?->name ?? 'Leads Engine');
                    $contextSub = $isSuperAdmin ? 'Global SaaS Management' : (strtoupper($authUser?->tenant?->plan ?? 'STARTER').' Workspace');
                @endphp
                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme pos-navbar pos-tone-primary" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="icon-base ti tabler-menu-2"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100" id="navbar-collapse">
                        <!-- Left: Brand / Org Name Treatment -->
                        <div class="pos-navbar-brand">
                            <span class="pos-navbar-org-name" title="{{ $contextLabel }}">{{ $contextLabel }}</span>
                            <small class="pos-navbar-subtitle text-muted">{{ $contextSub }}</small>
                        </div>

                        <!-- Right: Actions & User Dropdown -->
                        <ul class="navbar-nav flex-row align-items-center ms-auto gap-3">
                            <li class="nav-item d-none d-md-block">
                                <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                                    <i class="icon-base ti tabler-plus me-1"></i> New Extraction
                                </a>
                            </li>

                            <li class="nav-item dropdown pos-navbar-account">
                                <a class="nav-link dropdown-toggle hide-arrow p-0 pos-navbar-avatar-trigger" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Account menu" aria-expanded="false">
                                    <div class="avatar avatar-online pos-navbar-avatar">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                                        </span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end pos-navbar-dropdown">
                                    <li>
                                        <div class="pos-navbar-dropdown-head">
                                            <div class="avatar avatar-online pos-navbar-avatar pos-navbar-avatar--lg">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="pos-navbar-dropdown-meta min-w-0">
                                                <div class="pos-navbar-dropdown-name text-truncate">{{ $authUser->name }}</div>
                                                <small class="pos-navbar-dropdown-email text-truncate">{{ $authUser->email }}</small>
                                                <div class="mt-1">
                                                    <span class="badge {{ $authUser->getRoleBadgeClass() }}" style="font-size: 0.65rem;">
                                                        {{ $authUser->getRoleLabel() }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    @unless ($isSuperAdmin)
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <div class="pos-navbar-dropdown-org px-3 py-1">
                                                <span class="pos-navbar-org-name" title="{{ $contextLabel }}">{{ $contextLabel }}</span>
                                            </div>
                                        </li>
                                    @endunless
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.index') }}">
                                            <i class="icon-base ti tabler-user me-2"></i>
                                            <span class="align-middle">Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.index') }}">
                                            <i class="icon-base ti tabler-settings-cog me-2"></i>
                                            <span class="align-middle">Extractor Settings</span>
                                        </a>
                                    </li>
                                    @if ($isSuperAdmin)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('tenants.index') }}">
                                                <i class="icon-base ti tabler-building me-2"></i>
                                                <span class="align-middle">Manage Tenants</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="icon-base ti tabler-logout me-2"></i>
                                                <span class="align-middle">Sign out</span>
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
                </div>
                <!-- / Content wrapper -->
            </div>
            <!-- / Layout Page -->
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global Toastr Configuration (Matching POS Glass / Vuexy UI)
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                debug: false,
                newestOnTop: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                preventDuplicates: false,
                onclick: null,
                showDuration: '300',
                hideDuration: '1000',
                timeOut: '4500',
                extendedTimeOut: '1500',
                showEasing: 'swing',
                hideEasing: 'linear',
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut'
            };
        }

        // Global Helper: showToast
        window.showToast = function(type, message, title = '') {
            if (typeof toastr === 'undefined') {
                console.log(`[${type}] ${title}: ${message}`);
                return;
            }
            const titles = {
                success: title || 'Success',
                error: title || 'Error',
                warning: title || 'Warning',
                info: title || 'Notice'
            };
            switch (type) {
                case 'success':
                    toastr.success(message, titles.success);
                    break;
                case 'error':
                case 'danger':
                    toastr.error(message, titles.error);
                    break;
                case 'warning':
                    toastr.warning(message, titles.warning);
                    break;
                default:
                    toastr.info(message, titles.info);
                    break;
            }
        };

        // Global Helper: showConfirm
        window.showConfirm = function(title, text, confirmButtonText = 'Yes, Proceed', isDanger = true) {
            if (typeof Swal === 'undefined') {
                return Promise.resolve({ isConfirmed: window.confirm(`${title}\n\n${text}`) });
            }
            return Swal.fire({
                title: title,
                text: text,
                icon: isDanger ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'pos-swal-popup pos-glass-card',
                    confirmButton: isDanger ? 'btn btn-danger me-2' : 'btn btn-primary me-2',
                    cancelButton: 'btn btn-outline-secondary'
                },
                buttonsStyling: false
            });
        };

        // Global Session Flash Alerts listener (Auto Toastr)
        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success'))
                showToast('success', {!! json_encode(session('success')) !!}, 'Success');
            @endif

            @if (session('error'))
                showToast('error', {!! json_encode(session('error')) !!}, 'Error');
            @endif

            @if (session('warning'))
                showToast('warning', {!! json_encode(session('warning')) !!}, 'Warning');
            @endif

            @if (session('info'))
                showToast('info', {!! json_encode(session('info')) !!}, 'Information');
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    showToast('error', {!! json_encode($error) !!}, 'Validation Error');
                @endforeach
            @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
