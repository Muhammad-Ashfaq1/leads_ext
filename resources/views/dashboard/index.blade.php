@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <!-- Top Glass Intro Banner -->
    <div class="col-12">
        <div class="pos-glass-card pos-tone-primary">
            <div class="pos-glass-intro">
                <div class="pos-glass-intro-copy">
                    <h4 class="pos-glass-intro-title">
                        Welcome back, {{ $user->name }}
                    </h4>
                    <p class="pos-glass-intro-subtitle">
                        @if ($isSuperAdmin)
                            Central administrator workspace · Full platform visibility across {{ $platformStats['total_tenants'] ?? 0 }} organizations · {{ number_format($platformStats['global_leads'] ?? 0) }} total leads in platform
                        @elseif ($tenant)
                            {{ $tenant->name }} ({{ ucfirst($tenant->plan) }} Plan) ·
                            {{ number_format($totalLeads) }} leads extracted across {{ number_format($totalJobs) }} search tasks ·
                            {{ number_format($totalEmails) }} verified emails discovered
                        @endif
                    </p>
                </div>
                <div class="pos-glass-intro-actions d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                        <i class="icon-base ti tabler-plus me-1" aria-hidden="true"></i> Start Extraction
                    </a>
                    <a href="{{ route('leads.index') }}" class="btn btn-sm btn-label-secondary">
                        <i class="icon-base ti tabler-users-group me-1" aria-hidden="true"></i> View Leads
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
                        <h5 class="pos-glass-intro-title text-danger">Platform Operations</h5>
                        <p class="pos-glass-intro-subtitle">
                            {{ $platformStats['active_tenants'] }} active organizations · {{ $platformStats['total_users'] }} registered team members · Global quota utilization active
                        </p>
                    </div>
                    <div class="pos-glass-intro-actions">
                        <a href="{{ route('tenants.index') }}" class="btn btn-sm btn-danger">
                            <i class="icon-base ti tabler-building me-1"></i> Manage SaaS Tenants
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 5 Glass Stat Cards (Matching POS) -->
    <div class="col-xl col-md-4 col-sm-6">
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

    <div class="col-xl col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-success h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-mail" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Email Discovery</h6>
                </div>
                <p class="pos-stat-value">{{ $emailRate }}%</p>
                <p class="pos-stat-desc mb-0">{{ number_format($totalEmails) }} verified emails</p>
                <div class="pos-stat-note">
                    <span class="text-success fw-medium">Direct contact ready</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-info h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-phone" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Phone Coverage</h6>
                </div>
                <p class="pos-stat-value">{{ $phoneRate }}%</p>
                <p class="pos-stat-desc mb-0">{{ number_format($totalPhones) }} direct numbers</p>
                <div class="pos-stat-note">
                    <span class="text-info fw-medium">Phone verified</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-warning h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-world-www" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Websites</h6>
                </div>
                <p class="pos-stat-value">{{ $websiteRate }}%</p>
                <p class="pos-stat-desc mb-0">{{ number_format($totalWebsites) }} domains found</p>
                <div class="pos-stat-note">
                    <span class="text-warning fw-medium">Online presence</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-4 col-sm-6">
        <div class="pos-glass-card pos-tone-secondary h-100">
            <div class="pos-stat-body">
                <div class="pos-stat-head">
                    <span class="pos-stat-icon"><i class="icon-base ti tabler-building-store" aria-hidden="true"></i></span>
                    <h6 class="pos-stat-label">Scanned</h6>
                </div>
                <p class="pos-stat-value">{{ number_format($totalBusinessesSeen) }}</p>
                <p class="pos-stat-desc mb-0">Total places processed</p>
                <div class="pos-stat-note">
                    <span>{{ $totalBusinessesSeen > 0 ? round(($totalLeads / max(1, $totalBusinessesSeen)) * 100) : 100 }}% match rate</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Business Niches -->
    <div class="col-12 col-lg-4">
        <div class="pos-glass-card pos-tone-primary h-100">
            <div class="pos-glass-intro border-bottom">
                <div class="pos-glass-intro-copy">
                    <h5 class="pos-glass-intro-title">
                        <i class="icon-base ti tabler-category me-1 text-primary"></i> Top Business Niches
                    </h5>
                    <p class="pos-glass-intro-subtitle">Most discovered categories</p>
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
                                    <span class="fw-medium small text-truncate" style="max-width: 200px;">{{ $cat->category }}</span>
                                    <span class="badge bg-label-primary">{{ number_format($cat->count) }} leads</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, round(($cat->count / max(1, $totalLeads)) * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Extraction Tasks Table -->
    <div class="col-12 col-lg-8">
        <div class="pos-glass-card pos-tone-info h-100">
            <div class="pos-glass-intro border-bottom">
                <div class="pos-glass-intro-copy">
                    <h5 class="pos-glass-intro-title">
                        <i class="icon-base ti tabler-history me-1 text-info"></i> Recent Extraction Jobs
                    </h5>
                    <p class="pos-glass-intro-subtitle">Live status &amp; lead export</p>
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
                                        <span class="badge bg-label-info" style="font-size: 0.7rem;">Google API</span>
                                    @else
                                        <span class="badge bg-label-secondary" style="font-size: 0.7rem;">Browser</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-heading">{{ $job->leads_extracted }}</span>
                                    <small class="text-muted">/ {{ $job->limit }}</small>
                                </td>
                                <td>
                                    @if ($job->status === 'completed')
                                        <span class="badge bg-label-success" style="font-size: 0.7rem;">Completed</span>
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
@endsection
