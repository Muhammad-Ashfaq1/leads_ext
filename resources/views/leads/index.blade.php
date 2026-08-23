@extends('layouts.app')

@section('title', 'All Extracted Leads')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-bottom py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 fw-semibold text-heading">
                    <i class="icon-base ti tabler-users-group me-1 text-primary"></i> Extracted Leads Database
                </h5>
                <span class="badge bg-label-primary">{{ $leads->total() }} leads</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('leads.export.excel') }}" class="btn btn-sm btn-success text-white">
                    <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export All (Excel .xlsx)
                </a>
                <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i> Extract More Leads
                </a>
            </div>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="card-body border-bottom bg-light-subtle py-3 px-3 px-md-4">
        <form method="GET" action="{{ route('leads.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search name, phone, address..." value="{{ $filters['search'] }}">
                    </div>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Category: All</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected($filters['category'] === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="has_email" class="form-select form-select-sm">
                        <option value="">Email: All</option>
                        <option value="yes" @selected($filters['has_email'] === 'yes')>Has Email</option>
                        <option value="no" @selected($filters['has_email'] === 'no')>No Email</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="has_phone" class="form-select form-select-sm">
                        <option value="">Phone: All</option>
                        <option value="yes" @selected($filters['has_phone'] === 'yes')>Has Phone</option>
                        <option value="no" @selected($filters['has_phone'] === 'no')>No Phone</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="min_rating" class="form-select form-select-sm">
                        <option value="">Rating: All</option>
                        <option value="4.5" @selected($filters['min_rating'] === '4.5')>★ 4.5+</option>
                        <option value="4.0" @selected($filters['min_rating'] === '4.0')>★ 4.0+</option>
                        <option value="3.5" @selected($filters['min_rating'] === '3.5')>★ 3.5+</option>
                    </select>
                </div>

                <div class="col-12 col-sm-4 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    @if (array_filter($filters))
                        <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="icon-base ti tabler-x"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 40px;">
                        <input class="form-check-input" type="checkbox" id="masterTableCheckbox">
                    </th>
                    <th>Business Name</th>
                    <th>Category</th>
                    <th>Contact Info</th>
                    <th>Rating</th>
                    <th>Address</th>
                    <th class="pe-3 text-end">Links</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td class="ps-3">
                            <input class="form-check-input lead-row-checkbox" type="checkbox" value="{{ $lead->id }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($lead->avatar_url)
                                    <img src="{{ $lead->avatar_url }}" alt="" class="rounded" style="width: 32px; height: 32px; object-fit: cover;" onerror="this.style.display='none'">
                                @endif
                                <div>
                                    <div class="fw-semibold text-heading small">{{ $lead->business_name }}</div>
                                    <small class="text-muted">{{ $lead->source }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($lead->category)
                                <span class="badge bg-label-secondary small" style="font-size: 0.72rem;">{{ $lead->category }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="small">
                                @if ($lead->phone)
                                    <div><i class="icon-base ti tabler-phone me-1 text-success"></i><a href="tel:{{ $lead->phone }}" class="text-body">{{ $lead->phone }}</a></div>
                                @endif
                                @if (!empty($lead->emails))
                                    <div><i class="icon-base ti tabler-mail me-1 text-primary"></i><a href="mailto:{{ $lead->emails[0] }}" class="text-body">{{ $lead->emails[0] }}</a></div>
                                @endif
                                @if (!$lead->phone && empty($lead->emails))
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($lead->rating)
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-warning fw-bold small">★ {{ number_format($lead->rating, 1) }}</span>
                                    @if ($lead->review_count)
                                        <small class="text-muted">({{ number_format($lead->review_count) }})</small>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="small text-muted text-truncate d-inline-block" style="max-width: 200px;" title="{{ $lead->address }}">
                                {{ $lead->address ?: '—' }}
                            </span>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="d-flex justify-content-end gap-1">
                                @if ($lead->google_maps_url)
                                    <a href="{{ $lead->google_maps_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-secondary" title="Google Maps">
                                        <i class="icon-base ti tabler-map-pin"></i>
                                    </a>
                                @endif
                                @if ($lead->website)
                                    <a href="{{ $lead->website }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary" title="Website">
                                        <i class="icon-base ti tabler-world-www"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="icon-base ti tabler-database-off display-6 mb-2"></i>
                            <h6>No extracted leads found</h6>
                            <p class="small mb-3">Adjust your search filters or start a new extraction.</p>
                            <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">Start New Extraction</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($leads->hasPages())
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }} leads
                </div>
                <div>
                    {{ $leads->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
