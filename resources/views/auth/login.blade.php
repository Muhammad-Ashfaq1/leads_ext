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
    <title>Login - Leads Engine</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
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
            background: radial-gradient(circle at 10% 20%, rgba(105, 108, 255, 0.08) 0%, rgba(248, 249, 250, 0.96) 90%);
        }
        .authentication-wrapper .authentication-inner {
            max-width: 440px;
            width: 100%;
        }
        .app-brand-logo-custom {
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #696cff, #4338ca);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.45rem;
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.35);
        }
    </style>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Login Card (Matching POS) -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-sm-5">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6 text-center">
                            <a href="{{ url('/') }}" class="app-brand-link text-decoration-none d-inline-flex align-items-center gap-2">
                                <span class="app-brand-logo-custom">
                                    <i class="icon-base ti tabler-radar"></i>
                                </span>
                                <span class="app-brand-text text-heading fw-bold fs-4">Leads Engine</span>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <h4 class="mb-1">Welcome back 👋</h4>
                        <p class="mb-6 text-muted">Sign In to continue into your platform workspace.</p>

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

                        <form action="{{ route('login.post') }}" method="POST">
                            @csrf

                            <div class="mb-6 form-control-validation">
                                <label for="email" class="form-label">Email or Username</label>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="Enter your email"
                                    required
                                    autofocus />
                            </div>

                            <div class="mb-6 form-password-toggle form-control-validation">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group input-group-merge">
                                    <input
                                        type="password"
                                        id="password"
                                        class="form-control"
                                        name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password"
                                        required />
                                    <span class="input-group-text cursor-pointer" id="togglePasswordBtn"><i class="icon-base ti tabler-eye-off" id="togglePasswordIcon"></i></span>
                                </div>
                            </div>

                            <div class="my-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check mb-0 ms-1">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="remember-me"
                                            name="remember"
                                            checked />
                                        <label class="form-check-label" for="remember-me"> Remember Me </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Login Card -->
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePasswordBtn').addEventListener('click', function () {
            const pwdInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
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
    </script>
</body>
</html>
