@extends('layouts.app')

@section('title', 'Workspace Settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/settings.css') }}" />
    <style>
    .team-avatar-initial {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        color: #fff;
        background: linear-gradient(135deg, #7367f0 0%, #a855f7 100%);
        box-shadow: 0 2px 6px rgba(115, 103, 240, 0.3);
        flex-shrink: 0;
    }
    .team-avatar-initial.admin {
        background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
    }
    .team-avatar-initial.super_admin {
        background: linear-gradient(135deg, #ea5455 0%, #ff9f43 100%);
        box-shadow: 0 2px 6px rgba(234, 84, 85, 0.3);
    }
    .staff-quota-badge {
        font-size: 0.72rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
    }
    </style>
@endpush

@section('content')
<div class="row g-4">
    <!-- Left: Settings Tab Sidebar (Matching POS) -->
    <div class="col-12 col-md-4 col-xl-3 settings-tab-sidebar">
        <div class="settings-sidebar pos-glass-card pos-tone-primary">
            <div class="settings-sidebar-label">Search &amp; Outreach Preferences</div>
            <div class="nav flex-column settings-sidebar-nav" id="settings-tab-list" role="tablist">
                <button
                    type="button"
                    class="settings-sidebar-link {{ $activeTab === 'general' ? 'active is-active' : '' }} text-start border-0 bg-transparent w-100"
                    id="general-settings-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#general-tab"
                    role="tab"
                    aria-controls="general-tab"
                    aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                    <span class="settings-sidebar-icon">
                        <i class="icon-base ti tabler-adjustments-horizontal"></i>
                    </span>
                    <span class="settings-text-responsive">General &amp; Limits</span>
                </button>
                <button
                    type="button"
                    class="settings-sidebar-link {{ $activeTab === 'api' ? 'active is-active' : '' }} text-start border-0 bg-transparent w-100"
                    id="api-settings-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#api-tab"
                    role="tab"
                    aria-controls="api-tab"
                    aria-selected="{{ $activeTab === 'api' ? 'true' : 'false' }}">
                    <span class="settings-sidebar-icon">
                        <i class="icon-base ti tabler-key"></i>
                    </span>
                    <span class="settings-text-responsive">Discovery Engine API</span>
                </button>
                <button
                    type="button"
                    class="settings-sidebar-link {{ $activeTab === 'gmail' ? 'active is-active' : '' }} text-start border-0 bg-transparent w-100"
                    id="gmail-settings-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#gmail-tab"
                    role="tab"
                    aria-controls="gmail-tab"
                    aria-selected="{{ $activeTab === 'gmail' ? 'true' : 'false' }}">
                    <span class="settings-sidebar-icon">
                        <i class="icon-base ti tabler-mail"></i>
                    </span>
                    <span class="settings-text-responsive">Gmail Integration</span>
                </button>
                @if ($tenant)
                    <button
                        type="button"
                        class="settings-sidebar-link {{ $activeTab === 'team' ? 'active is-active' : '' }} text-start border-0 bg-transparent w-100 d-flex align-items-center justify-content-between"
                        id="team-settings-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#team-tab"
                        role="tab"
                        aria-controls="team-tab"
                        aria-selected="{{ $activeTab === 'team' ? 'true' : 'false' }}">
                        <span class="d-flex align-items-center">
                            <span class="settings-sidebar-icon">
                                <i class="icon-base ti tabler-users-group"></i>
                            </span>
                            <span class="settings-text-responsive">Team Members</span>
                        </span>
                        <span class="badge {{ $staffCount >= $maxStaff ? 'bg-label-warning' : 'bg-label-primary' }} staff-quota-badge">
                            {{ $staffCount }}/{{ $maxStaff }}
                        </span>
                    </button>
                @endif
            </div>

            @if ($tenant)
                <div class="settings-sidebar-label mt-4">Subscription Plan</div>
                <div class="p-2 text-center rounded bg-label-primary">
                    <span class="fw-bold d-block text-primary">{{ ucfirst($tenant->plan) }} Plan</span>
                    <small class="text-muted d-block mt-1">Allowance: {{ number_format($tenant->leads_extracted_count) }} / {{ number_format($tenant->lead_quota) }}</small>
                </div>
            @endif
        </div>
    </div>

    <!-- Right: Settings Panels (Matching POS) -->
    <div class="col-12 col-md-8 col-xl-9">
        <div class="pos-glass-card pos-tone-primary pos-settings-panel p-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="icon-base ti tabler-circle-check fs-5 me-2"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="icon-base ti tabler-alert-circle fs-5 me-2"></i>
                        <div class="fw-bold">Notice:</div>
                    </div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($tenant)
                <div class="tab-content p-0">
                    <!-- General & Limits Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="general-tab" role="tabpanel" aria-labelledby="general-settings-tab">
                        <form method="POST" action="{{ route('settings.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                                <div>
                                    <h5 class="mb-1 fw-bold text-heading">General &amp; Discovery Limits</h5>
                                    <p class="text-muted small mb-0">Configure your company identity and default search parameters.</p>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="name">Company / Organization Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="default_engine">Default Discovery Engine</label>
                                    <select id="default_engine" name="default_engine" class="form-select">
                                        <option value="google_api" @selected(($settings['default_engine'] ?? 'google_api') === 'google_api')>Cloud Lead Finder (Instant, recommended)</option>
                                        <option value="browser" disabled>Web Business Directory Search (temporarily unavailable)</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="default_limit">Default Lead Target per Search</label>
                                    <select id="default_limit" name="default_limit" class="form-select">
                                        <option value="10" @selected(($settings['default_limit'] ?? 50) == 10)>10 Leads</option>
                                        <option value="25" @selected(($settings['default_limit'] ?? 50) == 25)>25 Leads</option>
                                        <option value="50" @selected(($settings['default_limit'] ?? 50) == 50)>50 Leads</option>
                                        <option value="100" @selected(($settings['default_limit'] ?? 50) == 100)>100 Leads</option>
                                        <option value="200" @selected(($settings['default_limit'] ?? 50) == 200)>200 Leads</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="auto_email_enrichment" id="autoEmailSwitch" value="1" @checked($settings['auto_email_enrichment'] ?? true)>
                                        <label class="form-check-label fw-semibold" for="autoEmailSwitch">
                                            Auto-enrich discovered websites for email addresses
                                        </label>
                                    </div>
                                    <small class="text-muted d-block ms-5">Scrapes domain homepage and contact pages in background to find public email contacts.</small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Discovery Engine API Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'api' ? 'show active' : '' }}" id="api-tab" role="tabpanel" aria-labelledby="api-settings-tab">
                        <form method="POST" action="{{ route('settings.update') }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $tenant->name }}">
                            <input type="hidden" name="default_engine" value="{{ $settings['default_engine'] ?? 'google_api' }}">
                            <input type="hidden" name="default_limit" value="{{ $settings['default_limit'] ?? 50 }}">
                            @if ($settings['auto_email_enrichment'] ?? true)
                                <input type="hidden" name="auto_email_enrichment" value="1">
                            @endif

                            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                                <div>
                                    <h5 class="mb-1 fw-bold text-heading">Discovery Engine Platform Key</h5>
                                    <p class="text-muted small mb-0">Configure your dedicated Platform Key for high-velocity lead discovery.</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="tenantApiKeyInput">Platform Engine API Key</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                    <input type="password" name="google_maps_api_key" id="tenantApiKeyInput" class="form-control" value="{{ old('google_maps_api_key', $tenant->google_maps_api_key) }}" placeholder="AIzaSy...">
                                    <button class="btn btn-outline-secondary" type="button" onclick="const el = document.getElementById('tenantApiKeyInput'); el.type = el.type === 'password' ? 'text' : 'password';">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text small text-muted mt-2">
                                    @if ($hasGlobalGoogleKey)
                                        <span class="text-success fw-medium"><i class="icon-base ti tabler-check"></i> System default Engine key is active.</span> You can leave this blank to use the shared server key, or supply your dedicated workspace key.
                                    @else
                                        Enter your platform engine API key to activate instant lead queries.
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Gmail & Hostinger Email Integration Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'gmail' ? 'show active' : '' }}" id="gmail-tab" role="tabpanel" aria-labelledby="gmail-settings-tab">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <h5 class="mb-1 fw-bold text-heading">
                                    <i class="icon-base ti tabler-mail me-1 text-primary"></i> Hostinger &amp; Gmail Account Hub
                                </h5>
                                <p class="text-muted small mb-0">Connect your Hostinger Business Email or Gmail account to view incoming inquiries and send replies directly.</p>
                            </div>
                        </div>

                        @if ($gmailAccount)
                            <div class="card border border-success-subtle bg-success-subtle bg-opacity-10 p-4 mb-4 rounded-3">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar avatar-md bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold">
                                            <i class="icon-base ti tabler-circle-check fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-bold text-heading fs-6">{{ $gmailAccount->email }}</span>
                                                <span class="badge {{ $gmailAccount->isHostinger() ? 'bg-label-primary' : 'bg-label-danger' }} text-uppercase" style="font-size: 0.68rem;">
                                                    {{ $gmailAccount->isHostinger() ? 'Hostinger (IMAP/SMTP)' : 'Gmail OAuth' }}
                                                </span>
                                            </div>
                                            <div class="small text-secondary">
                                                Connected {{ $gmailAccount->created_at?->diffForHumans() }}
                                                @if ($gmailAccount->last_synced_at)
                                                    • Last synced: {{ $gmailAccount->last_synced_at->diffForHumans() }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('gmail.index') }}" class="btn btn-sm btn-primary">
                                            <i class="icon-base ti tabler-inbox me-1"></i> Open Inbox
                                        </a>
                                        <form action="{{ route('gmail.disconnect', $gmailAccount->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Disconnect this email account?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="icon-base ti tabler-plug-connected-x me-1"></i> Disconnect
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row g-3 mb-4">
                                <!-- Option 1: Hostinger Email -->
                                <div class="col-12 col-lg-7">
                                    <div class="card border h-100 p-4 rounded-3 shadow-xs">
                                        <h6 class="fw-bold text-heading mb-1 d-flex align-items-center">
                                            <i class="icon-base ti tabler-mail-plus text-primary me-2"></i> Connect Hostinger Email (Recommended)
                                        </h6>
                                        <p class="small text-secondary mb-3">
                                            Connect your Hostinger domain email (e.g. <code>info@yourdomain.com</code>) using pre-configured IMAP &amp; SMTP settings.
                                        </p>

                                        <form method="POST" action="{{ route('gmail.connect-hostinger') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Hostinger Email</label>
                                                <input type="email" name="email" class="form-control form-control-sm" placeholder="contact@yourcompany.com" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Email Password</label>
                                                <input type="password" name="password" class="form-control form-control-sm" placeholder="Hostinger email password" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Sender Display Name (Optional)</label>
                                                <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Support / Outreach">
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                                <i class="icon-base ti tabler-plug-connected me-1"></i> Connect &amp; Sync Hostinger Email
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Option 2: Google Gmail -->
                                <div class="col-12 col-lg-5">
                                    <div class="card border h-100 p-4 rounded-3 text-center shadow-xs d-flex flex-column justify-content-center">
                                        <div class="avatar avatar-md bg-label-danger rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center">
                                            <i class="icon-base ti tabler-brand-google fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold mb-1">Google Gmail OAuth</h6>
                                        <p class="text-secondary small mb-3">
                                            Connect via Google Cloud OAuth 2.0.
                                        </p>
                                        <div>
                                            @if ($isGmailConfigured)
                                                <a href="{{ route('gmail.connect') }}" class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="icon-base ti tabler-brand-google me-1"></i> Connect Google Account
                                                </a>
                                            @else
                                                <div class="alert alert-warning py-2 px-2 small mb-0 text-start">
                                                    <i class="icon-base ti tabler-alert-triangle me-1"></i> Add <code>GOOGLE_CLIENT_ID</code> to <code>.env</code> for OAuth.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="card bg-light border-0 p-3 rounded-3">
                            <h6 class="fw-semibold text-heading mb-2">
                                <i class="icon-base ti tabler-info-circle me-1 text-info"></i> Hostinger Email Server Details
                            </h6>
                            <div class="row g-2 small text-secondary">
                                <div class="col-md-6">
                                    <strong>Incoming Server (IMAP):</strong> <code>imap.hostinger.com</code> (Port 993, SSL/TLS)
                                </div>
                                <div class="col-md-6">
                                    <strong>Outgoing Server (SMTP):</strong> <code>smtp.hostinger.com</code> (Port 465, SSL/TLS)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team & Staff Members Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'team' ? 'show active' : '' }}" id="team-tab" role="tabpanel" aria-labelledby="team-settings-tab">
                        <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 mb-3 border-bottom gap-2">
                            <div>
                                <h5 class="mb-1 fw-bold text-heading">
                                    <i class="icon-base ti tabler-users-group me-1 text-primary"></i> Team &amp; Staff Members
                                </h5>
                                <p class="text-muted small mb-0">Manage your workspace administrator and up to {{ $maxStaff }} staff member accounts.</p>
                            </div>
                            <div>
                                @if ($canAddStaff)
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                        <i class="icon-base ti tabler-user-plus me-1"></i> Add Staff Member
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="Maximum 5 staff limit reached">
                                        <i class="icon-base ti tabler-lock me-1"></i> Staff Limit Reached (5/5)
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Staff Quota Info Bar -->
                        <div class="p-3 mb-4 rounded-3 border bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold small text-heading">
                                    <i class="icon-base ti tabler-id-badge-2 me-1 text-info"></i> Staff Allowance: {{ $staffCount }} of {{ $maxStaff }} Slots Used
                                </span>
                                @if ($staffCount >= $maxStaff)
                                    <span class="badge bg-label-warning">Maximum Limit Reached</span>
                                @else
                                    <span class="badge bg-label-success">{{ $maxStaff - $staffCount }} slots available</span>
                                @endif
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar {{ $staffCount >= $maxStaff ? 'bg-warning' : 'bg-primary' }}" style="width: {{ round(($staffCount / max(1, $maxStaff)) * 100) }}%"></div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Each workspace organization can invite up to {{ $maxStaff }} staff members (users) who can discover leads and manage outreach.
                            </small>
                        </div>

                        <!-- Team Members Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3"><i class="icon-base ti tabler-user me-1 text-primary"></i> Team Member</th>
                                        <th><i class="icon-base ti tabler-shield-check me-1 text-info"></i> Access Role</th>
                                        <th><i class="icon-base ti tabler-phone me-1 text-success"></i> Phone</th>
                                        <th><i class="icon-base ti tabler-activity me-1 text-warning"></i> Status</th>
                                        <th class="pe-3 text-end"><i class="icon-base ti tabler-settings me-1 text-muted"></i> Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($teamMembers as $member)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="team-avatar-initial {{ $member->role }}">
                                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-heading small d-flex align-items-center gap-1">
                                                            {{ $member->name }}
                                                            @if ($member->id === auth()->id())
                                                                <span class="badge bg-label-info ms-1" style="font-size: 0.65rem;">You</span>
                                                            @endif
                                                        </div>
                                                        <small class="text-muted d-block">{{ $member->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($member->role === 'admin')
                                                    <span class="badge bg-label-primary">
                                                        <i class="icon-base ti tabler-shield-check me-1"></i> Workspace Admin
                                                    </span>
                                                @elseif ($member->role === 'super_admin')
                                                    <span class="badge bg-label-danger">
                                                        <i class="icon-base ti tabler-shield-lock me-1"></i> Super Admin
                                                    </span>
                                                @else
                                                    <span class="badge bg-label-secondary">
                                                        <i class="icon-base ti tabler-user me-1"></i> Staff Member
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="small text-muted">
                                                {{ $member->phone ?: '—' }}
                                            </td>
                                            <td>
                                                @if ($member->is_active)
                                                    <span class="badge bg-label-success">Active</span>
                                                @else
                                                    <span class="badge bg-label-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-end">
                                                @if ($member->id === auth()->id())
                                                    <a href="{{ route('profile.index') }}" class="btn btn-xs btn-outline-secondary" title="Manage your profile in Profile settings">
                                                        <i class="icon-base ti tabler-user me-1"></i> Profile
                                                    </a>
                                                @else
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStaffModal{{ $member->id }}">
                                                            <i class="icon-base ti tabler-edit me-1"></i> Edit
                                                        </button>
                                                        @if ($member->role === 'user')
                                                            <form method="POST" action="{{ route('users.destroy', $member->id) }}" class="d-inline" onsubmit="return confirm('Remove staff member {{ $member->name }}? This will free up a staff slot.');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Remove Member">
                                                                    <i class="icon-base ti tabler-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>

                                                    <!-- Edit Staff Modal -->
                                                    <div class="modal fade" id="editStaffModal{{ $member->id }}" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered text-start">
                                                            <div class="modal-content border-0 shadow">
                                                                <form method="POST" action="{{ route('users.update', $member->id) }}">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <input type="hidden" name="redirect_to" value="{{ route('settings.index', ['tab' => 'team']) }}">
                                                                    <div class="modal-header border-bottom py-3">
                                                                        <h5 class="modal-title d-flex align-items-center">
                                                                            <i class="icon-base ti tabler-user-edit text-primary me-2"></i> Edit Member: {{ $member->name }}
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body p-4">
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
                                                                                <input type="text" name="name" class="form-control" value="{{ old('name', $member->name) }}" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                                                                                <input type="email" name="email" class="form-control" value="{{ old('email', $member->email) }}" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Contact Phone Number</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span class="input-group-text"><i class="icon-base ti tabler-phone"></i></span>
                                                                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $member->phone) }}" placeholder="+1 (555) 000-0000">
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-semibold">Reset Password (leave blank to keep current)</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                                                                <input type="password" name="password" class="form-control staff-pwd-input" placeholder="New password (min 6 chars)">
                                                                                <button class="btn btn-outline-secondary toggle-staff-pwd" type="button"><i class="icon-base ti tabler-eye"></i></button>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-check form-switch mt-2">
                                                                            <input class="form-check-input" type="checkbox" name="is_active" id="memberActive{{ $member->id }}" value="1" @checked(old('is_active', $member->is_active))>
                                                                            <label class="form-check-label fw-semibold" for="memberActive{{ $member->id }}">Active Account</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer border-top py-3">
                                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-primary">
                                                                            <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No team members registered yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="icon-base ti tabler-shield-check display-5 mb-3 text-primary"></i>
                    <h5 class="fw-semibold">Super Admin Master Mode</h5>
                    <p class="mb-0">Logged in as platform super administrator. Manage client organizations, plan quotas, and assign admins under <a href="{{ route('tenants.index') }}" class="fw-semibold text-primary">Workspaces &amp; Accounts</a>.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@if ($tenant && $canAddStaff)
    <!-- Add Staff Member Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered text-start">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ route('settings.index', ['tab' => 'team']) }}">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title d-flex align-items-center">
                            <i class="icon-base ti tabler-user-plus text-primary me-2"></i> Register New Staff Member
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert bg-label-info border-0 mb-3 py-2 px-3 small">
                            <i class="icon-base ti tabler-info-circle me-1"></i> Staff members have access to search, discover, and export leads within <strong>{{ $tenant->name }}</strong> (Slot {{ $staffCount + 1 }} of {{ $maxStaff }}).
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Staff Member Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Sarah Connor" value="{{ old('name') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Work Email Address <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="sarah@company.com" value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contact Phone Number</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-phone"></i></span>
                                <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="{{ old('phone') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Login Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                <input type="password" name="password" class="form-control staff-pwd-input" placeholder="Create password (min 6 chars)" required>
                                <button class="btn btn-outline-secondary toggle-staff-pwd" type="button"><i class="icon-base ti tabler-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="createStaffActive" value="1" checked>
                            <label class="form-check-label fw-semibold" for="createStaffActive">Active Account</label>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-base ti tabler-user-plus me-1"></i> Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabButtons = document.querySelectorAll('#settings-tab-list button[data-bs-toggle="tab"]');
        tabButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                tabButtons.forEach(btn => {
                    btn.classList.remove('is-active', 'active');
                    btn.setAttribute('aria-selected', 'false');
                });
                this.classList.add('is-active', 'active');
                this.setAttribute('aria-selected', 'true');

                const targetSelector = this.getAttribute('data-bs-target');
                document.querySelectorAll('.tab-content .tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                const targetPane = document.querySelector(targetSelector);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }
            });
        });

        // Toggle password inputs
        document.querySelectorAll('.toggle-staff-pwd').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var group = this.closest('.input-group');
                var input = group.querySelector('.staff-pwd-input');
                var icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'icon-base ti tabler-eye-off';
                } else {
                    input.type = 'password';
                    icon.className = 'icon-base ti tabler-eye';
                }
            });
        });
    });
</script>
@endpush
