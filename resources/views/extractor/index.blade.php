@extends('layouts.app')

@section('title', 'Lead Extractor')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold text-heading">
            <i class="icon-base ti tabler-map-pin-search me-1 text-primary"></i> Lead Extractor
        </h4>
        <p class="text-muted mb-0">Search businesses by category, city, area, or zip code and stream verified leads in real-time.</p>
    </div>
    @if ($tenant && $tenant->lead_quota > 0)
        <div class="text-end">
            <span class="badge bg-label-primary px-3 py-2">
                <i class="icon-base ti tabler-chart-pie me-1"></i> Quota: {{ number_format($tenant->leads_extracted_count) }} / {{ number_format($tenant->lead_quota) }}
            </span>
        </div>
    @endif
</div>

<div class="pos-glass-card pos-tone-primary mb-4 w-100" id="promptCard">
    <div class="card-body p-4">
        <!-- Vuexy Custom Option Engine Mode Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="form-check custom-option custom-option-basic checked" id="customOptionGoogleApi">
                    <label class="form-check-label custom-option-content" for="engineGoogleApi">
                        <input name="engineMode" class="form-check-input" type="radio" value="google_api" id="engineGoogleApi" checked />
                        <span class="custom-option-header">
                            <span class="h6 mb-0 d-flex align-items-center">
                                <i class="icon-base ti tabler-cpu me-2 text-primary fs-4"></i>AI Discovery Engine (Cloud Matrix)
                            </span>
                            <span class="badge bg-label-success">Recommended</span>
                        </span>
                        <span class="custom-option-body">
                            <small class="text-muted d-block mb-2">Instant high-density business discovery. Target by Zip Code, City, Area, or Region with zero delays.</small>
                            <span class="d-flex align-items-center gap-2">
                                @if ($hasGoogleApiKey)
                                    <span class="badge bg-label-success" id="apiKeyStatusBadge" title="Discovery Engine API Key is active">
                                        <i class="icon-base ti tabler-check me-1"></i>Platform Key Active
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
                                <i class="icon-base ti tabler-world me-2 text-info fs-4"></i>Autonomous Deep Crawler
                            </span>
                            <span class="badge bg-label-info">Local / Free</span>
                        </span>
                        <span class="custom-option-body">
                            <small class="text-muted d-block">Autonomous deep web crawler scanning business directories and public records with anti-bot intelligence.</small>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7" id="promptInputCol">
                <label class="form-label fw-semibold" for="promptInput" id="promptLabel">
                    <i class="icon-base ti tabler-category me-1 text-primary"></i>Industry / Business Category <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="promptInput"
                    class="form-control form-control-lg"
                    placeholder="e.g. Dentists, Real Estate, Plumbers, Software Companies, Law Firms"
                    autocomplete="off"
                    maxlength="500">
            </div>
            <div class="col-12 col-lg-5" id="locationInputCol">
                <label class="form-label fw-semibold" for="locationInput">
                    <i class="icon-base ti tabler-map-pin me-1 text-primary"></i>Zip Code, City, State, or Area
                </label>
                <input
                    type="text"
                    id="locationInput"
                    class="form-control form-control-lg"
                    placeholder="e.g. 90210, Miami FL, London, Toronto, Chicago"
                    autocomplete="off"
                    maxlength="200">
            </div>
        </div>

        <!-- Quick generic location suggestions -->
        <div class="d-flex align-items-center flex-wrap gap-1 mt-2">
            <small class="text-muted me-1"><i class="icon-base ti tabler-sparkles me-1 text-warning"></i>Quick Examples:</small>
            <span class="badge bg-label-secondary cursor-pointer loc-suggestion-pill" data-cat="Dentists" data-loc="Beverly Hills, CA 90210">Dentists in Beverly Hills, CA 90210</span>
            <span class="badge bg-label-secondary cursor-pointer loc-suggestion-pill" data-cat="Real Estate Agencies" data-loc="Miami, FL">Real Estate in Miami, FL</span>
            <span class="badge bg-label-secondary cursor-pointer loc-suggestion-pill" data-cat="Digital Marketing" data-loc="London, UK">Marketing in London, UK</span>
            <span class="badge bg-label-secondary cursor-pointer loc-suggestion-pill" data-cat="Accounting Firms" data-loc="Toronto, ON">Accounting in Toronto, ON</span>
            <span class="badge bg-label-secondary cursor-pointer loc-suggestion-pill" data-cat="Plumbers" data-loc="Chicago, IL">Plumbers in Chicago, IL</span>
        </div>

        <!-- Pre-Extraction Filters (Applied before API calling) -->
        <div class="mt-3 p-3 rounded-2 bg-light-subtle border" id="preFiltersContainer">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-semibold small text-uppercase text-muted d-flex align-items-center">
                    <i class="icon-base ti tabler-adjustments-horizontal me-1 text-primary"></i> Pre-Extraction Criteria (Filter Before Query)
                </span>
                <span class="badge bg-label-info small" style="font-size: 0.72rem;">Filters before saving</span>
            </div>
            <div class="row g-2 align-items-center">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="icon-base ti tabler-world"></i></span>
                        <select id="preWebsiteFilter" class="form-select form-select-sm">
                            <option value="all">Website: All / Any</option>
                            <option value="without_website">🚫 Without Website (No Site)</option>
                            <option value="has_website">🌐 Must Have Website</option>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-sm-3 col-md-3">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="preReqPhone">
                        <label class="form-check-label small fw-medium" for="preReqPhone">
                            <i class="icon-base ti tabler-phone me-1 text-success"></i>Require Phone
                        </label>
                    </div>
                </div>
                <div class="col-6 col-sm-3 col-md-3">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="preReqEmail">
                        <label class="form-check-label small fw-medium" for="preReqEmail">
                            <i class="icon-base ti tabler-shield-check me-1 text-success"></i>Require Email
                        </label>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="icon-base ti tabler-star text-warning"></i></span>
                        <select id="preMinRating" class="form-select form-select-sm">
                            <option value="0">Rating: Any Rating</option>
                            <option value="4.5">★ 4.5+ Rating</option>
                            <option value="4.0">★ 4.0+ Rating</option>
                            <option value="3.5">★ 3.5+ Rating</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom API Key input (shown when toggled or if key missing) -->
        <div class="mt-3 d-none" id="apiKeyRow">
            <label class="form-label small fw-semibold" for="customApiKeyInput">Discovery Engine API Key</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                <input type="password" id="customApiKeyInput" class="form-control" placeholder="AIzaSy...">
                <button class="btn btn-outline-secondary" type="button" id="toggleKeyVisibilityBtn"><i class="icon-base ti tabler-eye"></i></button>
            </div>
            <div class="form-text small text-muted">Enter your dedicated Platform API key or configure it in your workspace settings.</div>
        </div>

        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-4 pt-3 border-top">
            <div style="min-width: 180px;">
                <label class="form-label mb-1 fw-semibold small" for="limitInput">
                    <i class="icon-base ti tabler-list-numbers me-1 text-primary"></i>Maximum Leads Target
                </label>
                <select id="limitInput" class="form-select form-select-sm">
                    @foreach ($allowedLimits as $limit)
                        <option value="{{ $limit }}" @selected($limit === $defaultLimit)>{{ number_format($limit) }} leads</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-primary" id="startBtn">
                    <i class="icon-base ti tabler-player-play me-1"></i>
                    Start Extraction
                </button>
                <button type="button" class="btn btn-danger d-none" id="inlineStopBtn">
                    <i class="icon-base ti tabler-player-stop me-1"></i>
                    Stop Extraction
                </button>
                <button type="button" class="btn btn-outline-danger" id="clearAllResultsBtn" title="Clear all searched leads from screen">
                    <i class="icon-base ti tabler-trash me-1"></i>
                    Clear Results
                </button>
                <button type="button" class="btn btn-outline-secondary" id="newExtractionBtn" title="Reset search inputs and prepare new query">
                    <i class="icon-base ti tabler-rotate me-1"></i>
                    New Search
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 d-none" id="verificationCard">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <span class="avatar avatar-md bg-label-warning">
                <i class="icon-base ti tabler-shield-exclamation"></i>
            </span>
            <div class="flex-grow-1">
                <h5 class="mb-1">Human Verification Required</h5>
                <p class="mb-3 text-muted">A verification check is required before extraction can continue. Complete the verification in the browser window.</p>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-warning" id="openVerificationBtn">
                        <i class="icon-base ti tabler-external-link me-1"></i>
                        Open Verification
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="stopFromVerifyBtn">Stop Extraction</button>
                </div>
                <p class="small text-muted mt-3 mb-0" id="verificationHint">Waiting for verification...</p>
            </div>
        </div>
    </div>
