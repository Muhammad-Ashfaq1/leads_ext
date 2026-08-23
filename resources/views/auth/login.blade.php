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
    <link rel="stylesheet" href="{{ asset('assets/css/extractor.css') }}" />
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 10% 20%, rgba(105, 108, 255, 0.08) 0%, rgba(248, 249, 250, 0.95) 90%);
            padding: 1.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            border-radius: 0.75rem;
            border: 1px solid rgba(105, 108, 255, 0.15);
            box-shadow: 0 10px 30px rgba(47, 43, 61, 0.08);
            background: #ffffff;
        }
        .auth-brand-badge {
            width: 3.2rem;
            height: 3.2rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #696cff, #4338ca);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.6rem;
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.35);
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
                    <p class="text-muted small mb-0">Please sign in to your account</p>
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

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address</label>
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
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium mb-0" for="password">Password</label>
                        </div>
                        <div class="input-group input-group-merge">
                            <input
                                type="password"
                                id="password"
                                class="form-control"
                                name="password"
                                placeholder="············"
                                autocomplete="current-password"
                                required>
                        </div>
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label small" for="remember">Remember Me</label>
                        </div>
                    </div>
                    <button class="btn btn-primary d-grid w-100 py-2 fw-semibold" type="submit">
                        <i class="icon-base ti tabler-login me-1"></i> Sign In
                    </button>
                </form>

            </div>
        </div>
    </div>
</body>
</html>
