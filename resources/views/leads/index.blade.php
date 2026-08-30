@extends('layouts.app')

@section('title', 'Prospects Directory')

@section('content')
<div id="leadsMainContainer">
<div class="pos-glass-card pos-tone-primary mb-4" id="leadsTableCard">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-users-group me-1 text-primary"></i> Prospects Directory
            </h4>
            <p class="pos-glass-intro-subtitle">
                Comprehensive directory of all verified business prospects and leads discovered across your outreach campaigns.
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex flex-wrap align-items-center gap-2">
            <span class="pos-glass-pill pos-tone-primary">
                <i class="icon-base ti tabler-address-book me-1"></i> {{ number_format($leads->total()) }} prospects
            </span>
            <a href="{{ route('leads.export.excel') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="btn btn-sm btn-success text-white">
                <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export
            </a>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="p-3 border-bottom bg-light-subtle">
        <form id="leadsFilterForm" method="GET" action="{{ route('leads.index') }}">
            @if (!empty($filters['job_id']))
                <input type="hidden" name="job_id" value="{{ $filters['job_id'] }}">
            @endif
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search name, phone, email, city..." value="{{ $filters['search'] }}">
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
                        <option value="verified" @selected(($filters['has_email'] ?? '') === 'verified' || ($filters['verified_email'] ?? '') === 'yes')>Verified Email (MX Valid)</option>
                        <option value="yes" @selected(($filters['has_email'] ?? '') === 'yes')>Has Email (Any)</option>
                        <option value="no" @selected(($filters['has_email'] ?? '') === 'no')>No Email (Without Email)</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="has_website" class="form-select form-select-sm">
                        <option value="">Website: All</option>
                        <option value="yes" @selected($filters['has_website'] === 'yes')>Has Website</option>
                        <option value="no" @selected($filters['has_website'] === 'no')>Without Website (No Site)</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-1">
                    <select name="has_phone" class="form-select form-select-sm">
                        <option value="">Phone: All</option>
                        <option value="yes" @selected($filters['has_phone'] === 'yes')>Has Phone</option>
                        <option value="no" @selected($filters['has_phone'] === 'no')>No Phone</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="sort" class="form-select form-select-sm">
                        <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>Sort: Newest First</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Sort: Oldest First</option>
                        <option value="rating_desc" @selected(($filters['sort'] ?? '') === 'rating_desc')>Sort: Highest Rating</option>
                        <option value="reviews_desc" @selected(($filters['sort'] ?? '') === 'reviews_desc')>Sort: Most Reviews</option>
                        <option value="name_asc" @selected(($filters['sort'] ?? '') === 'name_asc')>Sort: Name (A-Z)</option>
                        <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>Sort: Name (Z-A)</option>
                    </select>
                </div>
            </div>

            <!-- Secondary Filter Row -->
            <div class="row g-2 align-items-center mt-1">
                <div class="col-6 col-sm-4 col-md-2">
                    <select name="min_rating" class="form-select form-select-sm">
                        <option value="">Rating: All</option>
                        <option value="4.5" @selected(($filters['min_rating'] ?? '') === '4.5')>★ 4.5+ (Top Rated)</option>
                        <option value="4.0" @selected(($filters['min_rating'] ?? '') === '4.0')>★ 4.0+</option>
                        <option value="3.5" @selected(($filters['min_rating'] ?? '') === '3.5')>★ 3.5+</option>
                        <option value="3.0" @selected(($filters['min_rating'] ?? '') === '3.0')>★ 3.0+</option>
                        <option value="unrated" @selected(($filters['min_rating'] ?? '') === 'unrated')>Unrated (0 Rating)</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="min_reviews" class="form-select form-select-sm">
                        <option value="">Reviews: All</option>
                        <option value="50" @selected(($filters['min_reviews'] ?? '') === '50')>50+ Reviews</option>
                        <option value="10" @selected(($filters['min_reviews'] ?? '') === '10')>10+ Reviews</option>
                        <option value="1" @selected(($filters['min_reviews'] ?? '') === '1')>Has Reviews (1+)</option>
                        <option value="0" @selected(($filters['min_reviews'] ?? '') === '0')>0 Reviews (No Reviews)</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="has_social" class="form-select form-select-sm">
                        <option value="">Socials: All</option>
                        <option value="yes" @selected(($filters['has_social'] ?? '') === 'yes')>Has Social Profiles</option>
                        <option value="no" @selected(($filters['has_social'] ?? '') === 'no')>No Social Profiles</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Status: All</option>
                        <option value="saved" @selected(($filters['status'] ?? '') === 'saved')>Saved Leads</option>
                        <option value="discarded" @selected(($filters['status'] ?? '') === 'discarded')>Discarded Leads</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2">
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="10" @selected(($filters['per_page'] ?? 10) == 10)>10 per page</option>
                        <option value="25" @selected(($filters['per_page'] ?? 10) == 25)>25 per page</option>
                        <option value="50" @selected(($filters['per_page'] ?? 10) == 50)>50 per page</option>
                        <option value="100" @selected(($filters['per_page'] ?? 10) == 100)>100 per page</option>
                        <option value="250" @selected(($filters['per_page'] ?? 10) == 250)>250 per page</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                        <i class="icon-base ti tabler-filter me-1"></i>Apply
                    </button>
                    @if (array_filter($filters))
                        <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset All Filters">
                            <i class="icon-base ti tabler-rotate"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- Quick filter chips / shortcut pills -->
        <div class="d-flex flex-wrap align-items-center gap-1.5 mt-2 pt-2 border-top">
            <small class="text-muted fw-semibold me-1"><i class="icon-base ti tabler-filter me-1"></i>Quick Filters:</small>
            @if (!empty($filters['job_id']))
                <a href="{{ route('leads.index', request()->except(['job_id', 'page'])) }}" class="badge bg-primary text-white leads-quick-chip text-decoration-none py-1 px-2" title="Filtering by Task #{{ $filters['job_id'] }} (Click to clear)">
                    <i class="icon-base ti tabler-history me-1"></i>Task #{{ $filters['job_id'] }} <i class="icon-base ti tabler-x ms-1"></i>
                </a>
            @endif
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_email' => 'verified'])) }}" class="badge {{ ($filters['has_email'] ?? '') === 'verified' || ($filters['verified_email'] ?? '') === 'yes' ? 'bg-primary text-white' : 'bg-label-primary' }} leads-quick-chip text-decoration-none py-1 px-2" title="Filter leads with MX verified email">
                <i class="icon-base ti tabler-rosette-discount-check-filled me-1"></i>Verified Email
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_email' => 'yes'])) }}" class="badge {{ ($filters['has_email'] ?? '') === 'yes' ? 'bg-success text-white' : 'bg-label-success' }} leads-quick-chip text-decoration-none py-1 px-2" title="Filter leads with email">
                <i class="icon-base ti tabler-mail me-1"></i>Has Email
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_email' => 'no'])) }}" class="badge {{ ($filters['has_email'] ?? '') === 'no' ? 'bg-danger text-white' : 'bg-label-danger' }} leads-quick-chip text-decoration-none py-1 px-2" title="Filter leads with NO email (easy cleanup)">
                <i class="icon-base ti tabler-mail-off me-1"></i>No Email
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_website' => 'no'])) }}" class="badge {{ ($filters['has_website'] ?? '') === 'no' ? 'bg-danger text-white' : 'bg-label-danger' }} leads-quick-chip text-decoration-none py-1 px-2" title="Filter leads with no website">
                <i class="icon-base ti tabler-world-off me-1"></i>Without Website
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_website' => 'yes'])) }}" class="badge {{ ($filters['has_website'] ?? '') === 'yes' ? 'bg-info text-white' : 'bg-label-info' }} leads-quick-chip text-decoration-none py-1 px-2">
                <i class="icon-base ti tabler-world me-1"></i>Has Website
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_phone' => 'yes'])) }}" class="badge {{ ($filters['has_phone'] ?? '') === 'yes' ? 'bg-primary text-white' : 'bg-label-primary' }} leads-quick-chip text-decoration-none py-1 px-2">
                <i class="icon-base ti tabler-phone me-1"></i>Has Phone
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_phone' => 'no'])) }}" class="badge {{ ($filters['has_phone'] ?? '') === 'no' ? 'bg-secondary text-white' : 'bg-label-secondary' }} leads-quick-chip text-decoration-none py-1 px-2" title="Filter leads with no phone">
                <i class="icon-base ti tabler-phone-off me-1"></i>No Phone
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['has_social' => 'yes'])) }}" class="badge {{ ($filters['has_social'] ?? '') === 'yes' ? 'bg-info text-white' : 'bg-label-info' }} leads-quick-chip text-decoration-none py-1 px-2">
                <i class="icon-base ti tabler-brand-linkedin me-1"></i>Has Social
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['min_rating' => '4.5'])) }}" class="badge {{ ($filters['min_rating'] ?? '') === '4.5' ? 'bg-warning text-white' : 'bg-label-warning' }} leads-quick-chip text-decoration-none py-1 px-2">
                <i class="icon-base ti tabler-star me-1"></i>Top Rated (4.5+ ★)
            </a>
            <a href="{{ route('leads.index', array_merge(request()->except(['page']), ['min_reviews' => '50'])) }}" class="badge {{ ($filters['min_reviews'] ?? '') === '50' ? 'bg-primary text-white' : 'bg-label-primary' }} leads-quick-chip text-decoration-none py-1 px-2">
                <i class="icon-base ti tabler-messages me-1"></i>50+ Reviews
            </a>
            @if (array_filter($filters))
                <a href="{{ route('leads.index') }}" class="badge bg-label-secondary leads-quick-chip text-decoration-none py-1 px-2 ms-auto">
                    <i class="icon-base ti tabler-rotate me-1"></i>Reset All
                </a>
            @endif
        </div>
    </div>

    <!-- Bulk Actions Toolbar (Active when rows checked) -->
    <div class="p-2 px-3 border-bottom bg-primary-subtle d-none" id="leadsBulkBar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary text-white fs-6 px-3 py-1" id="leadsBulkCount">0 selected</span>
                <button type="button" class="btn btn-xs btn-outline-primary" id="selectAllPageBtn">Select All on Page ({{ count($leads) }})</button>
                <button type="button" class="btn btn-xs btn-link text-secondary text-decoration-none p-0" id="deselectAllBtn">Deselect All</button>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary" id="sendEmailBulkBtn" title="Send Email to selected leads">
                    <i class="icon-base ti tabler-send me-1"></i>Send Email
                </button>
                <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="icon-base ti tabler-download me-1"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><button type="button" class="dropdown-item" id="exportSelectedExcelBtn"><i class="icon-base ti tabler-file-spreadsheet me-2 text-success"></i>Excel (.xlsx)</button></li>
                        <li><button type="button" class="dropdown-item" id="exportSelectedCsvBtn"><i class="icon-base ti tabler-file-text me-2 text-info"></i>CSV (.csv)</button></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-label-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="icon-base ti tabler-copy me-1"></i>Copy
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><button type="button" class="dropdown-item" id="copySelectedEmailsBtn"><i class="icon-base ti tabler-mail me-2"></i>Emails</button></li>
                        <li><button type="button" class="dropdown-item" id="copySelectedPhonesBtn"><i class="icon-base ti tabler-phone me-2"></i>Phones</button></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning" id="discardSelectedBtn" title="Mark selected leads as discarded">
                    <i class="icon-base ti tabler-archive me-1"></i>Discard
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="deleteSelectedBtn" title="Delete selected leads from database">
                    <i class="icon-base ti tabler-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Table of Extracted Leads -->
    <div class="table-responsive leads-table-responsive">
        <table class="table table-hover align-middle mb-0" id="leadsTable">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 40px;">
                        <input class="form-check-input" type="checkbox" id="masterTableCheckbox" style="cursor: pointer;" title="Select / Deselect all on this page">
                    </th>
                    <th><i class="icon-base ti tabler-building me-1 text-primary"></i> Business Name</th>
                    <th><i class="icon-base ti tabler-category me-1 text-primary"></i> Category</th>
                    <th><i class="icon-base ti tabler-mail-opened me-1 text-success"></i> Contact Info</th>
                    <th><i class="icon-base ti tabler-brand-linkedin me-1 text-info"></i> Social Profiles</th>
                    <th><i class="icon-base ti tabler-star me-1 text-warning"></i> Rating</th>
                    <th><i class="icon-base ti tabler-map-pin me-1 text-danger"></i> Address</th>
                    <th class="pe-3 text-end"><i class="icon-base ti tabler-link me-1 text-muted"></i> Links</th>
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
                    <tr data-id="{{ $lead->id }}" style="cursor: pointer;">
                        <td class="ps-3">
                            <input class="form-check-input row-select-checkbox" type="checkbox" value="{{ $lead->id }}" style="cursor: pointer;" data-email="{{ $firstEmail }}" data-phone="{{ $lead->phone }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($lead->avatar_url)
                                    <img src="{{ $lead->avatar_url }}" alt="{{ $lead->business_name }}" class="rounded-2 flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover; border: 1px solid rgba(0,0,0,0.08);" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="user-avatar-badge rounded-2 align-items-center justify-content-center bg-label-primary text-primary fw-bold flex-shrink-0" style="display: none; width: 36px; height: 36px; font-size: 0.9rem;">
                                        {{ strtoupper(substr($lead->business_name ?: 'B', 0, 1)) }}
                                    </div>
                                @else
                                    <div class="user-avatar-badge rounded-2 d-flex align-items-center justify-content-center bg-label-primary text-primary fw-bold flex-shrink-0" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                        {{ strtoupper(substr($lead->business_name ?: 'B', 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="fw-semibold text-heading small text-truncate" style="max-width: 210px;" title="{{ $lead->business_name }}">{{ $lead->business_name }}</div>
                                    <small class="text-muted text-truncate d-block" style="max-width: 210px;">{{ $lead->city ?: ($lead->category ?: 'Business') }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-label-primary small">{{ $lead->category ?: 'Business' }}</span>
                        </td>
                        <td>
                            <div class="small">
                                @if ($firstEmail)
                                    <div class="d-flex align-items-center gap-1 text-truncate" style="max-width: 200px;">
                                        <a href="mailto:{{ $firstEmail }}" class="text-success text-decoration-none fw-medium text-truncate" title="{{ $firstEmail }}">
                                            <i class="icon-base ti tabler-mail me-1"></i>{{ $firstEmail }}
                                        </a>
                                        @if ($isValidEmail)
                                            <span class="badge rounded-pill px-1.5 py-0 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.65rem; height: 18px; background-color: rgba(29, 155, 240, 0.12); color: #1d9bf0; border: 1px solid rgba(29, 155, 240, 0.25);" data-bs-toggle="tooltip" data-bs-placement="top" title="Verified Deliverable Email (Passed DNS & MX Validation)">
                                                <i class="icon-base ti tabler-rosette-discount-check-filled" style="font-size: 0.85rem; color: #1d9bf0;"></i>
                                                <span style="font-size: 0.62rem; font-weight: 700;">Verified</span>
                                            </span>
                                        @else
                                            <span class="badge bg-label-secondary rounded-pill px-1 py-0 d-inline-flex align-items-center flex-shrink-0" style="font-size: 0.65rem; height: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" title="Email Contact Found">
                                                <i class="icon-base ti tabler-check" style="font-size: 0.7rem;"></i>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                @if ($lead->phone)
                                    <div class="text-truncate" style="max-width: 200px;">
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
                            <div class="pos-lead-actions d-inline-flex align-items-center gap-1">
                                @if (empty($lead->website))
                                    <button type="button" class="btn btn-sm btn-icon {{ $lead->generated_website_content ? 'btn-label-success' : 'btn-label-warning' }} rounded-pill btn-generate-demo-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $lead->generated_website_content ? 'AI Demo Website Ready (Click to Regenerate)' : 'Generate AI Demo Website' }}" onclick="generateDemo({{ $lead->id }})" id="btn-demo-{{ $lead->id }}">
                                        <i class="icon-base ti tabler-sparkles"></i>
                                    </button>
                                    @if ($lead->uuid && $lead->generated_website_content)
                                        <a href="{{ route('leads.preview', $lead->uuid) }}" target="_blank" rel="noopener" class="btn btn-sm btn-icon btn-label-primary rounded-pill" data-bs-toggle="tooltip" data-bs-placement="top" title="View Spec Landing Page">
                                            <i class="icon-base ti tabler-external-link"></i>
                                        </a>
                                    @endif
                                @endif
                                @if ($firstEmail)
                                    <button type="button" class="btn btn-sm btn-icon btn-label-primary rounded-pill btn-send-single-email" data-bs-toggle="tooltip" data-bs-placement="top" title="Send Outreach Email ({{ $firstEmail }})" data-id="{{ $lead->id }}" data-email="{{ $firstEmail }}" data-name="{{ $lead->business_name }}" data-category="{{ $lead->category }}" data-city="{{ $lead->city }}" data-website="{{ $lead->website }}" data-phone="{{ $lead->phone }}">
                                        <i class="icon-base ti tabler-send"></i>
                                    </button>
                                @endif
                                @if ($lead->website)
                                    <a href="{{ $lead->website }}" target="_blank" rel="noopener" class="btn btn-sm btn-icon btn-label-info rounded-pill" data-bs-toggle="tooltip" data-bs-placement="top" title="Visit Website">
                                        <i class="icon-base ti tabler-world"></i>
                                    </a>
                                @endif
                                @if ($lead->google_maps_url)
                                    <a href="{{ $lead->google_maps_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-icon btn-label-danger rounded-pill" data-bs-toggle="tooltip" data-bs-placement="top" title="View Location">
                                        <i class="icon-base ti tabler-map-pin"></i>
                                    </a>
                                @endif

                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if (empty($lead->website))
                                            <li>
                                                <button type="button" class="dropdown-item" onClick="generateDemo({{ $lead->id }})">
                                                    <i class="icon-base ti tabler-sparkles me-2 text-warning"></i>✨ Generate AI Demo
                                                </button>
                                            </li>
                                            @if ($lead->uuid && $lead->generated_website_content)
                                                <li>
                                                    <a href="{{ route('leads.preview', $lead->uuid) }}" target="_blank" class="dropdown-item text-primary fw-semibold" id="dropdown-preview-{{ $lead->id }}">
                                                        <i class="icon-base ti tabler-world-www me-2"></i>🌐 View Spec Website
                                                    </a>
                                                </li>
                                            @endif
                                        @endif
                                        @if ($firstEmail)
                                            <li>
                                                <button type="button" class="dropdown-item btn-send-single-email" data-id="{{ $lead->id }}" data-email="{{ $firstEmail }}" data-name="{{ $lead->business_name }}" data-category="{{ $lead->category }}" data-city="{{ $lead->city }}" data-website="{{ $lead->website }}" data-phone="{{ $lead->phone }}">
                                                    <i class="icon-base ti tabler-send me-2 text-primary"></i>Send Outreach Email
                                                </button>
                                            </li>
                                        @endif
                                        @if ($lead->website)
                                            <li>
                                                <a href="{{ $lead->website }}" target="_blank" rel="noopener" class="dropdown-item">
                                                    <i class="icon-base ti tabler-world me-2 text-secondary"></i>Visit Current Website
                                                </a>
                                            </li>
                                        @endif
                                        @if ($lead->google_maps_url)
                                            <li>
                                                <a href="{{ $lead->google_maps_url }}" target="_blank" rel="noopener" class="dropdown-item">
                                                    <i class="icon-base ti tabler-map-pin me-2 text-danger"></i>View on Google Maps
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
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
                        <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
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
</div>

<!-- Send Outreach Email Modal -->
<div class="modal fade" id="sendLeadEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="sendLeadEmailModalTitle">
                    <i class="icon-base ti tabler-send me-1 text-primary"></i> Send Outreach Email
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between">
                    <div>
                        <i class="icon-base ti tabler-users me-1"></i>
                        <span id="modalRecipientsSummary" class="fw-semibold">1 recipient selected</span>
                    </div>
                    <span class="badge bg-label-info" id="modalValidEmailsBadge">1 valid</span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="modalTemplateSelect">Select Email Template</label>
                    <select id="modalTemplateSelect" class="form-select">
                        <option value="">-- Custom Cold Outreach Email --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="modalEmailSubject">Subject <span class="text-danger">*</span></label>
                    <input type="text" id="modalEmailSubject" class="form-control" placeholder="Subject line..." required>
                </div>

                <div class="mb-2">
                    <small class="text-muted fw-semibold me-2">Insert Variables:</small>
                    <div class="d-inline-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertModalVariable('@{{business_name}}')">@{{business_name}}</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertModalVariable('@{{city}}')">@{{city}}</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertModalVariable('@{{category}}')">@{{category}}</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertModalVariable('@{{website}}')">@{{website}}</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertModalVariable('@{{phone}}')">@{{phone}}</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertModalVariable('@{{sender_name}}')">@{{sender_name}}</button>
                        <button type="button" class="btn btn-xs btn-outline-success" onclick="insertModalVariable('@{{demo_website_url}}')">✨ @{{demo_website_url}}</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Body <span class="text-danger">*</span></label>
                    <div class="editor-toolbar" style="background: #f8f9fa; border: 1px solid #dee2e6; border-bottom: none; border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem; padding: 0.4rem; display: flex; flex-wrap: wrap; gap: 0.25rem;">
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatModalDoc('bold')" title="Bold"><i class="icon-base ti tabler-bold"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatModalDoc('italic')" title="Italic"><i class="icon-base ti tabler-italic"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatModalDoc('underline')" title="Underline"><i class="icon-base ti tabler-underline"></i></button>
                        <span class="border-end mx-1"></span>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatModalDoc('insertUnorderedList')" title="Bullet List"><i class="icon-base ti tabler-list"></i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatModalDoc('insertOrderedList')" title="Numbered List"><i class="icon-base ti tabler-list-numbers"></i></button>
                    </div>
                    <div class="editor-content" id="modalRichEditor" contenteditable="true" spellcheck="false" style="min-height: 180px; border: 1px solid #dee2e6; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; padding: 0.75rem; background: #fff; outline: none; overflow-y: auto; max-height: 350px;">
                        <p>Hi <strong>@{{business_name}}</strong> Team,</p>
                        <p>I came across your business in @{{city}} and wanted to reach out regarding our lead generation and growth services.</p>
                        <p>Would you have 5 minutes this week for a brief call?</p>
                        <p>Best regards,<br><strong>@{{sender_name}}</strong></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnConfirmSendEmail">
                    <i class="icon-base ti tabler-send me-1"></i> Send Outreach Email
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="leadsPageToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="leadsPageToastBody">Action completed.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Immediately clean browser URL bar so no query params or page numbers are exposed
    if (window.location.search) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    initLeadsPageHandlers();
    initAjaxLeadsNavigation();
    initEmailModalHandlers();
});

let sendEmailModalInstance = null;
let activeEmailLeadIds = [];
let loadedTemplates = [];

function initLeadsPageHandlers() {
    const masterCheckbox = document.getElementById('masterTableCheckbox');
    const rowCheckboxes = document.querySelectorAll('.row-select-checkbox');
    const bulkBar = document.getElementById('leadsBulkBar');
    const bulkCountLabel = document.getElementById('leadsBulkCount');
    const selectAllPageBtn = document.getElementById('selectAllPageBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const exportSelectedExcelBtn = document.getElementById('exportSelectedExcelBtn');
    const exportSelectedCsvBtn = document.getElementById('exportSelectedCsvBtn');
    const copySelectedEmailsBtn = document.getElementById('copySelectedEmailsBtn');
    const copySelectedPhonesBtn = document.getElementById('copySelectedPhonesBtn');
    const discardSelectedBtn = document.getElementById('discardSelectedBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

    function updateBulkBar() {
        const checkedBoxes = document.querySelectorAll('.row-select-checkbox:checked');
        const count = checkedBoxes.length;

        if (count > 0) {
            if (bulkBar) bulkBar.classList.remove('d-none');
            if (bulkCountLabel) bulkCountLabel.textContent = `${count} lead${count === 1 ? '' : 's'} selected`;
        } else {
            if (bulkBar) bulkBar.classList.add('d-none');
        }

        if (masterCheckbox) {
            const allCheckboxes = document.querySelectorAll('.row-select-checkbox');
            masterCheckbox.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
            masterCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
        }

        document.querySelectorAll('.row-select-checkbox').forEach(cb => {
            const row = cb.closest('tr');
            if (row) {
                row.classList.toggle('table-active', cb.checked);
            }
        });
    }

    if (masterCheckbox) {
        masterCheckbox.addEventListener('change', (e) => {
            document.querySelectorAll('.row-select-checkbox').forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateBulkBar();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    if (selectAllPageBtn) {
        selectAllPageBtn.addEventListener('click', () => {
            document.querySelectorAll('.row-select-checkbox').forEach(cb => {
                cb.checked = true;
            });
            updateBulkBar();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.row-select-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateBulkBar();
        });
    }

    function getSelectedIds() {
        const ids = [];
        document.querySelectorAll('.row-select-checkbox:checked').forEach(cb => {
            ids.push(parseInt(cb.value, 10));
        });
        return ids;
    }

    if (exportSelectedExcelBtn) {
        exportSelectedExcelBtn.addEventListener('click', () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            window.location.href = `{{ route('leads.export.excel') }}?ids=${ids.join(',')}`;
        });
    }

    if (exportSelectedCsvBtn) {
        exportSelectedCsvBtn.addEventListener('click', () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("leads.export-selected") }}';
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            const format = document.createElement('input');
            format.type = 'hidden';
            format.name = 'format';
            format.value = 'csv';
            form.appendChild(format);
            ids.forEach(id => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'lead_ids[]';
                idInput.value = id;
                form.appendChild(idInput);
            });
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }

    if (copySelectedEmailsBtn) {
        copySelectedEmailsBtn.addEventListener('click', () => {
            const emails = [];
            document.querySelectorAll('.row-select-checkbox:checked').forEach(cb => {
                const email = cb.dataset.email;
                if (email && email.trim() && email !== 'null' && email !== 'undefined') {
                    emails.push(email.trim());
                }
            });

            if (emails.length === 0) {
                if (window.showToast) window.showToast('error', 'None of the selected leads have an email address.', 'Notice');
                return;
            }

            const uniqueEmails = [...new Set(emails)].join(', ');
            navigator.clipboard.writeText(uniqueEmails).then(() => {
                if (window.showToast) window.showToast('success', `Copied ${emails.length} email(s) to clipboard.`, 'Copied');
            });
        });
    }

    if (copySelectedPhonesBtn) {
        copySelectedPhonesBtn.addEventListener('click', () => {
            const phones = [];
            document.querySelectorAll('.row-select-checkbox:checked').forEach(cb => {
                const phone = cb.dataset.phone;
                if (phone && phone.trim() && phone !== 'null' && phone !== 'undefined') {
                    phones.push(phone.trim());
                }
            });

            if (phones.length === 0) {
                if (window.showToast) window.showToast('error', 'None of the selected leads have a phone number.', 'Notice');
                return;
            }

            const uniquePhones = [...new Set(phones)].join(', ');
            navigator.clipboard.writeText(uniquePhones).then(() => {
                if (window.showToast) window.showToast('success', `Copied ${phones.length} phone(s) to clipboard.`, 'Copied');
            });
        });
    }

    if (discardSelectedBtn) {
        discardSelectedBtn.addEventListener('click', async () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;

            const confirmed = await window.showConfirm(
                'Discard Selected Leads',
                `Mark ${ids.length} selected lead(s) as discarded?`,
                'Yes, Discard Leads',
                true
            );

            if (!confirmed || !confirmed.isConfirmed) return;

            try {
                const resp = await fetch('{{ route("leads.bulk-action") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ lead_ids: ids, action: 'discard' })
                });

                const data = await resp.json();
                if (resp.ok && data.success) {
                    if (window.showToast) window.showToast('success', data.message || `Discarded ${ids.length} leads.`);
                    loadLeadsAjax(window.location.href);
                } else {
                    if (window.showToast) window.showToast('error', data.message || 'Failed to discard leads.', 'Error');
                }
            } catch (err) {
                if (window.showToast) window.showToast('error', 'Network error while discarding leads.', 'Error');
            }
        });
    }

    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', async () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;

            const confirmed = await window.showConfirm(
                'Delete Selected Leads',
                `Are you sure you want to permanently delete ${ids.length} selected lead(s)? This action cannot be undone.`,
                'Yes, Delete Leads',
                true
            );

            if (!confirmed || !confirmed.isConfirmed) return;

            try {
                const resp = await fetch('{{ route("leads.bulk-action") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ lead_ids: ids, action: 'delete' })
                });

                const data = await resp.json();
                if (resp.ok && data.success) {
                    if (window.showToast) window.showToast('success', data.message || `Deleted ${ids.length} leads.`);
                    loadLeadsAjax(window.location.href);
                } else {
                    if (window.showToast) window.showToast('error', data.message || 'Failed to delete leads.', 'Error');
                }
            } catch (err) {
                if (window.showToast) window.showToast('error', 'Network error while deleting leads.', 'Error');
            }
        });
    }

    // Single email buttons
    document.querySelectorAll('.btn-send-single-email').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = parseInt(btn.dataset.id, 10);
            const email = btn.dataset.email;
            const name = btn.dataset.name;
            const category = btn.dataset.category || '';
            const city = btn.dataset.city || '';
            const website = btn.dataset.website || '';
            const phone = btn.dataset.phone || '';

            if (!email) {
                if (window.showToast) window.showToast('error', 'This lead does not have an email address.', 'Notice');
                return;
            }

            activeEmailLeadIds = [id];

            const modalRecipientsSummary = document.getElementById('modalRecipientsSummary');
            const modalValidEmailsBadge = document.getElementById('modalValidEmailsBadge');
            const templateSelect = document.getElementById('modalTemplateSelect');
            const modalSubjectInput = document.getElementById('modalEmailSubject');
            const modalEditor = document.getElementById('modalRichEditor');

            if (modalRecipientsSummary) modalRecipientsSummary.textContent = `${name} <${email}>`;
            if (modalValidEmailsBadge) modalValidEmailsBadge.textContent = '1 recipient';

            const defaultTmpl = loadedTemplates.find(t => t.is_default) || loadedTemplates[0];
            if (defaultTmpl) {
                if (templateSelect) templateSelect.value = defaultTmpl.id;
                if (modalSubjectInput) modalSubjectInput.value = defaultTmpl.subject;
                if (modalEditor) modalEditor.innerHTML = defaultTmpl.body;
            }

            if (sendEmailModalInstance) sendEmailModalInstance.show();
        });
    });

    // Re-initialize Bootstrap Tooltips
    if (window.bootstrap && window.bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new window.bootstrap.Tooltip(el);
        });
    }
}

