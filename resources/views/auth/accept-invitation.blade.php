<!doctype html>
<html
    lang="en"
    class="layout-navbar-fixed layout-wide customizer-hide"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="{{ asset('assets') }}/"
    data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Accept Invitation - {{ $tenant->name ?? 'VektorLeads' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon/favicon.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-glass.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/extractor.css') }}" />
    <style>
        .authentication-wrapper {
            display: flex;
            flex-basis: 100%;
            min-height: 100vh;
            width: 100%;
        }
        .authentication-wrapper.authentication-basic {
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.08) 0%, rgba(248, 249, 250, 0.96) 90%);
        }
        .authentication-wrapper .authentication-inner {
            max-width: 480px;
            width: 100%;
        }
        .tenant-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background: rgba(115, 103, 240, 0.08);
            border: 1px solid rgba(115, 103, 240, 0.2);
            border-radius: 9999px;
            color: #7367f0;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Accept Invite Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-sm-5">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-4 text-center">
                            <a href="{{ url('/') }}" class="app-brand-link text-decoration-none d-inline-flex align-items-center gap-2">
                                @include('layouts.partials.brand-logo', ['size' => 42])
                                <span class="app-brand-text text-heading fw-bold fs-4">Vektor<span class="text-primary">Leads</span></span>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <div class="text-center mb-4">
                            <div class="tenant-pill mb-2">
                                <i class="icon-base ti tabler-building"></i>
                                <span>{{ $tenant->name }}</span>
                            </div>
                            <h4 class="mb-1 fw-bold">Accept Team Invitation 🎉</h4>
                            <p class="text-muted small mb-0">
                                @if ($invitation->invitedBy)
                                    <strong>{{ $invitation->invitedBy->name }}</strong> has invited you to join their workspace.
                                @else
                                    You've been invited to join the <strong>{{ $tenant->name }}</strong> team workspace.
                                @endif
                            </p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger py-2 px-3 mb-4" role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="icon-base ti tabler-alert-circle"></i>
                                    <div class="small">{{ $errors->first() }}</div>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('invitations.accept.post', ['token' => $invitation->token]) }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text bg-light text-muted"><i class="icon-base ti tabler-mail"></i></span>
                                    <input
                                        type="email"
                                        class="form-control bg-light"
                                        id="email"
                                        value="{{ $invitation->email }}"
                                        readonly />
                                </div>
                                <small class="text-muted">This email address is locked to your invitation.</small>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Your Full Name <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
                                    <input
                                        type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        id="name"
                                        name="name"
                                        value="{{ old('name', $invitation->name) }}"
                                        placeholder="e.g. Alex Morgan"
                                        required
                                        autofocus />
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold">Contact Phone Number <span class="text-muted fw-normal">(optional)</span></label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-phone"></i></span>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="phone"
                                        name="phone"
                                        value="{{ old('phone') }}"
                                        placeholder="+1 (555) 000-0000" />
                                </div>
                            </div>

                            <div class="mb-3 form-password-toggle">
                                <label class="form-label fw-semibold" for="password">Create Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                    <input
                                        type="password"
                                        id="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        name="password"
                                        placeholder="Minimum 6 characters"
                                        required />
                                    <span class="input-group-text cursor-pointer toggle-pwd-btn" data-target="password"><i class="icon-base ti tabler-eye-off"></i></span>
                                </div>
                            </div>

                            <div class="mb-4 form-password-toggle">
                                <label class="form-label fw-semibold" for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-lock-check"></i></span>
                                    <input
                                        type="password"
                                        id="password_confirmation"
                                        class="form-control"
                                        name="password_confirmation"
                                        placeholder="Repeat your password"
                                        required />
                                    <span class="input-group-text cursor-pointer toggle-pwd-btn" data-target="password_confirmation"><i class="icon-base ti tabler-eye-off"></i></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100 py-2 fw-semibold" type="submit">
                                    <i class="icon-base ti tabler-user-check me-1"></i> Complete Sign Up &amp; Join Team
                                </button>
                            </div>

                            <div class="text-center">
                                <a href="{{ route('login') }}" class="text-muted small">
                                    Already have an existing account? Sign In
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Accept Invite Card -->
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-pwd-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const pwdInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    icon.classList.remove('tabler-eye-off');
                    icon.classList.add('tabler-eye');
                } else {
                    pwdInput.type = 'password';
                    icon.classList.remove('tabler-eye');
                    icon.classList.add('tabler-eye-off');
                }
            });
        });
    </script>
</body>
</html>
