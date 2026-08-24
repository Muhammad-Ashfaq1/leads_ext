@extends('layouts.app')

@section('title', 'All Extracted Leads')

@section('content')
<div class="pos-glass-card pos-tone-primary mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-users-group me-1 text-primary"></i> Extracted Leads Database
            </h4>
            <p class="pos-glass-intro-subtitle">
                Comprehensive directory of all verified business leads discovered across your extraction runs.
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex flex-wrap align-items-center gap-2">
            <span class="pos-glass-pill pos-tone-primary">
                <i class="icon-base ti tabler-database me-1"></i> {{ number_format($leads->total()) }} leads
            </span>
            <a href="{{ route('leads.export.excel') }}" class="btn btn-sm btn-success text-white">
                <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export Excel (.xlsx)
            </a>
            <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                <i class="icon-base ti tabler-plus me-1"></i> Extract Leads
            </a>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="p-3 border-bottom bg-light-subtle">
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
                    <th>Social Profiles</th>
                    <th>Rating</th>
                    <th>Address</th>
                    <th class="pe-3 text-end">Links</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    @php
                        $emails = is_array($lead->emails) ? $lead->emails : [];
                        $firstEmail = $emails[0] ?? null;
                        $socials = is_array($lead->social_links) ? $lead->social_links : [];
                        $vStatus = is_array($lead->email_verification_status) ? $lead->email_verification_status : [];
                        $isValidEmail = $firstEmail && isset($vStatus[$firstEmail]['is_valid']) && $vStatus[$firstEmail]['is_valid'];
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <input class="form-check-input row-select-checkbox" type="checkbox" value="{{ $lead->id }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($lead->avatar_url)
                                    <img src="{{ $lead->avatar_url }}" alt="{{ $lead->business_name }}" class="rounded" style="width: 32px; height: 32px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="lead-avatar-fallback rounded align-items-center justify-content-center bg-primary text-white fw-bold" style="display: none; width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($lead->business_name ?: 'B', 0, 1)) }}
                                    </div>
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center bg-label-primary text-primary fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($lead->business_name ?: 'B', 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold text-heading small">{{ $lead->business_name }}</div>
                                    @if ($lead->source)
                                        <span class="badge bg-label-secondary" style="font-size: 0.65rem;">{{ $lead->source }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-label-primary small">{{ $lead->category ?: 'Business' }}</span>
                        </td>
                        <td>
                            <div class="small">
                                @if ($firstEmail)
                                    <div class="d-flex align-items-center gap-1 text-truncate" style="max-width: 190px;">
                                        <a href="mailto:{{ $firstEmail }}" class="text-success text-decoration-none" title="{{ $firstEmail }}">
                                            <i class="icon-base ti tabler-mail me-1"></i>{{ $firstEmail }}
                                        </a>
                                        @if ($isValidEmail)
                                            <span class="badge bg-label-success p-1" title="MX Validated"><i class="icon-base ti tabler-check"></i></span>
                                        @endif
                                    </div>
                                @endif
                                @if ($lead->phone)
                                    <div class="text-truncate" style="max-width: 190px;">
                                        <a href="tel:{{ $lead->phone }}" class="text-info text-decoration-none">
                                            <i class="icon-base ti tabler-phone me-1"></i>{{ $lead->phone }}
                                        </a>
                                    </div>
                                @endif
                                @if (! $firstEmail && ! $lead->phone)
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                @if (!empty($socials['linkedin']))
                                    <a href="{{ $socials['linkedin'] }}" target="_blank" rel="noopener" class="text-primary fs-6" title="LinkedIn"><i class="icon-base ti tabler-brand-linkedin"></i></a>
                                @endif
                                @if (!empty($socials['facebook']))
                                    <a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener" class="text-info fs-6" title="Facebook"><i class="icon-base ti tabler-brand-facebook"></i></a>
                                @endif
                                @if (!empty($socials['instagram']))
                                    <a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener" class="text-danger fs-6" title="Instagram"><i class="icon-base ti tabler-brand-instagram"></i></a>
                                @endif
                                @if (!empty($socials['twitter']))
                                    <a href="{{ $socials['twitter'] }}" target="_blank" rel="noopener" class="text-dark fs-6" title="Twitter / X"><i class="icon-base ti tabler-brand-x"></i></a>
                                @endif
                                @if (!empty($socials['youtube']))
                                    <a href="{{ $socials['youtube'] }}" target="_blank" rel="noopener" class="text-danger fs-6" title="YouTube"><i class="icon-base ti tabler-brand-youtube"></i></a>
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
                        <td>
                            <div class="small text-muted text-truncate" style="max-width: 190px;" title="{{ $lead->address }}">
                                {{ $lead->address ?: '—' }}
                            </div>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="d-flex justify-content-end gap-1">
                                @if ($lead->website)
                                    <a href="{{ $lead->website }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-info" title="Visit Website">
                                        <i class="icon-base ti tabler-world"></i>
                                    </a>
                                @endif
                                @if ($lead->google_maps_url)
                                    <a href="{{ $lead->google_maps_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-danger" title="Open Google Maps">
                                        <i class="icon-base ti tabler-map-pin"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="icon-base ti tabler-users-minus display-6 mb-2"></i>
                            <h6>No leads matched your criteria</h6>
                            <p class="small mb-3">Try adjusting your search filters or start a new extraction job.</p>
                            <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">Start Extraction</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($leads->total() > 0)
        <div class="card-footer border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">
                        Showing <span class="fw-semibold text-heading">{{ $leads->firstItem() }}</span> to <span class="fw-semibold text-heading">{{ $leads->lastItem() }}</span> of <span class="fw-semibold text-heading">{{ number_format($leads->total()) }}</span> leads
                    </small>
                    <div class="d-inline-flex align-items-center ms-3">
                        <label for="perPageSelect" class="small text-muted me-1 text-nowrap d-none d-sm-inline">Show:</label>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="window.location.href=this.value">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" @selected($leads->perPage() == $size)>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    {{ $leads->links('vendor.pagination.pos') }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
