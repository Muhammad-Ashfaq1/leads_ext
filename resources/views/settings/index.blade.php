@extends('layouts.app')

@section('title', 'Extractor Settings')

@section('content')
<div class="row g-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 fw-semibold text-heading">
                            <i class="icon-base ti tabler-settings me-1 text-primary"></i> Lead Extractor Settings
                        </h5>
                        <small class="text-muted">Configure default extraction parameters, engine mode, and API keys for your organization.</small>
                    </div>
                    @if ($tenant)
                        <span class="badge {{ $tenant->plan === 'enterprise' ? 'bg-label-primary' : 'bg-label-info' }}">
                            {{ ucfirst($tenant->plan) }} Plan
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body p-4">
                @if ($tenant)
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Organization / Company Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Google Maps Places API Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                                <input type="password" name="google_maps_api_key" id="tenantApiKeyInput" class="form-control" value="{{ old('google_maps_api_key', $tenant->google_maps_api_key) }}" placeholder="AIzaSy...">
                                <button class="btn btn-outline-secondary" type="button" onclick="const el = document.getElementById('tenantApiKeyInput'); el.type = el.type === 'password' ? 'text' : 'password';">
                                    <i class="icon-base ti tabler-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted mt-1">
                                @if ($hasGlobalGoogleKey)
                                    <span class="text-success"><i class="icon-base ti tabler-check"></i> System default API key is configured.</span> Enter a key here only if you want to use a custom Google Cloud project key.
                                @else
                                    Enter your Google Maps Platform API key to enable instant Google Places API lead extraction.
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Default Extraction Engine</label>
                                <select name="default_engine" class="form-select">
                                    <option value="google_api" @selected(($settings['default_engine'] ?? 'google_api') === 'google_api')>Google Places API (Instant, recommended)</option>
                                    <option value="browser" @selected(($settings['default_engine'] ?? '') === 'browser')>Browser Automation (Chromium)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Default Lead Limit per Job</label>
                                <select name="default_limit" class="form-select">
                                    <option value="10" @selected(($settings['default_limit'] ?? 100) == 10)>10 Leads</option>
                                    <option value="25" @selected(($settings['default_limit'] ?? 100) == 25)>25 Leads</option>
                                    <option value="50" @selected(($settings['default_limit'] ?? 100) == 50)>50 Leads</option>
                                    <option value="100" @selected(($settings['default_limit'] ?? 100) == 100)>100 Leads</option>
                                    <option value="200" @selected(($settings['default_limit'] ?? 100) == 200)>200 Leads</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_email_enrichment" id="autoEmailSwitch" value="1" @checked($settings['auto_email_enrichment'] ?? true)>
                                <label class="form-check-label fw-semibold" for="autoEmailSwitch">
                                    Auto-enrich website email addresses
                                </label>
                            </div>
                            <small class="text-muted d-block ms-5">Automatically scan discovered business websites for public contact email addresses during extraction.</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="icon-base ti tabler-device-floppy me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="icon-base ti tabler-settings-off display-6 mb-2"></i>
                        <p class="mb-0">Logged in as global Super Administrator without a tenant context.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
