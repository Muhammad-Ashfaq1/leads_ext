@extends('layouts.app')

@section('title', 'Subscription Plans')

@section('content')
<div class="pos-glass-card pos-tone-primary mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-packages me-1 text-primary"></i> Subscription &amp; Pricing Plans
            </h4>
            <p class="pos-glass-intro-subtitle">
                Configure client subscription packages, monthly lead discovery allowances, and organization staff quotas.
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex align-items-center gap-2">
            <a href="{{ route('tenants.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-base ti tabler-building me-1"></i> View Workspaces
            </a>
            <button type="button" class="btn btn-sm btn-primary" id="addPlanBtn">
                <i class="icon-base ti tabler-plus me-1"></i> Create New Plan
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible m-4 mb-0" role="alert">
            <div class="d-flex align-items-center mb-1">
                <i class="icon-base ti tabler-alert-circle fs-5 me-2"></i>
                <div class="fw-bold">Validation Error:</div>
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Plan Stat Metrics -->
    <div class="row g-3 p-4 border-bottom bg-light-subtle">
        <div class="col-12 col-md-4">
            <div class="p-3 rounded border bg-card text-start d-flex align-items-center gap-3">
                <div class="avatar avatar-md bg-label-primary rounded d-flex align-items-center justify-content-center">
                    <i class="icon-base ti tabler-packages fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Plan Tiers</div>
                    <div class="fs-4 fw-bold text-heading">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 rounded border bg-card text-start d-flex align-items-center gap-3">
                <div class="avatar avatar-md bg-label-success rounded d-flex align-items-center justify-content-center">
                    <i class="icon-base ti tabler-check fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Plans</div>
                    <div class="fs-4 fw-bold text-heading">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 rounded border bg-card text-start d-flex align-items-center gap-3">
                <div class="avatar avatar-md bg-label-info rounded d-flex align-items-center justify-content-center">
                    <i class="icon-base ti tabler-building fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small">Subscribed Workspaces</div>
                    <div class="fs-4 fw-bold text-heading">{{ $stats['total_subscribers'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plans Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3"><i class="icon-base ti tabler-crown me-1 text-primary"></i> Plan Name</th>
                    <th><i class="icon-base ti tabler-currency-dollar me-1 text-success"></i> Pricing</th>
                    <th><i class="icon-base ti tabler-chart-bar me-1 text-info"></i> Lead Allowance</th>
                    <th><i class="icon-base ti tabler-users me-1 text-secondary"></i> Staff Capacity</th>
                    <th><i class="icon-base ti tabler-building me-1 text-warning"></i> Active Workspaces</th>
                    <th><i class="icon-base ti tabler-activity me-1 text-primary"></i> Status</th>
                    <th class="pe-3 text-end"><i class="icon-base ti tabler-settings me-1 text-muted"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $p)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="fw-bold text-heading">{{ $p->name }}</div>
                                @if ($p->is_default)
                                    <span class="badge bg-label-primary font-monospace" style="font-size: 0.68rem;">Default Tier</span>
                                @endif
                            </div>
                            @if ($p->description)
                                <small class="text-muted d-block text-truncate" style="max-width: 280px;">{{ $p->description }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-success">{{ $p->formatted_price }}</div>
                            <small class="text-muted">{{ ucfirst($p->billing_interval) }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-heading">{{ number_format($p->lead_quota) }}</div>
                            <small class="text-muted">leads / month</small>
                        </td>
                        <td>
                            <span class="badge bg-label-secondary">
                                <i class="icon-base ti tabler-users me-1"></i> Up to {{ $p->max_staff_members }} Staff
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-label-info">{{ $p->tenants_count }} workspaces</span>
                        </td>
                        <td>
                            @if ($p->is_active)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary js-edit-plan" data-id="{{ $p->id }}">
                                    <i class="icon-base ti tabler-edit me-1"></i> Edit
                                </button>
                                <form method="POST" action="{{ route('plans.destroy', $p->id) }}" class="d-inline" onsubmit="return confirm('Delete or deactivate plan {{ $p->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Plan">
                                        <i class="icon-base ti tabler-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No subscription plans created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="planFormModal" tabindex="-1" aria-hidden="true" data-allow-outside-close="true">
    <div class="modal-dialog modal-dialog-centered modal-lg text-start">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('plans.store') }}" id="planForm">
                @csrf
                <input type="hidden" name="_method" id="planFormMethod" value="POST" disabled>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title d-flex align-items-center" id="planFormTitle">
                        <i class="icon-base ti tabler-packages text-primary me-2 fs-4"></i> Create Subscription Plan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Plan Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-crown"></i></span>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Growth Plan" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Price ($) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-currency-dollar"></i></span>
                                <input type="number" step="0.01" name="price" class="form-control" placeholder="49.00" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Billing Interval</label>
                            <select name="billing_interval" class="form-select">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Monthly Lead Discovery Allowance <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-chart-bar"></i></span>
                                <input type="number" name="lead_quota" class="form-control" placeholder="15000" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Max Staff Members Allowed <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base ti tabler-users"></i></span>
                                <input type="number" name="max_staff_members" class="form-control" placeholder="5" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Short Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Designed for mid-market sales teams">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Feature Highlights (one feature per line)</label>
                            <textarea name="features" class="form-control" rows="3" placeholder="15,000 Verified Leads Monthly&#10;5 Staff Accounts&#10;Unlimited Cloud Searches&#10;CSV &amp; Excel Direct Export"></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="planIsActive" value="1">
                                <label class="form-check-label fw-semibold" for="planIsActive">Active Plan Tier</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_default" id="planIsDefault" value="1">
                                <label class="form-check-label fw-semibold" for="planIsDefault">Set as Default Selection</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="planFormSubmit">
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
        var storeUrl = @json(route('plans.store'));
        var $modal = $('#planFormModal');
        var $form = $('#planForm');
        var $method = $('#planFormMethod');
        var $title = $('#planFormTitle');
        var $submit = $('#planFormSubmit');

        function showModal() {
            if ($.fn.modal) {
                $modal.modal('show');
                return;
            }
            bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        }

        function resetToCreate() {
            $form[0].reset();
            $form.attr('action', storeUrl);
            $method.val('POST').prop('disabled', true);
            $form.find('[name="price"]').val('49.00');
            $form.find('[name="billing_interval"]').val('monthly');
            $form.find('[name="lead_quota"]').val('15000');
            $form.find('[name="max_staff_members"]').val('5');
            $form.find('[name="is_active"]').prop('checked', true);
            $form.find('[name="is_default"]').prop('checked', false);
            $title.html('<i class="icon-base ti tabler-packages text-primary me-2 fs-4"></i> Create Subscription Plan');
            $submit.html('<i class="icon-base ti tabler-plus me-1"></i> Add');
        }

        $('#addPlanBtn').on('click', function () {
            resetToCreate();
            showModal();
        });

        $(document).on('click', '.js-edit-plan', function () {
            var id = $(this).data('id');

            $.ajax({
                url: '/plans/' + id,
                type: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    $form[0].reset();
                    $form.attr('action', '/plans/' + response.id);
                    $method.val('PUT').prop('disabled', false);

                    $form.find('[name="name"]').val(response.name);
                    $form.find('[name="price"]').val(response.price);
                    $form.find('[name="billing_interval"]').val(response.billing_interval);
                    $form.find('[name="lead_quota"]').val(response.lead_quota);
                    $form.find('[name="max_staff_members"]').val(response.max_staff_members);
                    $form.find('[name="description"]').val(response.description);
                    $form.find('[name="features"]').val(response.features);
                    $form.find('[name="is_active"]').prop('checked', !!response.is_active);
                    $form.find('[name="is_default"]').prop('checked', !!response.is_default);

                    $title.html('<i class="icon-base ti tabler-edit text-primary me-2"></i> Edit Plan');
                    $submit.html('<i class="icon-base ti tabler-device-floppy me-1"></i> Update');
                    showModal();
                },
                error: function () {
                    if (window.toastr) {
                        toastr.error('Could not load this plan.');
                    }
                }
            });
        });
    })(jQuery);
</script>
@endpush