function initAjaxLeadsNavigation() {
    const filterForm = document.querySelector('#leadsFilterForm');
    const perPageSelect = document.querySelector('#perPageSelect');

    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                const trimmed = typeof value === 'string' ? value.trim() : value;
                if (trimmed !== '' && trimmed !== null && trimmed !== undefined) {
                    params.append(key, trimmed);
                }
            }
            const queryStr = params.toString();
            const targetUrl = '{{ route("leads.index") }}' + (queryStr ? '?' + queryStr : '');
            loadLeadsAjax(targetUrl);
        });
    }

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            loadLeadsAjax(this.value);
        });
    }

    document.querySelectorAll('.leads-quick-chip').forEach(chip => {
        chip.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            if (href) loadLeadsAjax(href);
        });
    });

    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            if (href) loadLeadsAjax(href);
        });
    });
}

async function loadLeadsAjax(url) {
    const container = document.querySelector('#leadsMainContainer');
    if (!container) {
        window.location.href = url;
        return;
    }

    container.style.opacity = '0.5';
    container.style.pointerEvents = 'none';

    try {
        const resp = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const html = await resp.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContent = doc.querySelector('#leadsMainContainer');

        if (newContent) {
            container.innerHTML = newContent.innerHTML;
            window.history.replaceState({}, document.title, url);
            initLeadsPageHandlers();
            initAjaxLeadsNavigation();
        } else {
            window.location.href = url;
        }
    } catch (err) {
        console.error('AJAX leads navigation error:', err);
        window.location.href = url;
    } finally {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
    }
}

