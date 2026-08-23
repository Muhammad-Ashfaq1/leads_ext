@extends('layouts.app')

@section('title', 'Extraction History')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-bottom py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-history me-1 text-primary"></i> Extraction History
                </h5>
                <span class="badge bg-label-primary">{{ $jobs->total() }} tasks</span>
            </div>
            <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                <i class="icon-base ti tabler-plus me-1"></i> New Extraction
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Job ID &amp; Query</th>
                    <th>Engine</th>
                    <th>Leads Extracted</th>
                    <th>Emails &amp; Websites</th>
                    <th>Status</th>
                    <th>Started</th>
                    <th class="pe-3 text-end">Export &amp; Leads</th>
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
                            <span class="text-muted small">/ {{ $job->limit }} max</span>
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

    @if ($jobs->hasPages())
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Showing {{ $jobs->firstItem() ?? 0 }} to {{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }} extraction jobs
                </div>
                <div>
                    {{ $jobs->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

