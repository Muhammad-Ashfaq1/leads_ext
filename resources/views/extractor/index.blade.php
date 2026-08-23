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
        <label class="form-label fw-semibold" for="promptInput">What leads do you want to find?</label>
        <input
            type="text"
            id="promptInput"
            class="form-control form-control-lg"
            placeholder="Find dentists in Lahore"
            autocomplete="off"
            maxlength="500">
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
                    <label class="form-check-label" for="mockToggle">Development mock stream (no Google Maps)</label>
                </div>
                <div class="form-check form-switch">
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
                <i class="icon-base ti tabler-download me-1"></i>
                Export CSV
            </a>
            <button type="button" class="btn btn-outline-secondary" id="clearBtn">Clear Results</button>
            <button type="button" class="btn btn-outline-primary" id="summaryNewBtn">New Extraction</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Leads</h5>
        <span class="badge bg-label-primary" id="leadCountBadge">0</span>
    </div>
    <div class="card-body p-3 p-md-4">
        <div id="leadsGrid" class="extractor-leads-grid">
            <div id="leadsEmpty" class="extractor-leads-empty">
                <i class="icon-base ti tabler-building-store display-4 mb-2 text-muted"></i>
                <p class="mb-0 text-muted">No leads yet. Start an extraction to stream results here.</p>
            </div>
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
        csrf: @json(csrf_token()),
    };
</script>
<script src="{{ asset('assets/js/extractor.js') }}"></script>
@endpush
