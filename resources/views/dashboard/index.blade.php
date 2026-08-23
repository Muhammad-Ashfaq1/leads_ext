@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <!-- Header Welcome Banner -->
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(105, 108, 255, 0.08) 0%, rgba(37, 185, 214, 0.06) 100%);">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold text-heading">
                            Welcome back, {{ $user->name }}! 👋
                        </h4>
                        <p class="text-muted mb-0">
                            @if ($isSuperAdmin)
                                You are logged in as <span class="badge bg-label-danger">Super Admin</span> with full platform visibility across all client tenants.
                            @elseif ($tenant)
                                Managing lead extractions for <strong class="text-primary">{{ $tenant->name }}</strong> ({{ ucfirst($tenant->plan) }} Plan).
                            @endif
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('extractor.index') }}" class="btn btn-primary">
                            <i class="icon-base ti tabler-player-play me-1"></i> Start Lead Extraction
                        </a>
                        <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary">
                            <i class="icon-base ti tabler-users-group me-1"></i> View All Leads
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isSuperAdmin && $platformStats)
        <!-- Super Admin Global Stats Row -->
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-label-danger-subtle">
                <div class="card-body py-3 px-4">
                    <div class="row g-3 text-center text-sm-start align-items-center">
                        <div class="col-6 col-sm-3 col-lg-2">
                            <span class="text-muted small text-uppercase fw-semibold">Active Tenants</span>
                            <h5 class="mb-0 fw-bold text-danger">{{ $platformStats['active_tenants'] }} / {{ $platformStats['total_tenants'] }}</h5>
                        </div>
                        <div class="col-6 col-sm-3 col-lg-2">
                            <span class="text-muted small text-uppercase fw-semibold">Platform Users</span>
                            <h5 class="mb-0 fw-bold text-danger">{{ $platformStats['total_users'] }}</h5>
                        </div>
                        <div class="col-6 col-sm-3 col-lg-3">
                            <span class="text-muted small text-uppercase fw-semibold">Global Leads Database</span>
                            <h5 class="mb-0 fw-bold text-danger">{{ number_format($platformStats['global_leads']) }}</h5>
                        </div>
                        <div class="col-6 col-sm-3 col-lg-2">
                            <span class="text-muted small text-uppercase fw-semibold">Global Extractions</span>
                            <h5 class="mb-0 fw-bold text-danger">{{ number_format($platformStats['global_jobs']) }}</h5>
                        </div>
                        <div class="col-12 col-lg-3 text-lg-end mt-2 mt-lg-0">
                            <a href="{{ route('tenants.index') }}" class="btn btn-sm btn-danger">
                                <i class="icon-base ti tabler-building me-1"></i> Manage SaaS Tenants
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 4 Main KPI Cards -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small text-uppercase">Total Leads Extracted</span>
                    <div class="avatar avatar-sm flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="icon-base ti tabler-users"></i>
                        </span>
                    </div>
                </div>
                <h3 class="mb-1 fw-bold text-heading">{{ number_format($totalLeads) }}</h3>
                @if ($tenant && $tenant->lead_quota > 0)
                    <div class="d-flex align-items-center justify-content-between text-muted small mt-2">
                        <span>Quota: {{ number_format($tenant->leads_extracted_count) }} / {{ number_format($tenant->lead_quota) }}</span>
                        <span>{{ round(($tenant->leads_extracted_count / $tenant->lead_quota) * 100) }}%</span>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, round(($tenant->leads_extracted_count / $tenant->lead_quota) * 100)) }}%"></div>
                    </div>
                @else
                    <span class="text-muted small">From {{ $totalJobs }} extraction tasks</span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small text-uppercase">Email Discovery Rate</span>
                    <div class="avatar avatar-sm flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="icon-base ti tabler-mail"></i>
                        </span>
                    </div>
                </div>
                <h3 class="mb-1 fw-bold text-heading">{{ $emailRate }}%</h3>
                <div class="text-muted small mt-2">
                    <span class="text-success fw-semibold">{{ number_format($totalEmails) }}</span> leads with verified email
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small text-uppercase">Phone Coverage</span>
                    <div class="avatar avatar-sm flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="icon-base ti tabler-phone"></i>
                        </span>
                    </div>
                </div>
                <h3 class="mb-1 fw-bold text-heading">{{ $phoneRate }}%</h3>
                <div class="text-muted small mt-2">
                    <span class="text-info fw-semibold">{{ number_format($totalPhones) }}</span> leads with direct phone
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small text-uppercase">Website Coverage</span>
                    <div class="avatar avatar-sm flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="icon-base ti tabler-world-www"></i>
                        </span>
                    </div>
                </div>
                <h3 class="mb-1 fw-bold text-heading">{{ $websiteRate }}%</h3>
                <div class="text-muted small mt-2">
                    <span class="text-warning fw-semibold">{{ number_format($totalWebsites) }}</span> business websites found
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Categories Widget -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-category me-1 text-primary"></i> Top Business Niches
                </h6>
                <a href="{{ route('leads.index') }}" class="btn btn-xs btn-link text-decoration-none">View All</a>
            </div>
            <div class="card-body p-3">
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

    <!-- Recent Extractions Table -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-history me-1 text-primary"></i> Recent Extraction Jobs
                </h6>
                <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-outline-primary">View All Jobs</a>
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

