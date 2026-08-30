@extends('layouts.app')

@section('title', 'Organizations & Workspaces')

@section('content')
<div class="pos-glass-card pos-tone-primary mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-building me-1 text-primary"></i> Workspaces &amp; Client Accounts
            </h4>
            <p class="pos-glass-intro-subtitle">
                Manage client workspaces, subscription tiers, and monthly lead discovery quotas.
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex align-items-center gap-2">
            <a href="{{ route('plans.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-base ti tabler-packages me-1"></i> Subscription Plans
            </a>
            <button type="button" class="btn btn-sm btn-primary" id="addTenantBtn">
                <i class="icon-base ti tabler-plus me-1"></i> Add Workspace
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible m-4 mb-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="icon-base ti tabler-circle-check fs-5 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3"><i class="icon-base ti tabler-building me-1 text-primary"></i> Organization</th>
                    <th><i class="icon-base ti tabler-user-shield me-1 text-info"></i> Organization Admin</th>
                    <th><i class="icon-base ti tabler-crown me-1 text-warning"></i> Plan</th>
                    <th><i class="icon-base ti tabler-chart-bar me-1 text-primary"></i> Lead Quota Usage</th>
                    <th><i class="icon-base ti tabler-users-group me-1 text-success"></i> Total Discovered</th>
                    <th><i class="icon-base ti tabler-activity me-1 text-warning"></i> Status</th>
                    <th class="pe-3 text-end"><i class="icon-base ti tabler-settings me-1 text-muted"></i> Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $t)
                    @php
                        $admin = $t->users->first();
                        $staffCount = $t->users_count > 0 ? max(0, $t->users_count - ($admin ? 1 : 0)) : 0;
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold text-heading">{{ $t->name }}</div>
                            <small class="text-muted font-monospace">{{ $t->slug }}</small>
                        </td>
                        <td>
                            @if ($admin)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-xs bg-label-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-heading small">{{ $admin->name }}</div>
                                        <small class="text-muted">{{ $admin->email }}</small>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="icon-base ti tabler-users me-1"></i> {{ $staffCount }}/5 staff members
                                </small>
                            @else
                                <span class="badge bg-label-warning">No Admin Assigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($t->subscriptionPlan)
                                <span class="badge bg-label-primary">
                                    <i class="icon-base ti tabler-crown me-1"></i> {{ $t->subscriptionPlan->name }}
                                </span>
                            @else
                                <span class="badge {{ $t->plan === 'enterprise' ? 'bg-label-primary' : ($t->plan === 'pro' ? 'bg-label-info' : 'bg-label-secondary') }}">
                                    {{ ucfirst($t->plan) }}
                                </span>
                            @endif
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
                            <span class="fw-bold text-heading">{{ number_format($t->leads_count) }}</span>
                        </td>
                        <td>
                            @if ($t->is_active)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-secondary">Suspended</span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary js-edit-tenant" data-id="{{ $t->id }}">
                                <i class="icon-base ti tabler-edit me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No organizations registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tenants->total() > 0)
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">
                        Showing <span class="fw-semibold text-heading">{{ $tenants->firstItem() }}</span> to <span class="fw-semibold text-heading">{{ $tenants->lastItem() }}</span> of <span class="fw-semibold text-heading">{{ number_format($tenants->total()) }}</span> organizations
                    </small>
                    <div class="d-inline-flex align-items-center ms-3">
                        <label for="perPageTenants" class="small text-muted me-1 text-nowrap d-none d-sm-inline">Show:</label>
                        <select id="perPageTenants" class="form-select form-select-sm" style="width: auto;" onchange="window.location.href=this.value">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" @selected($tenants->perPage() == $size)>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    {{ $tenants->links('vendor.pagination.pos') }}
                </div>
            </div>
        </div>
    @endif
</div>

