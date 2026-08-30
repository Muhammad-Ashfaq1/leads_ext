@extends('layouts.app')

@section('title', 'Team & User Management')

@push('styles')
<style>
.user-avatar-initial {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: #fff;
    background: linear-gradient(135deg, #7367f0 0%, #a855f7 100%);
    box-shadow: 0 3px 8px rgba(115, 103, 240, 0.35);
    flex-shrink: 0;
}
.user-avatar-initial.admin {
    background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
    box-shadow: 0 3px 8px rgba(2, 132, 199, 0.35);
}
.user-avatar-initial.super_admin {
    background: linear-gradient(135deg, #ea5455 0%, #ff9f43 100%);
    box-shadow: 0 3px 8px rgba(234, 84, 85, 0.35);
}
.role-badge-pill {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35rem 0.65rem;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}
.modal-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #8592a3;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
</style>
@endpush

@section('content')
<div class="pos-glass-card pos-tone-primary mb-4">
    <!-- Header Hero Banner -->
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-user-cog me-1 text-primary"></i> Team &amp; User Accounts
            </h4>
            <p class="pos-glass-intro-subtitle">
                @if ($isSuperAdmin)
                    Platform Administrator Panel · Register and manage global admins, workspace managers, and user credentials.
                @else
                    Workspace Team Management · Manage user permissions, team members, and discovery privileges.
                @endif
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex flex-wrap align-items-center gap-2">
            <span class="pos-glass-pill pos-tone-primary">
                <i class="icon-base ti tabler-users me-1"></i> {{ number_format($stats['total']) }} Accounts
            </span>
            <span class="pos-glass-pill pos-tone-info">
                <i class="icon-base ti tabler-shield-check me-1"></i> {{ number_format($stats['admins']) }} Admins
            </span>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="icon-base ti tabler-user-plus me-1"></i> Register New User
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible m-3 mb-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="icon-base ti tabler-circle-check fs-5 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible m-3 mb-0" role="alert">
            <div class="d-flex align-items-center mb-1">
                <i class="icon-base ti tabler-alert-circle fs-5 me-2"></i>
                <div class="fw-bold">Please correct the following errors:</div>
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters & Search Toolbar -->
    <div class="card-body border-bottom bg-light-subtle py-3 px-3 px-md-4">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-center" id="userFilterForm">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="form-control form-control-sm"
                        placeholder="Search name, email, phone..."
                        autocomplete="off">
                </div>
            </div>

            <div class="col-6 col-sm-4 col-md-2">
                <select name="role" class="form-select form-select-sm" onchange="document.getElementById('userFilterForm').submit()">
                    <option value="">Role: All Roles</option>
                    @if ($isSuperAdmin)
                        <option value="super_admin" @selected($filters['role'] === 'super_admin')>Super Admin</option>
                    @endif
                    <option value="admin" @selected($filters['role'] === 'admin')>Workspace Admin</option>
                    <option value="user" @selected($filters['role'] === 'user')>Team Member</option>
                </select>
            </div>

            @if ($isSuperAdmin)
                <div class="col-6 col-sm-4 col-md-3">
                    <select name="tenant_id" class="form-select form-select-sm" onchange="document.getElementById('userFilterForm').submit()">
                        <option value="">Organization: All</option>
                        <option value="global" @selected($filters['tenant_id'] === 'global')>Global Super Admins</option>
                        @foreach ($tenants as $t)
                            <option value="{{ $t->id }}" @selected($filters['tenant_id'] == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-6 col-sm-4 col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="document.getElementById('userFilterForm').submit()">
                    <option value="">Status: All</option>
                    <option value="active" @selected($filters['status'] === 'active')>Active Only</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive Only</option>
                </select>
            </div>

            <div class="col-6 col-sm-4 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="icon-base ti tabler-filter me-1"></i> Filter
                </button>
                @if ($filters['search'] || $filters['role'] || $filters['status'] || $filters['tenant_id'])
                    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                        <i class="icon-base ti tabler-x"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3"><i class="icon-base ti tabler-user me-1 text-primary"></i> User Identity</th>
                    @if ($isSuperAdmin)
                        <th><i class="icon-base ti tabler-building me-1 text-danger"></i> Organization</th>
                    @endif
                    <th><i class="icon-base ti tabler-shield-check me-1 text-info"></i> Access Role</th>
                    <th><i class="icon-base ti tabler-phone me-1 text-success"></i> Phone Number</th>
                    <th><i class="icon-base ti tabler-activity me-1 text-warning"></i> Status</th>
                    <th class="pe-3 text-end"><i class="icon-base ti tabler-settings me-1 text-muted"></i> Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar-initial {{ $u->role }}">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-heading small d-flex align-items-center gap-1">
                                        {{ $u->name }}
                                        @if ($u->id === auth()->id())
                                            <span class="badge bg-label-info ms-1" style="font-size: 0.65rem;">You</span>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block">{{ $u->email }}</small>
                                </div>
                            </div>
                        </td>
                        @if ($isSuperAdmin)
                            <td>
                                @if ($u->tenant)
                                    <span class="badge bg-label-primary">
                                        <i class="icon-base ti tabler-building me-1"></i> {{ $u->tenant->name }}
                                    </span>
                                @else
                                    <span class="badge bg-label-danger">
                                        <i class="icon-base ti tabler-shield-check me-1"></i> Global Platform
                                    </span>
                                @endif
                            </td>
                        @endif
                        <td>
                            @if ($u->role === 'super_admin')
                                <span class="badge bg-label-danger role-badge-pill">
                                    <i class="icon-base ti tabler-shield-lock"></i> Super Admin
                                </span>
                            @elseif ($u->role === 'admin')
                                <span class="badge bg-label-primary role-badge-pill">
                                    <i class="icon-base ti tabler-shield-check"></i> Workspace Admin
                                </span>
                            @else
                                <span class="badge bg-label-secondary role-badge-pill">
                                    <i class="icon-base ti tabler-user"></i> Team Member
                                </span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            @if ($u->phone)
                                <span><i class="icon-base ti tabler-phone text-muted me-1"></i>{{ $u->phone }}</span>
                            @else
                                <span class="text-muted opacity-50">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($u->is_active)
                                <span class="badge bg-label-success">
                                    <i class="icon-base ti tabler-circle-check me-1"></i> Active
                                </span>
                            @else
                                <span class="badge bg-label-secondary">
                                    <i class="icon-base ti tabler-circle-x me-1"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                <i class="icon-base ti tabler-edit me-1"></i> Edit
                            </button>

                            <!-- Edit Modal (POS Styled) -->
                            <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg text-start">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="{{ route('users.update', $u->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header border-bottom py-3">
                                                <h5 class="modal-title d-flex align-items-center">
                                                    <i class="icon-base ti tabler-user-edit text-primary me-2 fs-4"></i> Edit User: {{ $u->name }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <!-- Section 1: Identity Details -->
                                                <div class="modal-section-title">
                                                    <i class="icon-base ti tabler-id"></i> 1. Personal &amp; Contact Details
                                                </div>
                                                <div class="row g-3 mb-4">
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
                                                            <input type="text" name="name" class="form-control" value="{{ old('name', $u->name) }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                                                            <input type="email" name="email" class="form-control" value="{{ old('email', $u->email) }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label fw-semibold">Phone Number</label>
                                                        <div class="input-group input-group-merge">
                                                            <span class="input-group-text"><i class="icon-base ti tabler-phone"></i></span>
                                                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $u->phone) }}" placeholder="+1 (555) 000-0000">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Section 2: Organization & Role -->
                                                <div class="modal-section-title">
                                                    <i class="icon-base ti tabler-shield-check"></i> 2. Organization &amp; Role Permissions
                                                </div>
                                                <div class="row g-3 mb-4">
                                                    @if ($isSuperAdmin)
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label fw-semibold">Assigned Organization</label>
                                                            <div class="input-group input-group-merge">
                                                                <span class="input-group-text"><i class="icon-base ti tabler-building"></i></span>
                                                                <select name="tenant_id" class="form-select">
                                                                    <option value="">Global Super Admin (No Workspace)</option>
                                                                    @foreach ($tenants as $t)
                                                                        <option value="{{ $t->id }}" @selected(old('tenant_id', $u->tenant_id) == $t->id)>
                                                                            {{ $t->name }} ({{ ucfirst($t->plan) }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="col-12 {{ $isSuperAdmin ? 'col-md-6' : 'col-md-12' }}">
                                                        <label class="form-label fw-semibold">Account Role <span class="text-danger">*</span></label>
                                                        <div class="input-group input-group-merge">
                                                            <span class="input-group-text"><i class="icon-base ti tabler-shield"></i></span>
                                                            <select name="role" class="form-select" required>
                                                                @if ($isSuperAdmin)
                                                                    <option value="super_admin" @selected(old('role', $u->role) === 'super_admin')>Super Admin (Full Platform Access)</option>
                                                                @endif
                                                                <option value="admin" @selected(old('role', $u->role) === 'admin')>Workspace Admin (Manage Leads, Team & Settings)</option>
                                                                <option value="user" @selected(old('role', $u->role) === 'user')>Team Member (Search & Extract Leads)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Section 3: Security & Credentials -->
                                                <div class="modal-section-title">
                                                    <i class="icon-base ti tabler-lock"></i> 3. Security &amp; Credentials
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-12 col-md-8">
                                                        <label class="form-label fw-semibold">Reset Password (leave blank to keep current)</label>
                                                        <div class="input-group input-group-merge">
                                                            <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                                            <input type="password" name="password" class="form-control user-pwd-input" placeholder="Enter new password (min 6 chars)">
                                                            <button class="btn btn-outline-secondary toggle-user-pwd" type="button"><i class="icon-base ti tabler-eye"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-4 d-flex align-items-center">
                                                        <div class="form-check form-switch mt-3">
                                                            <input class="form-check-input" type="checkbox" name="is_active" id="userActive{{ $u->id }}" value="1" @checked(old('is_active', $u->is_active))>
                                                            <label class="form-check-label fw-semibold" for="userActive{{ $u->id }}">Account Active</label>
                                                        </div>
                                                    </div>
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 6 : 5 }}" class="text-center py-5 text-muted">
                            <i class="icon-base ti tabler-users-minus fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <div class="fw-semibold">No user accounts found matching your filters.</div>
                            <small class="text-muted">Try adjusting your search criteria or register a new user.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    @if ($users->total() > 0)
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">
                        Showing <span class="fw-semibold text-heading">{{ $users->firstItem() }}</span> to <span class="fw-semibold text-heading">{{ $users->lastItem() }}</span> of <span class="fw-semibold text-heading">{{ number_format($users->total()) }}</span> accounts
                    </small>
                    <div class="d-inline-flex align-items-center ms-3">
                        <label for="perPageUsers" class="small text-muted me-1 text-nowrap d-none d-sm-inline">Show:</label>
                        <select id="perPageUsers" class="form-select form-select-sm" style="width: auto;" onchange="window.location.href=this.value">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" @selected($users->perPage() == $size)>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    {{ $users->links('vendor.pagination.pos') }}
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Create / Register User Modal (Matching POS) -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="icon-base ti tabler-user-plus text-primary me-2 fs-4"></i> Register New User / Administrator
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Section 1: Basic Information -->
                    <div class="modal-section-title">
                        <i class="icon-base ti tabler-id"></i> 1. Basic &amp; Contact Details
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Alexander Vance" value="{{ old('name') }}" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Work Email Address <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="alexander@company.com" value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Contact Phone Number</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-phone"></i></span>
                                <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="{{ old('phone') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Organization & Role Permissions -->
                    <div class="modal-section-title">
                        <i class="icon-base ti tabler-shield-check"></i> 2. Organization &amp; Role Permissions
                    </div>
                    <div class="row g-3 mb-4">
                        @if ($isSuperAdmin)
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Assign to Organization <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-building"></i></span>
                                    <select name="tenant_id" class="form-select" id="createTenantSelect">
                                        <option value="">Global Platform (Super Admin Level)</option>
                                        @foreach ($tenants as $t)
                                            <option value="{{ $t->id }}" @selected(old('tenant_id') == $t->id)>
                                                {{ $t->name }} ({{ ucfirst($t->plan) }} Plan)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <small class="text-muted">Choose Global Platform for root administrators or select a client workspace.</small>
                            </div>
                        @endif
                        <div class="col-12 {{ $isSuperAdmin ? 'col-md-6' : 'col-md-12' }}">
                            <label class="form-label fw-semibold">Account Role <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-shield"></i></span>
                                <select name="role" class="form-select" id="createRoleSelect" required>
                                    @if ($isSuperAdmin)
                                        <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin (Full Platform Control)</option>
                                    @endif
                                    <option value="admin" @selected(old('role') === 'admin')>Workspace Admin (Manage Organization &amp; Team)</option>
                                    <option value="user" @selected(old('role', 'user') === 'user')>Team Member (Search &amp; Export Leads)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Credentials & Security -->
                    <div class="modal-section-title">
                        <i class="icon-base ti tabler-lock"></i> 3. Security &amp; Credentials
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                <input type="password" name="password" class="form-control user-pwd-input" placeholder="Enter secure password (min 6 chars)" required>
                                <button class="btn btn-outline-secondary toggle-user-pwd" type="button"><i class="icon-base ti tabler-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-center">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="createIsActive" value="1" checked>
                                <label class="form-check-label fw-semibold" for="createIsActive">Active Account</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-base ti tabler-user-plus me-1"></i> Register Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle handlers
    document.querySelectorAll('.toggle-user-pwd').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var group = this.closest('.input-group');
            var input = group.querySelector('.user-pwd-input');
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
