@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row g-4">
    <!-- User Overview Card -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="card-body">
                <div class="user-avatar-badge mx-auto mb-3" style="width: 4.5rem; height: 4.5rem; font-size: 1.75rem;">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <h5 class="mb-1 fw-bold text-heading">{{ $user->name }}</h5>
                <p class="text-muted small mb-2">{{ $user->email }}</p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge {{ $user->getRoleBadgeClass() }} px-3 py-1">
                        {{ $user->getRoleLabel() }}
                    </span>
                    @if ($user->tenant)
                        <span class="badge bg-label-primary px-3 py-1">
                            {{ $user->tenant->name }}
                        </span>
                    @endif
                </div>

                <div class="border-top pt-3 text-start small">
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Account Status:</span>
                        <span class="text-success fw-semibold">Active</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Joined:</span>
                        <span>{{ $user->created_at?->format('M d, Y') ?? '—' }}</span>
                    </div>
                    @if ($user->tenant)
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Plan:</span>
                            <span class="fw-semibold text-primary">{{ ucfirst($user->tenant->plan) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile & Security Forms -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-bottom py-3">
                <h6 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-user me-1 text-primary"></i> Personal Information
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 000-0000">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-bottom py-3">
                <h6 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-lock me-1 text-primary"></i> Change Password
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="icon-base ti tabler-key me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