<div class="modal fade" id="tenantFormModal" tabindex="-1" aria-hidden="true" data-allow-outside-close="true">
    <div class="modal-dialog modal-dialog-centered modal-lg text-start">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('tenants.store') }}" id="tenantForm">
                @csrf
                <input type="hidden" name="_method" id="tenantFormMethod" value="POST" disabled>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title d-flex align-items-center" id="tenantFormTitle">
                        <i class="icon-base ti tabler-building-plus text-primary me-2 fs-4"></i> Create New Organization Workspace
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="small fw-bold text-uppercase text-muted mb-2 d-flex align-items-center gap-1">
                        <i class="icon-base ti tabler-building text-primary"></i> 1. Organization Details
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Company / Organization Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-building"></i></span>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Apex Marketing Agency" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Custom Domain (Optional)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-world"></i></span>
                                <input type="text" name="domain" class="form-control" placeholder="e.g. apexmarketing.io">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Subscription Plan <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-crown"></i></span>
                                <select name="plan_id" id="tenantPlanSelect" class="form-select">
                                    @forelse ($plans as $p)
                                        <option value="{{ $p->id }}" data-quota="{{ $p->lead_quota }}" @selected($p->is_default)>
                                            {{ $p->name }} ({{ $p->formatted_price }} — {{ number_format($p->lead_quota) }} leads)
                                        </option>
                                    @empty
                                        <option value="">No plans available</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Monthly Lead Allowance <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-chart-bar"></i></span>
                                <input type="number" id="tenantLeadQuota" name="lead_quota" class="form-control" value="{{ $plans->firstWhere('is_default', true)?->lead_quota ?? 25000 }}" required>
                            </div>
                        </div>
                        <div class="col-12 js-tenant-active-wrap d-none">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="tenantIsActive" value="1">
                                <label class="form-check-label fw-semibold" for="tenantIsActive">Active Organization Account</label>
                            </div>
                        </div>
                    </div>

                    <div id="tenantAdminSection">
                        <div class="small fw-bold text-uppercase text-muted mb-2 d-flex align-items-center gap-1">
                            <i class="icon-base ti tabler-user-shield text-info"></i> 2. Organization Administrator (Initial Admin)
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Admin Full Name</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
                                    <input type="text" name="admin_name" class="form-control" placeholder="e.g. John Doe">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Admin Email Address</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                                    <input type="email" name="admin_email" class="form-control" placeholder="admin@apexmarketing.io">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Admin Initial Password</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                    <input type="password" name="admin_password" class="form-control" placeholder="Min 6 characters">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Admin Phone Number</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base ti tabler-phone"></i></span>
                                    <input type="text" name="admin_phone" class="form-control" placeholder="+1 (555) 000-0000">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="small fw-bold text-uppercase text-muted mb-2 d-flex align-items-center gap-1">
                        <i class="icon-base ti tabler-key text-warning"></i> <span class="js-api-key-step">3.</span> Dedicated API Key (Optional)
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                <input type="text" name="google_maps_api_key" class="form-control" placeholder="AIzaSy...">
                            </div>
                            <small class="text-muted">Leave empty to use the platform default Discovery Engine key.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="tenantFormSubmit">
                        <i class="icon-base ti tabler-plus me-1"></i> Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function ($) {
        var storeUrl = @json(route('tenants.store'));
        var defaultQuota = @json($plans->firstWhere('is_default', true)?->lead_quota ?? 25000);
        var $modal = $('#tenantFormModal');
        var $form = $('#tenantForm');
        var $method = $('#tenantFormMethod');
        var $title = $('#tenantFormTitle');
        var $submit = $('#tenantFormSubmit');
        var $adminSection = $('#tenantAdminSection');
        var $activeWrap = $('.js-tenant-active-wrap');
        var $planSelect = $('#tenantPlanSelect');
        var $quota = $('#tenantLeadQuota');

        function showModal() {
            if ($.fn.modal) {
                $modal.modal('show');
                return;
            }
            bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        }

        function applyPlanQuota() {
            var quota = $planSelect.find('option:selected').data('quota');
            if (quota) {
                $quota.val(quota);
            }
        }

        function resetToCreate() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $method.val('POST').prop('disabled', true);
            $adminSection.show();
            $adminSection.find('input').prop('disabled', false);
            $activeWrap.addClass('d-none');
            $form.find('[name="is_active"]').prop('checked', true);
            $quota.val(defaultQuota);
            applyPlanQuota();
            $('.js-api-key-step').text('3.');
            $title.html('<i class="icon-base ti tabler-building-plus text-primary me-2 fs-4"></i> Create New Organization Workspace');
            $submit.html('<i class="icon-base ti tabler-plus me-1"></i> Add');
        }

        $planSelect.on('change', applyPlanQuota);

        $('#addTenantBtn').on('click', function () {
            resetToCreate();
            showModal();
        });

        $(document).on('click', '.js-edit-tenant', function () {
            var id = $(this).data('id');

            $.ajax({
                url: '/tenants/' + id,
                type: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    $form[0].reset();
                    $form.attr('action', '/tenants/' + response.id);
                    $method.val('PUT').prop('disabled', false);
                    $adminSection.hide();
                    $adminSection.find('input').prop('disabled', true);
                    $activeWrap.removeClass('d-none');
                    $('.js-api-key-step').text('2.');

                    $form.find('[name="name"]').val(response.name);
                    $form.find('[name="domain"]').val(response.domain);
                    if (response.plan_id && !$planSelect.find('option[value="' + response.plan_id + '"]').length) {
                        $planSelect.append($('<option>', {
                            value: response.plan_id,
                            text: response.plan_name || ('Plan #' + response.plan_id)
                        }));
                    }
                    $form.find('[name="plan_id"]').val(response.plan_id);
                    $form.find('[name="lead_quota"]').val(response.lead_quota);
                    $form.find('[name="google_maps_api_key"]').val(response.google_maps_api_key);
                    $form.find('[name="is_active"]').prop('checked', !!response.is_active);

                    $title.html('<i class="icon-base ti tabler-building text-primary me-2"></i> Edit Workspace');
                    $submit.html('<i class="icon-base ti tabler-device-floppy me-1"></i> Update');
                    showModal();
                },
                error: function () {
                    if (window.toastr) {
                        toastr.error('Could not load this workspace.');
                    }
                }
            });
        });
    })(jQuery);
</script>
@endpush
