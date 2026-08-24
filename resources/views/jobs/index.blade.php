@extends('layouts.app')

@section('title', 'Extraction History')

@section('content')
<div class="pos-glass-card pos-tone-info mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-history me-1 text-info"></i> Extraction History
            </h4>
            <p class="pos-glass-intro-subtitle">
                Audit log of all extraction tasks, real-time status, and instant Excel re-exports.
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex align-items-center gap-2">
            <span class="pos-glass-pill pos-tone-info">
                <i class="icon-base ti tabler-list-check me-1"></i> {{ $jobs->total() }} tasks
            </span>
            <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                <i class="icon-base ti tabler-plus me-1"></i> New Extraction
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Search Prompt &amp; Task ID</th>
                    <th>Engine</th>
                    <th>Leads Extracted</th>
                    <th>Emails &amp; Websites</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="pe-3 text-end">Export &amp; Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold text-heading small">{{ $job->prompt }}</div>
                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">{{ substr($job->uuid, 0, 13) }}...</small>
                        </td>
                        <td>
                            @if ($job->mode === 'google_api')
                                <span class="badge bg-label-info" style="font-size: 0.72rem;">Google API</span>
                            @else
                                <span class="badge bg-label-secondary" style="font-size: 0.72rem;">Browser</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-heading">{{ $job->leads_extracted }}</span>
                            <span class="text-muted small">/ {{ $job->limit }}</span>
                        </td>
                        <td>
                            <div class="small">
                                <span class="text-success"><i class="icon-base ti tabler-mail me-1"></i>{{ $job->emails_found }} emails</span>
                                <br>
                                <span class="text-warning"><i class="icon-base ti tabler-world-www me-1"></i>{{ $job->websites_found }} sites</span>
                            </div>
                        </td>
                        <td>
                            @if ($job->status === 'completed')
                                <span class="badge bg-label-success" style="font-size: 0.72rem;">Completed</span>
                            @elseif ($job->status === 'extracting')
                                <span class="badge bg-label-primary" style="font-size: 0.72rem;">Extracting</span>
                            @elseif ($job->status === 'error')
                                <span class="badge bg-label-danger" style="font-size: 0.72rem;">Error</span>
                            @else
                                <span class="badge bg-label-secondary" style="font-size: 0.72rem;">{{ ucfirst($job->status) }}</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $job->created_at?->format('M d, Y H:i') ?? '—' }}
                        </td>
                        <td class="pe-3 text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('extractor.job.export', $job->uuid) }}?format=excel" class="btn btn-sm btn-outline-success" title="Download Excel (.xlsx)">
                                    <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Excel
                                </a>
                                <a href="{{ route('leads.index', ['job_id' => $job->id]) }}" class="btn btn-sm btn-outline-primary" title="View Leads">
                                    <i class="icon-base ti tabler-eye me-1"></i> Leads
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="icon-base ti tabler-history-off display-6 mb-2"></i>
                            <h6>No extraction history yet</h6>
                            <p class="small mb-3">Run your first extraction to see historical results and performance.</p>
                            <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">Start Extraction</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($jobs->total() > 0)
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">
                        Showing <span class="fw-semibold text-heading">{{ $jobs->firstItem() }}</span> to <span class="fw-semibold text-heading">{{ $jobs->lastItem() }}</span> of <span class="fw-semibold text-heading">{{ number_format($jobs->total()) }}</span> tasks
                    </small>
                    <div class="d-inline-flex align-items-center ms-3">
                        <label for="perPageJobs" class="small text-muted me-1 text-nowrap d-none d-sm-inline">Show:</label>
                        <select id="perPageJobs" class="form-select form-select-sm" style="width: auto;" onchange="window.location.href=this.value">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" @selected($jobs->perPage() == $size)>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    {{ $jobs->links('vendor.pagination.pos') }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
