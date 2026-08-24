@extends('layouts.app')

@section('title', 'SaaS Tenants & Clients')

@section('content')
<div class="pos-glass-card pos-tone-danger mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-building me-1 text-danger"></i> SaaS Tenants &amp; Organizations
            </h4>
            <p class="pos-glass-intro-subtitle">
                Manage multi-tenant client accounts, plan tiers, and lead extraction quotas.
            </p>
        </div>
        <div class="pos-glass-intro-actions">
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#createTenantModal">
                <i class="icon-base ti tabler-plus me-1"></i> Add Tenant
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Organization</th>
                    <th>Plan</th>
                    <th>Lead Quota Usage</th>
                    <th>Team</th>
                    <th>Leads Extracted</th>
                    <th>Status</th>
                    <th class="pe-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $t)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold text-heading">{{ $t->name }}</div>
                            <small class="text-muted font-monospace">{{ $t->slug }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $t->plan === 'enterprise' ? 'bg-label-primary' : ($t->plan === 'pro' ? 'bg-label-info' : 'bg-label-secondary') }}">
                                {{ ucfirst($t->plan) }}
                            </span>
                        </td>
                        <td>
                            <div class="small fw-semibold text-heading mb-1">
                                {{ number_format($t->leads_extracted_count) }} / {{ number_format($t->lead_quota) }}
                            </div>
                            <div class="progress" style="height: 5px; width: 140px;">
                                <div class="progress-bar bg-primary" style="width: {{ min(100, round(($t->leads_extracted_count / max(1, $t->lead_quota)) * 100)) }}%"></div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-label-secondary">{{ $t->users_count }} users</span>
                        </td>
                        <td>
                            <span class="fw-bold text-heading">{{ number_format($t->leads_count) }}</span>
                        </td>
                        <td>
                            @if ($t->is_active)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-danger">Suspended</span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTenantModal{{ $t->id }}">
                                <i class="icon-base ti tabler-edit me-1"></i> Edit
                            </button>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editTenantModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('tenants.update', $t->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Tenant: {{ $t->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Tenant Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $t->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Plan Tier</label>
                                                    <select name="plan" class="form-select">
                                                        <option value="starter" @selected($t->plan === 'starter')>Starter</option>
                                                        <option value="pro" @selected($t->plan === 'pro')>Pro</option>
                                                        <option value="enterprise" @selected($t->plan === 'enterprise')>Enterprise</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Lead Quota</label>
                                                    <input type="number" name="lead_quota" class="form-control" value="{{ $t->lead_quota }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Tenant-Specific Google Maps API Key</label>
                                                    <input type="text" name="google_maps_api_key" class="form-control" value="{{ $t->google_maps_api_key }}" placeholder="Leave blank to use platform default">
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive{{ $t->id }}" value="1" @checked($t->is_active)>
                                                    <label class="form-check-label" for="isActive{{ $t->id }}">Active Subscription</label>
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
                        <td colspan="7" class="text-center py-4 text-muted">No tenants registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tenants->hasPages())
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Showing {{ $tenants->firstItem() }} to {{ $tenants->lastItem() }} of {{ number_format($tenants->total()) }} tenants</small>
                <div>{{ $tenants->links('vendor.pagination.pos') }}</div>
            </div>
        </div>
    @endif
</div>

<!-- Create Tenant Modal -->
<div class="modal fade" id="createTenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('tenants.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New SaaS Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company / Organization Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Apex Marketing LLC" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plan Tier</label>
                        <select name="plan" class="form-select">
                            <option value="starter">Starter (10,000 leads)</option>
                            <option value="pro" selected>Pro (25,000 leads)</option>
                            <option value="enterprise">Enterprise (50,000+ leads)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lead Quota</label>
                        <input type="number" name="lead_quota" class="form-control" value="25000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tenant Google Maps API Key (Optional)</label>
                        <input type="text" name="google_maps_api_key" class="form-control" placeholder="AIzaSy...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
