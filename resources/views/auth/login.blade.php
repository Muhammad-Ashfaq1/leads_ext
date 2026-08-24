<!doctype html>
<html
    lang="en"
    class="layout-navbar-fixed layout-wide"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="{{ asset('assets') }}/"
    data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Login | Leads Engine</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pos-glass.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/extractor.css') }}" />
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 10% 20%, rgba(105, 108, 255, 0.12) 0%, rgba(248, 249, 250, 0.96) 90%);
            padding: 1.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 450px;
            border-radius: 1rem;
            border: 1px solid rgba(105, 108, 255, 0.18);
            box-shadow: 0 16px 40px rgba(47, 43, 61, 0.1);
            background: #ffffff;
        }
        .auth-brand-badge {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 0.85rem;
            background: linear-gradient(135deg, #696cff, #4338ca);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.8rem;
            box-shadow: 0 6px 16px rgba(105, 108, 255, 0.35);
        }
        .demo-cred-btn {
            background: rgba(105, 108, 255, 0.08);
            border: 1px dashed rgba(105, 108, 255, 0.35);
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-size: 0.8rem;
            text-align: left;
        }
        .demo-cred-btn:hover {
            background: rgba(105, 108, 255, 0.18);
            border-color: #696cff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="auth-brand-badge mb-3">
                        <i class="icon-base ti tabler-radar"></i>
                    </div>
                    <h4 class="mb-1 fw-bold">Leads Engine</h4>
                    <p class="text-muted small mb-0">Sign in to your Obtain Solutions account</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 px-3 mb-4" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="icon-base ti tabler-alert-circle"></i>
                            <div class="small">{{ $errors->first() }}</div>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success py-2 px-3 mb-4" role="alert">
                        <div class="small">{{ session('success') }}</div>
                    </div>
                @endif

                <!-- Quick Login Credentials Pill Bar -->
                <div class="mb-4">
                    <label class="form-label small text-muted fw-semibold mb-2">
                        <i class="icon-base ti tabler-key me-1"></i> Quick Sign-In Accounts:
                    </label>
                    <div class="d-grid gap-2">
                        <button type="button" class="demo-cred-btn d-flex justify-content-between align-items-center" onclick="fillCreds('superadmin@obtainsolutions.com', 'Obtain@2026!')">
                            <div>
                                <span class="badge bg-label-danger me-1">Super Admin</span>
                                <strong class="text-heading">superadmin@obtainsolutions.com</strong>
                            </div>
                            <i class="icon-base ti tabler-chevron-right text-muted"></i>
                        </button>
                        <button type="button" class="demo-cred-btn d-flex justify-content-between align-items-center" onclick="fillCreds('admin@obtainsolutions.com', 'Obtain@2026!')">
                            <div>
                                <span class="badge bg-label-primary me-1">Tenant Admin</span>
                                <strong class="text-heading">admin@obtainsolutions.com</strong>
                            </div>
                            <i class="icon-base ti tabler-chevron-right text-muted"></i>
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Enter your email"
                                autocomplete="email"
                                autofocus
                                required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium mb-0" for="password">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="icon-base ti tabler-lock"></i></span>
                            <input
                                type="password"
                                id="password"
                                class="form-control"
                                name="password"
                                placeholder="············"
                                autocomplete="current-password"
                                required>
                            <span class="input-group-text cursor-pointer" id="togglePasswordBtn" title="Toggle password visibility">
                                <i class="icon-base ti tabler-eye" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" checked>
                            <label class="form-check-label small" for="remember">Remember Me</label>
                        </div>
                    </div>
                    <button class="btn btn-primary d-grid w-100 py-2 fw-semibold" type="submit">
                        <i class="icon-base ti tabler-login me-1"></i> Sign In to Platform
                    </button>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">Obtain Solutions Leads Engine Platform © 2026</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillCreds(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }

        document.getElementById('togglePasswordBtn').addEventListener('click', function () {
            const pwdInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                icon.classList.remove('tabler-eye');
                icon.classList.add('tabler-eye-off');
            } else {
                pwdInput.type = 'password';
                icon.classList.remove('tabler-eye-off');
                icon.classList.add('tabler-eye');
            }
        });
    </script>
</body>
</html>