function initEmailModalHandlers() {
    const modalEl = document.getElementById('sendLeadEmailModal');
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        sendEmailModalInstance = new window.bootstrap.Modal(modalEl);
    }

    const sendEmailBulkBtn = document.getElementById('sendEmailBulkBtn');
    const templateSelect = document.getElementById('modalTemplateSelect');
    const modalSubjectInput = document.getElementById('modalEmailSubject');
    const modalEditor = document.getElementById('modalRichEditor');
    const btnConfirmSendEmail = document.getElementById('btnConfirmSendEmail');

    // Load templates
    fetch('{{ route("email-templates.list") }}')
        .then(res => res.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.templates)) {
                loadedTemplates = data.templates;
                if (templateSelect) {
                    templateSelect.innerHTML = '<option value="">-- Custom Cold Outreach Email --</option>';
                    loadedTemplates.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = `${t.name} (${t.category})`;
                        if (t.is_default) opt.textContent += ' [Default]';
                        templateSelect.appendChild(opt);
                    });
                }
            }
        }).catch(() => {});

    if (templateSelect) {
        templateSelect.addEventListener('change', () => {
            const selectedId = parseInt(templateSelect.value, 10);
            const tmpl = loadedTemplates.find(t => t.id === selectedId);
            if (tmpl) {
                if (modalSubjectInput) modalSubjectInput.value = tmpl.subject;
                if (modalEditor) modalEditor.innerHTML = tmpl.body;
            }
        });
    }

    if (sendEmailBulkBtn) {
        sendEmailBulkBtn.addEventListener('click', () => {
            const selectedCheckboxes = document.querySelectorAll('.row-select-checkbox:checked');
            const idsWithEmail = [];
            selectedCheckboxes.forEach(cb => {
                const email = cb.dataset.email;
                if (email && email.trim() && email !== 'null' && email !== 'undefined') {
                    idsWithEmail.push(parseInt(cb.value, 10));
                }
            });

            if (idsWithEmail.length === 0) {
                if (window.showToast) window.showToast('error', 'None of the selected leads have an email address.', 'Notice');
                return;
            }

            activeEmailLeadIds = idsWithEmail;

            const modalRecipientsSummary = document.getElementById('modalRecipientsSummary');
            const modalValidEmailsBadge = document.getElementById('modalValidEmailsBadge');

            if (modalRecipientsSummary) modalRecipientsSummary.textContent = `Sending outreach to ${idsWithEmail.length} selected lead(s)`;
            if (modalValidEmailsBadge) modalValidEmailsBadge.textContent = `${idsWithEmail.length} with valid email`;

            const defaultTmpl = loadedTemplates.find(t => t.is_default) || loadedTemplates[0];
            if (defaultTmpl) {
                if (templateSelect) templateSelect.value = defaultTmpl.id;
                if (modalSubjectInput) modalSubjectInput.value = defaultTmpl.subject;
                if (modalEditor) modalEditor.innerHTML = defaultTmpl.body;
            }

            if (sendEmailModalInstance) sendEmailModalInstance.show();
        });
    }

    if (btnConfirmSendEmail) {
        btnConfirmSendEmail.addEventListener('click', async () => {
            const subject = (modalSubjectInput ? modalSubjectInput.value : '').trim();
            const body = modalEditor ? modalEditor.innerHTML.trim() : '';

            if (!subject) {
                if (window.showToast) window.showToast('error', 'Please enter an email subject.', 'Required');
                if (modalSubjectInput) modalSubjectInput.focus();
                return;
            }
            if (!body || body === '<p><br></p>') {
                if (window.showToast) window.showToast('error', 'Please enter an email body message.', 'Required');
                if (modalEditor) modalEditor.focus();
                return;
            }

            btnConfirmSendEmail.disabled = true;
            btnConfirmSendEmail.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

            try {
                const resp = await fetch('{{ route("leads.send-email") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        lead_ids: activeEmailLeadIds,
                        template_id: templateSelect && templateSelect.value ? parseInt(templateSelect.value, 10) : null,
                        subject: subject,
                        body: body
                    })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    if (sendEmailModalInstance) sendEmailModalInstance.hide();
                    if (window.showToast) window.showToast('success', data.message || `Dispatched ${data.sent_count} email(s) successfully!`);
                } else {
                    if (window.showToast) window.showToast('error', data.message || 'Failed to dispatch email.', 'Error');
                }
            } catch (err) {
                if (window.showToast) window.showToast('error', 'Network error while dispatching email.', 'Error');
            } finally {
                btnConfirmSendEmail.disabled = false;
                btnConfirmSendEmail.innerHTML = '<i class="icon-base ti tabler-send me-1"></i> Send Outreach Email';
            }
        });
    }
}