</div>

<div class="pos-glass-card pos-tone-info mb-4 w-100" id="statusCard">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h5 class="mb-1">Extraction Status</h5>
                <div class="extractor-status-line">
                    <span class="extractor-status-dot" data-status="ready" id="statusDot"></span>
                    <span class="fw-semibold" id="statusLabel">Ready</span>
                </div>
                <p class="small text-muted mb-0 mt-1" id="searchLabel">Search: —</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-danger d-none" id="stopBtn">
                    <i class="icon-base ti tabler-player-stop me-1"></i>
                    Stop Extraction
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="statusClearBtn" title="Clear searched leads and reset counters">
                    <i class="icon-base ti tabler-trash me-1"></i>
                    Clear Results
                </button>
            </div>
        </div>
        <p class="mb-3" id="activityLabel">Current Activity: Waiting for a search.</p>
        <div class="alert d-none" id="statusAlert" role="alert"></div>
        <div class="row g-3 extractor-kpis">
            <div class="col-6 col-lg-3">
                <div class="card border bg-light-subtle py-2 px-3">
                    <div class="text-muted small">Leads Found</div>
                    <div class="fw-bold fs-4 text-primary" id="kpiLeads">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border bg-light-subtle py-2 px-3">
                    <div class="text-muted small">Businesses Processed</div>
                    <div class="fw-bold fs-4 text-info" id="kpiSeen">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border bg-light-subtle py-2 px-3">
                    <div class="text-muted small">Emails Found</div>
                    <div class="fw-bold fs-4 text-success" id="kpiEmails">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border bg-light-subtle py-2 px-3">
                    <div class="text-muted small">Websites Found</div>
                    <div class="fw-bold fs-4 text-warning" id="kpiWebsites">0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pos-glass-card pos-tone-success mb-4 d-none w-100" id="summaryCard">
    <div class="card-body p-4">
        <h5 class="mb-3">Extraction Completed</h5>
        <div class="row g-3 mb-3" id="summaryStats"></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary disabled" id="exportBtn" href="#">
                <i class="icon-base ti tabler-file-spreadsheet me-1"></i>
                Download Excel (.xlsx)
            </a>
            <button type="button" class="btn btn-outline-danger" id="clearBtn">Clear Results</button>
            <button type="button" class="btn btn-outline-primary" id="summaryNewBtn">New Search</button>
        </div>
    </div>
