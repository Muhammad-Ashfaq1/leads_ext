@extends('layouts.app')

@section('title', 'Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/account-settings.css') }}" />
@endpush

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-9 mx-auto">
        <div class="account-settings-card pos-glass-card pos-tone-primary" id="account-settings">
            <!-- Tabs (Matching POS) -->
            <ul class="account-settings-tabs nav nav-tabs border-0" role="tablist">
                <li class="nav-item">
                    <a href="#profile-tab" class="active" data-bs-toggle="tab" role="tab">Profile</a>
                </li>
                <li class="nav-item">
                    <a href="#password-tab" class="" data-bs-toggle="tab" role="tab">Change Password</a>
                </li>
            </ul>

            <div class="tab-content p-0">
                <!-- Profile Panel -->
                <div class="tab-pane fade show active" id="profile-tab" role="tabpanel">
                    <div class="account-settings-header">
                        <div class="account-settings-avatar">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="account-settings-header-text">
                            <h4 class="account-settings-title">{{ $user->name }}</h4>
                            <p class="account-settings-subtitle">
                                {{ $user->getRoleLabel() }}
                                @if ($user->tenant)
                                    · {{ $user->tenant->name }} ({{ ucfirst($user->tenant->plan) }})
                                @endif
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="name">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="email">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 000-0000">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Role</label>
                                <input type="text" class="form-control" value="{{ $user->getRoleLabel() }}" disabled>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Panel -->
                <div class="tab-pane fade" id="password-tab" role="tabpanel">
                    <div class="account-settings-header">
                        <div class="account-settings-avatar bg-label-warning text-warning">
                            <i class="icon-base ti tabler-lock"></i>
                        </div>
                        <div class="account-settings-header-text">
                            <h4 class="account-settings-title">Security &amp; Password</h4>
                            <p class="account-settings-subtitle">Ensure your account is using a long, random password to stay secure.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="current_password">
                                    Current Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="••••••••" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="password">
                                    New Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="password_confirmation">
                                    Confirm Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="icon-base ti tabler-key me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
