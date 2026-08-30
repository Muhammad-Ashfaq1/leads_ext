@extends('layouts.app')

@section('title', 'Workspace Settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/settings.css') }}" />
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
                    class="settings-sidebar-link active is-active text-start border-0 bg-transparent w-100"
                    id="general-settings-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#general-tab"
                    role="tab"
                    aria-controls="general-tab"
                    aria-selected="true">
                    <span class="settings-sidebar-icon">
                        <i class="icon-base ti tabler-adjustments-horizontal"></i>
                    </span>
                    <span class="settings-text-responsive">General &amp; Limits</span>
                </button>
                <button
                    type="button"
                    class="settings-sidebar-link text-start border-0 bg-transparent w-100"
                    id="api-settings-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#api-tab"
                    role="tab"
                    aria-controls="api-tab"
                    aria-selected="false">
                    <span class="settings-sidebar-icon">
                        <i class="icon-base ti tabler-key"></i>
                    </span>
                    <span class="settings-text-responsive">Discovery Engine API</span>
                </button>
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
            @if ($tenant)
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="tab-content p-0">
                        <!-- General & Limits Tab -->
                        <div class="tab-pane fade show active" id="general-tab" role="tabpanel" aria-labelledby="general-settings-tab">
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
                                        <option value="browser" @selected(($settings['default_engine'] ?? '') === 'browser')>Web Business Directory Search</option>
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
                        </div>

                        <!-- Discovery Engine API Tab -->
                        <div class="tab-pane fade" id="api-tab" role="tabpanel" aria-labelledby="api-settings-tab">
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
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="icon-base ti tabler-shield-check display-5 mb-3 text-primary"></i>
                    <h5 class="fw-semibold">Super Admin Master Mode</h5>
                    <p class="mb-0">Logged in as platform super administrator. Manage client tenant quotas and keys under <a href="{{ route('tenants.index') }}">SaaS Tenants</a>.</p>
                </div>
            @endif
        </div>
    </div>
</div>
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
    });
</script>
@endpush
