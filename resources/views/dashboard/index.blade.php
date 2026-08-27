@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <!-- Top Glass Intro Banner (Matching POS) -->
    <div class="col-12">
        <div class="pos-glass-card pos-tone-primary">
            <div class="pos-glass-intro">
                <div class="pos-glass-intro-copy">
                    <h4 class="pos-glass-intro-title">
                        Welcome back, {{ $user->name }}
                    </h4>
                    <p class="pos-glass-intro-subtitle">
                        @if ($isSuperAdmin)
                            Platform Administrator Workspace · Full visibility across {{ $platformStats['total_tenants'] ?? 0 }} organizations · {{ number_format($platformStats['global_leads'] ?? 0) }} total leads managed
                        @elseif ($tenant)
                            {{ $tenant->name }} ({{ ucfirst($tenant->plan) }} Plan) ·
                            {{ number_format($totalLeads) }} leads extracted across {{ number_format($totalJobs) }} extraction runs ·
                            {{ number_format($totalEmails) }} verified contacts discovered
                        @endif
                    </p>
                </div>
                <div class="pos-glass-intro-actions d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                        <i class="icon-base ti tabler-map-pin-search me-1" aria-hidden="true"></i> Start Extraction
                    </a>
                    <a href="{{ route('leads.index') }}" class="btn btn-sm btn-label-secondary">
                        <i class="icon-base ti tabler-users-group me-1" aria-hidden="true"></i> View All Leads
                    </a>
                    @if ($tenant && $tenant->lead_quota > 0)
                        <span class="pos-glass-pill pos-tone-info">
                            <i class="icon-base ti tabler-chart-pie" aria-hidden="true"></i>
                            Quota: {{ number_format($tenant->leads_extracted_count) }} / {{ number_format($tenant->lead_quota) }} ({{ round(($tenant->leads_extracted_count / $tenant->lead_quota) * 100) }}%)
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($isSuperAdmin && $platformStats)
        <!-- Super Admin Operations Banner -->
        <div class="col-12">
            <div class="pos-glass-card pos-tone-danger">
                <div class="pos-glass-intro">
                    <div class="pos-glass-intro-copy">
                        <h5 class="pos-glass-intro-title text-danger mb-1">
                            <i class="icon-base ti tabler-shield-check me-1"></i> Super Admin Global Overview
                        </h5>
                        <p class="pos-glass-intro-subtitle mb-0">
                            {{ $platformStats['active_tenants'] }} active organizations · {{ $platformStats['total_users'] }} platform users · {{ number_format($platformStats['global_leads']) }} total leads database
                        </p>
                    </div>
                    <div class="pos-glass-intro-actions d-flex align-items-center gap-2">
                        <a href="{{ route('tenants.index') }}" class="btn btn-sm btn-danger">
                            <i class="icon-base ti tabler-building me-1"></i> Manage SaaS Tenants
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 6 Glass Stat Cards (Matching POS) -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-primary h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-users" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Total Leads</h6>
                </div>
                <p class="pos-stat-value">{{ number_format($totalLeads) }}</p>
                <p class="pos-stat-desc mb-0">From {{ $totalJobs }} extraction tasks</p>
                <div class="pos-stat-note">
                    <a href="{{ route('leads.index') }}">Browse database →</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-success h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-mail-check" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Email Discovery</h6>
                </div>
                <p class="pos-stat-value">{{ $emailRate }}%</p>
                <p class="pos-stat-desc mb-0">{{ number_format($totalEmails) }} verified emails</p>
                <div class="pos-stat-note">
                    <a href="{{ route('leads.index', ['has_email' => 'yes']) }}" class="text-success fw-medium text-decoration-none" title="View leads with verified emails">
                        <i class="icon-base ti tabler-check me-1"></i>View Leads ➔
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-info h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-phone-call" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Phone Coverage</h6>
                </div>
                <p class="pos-stat-value">{{ $phoneRate }}%</p>
                <p class="pos-stat-desc mb-0">{{ number_format($totalPhones) }} direct numbers</p>
                <div class="pos-stat-note">
                    <a href="{{ route('leads.index', ['has_phone' => 'yes']) }}" class="text-info fw-medium text-decoration-none" title="View leads with direct phone numbers">
                        <i class="icon-base ti tabler-phone me-1"></i>View Leads ➔
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-warning h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-world-www" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Websites</h6>
                </div>
                <p class="pos-stat-value">{{ $websiteRate }}%</p>
                <p class="pos-stat-desc mb-0">{{ number_format($totalWebsites) }} domains found</p>
                <div class="pos-stat-note d-flex align-items-center justify-content-between">
                    <a href="{{ route('leads.index', ['has_website' => 'yes']) }}" class="text-warning fw-medium text-decoration-none" title="View leads with websites">
                        Found: {{ number_format($totalWebsites) }}
                    </a>
                    <a href="{{ route('leads.index', ['has_website' => 'no']) }}" class="badge bg-label-danger text-decoration-none small" title="View leads with no website">
                        <i class="icon-base ti tabler-world-off me-1"></i>No Site: {{ number_format(max(0, $totalLeads - $totalWebsites)) }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-primary h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-share" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Social Profiles</h6>
                </div>
                <p class="pos-stat-value">{{ $socialRate }}%</p>
                <p class="pos-stat-desc mb-0">{{ number_format($totalSocials) }} companies enriched</p>
                <div class="pos-stat-note">
                    <a href="{{ route('leads.index') }}" class="text-primary fw-medium text-decoration-none">
                        <i class="icon-base ti tabler-social me-1"></i>5 platforms scanned
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-secondary h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-building-store" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Places Scanned</h6>
                </div>
                <p class="pos-stat-value">{{ number_format($totalBusinessesSeen) }}</p>
                <p class="pos-stat-desc mb-0">Total places processed</p>
                <div class="pos-stat-note">
                    <span>{{ $totalBusinessesSeen > 0 ? round(($totalLeads / max(1, $totalBusinessesSeen)) * 100) : 100 }}% extraction rate</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Social Media Platform Discovery Strip -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-label-primary rounded">
                            <i class="icon-base ti tabler-brand-sharetech fs-5"></i>
                        </span>
                        <div>
                            <h6 class="mb-0 fw-semibold">Social Media Profile Discovery</h6>
                            <small class="text-muted">Single-pass HTML social extraction</small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-label-primary p-2"><i class="icon-base ti tabler-brand-linkedin fs-5"></i></span>
                            <div>
                                <span class="fw-bold d-block">{{ number_format($socialBreakdown['linkedin']) }}</span>
                                <small class="text-muted">LinkedIn</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-label-info p-2"><i class="icon-base ti tabler-brand-facebook fs-5"></i></span>
                            <div>
                                <span class="fw-bold d-block">{{ number_format($socialBreakdown['facebook']) }}</span>
                                <small class="text-muted">Facebook</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-label-danger p-2"><i class="icon-base ti tabler-brand-instagram fs-5"></i></span>
                            <div>
                                <span class="fw-bold d-block">{{ number_format($socialBreakdown['instagram']) }}</span>
                                <small class="text-muted">Instagram</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-label-dark p-2"><i class="icon-base ti tabler-brand-x fs-5"></i></span>
                            <div>
                                <span class="fw-bold d-block">{{ number_format($socialBreakdown['twitter']) }}</span>
                                <small class="text-muted">Twitter / X</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-label-danger p-2"><i class="icon-base ti tabler-brand-youtube fs-5"></i></span>
                            <div>
                                <span class="fw-bold d-block">{{ number_format($socialBreakdown['youtube']) }}</span>
                                <small class="text-muted">YouTube</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Interactive ApexCharts Graphs Row -->
<div class="row g-4 mb-4">
    <!-- Lead Extraction Trend Chart -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center pb-2">
                <div>
                    <h5 class="card-title mb-1">
                        <i class="icon-base ti tabler-chart-area-line me-1 text-primary"></i> Lead Extraction Trend (7 Days)
                    </h5>
                    <p class="card-subtitle text-muted mb-0 small">Daily volume of leads discovered across extraction runs</p>
                </div>
                <span class="badge bg-label-primary">{{ number_format($totalLeads) }} total leads</span>
            </div>
            <div class="card-body">
                <div id="leadTrendChart" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>

    <!-- Enrichment Breakdown Donut Chart -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header pb-2">
                <h5 class="card-title mb-1">
                    <i class="icon-base ti tabler-chart-donut me-1 text-success"></i> Data Enrichment Mix
                </h5>
                <p class="card-subtitle text-muted mb-0 small">Contact discovery breakdown</p>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div id="enrichmentDonutChart" style="min-height: 240px;"></div>
                <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                    <div>
                        <small class="text-muted d-block">Emails</small>
                        <span class="fw-bold text-success">{{ number_format($totalEmails) }}</span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Phones</small>
                        <span class="fw-bold text-info">{{ number_format($totalPhones) }}</span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Socials</small>
                        <span class="fw-bold text-primary">{{ number_format($totalSocials) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maximum Records: Top Niches & Recent Extraction Jobs -->
<div class="row g-4 mb-4">
    <!-- Top Business Niches -->
    <div class="col-12 col-lg-4">
        <div class="pos-glass-card pos-tone-primary h-100">
            <div class="pos-glass-intro border-bottom">
                <div class="pos-glass-intro-copy">
                    <h5 class="pos-glass-intro-title">
                        <i class="icon-base ti tabler-category me-1 text-primary"></i> Top Business Niches
                    </h5>
                    <p class="pos-glass-intro-subtitle">Top 8 discovered industry categories</p>
                </div>
                <a href="{{ route('leads.index') }}" class="btn btn-xs btn-outline-primary">View All</a>
            </div>
            <div class="p-3">
                @if ($topCategories->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="icon-base ti tabler-folder-off display-6 mb-2"></i>
                        <p class="small mb-0">No lead categories yet. Run an extraction to discover leads!</p>
                    </div>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach ($topCategories as $cat)
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-medium small text-truncate" style="max-width: 200px;" title="{{ $cat->category }}">{{ $cat->category }}</span>
                                    <span class="badge bg-label-primary">{{ number_format($cat->count) }} leads</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, round(($cat->count / max(1, $totalLeads)) * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Extraction Tasks Table (Max 10 Records) -->
    <div class="col-12 col-lg-8">
        <div class="pos-glass-card pos-tone-info h-100">
            <div class="pos-glass-intro border-bottom">
                <div class="pos-glass-intro-copy">
                    <h5 class="pos-glass-intro-title">
                        <i class="icon-base ti tabler-history me-1 text-info"></i> Recent Extraction Jobs
                    </h5>
                    <p class="pos-glass-intro-subtitle">Live status &amp; lead export (Showing 10 jobs)</p>
                </div>
                <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-outline-primary">All Tasks</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Search Query</th>
                            <th>Mode</th>
                            <th>Leads</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentJobs as $job)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-heading small text-truncate" style="max-width: 220px;" title="{{ $job->prompt }}">
                                        {{ $job->prompt }}
                                    </div>
                                    <small class="text-muted">{{ $job->query }}</small>
                                </td>
                                <td>
                                    @if ($job->mode === 'google_api')
                                        <span class="badge bg-label-info" style="font-size: 0.7rem;"><i class="icon-base ti tabler-cpu me-1"></i>Cloud Matrix</span>
                                    @else
                                        <span class="badge bg-label-secondary" style="font-size: 0.7rem;"><i class="icon-base ti tabler-world me-1"></i>Deep Crawler</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-heading">{{ $job->leads_extracted }}</span>
                                    <small class="text-muted">/ {{ $job->limit }}</small>
                                </td>
                                <td>
                                    @if ($job->status === 'completed')
                                        <span class="badge bg-label-success" style="font-size: 0.7rem;"><i class="icon-base ti tabler-circle-check me-1"></i>Completed</span>
                                    @elseif ($job->status === 'extracting')
                                        <span class="badge bg-label-primary" style="font-size: 0.7rem;">Extracting</span>
                                    @elseif ($job->status === 'error')
                                        <span class="badge bg-label-danger" style="font-size: 0.7rem;">Error</span>
                                    @else
                                        <span class="badge bg-label-secondary" style="font-size: 0.7rem;">{{ ucfirst($job->status) }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $job->created_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('extractor.job.export', $job->uuid) }}?format=excel" class="btn btn-xs btn-outline-success" title="Download Excel">
                                        <i class="icon-base ti tabler-file-spreadsheet"></i>
                                    </a>
                                    <a href="{{ route('leads.index', ['job_id' => $job->id]) }}" class="btn btn-xs btn-outline-primary" title="View Leads">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No extraction jobs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Maximum Records: Recently Discovered Enriched Leads Table -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-3">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="icon-base ti tabler-sparkles me-1 text-warning"></i> Recently Extracted &amp; Enriched Leads
                    </h5>
                    <small class="text-muted">Showing latest 10 enriched business leads across extractions</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('leads.export.excel') }}" class="btn btn-sm btn-outline-success">
                        <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export All Excel
                    </a>
                    <a href="{{ route('leads.index') }}" class="btn btn-sm btn-primary">
                        <i class="icon-base ti tabler-list me-1"></i> View All Leads ({{ number_format($totalLeads) }})
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Business</th>
                            <th>Category</th>
                            <th>Contact Phone</th>
                            <th>Verified Email</th>
                            <th>Social Profiles</th>
                            <th>Rating</th>
                            <th class="pe-3 text-end">Extracted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLeads as $lead)
                            @php
                                $emails = is_array($lead->emails) ? $lead->emails : [];
                                $firstEmail = $emails[0] ?? null;
                                $socials = is_array($lead->social_links) ? $lead->social_links : [];
                                $vStatus = is_array($lead->email_verification_status) ? $lead->email_verification_status : [];
                                $isValidEmail = $firstEmail && isset($vStatus[$firstEmail]['is_valid']) && $vStatus[$firstEmail]['is_valid'];
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($lead->avatar_url)
                                            <img src="{{ $lead->avatar_url }}" alt="{{ $lead->business_name }}" class="rounded-circle" width="28" height="28" onerror="this.style.display='none'">
                                        @endif
                                        <div>
                                            <div class="fw-semibold text-heading small">{{ $lead->business_name }}</div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 220px;">{{ $lead->address ?: '—' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($lead->category)
                                        <span class="badge bg-label-primary small">{{ $lead->category }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($lead->phone)
                                        <a href="tel:{{ $lead->phone }}" class="text-body small text-nowrap"><i class="icon-base ti tabler-phone me-1 text-info"></i>{{ $lead->phone }}</a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($firstEmail)
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="mailto:{{ $firstEmail }}" class="small text-truncate" style="max-width: 170px;" title="{{ $firstEmail }}">{{ $firstEmail }}</a>
                                            @if ($isValidEmail)
                                                <span class="badge bg-label-success p-1" title="MX Validated"><i class="icon-base ti tabler-check"></i></span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">No email</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        @if (!empty($socials['linkedin']))
                                            <a href="{{ $socials['linkedin'] }}" target="_blank" rel="noopener" class="text-primary" title="LinkedIn"><i class="icon-base ti tabler-brand-linkedin"></i></a>
                                        @endif
                                        @if (!empty($socials['facebook']))
                                            <a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener" class="text-info" title="Facebook"><i class="icon-base ti tabler-brand-facebook"></i></a>
                                        @endif
                                        @if (!empty($socials['instagram']))
                                            <a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener" class="text-danger" title="Instagram"><i class="icon-base ti tabler-brand-instagram"></i></a>
                                        @endif
                                        @if (!empty($socials['twitter']))
                                            <a href="{{ $socials['twitter'] }}" target="_blank" rel="noopener" class="text-dark" title="Twitter / X"><i class="icon-base ti tabler-brand-x"></i></a>
                                        @endif
                                        @if (!empty($socials['youtube']))
                                            <a href="{{ $socials['youtube'] }}" target="_blank" rel="noopener" class="text-danger" title="YouTube"><i class="icon-base ti tabler-brand-youtube"></i></a>
                                        @endif
                                        @if (empty($socials))
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($lead->rating)
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="text-warning fw-bold small">★ {{ number_format($lead->rating, 1) }}</span>
                                            @if ($lead->review_count)
                                                <small class="text-muted">({{ $lead->review_count }})</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end small text-muted">
                                    {{ $lead->created_at?->diffForHumans() ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No leads extracted yet. Launch an extraction to discover leads!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trendData = @json($dailyTrend);
    const dates = trendData.map(d => d.date);
    const leadsSeries = trendData.map(d => d.leads);
    const jobsSeries = trendData.map(d => d.jobs);

    // 1. Daily Extraction Trend Area Spline Chart
    const trendOptions = {
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            fontFamily: 'Public Sans, sans-serif'
        },
        series: [
            { name: 'Leads Extracted', data: leadsSeries },
            { name: 'Extraction Jobs', data: jobsSeries }
        ],
        colors: ['#696cff', '#03c3ec'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2.5 },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 95, 100]
            }
        },
        xaxis: {
            categories: dates,
            labels: { style: { colors: '#a1acb8', fontSize: '12px' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: { style: { colors: '#a1acb8', fontSize: '12px' } }
        },
        grid: {
            borderColor: 'rgba(67, 89, 113, 0.08)',
            strokeDashArray: 4
        },
        tooltip: { theme: 'light' },
        legend: { position: 'top', horizontalAlign: 'right' }
    };

    const trendChart = new ApexCharts(document.querySelector("#leadTrendChart"), trendOptions);
    trendChart.render();

    // 2. Data Enrichment Mix Donut Chart
    const donutOptions = {
        chart: {
            type: 'donut',
            height: 240,
            fontFamily: 'Public Sans, sans-serif'
        },
        series: [
            {{ $totalEmails ?: 1 }},
            {{ $totalPhones ?: 1 }},
            {{ $totalWebsites ?: 1 }},
            {{ $totalSocials ?: 1 }}
        ],
        labels: ['Verified Emails', 'Phone Numbers', 'Websites', 'Social Profiles'],
        colors: ['#71dd37', '#03c3ec', '#ffab00', '#696cff'],
        stroke: { width: 2, colors: ['#fff'] },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { theme: 'light' },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Enriched',
                            formatter: () => '{{ number_format($totalLeads) }}'
                        }
                    }
                }
            }
        }
    };

    const donutChart = new ApexCharts(document.querySelector("#enrichmentDonutChart"), donutOptions);
    donutChart.render();
});
</script>
@endpush
