@extends('layouts.app')

@section('title', 'Profile & Account Settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pos-glass.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/account-settings.css') }}" />
@endpush

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-10 mx-auto">
        <div class="account-settings-card pos-glass-card pos-tone-secondary pos-settings-panel" id="account-settings" data-active="profile">
            <!-- Tabs (Matching POS) -->
            <ul class="account-settings-tabs" role="tablist">
                <li>
                    <a href="javascript:void(0);" data-account-tab="profile" class="active">
                        <i class="icon-base ti tabler-user me-1"></i> Profile
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" data-account-tab="password" class="">
                        <i class="icon-base ti tabler-lock me-1"></i> Change Password
                    </a>
                </li>
            </ul>

            <!-- Profile Panel (Matching POS) -->
            <div class="account-settings-panel" data-panel="profile">
                <form method="POST" action="{{ route('profile.update') }}" class="account-settings-form" id="account-profile-form">
                    @csrf
                    @method('PUT')

                    <div class="account-settings-header">
                        <div class="account-settings-avatar" id="account-avatar-preview">
                            <span id="account-avatar-initial">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        <div class="account-settings-header-text">
                            <h4 class="account-settings-title">{{ $user->name }}</h4>
                            <p class="account-settings-subtitle">
                                {{ $user->getRoleLabel() }}
                                @if ($user->tenant)
                                    · {{ $user->tenant->name }} ({{ ucfirst($user->tenant->plan) }} Plan)
                                @else
                                    · Global Platform Owner
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row g-4 account-settings-fields">
                        <div class="col-md-6">
                            <label class="form-label" for="name">
                                Full Name <span class="required-mark">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $user->name) }}"
                                required
                                maxlength="75"
                                autofocus>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">
                                Email Address <span class="required-mark">*</span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}"
                                required>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $user->phone) }}"
                                placeholder="Enter phone number"
                                maxlength="30">
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="role_display">Assigned Role</label>
                            <input type="text" id="role_display" class="form-control" value="{{ $user->getRoleLabel() }}" disabled>
                        </div>
                    </div>

                    <div class="account-settings-actions">
                        <button type="submit" class="btn btn-primary account-settings-save-btn">
                            <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Panel (Matching POS) -->
            <div class="account-settings-panel" data-panel="password" hidden>
                <h4 class="account-settings-password-title">Change Password</h4>
                <p class="text-muted small mb-4">Ensure your account is using a long, random password to stay secure.</p>

                <form method="POST" action="{{ route('profile.password') }}" class="account-settings-form" id="account-password-form">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">
                        <div class="col-md-4 form-password-toggle">
                            <label class="form-label" for="current_password">
                                Current Password <span class="required-mark">*</span>
                            </label>
                            <div class="input-group input-group-merge @error('current_password') is-invalid @enderror">
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    placeholder="Enter current password"
                                    required
                                    autocomplete="current-password">
                                <span class="input-group-text cursor-pointer toggle-pwd"><i class="icon-base ti tabler-eye-off"></i></span>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 form-password-toggle">
                            <label class="form-label" for="password">
                                New Password <span class="required-mark">*</span>
                            </label>
                            <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Enter new password"
                                    required
                                    minlength="8"
                                    autocomplete="new-password">
                                <span class="input-group-text cursor-pointer toggle-pwd"><i class="icon-base ti tabler-eye-off"></i></span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 form-password-toggle">
                            <label class="form-label" for="password_confirmation">
                                Confirm New Password <span class="required-mark">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    class="form-control"
                                    placeholder="Confirm new password"
                                    required
                                    minlength="8"
                                    autocomplete="new-password">
                                <span class="input-group-text cursor-pointer toggle-pwd"><i class="icon-base ti tabler-eye-off"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="account-settings-actions">
                        <button type="submit" class="btn btn-primary account-settings-save-btn">
                            <i class="icon-base ti tabler-key me-1"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('account-settings');
    if (!root) return;

    const tabs = root.querySelectorAll('[data-account-tab]');
    const panels = root.querySelectorAll('[data-panel]');

    function switchTab(tab) {
        tabs.forEach(el => {
            el.classList.toggle('active', el.dataset.accountTab === tab);
        });

        panels.forEach(panel => {
            panel.hidden = panel.dataset.panel !== tab;
        });

        root.dataset.active = tab;
    }

    tabs.forEach(tabLink => {
        tabLink.addEventListener('click', function (e) {
            e.preventDefault();
            switchTab(this.dataset.accountTab);
        });
    });

    // Check if URL hash specifies #password
    if (window.location.hash === '#password') {
        switchTab('password');
    }

    // Password show/hide toggle buttons
    document.querySelectorAll('.toggle-pwd').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('tabler-eye-off');
                icon.classList.add('tabler-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('tabler-eye');
                icon.classList.add('tabler-eye-off');
            }
        });
    });
});
</script>
@endpush
