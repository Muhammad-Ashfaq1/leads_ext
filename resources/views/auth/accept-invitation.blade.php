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
    <title>Join {{ $tenant->name ?? 'Workspace' }} - VektorLeads</title>
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
            max-width: 460px;
            width: 100%;
        }
        .tenant-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            background: rgba(115, 103, 240, 0.08);
            border: 1px solid rgba(115, 103, 240, 0.2);
            border-radius: 9999px;
            color: #7367f0;
            font-size: 0.825rem;
            font-weight: 600;
        }
        .form-control-validation label.form-label {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
        }
    </style>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Accept Invite Card (AWT Phone & POS Style) -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-sm-5">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-5 text-center">
                            <a href="{{ url('/') }}" class="app-brand-link text-decoration-none d-inline-flex align-items-center gap-2">
                                @include('layouts.partials.brand-logo', ['size' => 42])
                                <span class="app-brand-text text-heading fw-bold fs-4">Vektor<span class="text-primary">Leads</span></span>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <div class="text-center mb-5">
                            @if ($tenant)
                                <div class="tenant-pill mb-2">
                                    <i class="icon-base ti tabler-building fs-6"></i>
                                    <span>{{ $tenant->name }}</span>
                                </div>
                            @endif
                            <h4 class="mb-1 fw-bold">Join {{ $tenant->name ?? 'your team' }}</h4>
                            <p class="text-muted small mb-0">
                                @if ($invitation->invitedBy)
                                    Invited by <strong>{{ $invitation->invitedBy->name }}</strong>. Complete your details to activate your account.
                                @else
                                    Complete your details to activate your team account.
                                @endif
                            </p>
                        </div>

                        @if (session('error'))
                            <div class="alert alert-danger py-2 px-3 mb-4" role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="icon-base ti tabler-alert-circle"></i>
                                    <div class="small">{{ session('error') }}</div>
                                </div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger py-2 px-3 mb-4" role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="icon-base ti tabler-alert-circle"></i>
                                    <div class="small">{{ $errors->first() }}</div>
                                </div>
                            </div>
                        @endif

                        <form class="mb-4" action="{{ route('invitations.accept.post', ['token' => $invitation->token]) }}" method="POST">
                            @csrf

                            <div class="mb-4 form-control-validation">
                                <label for="inviteEmail" class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    class="form-control bg-light"
                                    id="inviteEmail"
                                    value="{{ $invitation->email }}"
                                    disabled
                                    readonly />
                                <small class="text-muted">You were invited with this email address.</small>
                            </div>

                            <div class="mb-4 form-control-validation">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $invitation->name) }}"
                                    placeholder="Enter your full name"
                                    required
                                    autofocus />
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4 form-control-validation">
                                <label for="phone" class="form-label">Phone Number <span class="text-muted fw-normal">(optional)</span></label>
                                <input
                                    type="text"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="+1 (555) 000-0000" />
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4 form-password-toggle form-control-validation">
                                <label class="form-label" for="password">Create Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        id="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        name="password"
                                        placeholder="••••••••"
                                        required />
                                    <span class="input-group-text cursor-pointer toggle-pwd-btn" data-target="password">
                                        <i class="icon-base ti tabler-eye-off"></i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimum 6 characters.</small>
                            </div>

                            <div class="mb-5 form-password-toggle form-control-validation">
                                <label class="form-label" for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        id="password_confirmation"
                                        class="form-control"
                                        name="password_confirmation"
                                        placeholder="••••••••"
                                        required />
                                    <span class="input-group-text cursor-pointer toggle-pwd-btn" data-target="password_confirmation">
                                        <i class="icon-base ti tabler-eye-off"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 w-100 py-2" type="submit">
                                    <i class="icon-base ti tabler-user-check fs-5"></i>
                                    <span class="fw-semibold">Create Account &amp; Join Team</span>
                                </button>
                            </div>
                        </form>

                        <p class="text-center mb-0">
                            <span class="text-muted">Already have an account?</span>
                            <a href="{{ route('login') }}" class="text-primary fw-medium text-decoration-none ms-1">Sign in instead</a>
                        </p>
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
