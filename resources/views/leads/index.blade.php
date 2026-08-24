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
                <button type="button" class="btn btn-sm btn-success" id="exportSelectedExcelBtn">
                    <i class="icon-base ti tabler-file-spreadsheet me-1"></i>Export Selected (Excel)
                    <i class="icon-base ti tabler-file-spreadsheet me-1"></i>Export (Excel)
                </button>
                <button type="button" class="btn btn-sm btn-info text-white" id="exportSelectedCsvBtn">
                    <i class="icon-base ti tabler-file-text me-1"></i>CSV
                </button>
                <button type="button" class="btn btn-sm btn-label-secondary" id="copySelectedEmailsBtn" title="Copy all emails from selected leads">
                    <i class="icon-base ti tabler-copy me-1"></i>Emails
                </button>
                <button type="button" class="btn btn-sm btn-label-secondary" id="copySelectedPhonesBtn" title="Copy all phones from selected leads">
                    <i class="icon-base ti tabler-phone me-1"></i>Phones
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="deleteSelectedBtn" title="Delete selected leads from database">
                    <i class="icon-base ti tabler-trash me-1"></i>Delete Selected
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="leadsTable">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 40px;">
                        <input class="form-check-input" type="checkbox" id="masterTableCheckbox" style="cursor: pointer;" title="Select / Deselect all on this page">
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
                    <tr data-id="{{ $lead->id }}" style="cursor: pointer;">
                        <td class="ps-3">
                            <input class="form-check-input row-select-checkbox" type="checkbox" value="{{ $lead->id }}" style="cursor: pointer;" data-email="{{ $firstEmail }}" data-phone="{{ $lead->phone }}">
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
                                @if ($firstEmail)
                                    <button type="button" class="btn btn-xs btn-primary btn-send-single-email" title="Send Email to {{ $firstEmail }}" data-id="{{ $lead->id }}" data-email="{{ $firstEmail }}" data-name="{{ $lead->business_name }}" data-category="{{ $lead->category }}" data-city="{{ $lead->city }}" data-website="{{ $lead->website }}" data-phone="{{ $lead->phone }}">
                                        <i class="icon-base ti tabler-send"></i>
                                    </button>
                                @endif
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
                        <i class="icon-base ti tabler-mail me-1"></i>
                        <span id="modalRecipientsSummary" class="fw-semibold">1 recipient selected</span>
                    </div>
                    <span class="badge bg-white text-primary" id="modalValidEmailsBadge">1 with valid email</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="modalTemplateSelect">Select Email Template</label>
                        <select class="form-select" id="modalTemplateSelect">
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
                    <label class="form-label fw-semibold" for="modalEmailSubject">Email Subject <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="modalEmailSubject" placeholder="e.g. Quick inquiry for @{{business_name}}">
                </div>

                <!-- Dynamic tags toolbar -->
                <div class="mb-3 p-2 bg-light-subtle border rounded">
                    <div class="d-flex align-items-center flex-wrap gap-1">
                        <small class="fw-bold text-muted me-2"><i class="icon-base ti tabler-code me-1"></i>Insert Tag:</small>
                        <span class="badge bg-label-primary cursor-pointer modal-var-pill" onclick="insertModalVariable('@{{business_name}}')">@{{business_name}}</span>
                        <span class="badge bg-label-info cursor-pointer modal-var-pill" onclick="insertModalVariable('@{{email}}')">@{{email}}</span>
                        <span class="badge bg-label-secondary cursor-pointer modal-var-pill" onclick="insertModalVariable('@{{phone}}')">@{{phone}}</span>
                        <span class="badge bg-label-success cursor-pointer modal-var-pill" onclick="insertModalVariable('@{{city}}')">@{{city}}</span>
                        <span class="badge bg-label-warning cursor-pointer modal-var-pill" onclick="insertModalVariable('@{{category}}')">@{{category}}</span>
                        <span class="badge bg-label-dark cursor-pointer modal-var-pill" onclick="insertModalVariable('@{{website}}')">@{{website}}</span>
                        <span class="badge bg-label-primary cursor-pointer modal-var-pill" onclick="insertModalVariable('@{{sender_name}}')">@{{sender_name}}</span>
                    </div>
                </div>

                <!-- Rich Text Editor -->
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
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const toastEl = document.getElementById('leadsPageToast');
    const toastBody = document.getElementById('leadsPageToastBody');

    let toastInstance = null;
    if (toastEl && window.bootstrap && window.bootstrap.Toast) {
        toastInstance = new window.bootstrap.Toast(toastEl, { delay: 3000 });
    }

    function showToast(message, isDanger = false) {
        if (!toastEl || !toastBody) {
            alert(message);
            return;
        }
        toastBody.textContent = message;
        toastEl.className = `toast align-items-center border-0 ${isDanger ? 'text-bg-danger' : 'text-bg-primary'}`;
        if (toastInstance) toastInstance.show();
    }

    function getSelectedIds() {
        const ids = [];
        document.querySelectorAll('.row-select-checkbox:checked').forEach(cb => {
            const id = parseInt(cb.value, 10);
            if (id) ids.push(id);
        });
        return ids;
    }

    function updateSelectionState() {
        const total = rowCheckboxes.length;
        const checkedList = document.querySelectorAll('.row-select-checkbox:checked');
        const selected = checkedList.length;

        if (masterCheckbox) {
            if (total > 0 && selected === total) {
                masterCheckbox.checked = true;
                masterCheckbox.indeterminate = false;
            } else if (selected > 0) {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = true;
            } else {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = false;
            }
        }

        if (bulkBar) {
            if (selected > 0) {
                bulkBar.classList.remove('d-none');
                if (bulkCountLabel) bulkCountLabel.textContent = `${selected} selected`;
            } else {
                bulkBar.classList.add('d-none');
            }
        }

        // Highlight selected rows
        rowCheckboxes.forEach(cb => {
            const tr = cb.closest('tr');
            if (tr) {
                tr.classList.toggle('table-active', cb.checked);
            }
        });
    }

    // Master Table Checkbox
    if (masterCheckbox) {
        masterCheckbox.addEventListener('change', () => {
            const shouldCheck = masterCheckbox.checked;
            rowCheckboxes.forEach(cb => {
                cb.checked = shouldCheck;
            });
            updateSelectionState();
        });
    }

    // Individual Row Checkboxes
    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            updateSelectionState();
        });
    });

    // Row Click to toggle
    document.querySelectorAll('#leadsTable tbody tr').forEach(tr => {
        tr.addEventListener('click', (e) => {
            if (e.target.closest('a, button, .btn, input, label, select, .dropdown')) {
                return;
            }
            const cb = tr.querySelector('.row-select-checkbox');
            if (cb) {
                cb.checked = !cb.checked;
                updateSelectionState();
            }
        });
    });

    // Select All on Page button
    if (selectAllPageBtn) {
        selectAllPageBtn.addEventListener('click', () => {
            rowCheckboxes.forEach(cb => {
                cb.checked = true;
            });
            updateSelectionState();
        });
    }

    // Deselect All button
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            rowCheckboxes.forEach(cb => {
                cb.checked = false;
            });
            updateSelectionState();
        });
    }

    // Export Selected Excel
    if (exportSelectedExcelBtn) {
        exportSelectedExcelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const ids = getSelectedIds();
            if (!ids.length) {
                showToast('Please select at least one lead.', true);
                return;
            }
            window.location.href = `{{ route('leads.export.excel') }}?ids=${ids.join(',')}`;
        });
    }

    // Export Selected CSV
    if (exportSelectedCsvBtn) {
        exportSelectedCsvBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const ids = getSelectedIds();
            if (!ids.length) {
                showToast('Please select at least one lead.', true);
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("leads.export-selected") }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = 'csv';
            form.appendChild(formatInput);

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

    // Quick Copy Emails
    if (copySelectedEmailsBtn) {
        copySelectedEmailsBtn.addEventListener('click', () => {
            const emails = new Set();
            document.querySelectorAll('.row-select-checkbox:checked').forEach(cb => {
                const email = cb.dataset.email;
                if (email && email.trim()) emails.add(email.trim());
            });
            if (emails.size === 0) {
                showToast('No emails found in selected leads.', true);
                return;
            }
            navigator.clipboard.writeText(Array.from(emails).join(', ')).then(() => {
                showToast(`Copied ${emails.size} email(s) to clipboard.`);
            });
        });
    }

    // Quick Copy Phones
    if (copySelectedPhonesBtn) {
        copySelectedPhonesBtn.addEventListener('click', () => {
            const phones = new Set();
            document.querySelectorAll('.row-select-checkbox:checked').forEach(cb => {
                const phone = cb.dataset.phone;
                if (phone && phone.trim()) phones.add(phone.trim());
            });
            if (phones.size === 0) {
                showToast('No phone numbers found in selected leads.', true);
                return;
            }
            navigator.clipboard.writeText(Array.from(phones).join(', ')).then(() => {
                showToast(`Copied ${phones.size} phone number(s) to clipboard.`);
            });
        });
    }

    // Delete Selected Leads
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', async () => {
            const ids = getSelectedIds();
            if (!ids.length) {
                showToast('Please select at least one lead to delete.', true);
                return;
            }
            if (!confirm(`Are you sure you want to delete ${ids.length} selected lead(s)?`)) {
                return;
            }

            deleteSelectedBtn.disabled = true;
            try {
                const response = await fetch('{{ route("leads.bulk-action") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        lead_ids: ids,
                        action: 'delete'
                    })
                });
                const data = await response.json();
                if (response.ok) {
                    window.location.reload();
                } else {
                    showToast(data.message || 'Failed to delete leads.', true);
                    deleteSelectedBtn.disabled = false;
                }
            } catch (err) {
                showToast('Network error while deleting leads.', true);
                deleteSelectedBtn.disabled = false;
            }
        });
    }

    // Email Outreach System
    let loadedTemplates = [];
    let activeEmailLeadIds = [];
    const sendEmailModalEl = document.getElementById('sendLeadEmailModal');
    let sendEmailModalInstance = null;
    if (sendEmailModalEl && window.bootstrap && window.bootstrap.Modal) {
        sendEmailModalInstance = new window.bootstrap.Modal(sendEmailModalEl);
    }
    const templateSelect = document.getElementById('modalTemplateSelect');
    const modalSubjectInput = document.getElementById('modalEmailSubject');
    const modalEditor = document.getElementById('modalRichEditor');
    const modalRecipientsSummary = document.getElementById('modalRecipientsSummary');
    const modalValidEmailsBadge = document.getElementById('modalValidEmailsBadge');
    const btnConfirmSendEmail = document.getElementById('btnConfirmSendEmail');
    const sendEmailBulkBtn = document.getElementById('sendEmailBulkBtn');

    // Fetch Email Templates
    async function loadTemplates() {
        try {
            const resp = await fetch('{{ route("email-templates.list") }}');
            if (resp.ok) {
                loadedTemplates = await resp.json();
                if (templateSelect) {
                    templateSelect.innerHTML = '<option value="">-- Choose a template (optional) --</option>';
                    loadedTemplates.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = `${t.name} (${t.category || 'Outreach'})${t.is_default ? ' ★ Default' : ''}`;
                        templateSelect.appendChild(opt);
                    });
                }
            }
        } catch (_) {}
    }
    loadTemplates();

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

    // Single Lead Email Click Handler
    document.querySelectorAll('.btn-send-single-email').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const leadId = parseInt(btn.dataset.id, 10);
            const email = btn.dataset.email || '';
            const name = btn.dataset.name || 'Business Owner';
            activeEmailLeadIds = [leadId];

            if (modalRecipientsSummary) {
                modalRecipientsSummary.textContent = `${name} <${email}>`;
            }
            if (modalValidEmailsBadge) {
                modalValidEmailsBadge.textContent = '1 recipient';
            }

            // Apply default template if exists
            const defaultTmpl = loadedTemplates.find(t => t.is_default) || loadedTemplates[0];
            if (defaultTmpl) {
                if (templateSelect) templateSelect.value = defaultTmpl.id;
                if (modalSubjectInput) modalSubjectInput.value = defaultTmpl.subject;
                if (modalEditor) modalEditor.innerHTML = defaultTmpl.body;
            } else {
                if (modalSubjectInput) modalSubjectInput.value = `Quick inquiry regarding @{{business_name}}`;
                if (modalEditor) {
                    modalEditor.innerHTML = `
                        <p>Hi <strong>@{{business_name}}</strong> Team,</p>
                        <p>I came across your business in @{{city}} and wanted to reach out regarding our lead generation and growth services.</p>
                        <p>Would you have 5 minutes this week for a brief call?</p>
                        <p>Best regards,<br><strong>@{{sender_name}}</strong></p>
                    `;
                }
            }

            if (sendEmailModalInstance) sendEmailModalInstance.show();
        });
    });

    // Bulk Email Button Handler
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
                showToast('None of the selected leads have an email address.', true);
                return;
            }

            activeEmailLeadIds = idsWithEmail;

            if (modalRecipientsSummary) {
                modalRecipientsSummary.textContent = `Sending outreach to ${idsWithEmail.length} selected lead(s)`;
            }
            if (modalValidEmailsBadge) {
                modalValidEmailsBadge.textContent = `${idsWithEmail.length} with valid email`;
            }

            const defaultTmpl = loadedTemplates.find(t => t.is_default) || loadedTemplates[0];
            if (defaultTmpl) {
                if (templateSelect) templateSelect.value = defaultTmpl.id;
                if (modalSubjectInput) modalSubjectInput.value = defaultTmpl.subject;
                if (modalEditor) modalEditor.innerHTML = defaultTmpl.body;
            }

            if (sendEmailModalInstance) sendEmailModalInstance.show();
        });
    }

    // Confirm Send Email Submission
    if (btnConfirmSendEmail) {
        btnConfirmSendEmail.addEventListener('click', async () => {
            const subject = (modalSubjectInput ? modalSubjectInput.value : '').trim();
            const body = modalEditor ? modalEditor.innerHTML.trim() : '';

            if (!subject) {
                showToast('Please enter an email subject.', true);
                if (modalSubjectInput) modalSubjectInput.focus();
                return;
            }
            if (!body || body === '<p><br></p>') {
                showToast('Please enter an email body message.', true);
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
                    showToast(data.message || `Dispatched ${data.sent_count} email(s) successfully!`);
                } else {
                    showToast(data.message || 'Failed to dispatch email.', true);
                }
            } catch (err) {
                showToast('Network error while dispatching email.', true);
            } finally {
                btnConfirmSendEmail.disabled = false;
                btnConfirmSendEmail.innerHTML = '<i class="icon-base ti tabler-send me-1"></i> Send Outreach Email';
            }
        });
    }
});

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
</script>
@endpush