function formatModalDoc(cmd, val = null) {
    document.execCommand(cmd, false, val);
    const ed = document.getElementById('modalRichEditor');
    if (ed) ed.focus();
}

function insertModalVariable(tag) {
    const editor = document.getElementById('modalRichEditor');
    if (editor) {
        editor.focus();
        document.execCommand('insertText', false, tag);
    }
}

window.generateDemo = async function(leadId) {
    const btn = document.getElementById(`btn-demo-${leadId}`);
    let originalHtml = '';
    if (btn) {
        originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }

    if (window.appNotify) {
        window.appNotify('info', '✨ Generating custom AI spec website...');
    }

    try {
        const response = await fetch(`/api/leads/${leadId}/generate-demo`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (btn) {
                btn.className = 'btn btn-sm btn-icon btn-label-success rounded-pill btn-generate-demo-icon';
                btn.title = 'AI Demo Website Ready (Click to Regenerate)';
            }
            const dropdownLink = document.getElementById(`dropdown-preview-${leadId}`);
            if (dropdownLink) {
                dropdownLink.href = data.preview_url;
                dropdownLink.className = 'dropdown-item text-primary fw-semibold';
            }

            if (window.appNotify) {
                window.appNotify('success', data.message || '✨ Spec website generated successfully!');
            }
        } else {
            const errorMsg = data.message || 'Failed to generate demo website with Gemini AI.';
            if (window.appNotify) {
                window.appNotify('error', errorMsg);
            }
        }
    } catch (error) {
        console.error('generateDemo error:', error);
        if (window.appNotify) {
            window.appNotify('error', 'A network error occurred while generating the demo website.');
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml || '<i class="icon-base ti tabler-sparkles"></i>';
        }
    }
};

function generateDemo(leadId) {
    return window.generateDemo(leadId);
}
</script>
@endpush
