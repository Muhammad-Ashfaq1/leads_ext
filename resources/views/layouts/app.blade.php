@php
    $authUser = auth()->user();
    $bodyClasses = ' layout-navbar-fixed layout-menu-fixed layout-compact ';
    $contentContainerClass = trim($__env->yieldContent('content_container_class')) ?: 'container-xxl flex-grow-1 container-p-y';
@endphp
<!doctype html>
<html
    lang="en"
    class="{{ trim($bodyClasses) }}"
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Dashboard') | Leads Engine</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons & Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <!-- POS Design System Stylesheets -->
    <link rel="stylesheet" href="{{ asset('assets/css/pos-glass.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-navbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-menu.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-table.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-notifications.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/extractor.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

    <!-- Helpers & Config -->
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
    @stack('page-style')

    <link rel="stylesheet" href="{{ asset('assets/css/pos-responsive.css') }}" />
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Sidebar / Vertical Navigation -->
            @include('layouts.partials.sidebar')

            <!-- Layout Page -->
            <div class="layout-page">
                <!-- Top Navbar -->
                @include('layouts.partials.navbar')

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Main Content -->
                    <div class="{{ $contentContainerClass }}">
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
                    <!-- / Main Content -->

                    <!-- Footer -->
                    @include('layouts.partials.footer')

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- / Content wrapper -->
            </div>
            <!-- / Layout Page -->
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>

    <!-- Core Scripts -->
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Global Toastr Configuration (Matching POS Glass UI)
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
                showMethod: 'fadeOut'
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

    @stack('page-script')
    @stack('scripts')
    @yield('scripts')
</body>
</html>