</div>

<div class="pos-glass-card pos-tone-primary mb-4 w-100" id="leadsSection">
    <div class="card-header border-bottom py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">Discovered Leads</h5>
                <span class="badge bg-label-primary" id="leadCountBadge" title="Total leads extracted">0 total</span>
                <span class="badge bg-label-info d-none" id="leadFilterBadge" title="Currently visible filtered leads">0 shown</span>
                <span class="badge bg-label-success d-none" id="leadSelectedBadge" title="Currently selected leads">
                    <i class="icon-base ti tabler-checks me-1"></i><span id="selectedRatioText">0 selected</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <button type="button" class="btn btn-sm btn-outline-danger" id="leadsClearBtn" title="Clear all searched leads from screen">
                    <i class="icon-base ti tabler-trash me-1"></i>Clear Results
                </button>
                <button type="button" class="btn btn-sm btn-success d-none" id="saveAllDiscoveredBtn">
                    <i class="icon-base ti tabler-device-floppy me-1"></i>Save All (<span id="saveAllCount">0</span>)
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle disabled" type="button" id="exportDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="icon-base ti tabler-download me-1"></i>
                        Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="exportDropdownBtn">
                        <li><h6 class="dropdown-header">Export All Leads</h6></li>
                        <li><a class="dropdown-item" href="#" id="exportAllExcelBtn"><i class="icon-base ti tabler-file-spreadsheet me-2 text-success"></i>Export All (Excel .xlsx)</a></li>
                        <li><a class="dropdown-item" href="#" id="exportAllCsvBtn"><i class="icon-base ti tabler-file-text me-2 text-info"></i>Export All (CSV .csv)</a></li>
                        <li><a class="dropdown-item" href="#" id="exportAllJsonBtn"><i class="icon-base ti tabler-file-type-json me-2 text-warning"></i>Export All (JSON)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Filtered Leads</h6></li>
                        <li><a class="dropdown-item" href="#" id="exportFilteredExcelBtn"><i class="icon-base ti tabler-filter me-2 text-info"></i>Export Filtered (Excel .xlsx)</a></li>
                        <li><a class="dropdown-item" href="#" id="exportFilteredCsvBtn"><i class="icon-base ti tabler-file-text me-2 text-info"></i>Export Filtered (CSV .csv)</a></li>
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
            <button type="button" class="btn btn-xs btn-outline-secondary extractor-filter-chip" data-filter="no_website">
                <i class="icon-base ti tabler-world-off me-1"></i>No Website
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

    <!-- Bulk Selection Floating / Sticky Toolbar (Active when items checked) -->
    <div class="card-body border-bottom bg-primary-subtle py-2 px-3 px-md-4 extractor-bulk-bar d-none" id="bulkBar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                    <label class="form-check-label fw-bold text-primary" for="selectAllCheckbox" id="bulkCountLabel">
                        0 selected
                    </label>
                </div>
                <button type="button" class="btn btn-xs btn-outline-primary" id="selectAllFilteredBtn">Select All (<span id="bulkFilteredCount">0</span>)</button>
                <button type="button" class="btn btn-xs btn-link text-secondary text-decoration-none p-0" id="bulkDeselectBtn">Deselect</button>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary" id="bulkSendEmailBtn" title="Send Email to selected leads">
                    <i class="icon-base ti tabler-send me-1"></i>Send Email
                </button>
                <button type="button" class="btn btn-sm btn-success" id="bulkSaveBtn">
                    <i class="icon-base ti tabler-device-floppy me-1"></i>Save Selected
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="bulkExportDropdownBtn">
                        <i class="icon-base ti tabler-download me-1"></i>Export Selected
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="bulkExportDropdownBtn">
                        <li><a class="dropdown-item" href="#" id="bulkExportExcelBtn"><i class="icon-base ti tabler-file-spreadsheet me-2 text-success"></i>Excel (.xlsx)</a></li>
                        <li><a class="dropdown-item" href="#" id="bulkExportCsvBtn"><i class="icon-base ti tabler-file-text me-2 text-info"></i>CSV (.csv)</a></li>
                        <li><a class="dropdown-item" href="#" id="bulkExportJsonBtn"><i class="icon-base ti tabler-file-type-json me-2 text-warning"></i>JSON</a></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-sm btn-label-secondary" id="bulkCopyEmailsBtn" title="Copy all emails from selected leads">
                    <i class="icon-base ti tabler-copy me-1"></i>Emails
                </button>
                <button type="button" class="btn btn-sm btn-label-secondary" id="bulkCopyPhonesBtn" title="Copy all phones from selected leads">
                    <i class="icon-base ti tabler-phone me-1"></i>Phones
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDiscardBtn" title="Discard selected leads from view">
                    <i class="icon-base ti tabler-trash me-1"></i>Discard Selected
                </button>
            </div>
        </div>
    </div>

    <!-- Leads Grid Section with Small UI Cards -->
    <div class="card-body p-3 p-md-4">
        <!-- Master select row when bulk bar is hidden -->
        <div class="d-flex align-items-center justify-content-between mb-3 extractor-leads-topbar" id="leadsTopbar">
            <div class="form-check m-0">
                <input class="form-check-input" type="checkbox" id="masterCheckbox" style="cursor: pointer;">
                <label class="form-check-label small text-muted user-select-none" for="masterCheckbox" id="masterCheckboxLabel" style="cursor: pointer;">Select All</label>
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

