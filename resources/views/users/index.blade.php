@extends('layouts.app')

@section('title', 'Team Members')

@section('content')
<div class="pos-glass-card pos-tone-primary mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-user-cog me-1 text-primary"></i> Team &amp; User Accounts
            </h4>
            <p class="pos-glass-intro-subtitle">
                Manage access permissions, team roles, and lead extraction access.
            </p>
        </div>
        <div class="pos-glass-intro-actions">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="icon-base ti tabler-plus me-1"></i> Add Member
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">User</th>
                    @if ($isSuperAdmin)
                        <th>Organization</th>
                    @endif
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th class="pe-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar-badge" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-heading small">{{ $u->name }}</div>
                                    <small class="text-muted">{{ $u->email }}</small>
                                </div>
                            </div>
                        </td>
                        @if ($isSuperAdmin)
                            <td>
                                @if ($u->tenant)
                                    <span class="badge bg-label-primary">{{ $u->tenant->name }}</span>
                                @else
                                    <span class="badge bg-label-danger">Global Platform</span>
                                @endif
                            </td>
                        @endif
                        <td>
                            <span class="badge {{ $u->getRoleBadgeClass() }}">
                                {{ $u->getRoleLabel() }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $u->phone ?: '—' }}</td>
                        <td>
                            @if ($u->is_active)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                <i class="icon-base ti tabler-edit me-1"></i> Edit
                            </button>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('users.update', $u->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit User: {{ $u->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email Address</label>
                                                    <input type="email" name="email" class="form-control" value="{{ $u->email }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Phone</label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $u->phone }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Role</label>
                                                    <select name="role" class="form-select">
                                                        @if ($isSuperAdmin)
                                                            <option value="super_admin" @selected($u->role === 'super_admin')>Super Admin</option>
                                                        @endif
                                                        <option value="admin" @selected($u->role === 'admin')>Tenant Admin</option>
                                                        <option value="user" @selected($u->role === 'user')>Team Member</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Change Password (leave blank to keep current)</label>
                                                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" id="userActive{{ $u->id }}" value="1" @checked($u->is_active)>
                                                    <label class="form-check-label" for="userActive{{ $u->id }}">Account Active</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center py-4 text-muted">
                            No team members found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ number_format($users->total()) }} members</small>
                <div>{{ $users->links('vendor.pagination.pos') }}</div>
            </div>
        </div>
    @endif
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Team Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($isSuperAdmin)
                        <div class="mb-3">
                            <label class="form-label">Assign to Tenant</label>
                            <select name="tenant_id" class="form-select">
                                <option value="">Global Super Admin (No Tenant)</option>
                                @foreach ($tenants as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Jane Smith" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="jane@company.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            @if ($isSuperAdmin)
                                <option value="super_admin">Super Admin</option>
                            @endif
                            <option value="admin">Tenant Admin</option>
                            <option value="user" selected>Team Member</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone (Optional)</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
