@extends('layouts.app')

@section('title', 'Lead Extractor')

@section('content')
<div class="awt-dash-header mb-4">
    <div class="awt-dash-header-body">
        <div class="awt-dash-header-identity">
            <span class="awt-dash-header-accent" aria-hidden="true"></span>
            <div>
                <h1 class="awt-dash-header-title">Lead Extractor</h1>
                <p class="awt-dash-header-subtitle mb-0">Search Google Maps and stream publicly listed businesses as they are found.</p>
            </div>
        </div>
    </div>
</div>

<div class="card awt-glass-card awt-tone-primary mb-4" id="promptCard">
    <div class="card-body">
        <!-- Vuexy Custom Option Engine Mode Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="form-check custom-option custom-option-basic checked" id="customOptionGoogleApi">
                    <label class="form-check-label custom-option-content" for="engineGoogleApi">
                        <input name="engineMode" class="form-check-input" type="radio" value="google_api" id="engineGoogleApi" checked />
                        <span class="custom-option-header">
                            <span class="h6 mb-0 d-flex align-items-center">
                                <i class="icon-base ti tabler-api me-2 text-primary fs-4"></i>Google Places API
                            </span>
                            <span class="badge bg-label-success">Recommended</span>
                        </span>
                        <span class="custom-option-body">
                            <small class="text-muted d-block mb-2">Instant extraction from Google Maps API. Target by Zip Code, City, Area, or State with zero CAPTCHAs.</small>
                            <span class="d-flex align-items-center gap-2">
                                @if ($hasGoogleApiKey)
                                    <span class="badge bg-label-success" id="apiKeyStatusBadge" title="Google Maps API Key is active in .env">
                                        <i class="icon-base ti tabler-check me-1"></i>API Key Active (.env)
                                    </span>
                                @else
                                    <button type="button" class="btn btn-xs btn-outline-secondary" id="toggleApiKeyBtn">
                                        <i class="icon-base ti tabler-key me-1"></i>Enter API Key
                                    </button>
                                @endif
                            </span>
                        </span>
                    </label>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-check custom-option custom-option-basic" id="customOptionBrowser">
                    <label class="form-check-label custom-option-content" for="engineBrowser">
                        <input name="engineMode" class="form-check-input" type="radio" value="live" id="engineBrowser" />
                        <span class="custom-option-header">
                            <span class="h6 mb-0 d-flex align-items-center">
                                <i class="icon-base ti tabler-brand-chrome me-2 text-info fs-4"></i>Browser Extractor (Chromium)
                            </span>
                            <span class="badge bg-label-info">Local / Free</span>
                        </span>
                        <span class="custom-option-body">
                            <small class="text-muted d-block">Automated Playwright Chromium desktop browser scrolling and scraping live Google Maps pages locally without an API key.</small>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7" id="promptInputCol">
                <label class="form-label fw-semibold" for="promptInput" id="promptLabel">What leads do you want to find?</label>
                <input
                    type="text"
                    id="promptInput"
                    class="form-control form-control-lg"
                    placeholder="e.g. Dentists, Real Estate, Plumbers, Law Firms"
                    autocomplete="off"
                    maxlength="500">
            </div>
            <div class="col-12 col-lg-5" id="locationInputCol">
                <label class="form-label fw-semibold" for="locationInput">
                    <i class="icon-base ti tabler-map-pin me-1 text-primary"></i>Zip code, City, State, or Area
                </label>
                <input
                    type="text"
                    id="locationInput"
                    class="form-control form-control-lg"
                    placeholder="e.g. 90210, Miami FL, Lahore, Texas"
                    autocomplete="off"
                    maxlength="200">
            </div>
        </div>

        <!-- Pre-Extraction Filters (Applied before API calling) -->
        <div class="mt-3 p-3 rounded-2 bg-light-subtle border" id="preFiltersContainer">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-semibold small text-uppercase text-muted d-flex align-items-center">
                    <i class="icon-base ti tabler-adjustments-horizontal me-1 text-primary"></i> Pre-Extraction Criteria (API Filters)
                </span>
                <span class="badge bg-label-info small" style="font-size: 0.72rem;">Filters before saving</span>
            </div>
            <div class="row g-2 align-items-center">
                <div class="col-6 col-sm-4 col-md-3">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="preReqWebsite">
                        <label class="form-check-label small fw-medium" for="preReqWebsite">
                            <i class="icon-base ti tabler-world-www me-1 text-primary"></i>Require Website
                        </label>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="preReqPhone">
                        <label class="form-check-label small fw-medium" for="preReqPhone">
                            <i class="icon-base ti tabler-phone me-1 text-success"></i>Require Phone
                        </label>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="preReqEmail">
                        <label class="form-check-label small fw-medium" for="preReqEmail">
                            <i class="icon-base ti tabler-mail me-1 text-danger"></i>Require Email
                        </label>
                    </div>
                </div>
                <div class="col-6 col-sm-6 col-md-3">
                    <div class="d-flex align-items-center gap-1">
                        <label class="small text-muted mb-0 text-nowrap" for="preMinRating">Min Rating:</label>
                        <select id="preMinRating" class="form-select form-select-sm">
                            <option value="0">Any Rating</option>
                            <option value="4.5">★ 4.5+</option>
                            <option value="4.0">★ 4.0+</option>
                            <option value="3.5">★ 3.5+</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom API Key input (shown when toggled or if key missing) -->
        <div class="mt-3 d-none" id="apiKeyRow">
            <label class="form-label small fw-semibold" for="customApiKeyInput">Google Maps Places API Key</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                <input type="password" id="customApiKeyInput" class="form-control" placeholder="AIzaSy...">
                <button class="btn btn-outline-secondary" type="button" id="toggleKeyVisibilityBtn"><i class="icon-base ti tabler-eye"></i></button>
            </div>
            <div class="form-text small text-muted">Enter a Google Maps Platform API key with Places API enabled, or configure `GOOGLE_MAPS_API_KEY` in `.env`.</div>
        </div>

        <div class="d-flex flex-wrap align-items-end gap-3 mt-4">
            <div>
                <label class="form-label mb-1" for="limitInput">Maximum Leads</label>
                <select id="limitInput" class="form-select">
                    @foreach ($allowedLimits as $limit)
                        <option value="{{ $limit }}" @selected($limit === $defaultLimit)>{{ $limit }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-primary" id="startBtn">
                <i class="icon-base ti tabler-player-play me-1"></i>
                Start Extraction
            </button>
            <button type="button" class="btn btn-outline-secondary d-none" id="newExtractionBtn">
                <i class="icon-base ti tabler-plus me-1"></i>
                New Extraction
            </button>
        </div>
        @if ($allowMock)
            <div class="extractor-dev mt-4">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="mockToggle">
                    <label class="form-check-label" for="mockToggle">Development mock stream (simulated data)</label>
                </div>
                <div class="form-check form-switch" id="verifyToggleWrap">
                    <input class="form-check-input" type="checkbox" id="verifyToggle">
                    <label class="form-check-label" for="verifyToggle">Simulate human verification</label>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card awt-glass-card awt-tone-warning mb-4 d-none" id="verificationCard">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <span class="avatar avatar-md bg-label-warning">
                <i class="icon-base ti tabler-shield-exclamation"></i>
            </span>
            <div class="flex-grow-1">
                <h5 class="mb-1">Human Verification Required</h5>
                <p class="mb-3 text-muted">Google Maps requires you to complete a verification before extraction can continue. Complete the verification in the browser window.</p>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-warning" id="openVerificationBtn">
                        <i class="icon-base ti tabler-external-link me-1"></i>
                        Open Verification
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="stopFromVerifyBtn">Stop Extraction</button>
                    @if ($allowMock)
                        <button type="button" class="btn btn-outline-primary d-none" id="completeMockVerifyBtn">Mark Verification Complete</button>
                    @endif
                </div>
                <p class="small text-muted mt-3 mb-0" id="verificationHint">Waiting for verification...</p>
            </div>
        </div>
    </div>
</div>

<div class="card awt-glass-card awt-tone-info mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h5 class="mb-1">Extraction Status</h5>
                <div class="extractor-status-line">
                    <span class="extractor-status-dot" data-status="ready" id="statusDot"></span>
                    <span class="fw-semibold" id="statusLabel">Ready</span>
                </div>
                <p class="small text-muted mb-0 mt-1" id="searchLabel">Search: —</p>
            </div>
            <button type="button" class="btn btn-outline-danger d-none" id="stopBtn">
                <i class="icon-base ti tabler-player-stop me-1"></i>
                Stop Extraction
            </button>
        </div>
        <p class="mb-3" id="activityLabel">Current Activity: Waiting for a search.</p>
        <div class="alert d-none" id="statusAlert" role="alert"></div>
        <div class="row g-3 extractor-kpis">
            <div class="col-6 col-lg-3">
                <div class="awt-glass-card awt-tone-primary awt-stat-card">
                    <div class="awt-stat-label">Leads Found</div>
                    <div class="awt-stat-value" id="kpiLeads">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="awt-glass-card awt-tone-info awt-stat-card">
                    <div class="awt-stat-label">Businesses Processed</div>
                    <div class="awt-stat-value" id="kpiSeen">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="awt-glass-card awt-tone-success awt-stat-card">
                    <div class="awt-stat-label">Emails Found</div>
                    <div class="awt-stat-value" id="kpiEmails">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="awt-glass-card awt-tone-secondary awt-stat-card">
                    <div class="awt-stat-label">Websites Found</div>
                    <div class="awt-stat-value" id="kpiWebsites">0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card awt-glass-card awt-tone-success mb-4 d-none" id="summaryCard">
    <div class="card-body">
        <h5 class="mb-3">Extraction Completed</h5>
        <div class="row g-3 mb-3" id="summaryStats"></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary disabled" id="exportBtn" href="#">
                <i class="icon-base ti tabler-file-spreadsheet me-1"></i>
                Download Excel (.xlsx)
            </a>
            <button type="button" class="btn btn-outline-secondary" id="clearBtn">Clear Results</button>
            <button type="button" class="btn btn-outline-primary" id="summaryNewBtn">New Extraction</button>
        </div>
    </div>
</div>

<div class="card mb-4" id="leadsSection">
    <div class="card-header border-bottom py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">Leads</h5>
                <span class="badge bg-label-primary" id="leadCountBadge" title="Total leads extracted">0 total</span>
                <span class="badge bg-label-info d-none" id="leadFilterBadge" title="Currently visible filtered leads">0 shown</span>
                <span class="badge bg-label-success d-none" id="leadSelectedBadge" title="Currently selected leads">0 selected</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle disabled" type="button" id="exportDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="icon-base ti tabler-download me-1"></i>
                        Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="exportDropdownBtn">
                        <li><h6 class="dropdown-header">Export All Leads</h6></li>
                        <li><a class="dropdown-item" href="#" id="exportAllExcelBtn"><i class="icon-base ti tabler-file-spreadsheet me-2 text-success"></i>Export All (Excel .xlsx)</a></li>
                        <li><a class="dropdown-item" href="#" id="exportAllJsonBtn"><i class="icon-base ti tabler-file-type-json me-2 text-warning"></i>Export All (JSON)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Filtered Leads</h6></li>
                        <li><a class="dropdown-item" href="#" id="exportFilteredExcelBtn"><i class="icon-base ti tabler-filter me-2 text-info"></i>Export Filtered (Excel .xlsx)</a></li>
                        <li><a class="dropdown-item" href="#" id="exportFilteredJsonBtn"><i class="icon-base ti tabler-code me-2 text-info"></i>Export Filtered (JSON)</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card-body border-bottom bg-light-subtle py-3 px-3 px-md-4 extractor-filter-panel">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                    <input type="text" id="leadSearchInput" class="form-control form-control-sm" placeholder="Search name, phone, email, category..." autocomplete="off">
                    <button class="btn btn-outline-secondary btn-sm d-none" type="button" id="leadSearchClear" title="Clear search">
                        <i class="icon-base ti tabler-x"></i>
                    </button>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-2 col-lg-2">
                <select id="filterEmail" class="form-select form-select-sm" title="Filter by email availability">
                    <option value="all">Emails: All</option>
                    <option value="has">Has Email</option>
                    <option value="none">No Email</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2 col-lg-2">
                <select id="filterWebsite" class="form-select form-select-sm" title="Filter by website availability">
                    <option value="all">Websites: All</option>
                    <option value="has">Has Website</option>
                    <option value="none">No Website</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2 col-lg-2">
                <select id="filterPhone" class="form-select form-select-sm" title="Filter by phone availability">
                    <option value="all">Phones: All</option>
                    <option value="has">Has Phone</option>
                    <option value="none">No Phone</option>
                </select>
            </div>
            <div class="col-6 col-sm-6 col-md-2 col-lg-1">
                <select id="filterRating" class="form-select form-select-sm" title="Filter by minimum rating">
                    <option value="all">Rating: All</option>
                    <option value="4.5">★ 4.5+</option>
                    <option value="4.0">★ 4.0+</option>
                    <option value="3.5">★ 3.5+</option>
                    <option value="has">Rated only</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-2 col-lg-2">
                <select id="sortLeads" class="form-select form-select-sm" title="Sort leads">
                    <option value="newest">Sort: Newest First</option>
                    <option value="oldest">Sort: Oldest First</option>
                    <option value="rating_desc">Sort: Highest Rated</option>
                    <option value="reviews_desc">Sort: Most Reviews</option>
                    <option value="name_asc">Sort: Name (A to Z)</option>
                </select>
            </div>
        </div>

        <!-- Quick filter pills row -->
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 pt-1 extractor-quick-filters">
            <span class="text-muted small me-1">Quick:</span>
            <button type="button" class="btn btn-xs btn-outline-secondary extractor-filter-chip" data-filter="email">
                <i class="icon-base ti tabler-mail me-1"></i>Has Email
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary extractor-filter-chip" data-filter="website">
                <i class="icon-base ti tabler-world-www me-1"></i>Has Website
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary extractor-filter-chip" data-filter="phone">
                <i class="icon-base ti tabler-phone me-1"></i>Has Phone
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary extractor-filter-chip" data-filter="high_rating">
                <i class="icon-base ti tabler-star me-1"></i>4.0+ Stars
            </button>
            <button type="button" class="btn btn-xs btn-link text-danger text-decoration-none d-none ms-auto" id="resetFiltersBtn">
                <i class="icon-base ti tabler-rotate me-1"></i>Reset Filters
            </button>
        </div>
    </div>

    <!-- Bulk Selection Toolbar (Active when items checked) -->
    <div class="card-body border-bottom bg-primary-subtle py-2 px-3 px-md-4 extractor-bulk-bar d-none" id="bulkBar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                    <label class="form-check-label fw-semibold text-primary" for="selectAllCheckbox" id="bulkCountLabel">
                        0 selected
                    </label>
                </div>
                <button type="button" class="btn btn-xs btn-outline-primary" id="selectAllFilteredBtn">Select All (<span id="bulkFilteredCount">0</span>)</button>
                <button type="button" class="btn btn-xs btn-link text-secondary text-decoration-none p-0" id="bulkDeselectBtn">Deselect</button>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary" id="bulkExportExcelBtn">
                    <i class="icon-base ti tabler-file-spreadsheet me-1"></i>Export Selected (Excel)
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="bulkExportJsonBtn">
                    <i class="icon-base ti tabler-file-type-json me-1"></i>JSON
                </button>
                <button type="button" class="btn btn-sm btn-label-secondary" id="bulkCopyEmailsBtn" title="Copy all emails from selected leads">
                    <i class="icon-base ti tabler-copy me-1"></i>Emails
                </button>
                <button type="button" class="btn btn-sm btn-label-secondary" id="bulkCopyPhonesBtn" title="Copy all phones from selected leads">
                    <i class="icon-base ti tabler-phone me-1"></i>Phones
                </button>
            </div>
        </div>
    </div>

    <!-- Leads Grid Section with Small UI Cards -->
    <div class="card-body p-3 p-md-4">
        <!-- Master select row when bulk bar is hidden -->
        <div class="d-flex align-items-center justify-content-between mb-3 extractor-leads-topbar" id="leadsTopbar">
            <div class="form-check m-0">
                <input class="form-check-input" type="checkbox" id="masterCheckbox" disabled>
                <label class="form-check-label small text-muted user-select-none" for="masterCheckbox">Select All</label>
            </div>
            <div class="small text-muted" id="leadsSummaryText">0 leads found</div>
        </div>

        <div id="leadsGrid" class="extractor-leads-grid extractor-small-cards-grid">
            <div id="leadsEmpty" class="extractor-leads-empty">
                <i class="icon-base ti tabler-building-store display-4 mb-2 text-muted"></i>
                <p class="mb-0 text-muted">No leads yet. Start an extraction to stream results here.</p>
            </div>
        </div>

        <div id="noFilterResults" class="extractor-leads-empty d-none py-5">
            <i class="icon-base ti tabler-filter-off display-4 mb-2 text-muted"></i>
            <h6 class="text-muted mb-1">No matching leads found</h6>
            <p class="small text-muted mb-3">Try adjusting your keyword search or active filter toggles.</p>
            <button type="button" class="btn btn-sm btn-outline-primary" id="noFilterResetBtn">Reset Filters</button>
        </div>
    </div>
</div>

<!-- Toast notification container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="extractorToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Action completed.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.ExtractorConfig = {
        startUrl: @json(route('extractor.start')),
        streamUrl: @json(url('/api/extractor/__JOB__/stream')),
        statusUrl: @json(url('/api/extractor/__JOB__/status')),
        stopUrl: @json(url('/api/extractor/__JOB__/stop')),
        focusUrl: @json(url('/api/extractor/__JOB__/focus')),
        exportUrl: @json(url('/api/extractor/__JOB__/export')),
        verifyCompleteUrl: @json(url('/api/extractor/__JOB__/verify-complete')),
        allowMock: @json((bool) $allowMock),
        hasGoogleApiKey: @json((bool) $hasGoogleApiKey),
        csrf: @json(csrf_token()),
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="{{ asset('assets/js/extractor.js') }}"></script>
@endpush