<!-- Extractor Send Outreach Email Modal -->
<div class="modal fade" id="extractorSendEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title">
                    <i class="icon-base ti tabler-send me-1 text-primary"></i> Send Outreach Email
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between">
                    <div>
                        <i class="icon-base ti tabler-mail me-1"></i>
                        <span id="extractorModalRecipients" class="fw-semibold">1 recipient selected</span>
                    </div>
                    <span class="badge bg-white text-primary" id="extractorModalEmailBadge">1 with email</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="extractorTemplateSelect">Select Email Template</label>
                        <select class="form-select" id="extractorTemplateSelect">
                            <option value="">-- Choose a template (optional) --</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end justify-content-end">
                        <a href="{{ route('email-templates.index') }}" target="_blank" class="small text-decoration-none">
                            <i class="icon-base ti tabler-external-link me-1"></i> Manage Templates
                        </a>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="extractorModalSubject">Email Subject <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="extractorModalSubject" placeholder="e.g. Quick inquiry for @{{business_name}}">
                </div>

                <!-- Dynamic tags toolbar -->
                <div class="mb-3 p-2 bg-light-subtle border rounded">
                    <div class="d-flex align-items-center flex-wrap gap-1">
                        <small class="fw-bold text-muted me-2"><i class="icon-base ti tabler-code me-1"></i>Insert Tag:</small>
                        <span class="badge bg-label-primary cursor-pointer ext-var-pill" onclick="insertExtractorVariable('@{{business_name}}')">@{{business_name}}</span>
                        <span class="badge bg-label-info cursor-pointer ext-var-pill" onclick="insertExtractorVariable('@{{email}}')">@{{email}}</span>
                        <span class="badge bg-label-secondary cursor-pointer ext-var-pill" onclick="insertExtractorVariable('@{{phone}}')">@{{phone}}</span>
                        <span class="badge bg-label-success cursor-pointer ext-var-pill" onclick="insertExtractorVariable('@{{city}}')">@{{city}}</span>
                        <span class="badge bg-label-warning cursor-pointer ext-var-pill" onclick="insertExtractorVariable('@{{category}}')">@{{category}}</span>
                        <span class="badge bg-label-dark cursor-pointer ext-var-pill" onclick="insertExtractorVariable('@{{website}}')">@{{website}}</span>
                        <span class="badge bg-label-primary cursor-pointer ext-var-pill" onclick="insertExtractorVariable('@{{sender_name}}')">@{{sender_name}}</span>
                    </div>
                </div>

                <!-- Rich Text Editor -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Body <span class="text-danger">*</span></label>
                    <div class="editor-toolbar" style="background: #f8f9fa; border: 1px solid #dee2e6; border-bottom: none; border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem; padding: 0.4rem; display: flex; flex-wrap: wrap; gap: 0.25rem;">
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatExtractorDoc('bold')" title="Bold"><i class="icon-base ti tabler-bold"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatExtractorDoc('italic')" title="Italic"><i class="icon-base ti tabler-italic"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatExtractorDoc('underline')" title="Underline"><i class="icon-base ti tabler-underline"></i></button>
                        <span class="border-end mx-1"></span>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatExtractorDoc('insertUnorderedList')" title="Bullet List"><i class="icon-base ti tabler-list"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatExtractorDoc('insertOrderedList')" title="Numbered List"><i class="icon-base ti tabler-list-numbers"></i></button>
                    </div>
                    <div class="editor-content" id="extractorModalEditor" contenteditable="true" spellcheck="false" style="min-height: 180px; border: 1px solid #dee2e6; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; padding: 0.75rem; background: #fff; outline: none; overflow-y: auto; max-height: 350px;">
                        <p>Hi <strong>@{{business_name}}</strong> Team,</p>
                        <p>I came across your business in @{{city}} and wanted to reach out regarding our lead generation and growth services.</p>
                        <p>Would you have 5 minutes this week for a brief call?</p>
                        <p>Best regards,<br><strong>@{{sender_name}}</strong></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnExtractorConfirmSend">
                    <i class="icon-base ti tabler-send me-1"></i> Send Outreach Email
                </button>
            </div>
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
        bulkActionUrl: @json(route('leads.bulk-action')),
        exportSelectedUrl: @json(route('leads.export-selected')),
        sendEmailUrl: @json(route('leads.send-email')),
        emailTemplatesUrl: @json(route('email-templates.list')),
        allowMock: @json((bool) $allowMock),
        hasGoogleApiKey: @json((bool) $hasGoogleApiKey),
        csrf: @json(csrf_token()),
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="{{ asset('assets/js/extractor.js') }}"></script>
@endpush
