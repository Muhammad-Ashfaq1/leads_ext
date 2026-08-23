@extends('layouts.app')

@section('title', 'Settings & API Keys')

@section('content')
<div class="row g-4">
    @if ($tenant && ($user->isAdmin() || $user->isSuperAdmin()))
        <!-- Tenant Organization Settings -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-semibold text-heading">
                        <i class="icon-base ti tabler-building me-1 text-primary"></i> Organization &amp; API Keys
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('settings.tenant') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Company / Organization Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $tenant->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Plan Tier</label>
                            <div>
                                <span class="badge {{ $tenant->plan === 'enterprise' ? 'bg-label-primary' : 'bg-label-info' }} fs-6">
                                    {{ ucfirst($tenant->plan) }} Plan ({{ number_format($tenant->lead_quota) }} monthly quota)
                                </span>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Google Maps Places API Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                <input type="password" name="google_maps_api_key" id="tenantApiKeyInput" class="form-control" value="{{ $tenant->google_maps_api_key }}" placeholder="AIzaSy...">
                                <button class="btn btn-outline-secondary" type="button" onclick="const el = document.getElementById('tenantApiKeyInput'); el.type = el.type === 'password' ? 'text' : 'password';">
                                    <i class="icon-base ti tabler-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted mt-1">
                                @if ($hasGlobalGoogleKey)
                                    <span class="text-success"><i class="icon-base ti tabler-check"></i> System default API key is active.</span> You can optionally provide your own key here to override the system key.
                                @else
                                    Enter your Google Maps Platform API key to enable high-speed Places API lead extraction.
                                @endif
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-base ti tabler-device-floppy me-1"></i> Save Organization Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Profile Information -->
    <div class="col-12 col-lg-{{ $tenant && ($user->isAdmin() || $user->isSuperAdmin()) ? '6' : '12' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom py-3">
                <h5 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-user me-1 text-primary"></i> Personal Profile
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('settings.profile') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ $user->phone }}" placeholder="+1 (555) 000-0000">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-base ti tabler-device-floppy me-1"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header border-bottom py-3">
                <h5 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-lock me-1 text-primary"></i> Change Password
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('settings.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="icon-base ti tabler-key me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
