@extends('layouts.app')

@section('title', 'Email Templates & Outreach')

@push('styles')
<style>
.editor-toolbar {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    padding: 0.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}
.editor-content {
    min-height: 260px;
    border: 1px solid #dee2e6;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
    padding: 1rem;
    background: #fff;
    outline: none;
    overflow-y: auto;
    max-height: 480px;
    font-size: 0.95rem;
    line-height: 1.6;
}
.editor-content:focus {
    border-color: #696cff;
    box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
}
.var-pill {
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
    font-weight: 500;
}
.var-pill:hover {
    transform: translateY(-1px);
    background-color: #696cff !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(105, 108, 255, 0.3);
}
.template-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 0.75rem;
}
.template-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.email-preview-window {
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.08);
    background: #ffffff;
}
.email-preview-header {
    background: #f8f9fb;
    border-bottom: 1px solid #edf0f2;
    padding: 1rem 1.25rem;
}
.category-filter-btn.active {
    background-color: #7367f0 !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(115, 103, 240, 0.3);
}
</style>
@endpush

@section('content')
<div class="pos-glass-card pos-tone-primary mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-template me-1 text-primary"></i> Email Templates &amp; SaaS Outreach
            </h4>
            <p class="pos-glass-intro-subtitle">
                High-converting outreach templates crafted for Automobile Garages, Oil Change, Tyre, and Repair Shops with live placeholders.
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex flex-wrap align-items-center gap-2">
            <form action="{{ route('email-templates.restore-defaults') }}" method="POST" id="restoreDefaultsForm">
                @csrf
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="confirmRestoreDefaults(event)">
                    <i class="icon-base ti tabler-refresh me-1"></i> Restore Standard Templates
                </button>
            </form>
            <button type="button" class="btn btn-sm btn-primary" id="btnCreateNewTemplate">
                <i class="icon-base ti tabler-plus me-1"></i> New Template
            </button>
            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-base ti tabler-users me-1"></i> Prospects Directory
            </a>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="p-3 border-bottom bg-light-subtle">
        <ul class="nav nav-pills" id="emailTemplatesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active btn-sm" id="library-tab" data-bs-toggle="pill" data-bs-target="#libraryTabPane" type="button" role="tab">
                    <i class="icon-base ti tabler-layout-grid me-1"></i> Template Library (<span id="totalTemplatesCount">{{ count($templates) }}</span>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm" id="builder-tab" data-bs-toggle="pill" data-bs-target="#builderTabPane" type="button" role="tab">
                    <i class="icon-base ti tabler-edit me-1"></i> Template Builder &amp; Editor
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm" id="logs-tab" data-bs-toggle="pill" data-bs-target="#logsTabPane" type="button" role="tab">
                    <i class="icon-base ti tabler-mail-fast me-1"></i> Sent Outreach Logs ({{ $logs->total() }})
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Panes -->
    <div class="tab-content p-3 p-md-4">
        <!-- 1. TEMPLATE LIBRARY TAB -->
        <div class="tab-pane fade show active" id="libraryTabPane" role="tabpanel">
            <!-- Filter & Search Bar -->
            <div class="row g-3 align-items-center mb-4">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" id="templateSearchInput" placeholder="Search templates or subjects..." onkeyup="filterTemplates()">
                    </div>
                </div>
                <div class="col-12 col-md-7 col-lg-8">
                    <div class="d-flex flex-wrap gap-1 align-items-center" id="categoryFilterBar">
                        <button type="button" class="btn btn-xs btn-outline-primary category-filter-btn active" data-category="all" onclick="selectCategoryFilter('all', this)">
                            All ({{ count($templates) }})
                        </button>
                        @php
                            $uniqueCategories = $templates->pluck('category')->filter()->unique();
                        @endphp
                        @foreach ($uniqueCategories as $cat)
                            <button type="button" class="btn btn-xs btn-outline-secondary category-filter-btn" data-category="{{ $cat }}" onclick="selectCategoryFilter('{{ $cat }}', this)">
                                {{ $cat }} ({{ $templates->where('category', $cat)->count() }})
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($templates->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="icon-base ti tabler-mail-cancel display-4 mb-2"></i>
                    <h5>No email templates yet</h5>
                    <p class="small mb-3">Click below to load standard Automobile &amp; Garage POS templates or create your custom template.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <form action="{{ route('email-templates.restore-defaults') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="icon-base ti tabler-refresh me-1"></i> Load Standard Auto Templates
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="switchToBuilder()">
                            <i class="icon-base ti tabler-plus me-1"></i> Create Custom Template
                        </button>
                    </div>
                </div>
            @else
                <div class="row g-3" id="templatesGrid">
                    @foreach ($templates as $tmpl)
                        @php
                            $cat = $tmpl->category ?: 'Outreach';
                            $badgeClass = match ($cat) {
                                'Auto Repair & Garage' => 'bg-label-primary',
                                'Oil & Lube Service' => 'bg-label-warning',
                                'Tyre & Wheel Shop' => 'bg-label-info',
                                'Electrical & AC Repair' => 'bg-label-primary',
                                'Special Offer' => 'bg-label-success',
                                'Follow-up' => 'bg-label-secondary',
                                default => 'bg-label-dark',
                            };
                            $catIcon = match ($cat) {
                                'Auto Repair & Garage' => 'tabler-tool',
                                'Oil & Lube Service' => 'tabler-droplet',
                                'Tyre & Wheel Shop' => 'tabler-circle-dot',
                                'Electrical & AC Repair' => 'tabler-bolt',
                                'Special Offer' => 'tabler-sparkles',
                                'Follow-up' => 'tabler-clock',
                                default => 'tabler-template',
                            };
                        @endphp
                        <div class="col-12 col-md-6 col-xl-4 template-grid-item" data-category="{{ $cat }}" data-name="{{ strtolower($tmpl->name) }}" data-subject="{{ strtolower($tmpl->subject) }}">
                            <div class="card h-100 border shadow-none template-card">
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <span class="badge {{ $badgeClass }} mb-1">
                                                <i class="icon-base ti {{ $catIcon }} me-1"></i>{{ $cat }}
                                            </span>
                                            @if ($tmpl->is_default)
                                                <span class="badge bg-label-success mb-1 ms-1"><i class="icon-base ti tabler-check me-1"></i>Default</span>
                                            @endif
                                            <h6 class="card-title mb-0 fw-bold text-heading mt-1">{{ $tmpl->name }}</h6>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                <i class="icon-base ti tabler-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="editTemplate({{ json_encode($tmpl) }})">
                                                        <i class="icon-base ti tabler-edit me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="duplicateTemplate({{ json_encode($tmpl) }})">
                                                        <i class="icon-base ti tabler-copy me-2"></i>Duplicate / Clone
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="previewTemplateModal({{ json_encode($tmpl) }})">
                                                        <i class="icon-base ti tabler-eye me-2"></i>Preview
                                                    </a>
                                                </li>
                                                @if (!$tmpl->is_default)
                                                    <li>
                                                        <form action="{{ route('email-templates.default', $tmpl) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="icon-base ti tabler-star me-2"></i>Set as Default
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('email-templates.destroy', $tmpl) }}" method="POST" onsubmit="return confirmDeleteTemplate(event, this);">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="icon-base ti tabler-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <span class="fw-semibold text-heading">Subject:</span> {{ Str::limit($tmpl->subject, 60) }}
                                    </div>
                                    @if ($tmpl->description)
                                        <p class="small text-muted mb-2 fst-italic">{{ Str::limit($tmpl->description, 85) }}</p>
                                    @endif
                                    <div class="small text-muted mb-3 flex-grow-1 border rounded p-2 bg-light-subtle" style="max-height: 95px; overflow: hidden; font-size: 0.825rem; line-height: 1.4;">
                                        {!! Str::limit(strip_tags($tmpl->body), 150) !!}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="previewTemplateModal({{ json_encode($tmpl) }})">
                                            <i class="icon-base ti tabler-eye me-1"></i> Preview
                                        </button>
                                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="editTemplate({{ json_encode($tmpl) }})">
                                            <i class="icon-base ti tabler-edit me-1"></i> Edit Template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 2. TEMPLATE BUILDER & EDITOR TAB -->
        <div class="tab-pane fade" id="builderTabPane" role="tabpanel">
            <div class="card border shadow-none">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0" id="builderFormTitle">
                        <i class="icon-base ti tabler-edit me-1 text-primary"></i> Create Email Template
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="resetBuilderForm()">
                            <i class="icon-base ti tabler-rotate me-1"></i> Reset Form
                        </button>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form id="templateForm" method="POST" action="{{ route('email-templates.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">
                        <input type="hidden" name="template_id" id="templateId" value="">

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label fw-semibold" for="templateName">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="templateName" name="name" placeholder="e.g. Auto Garage Management POS Pitch" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="templateCategory">Target Industry / Category</label>
                                <select class="form-select" id="templateCategory" name="category">
                                    <option value="Auto Repair & Garage" selected>Auto Repair &amp; Garage</option>
                                    <option value="Oil & Lube Service">Oil &amp; Lube Service</option>
                                    <option value="Tyre & Wheel Shop">Tyre &amp; Wheel Shop</option>
                                    <option value="Electrical & AC Repair">Electrical &amp; AC Repair</option>
                                    <option value="Auto Parts & Accessories">Auto Parts &amp; Accessories</option>
                                    <option value="Special Offer">Special Offer &amp; Demo</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Outreach">General Outreach</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="templateIsDefault" name="is_default" value="1">
                                    <label class="form-check-label user-select-none" for="templateIsDefault">
                                        Set as Default Template
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="templateDescription">Internal Description / Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control" id="templateDescription" name="description" placeholder="e.g. Targeted at independent auto garages with 2-5 service bays">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="templateSubject">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="templateSubject" name="subject" placeholder="e.g. Modern POS & Digital Job Cards for @{{business_name}}" required>
                        </div>

                        <!-- Dynamic Variable Insert Bar -->
                        <div class="mb-3 p-3 bg-light-subtle border rounded">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <small class="fw-bold text-muted me-1"><i class="icon-base ti tabler-code me-1"></i>Insert Dynamic Tag:</small>
                                <span class="badge bg-label-primary var-pill" onclick="insertVariable('@{{business_name}}')" title="Recipient Business / Garage Name">@{{business_name}}</span>
                                <span class="badge bg-label-info var-pill" onclick="insertVariable('@{{email}}')" title="Recipient Email">@{{email}}</span>
                                <span class="badge bg-label-secondary var-pill" onclick="insertVariable('@{{phone}}')" title="Recipient Phone">@{{phone}}</span>
                                <span class="badge bg-label-success var-pill" onclick="insertVariable('@{{city}}')" title="City / Location">@{{city}}</span>
                                <span class="badge bg-label-warning var-pill" onclick="insertVariable('@{{category}}')" title="Auto Service Category">@{{category}}</span>
                                <span class="badge bg-label-dark var-pill" onclick="insertVariable('@{{website}}')" title="Prospect Website">@{{website}}</span>
                                <span class="badge bg-label-danger var-pill" onclick="insertVariable('@{{rating}}')" title="Google Star Rating">@{{rating}}</span>
                                <span class="badge bg-label-primary var-pill" onclick="insertVariable('@{{sender_name}}')" title="Your Staff Name">@{{sender_name}}</span>
                                <span class="badge bg-label-secondary var-pill" onclick="insertVariable('@{{sender_company}}')" title="Your SaaS / Workspace Name">@{{sender_company}}</span>
                                <span class="badge bg-label-success var-pill" onclick="insertVariable('@{{demo_website_url}}')" title="Interactive Demo Preview Link">✨ @{{demo_website_url}}</span>
                            </div>
                        </div>

                        <!-- Rich Text Editor Container -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Email Body Content <span class="text-danger">*</span></label>
                            <div class="editor-toolbar" id="editorToolbar">
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('bold')" title="Bold"><i class="icon-base ti tabler-bold"></i></button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('italic')" title="Italic"><i class="icon-base ti tabler-italic"></i></button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('underline')" title="Underline"><i class="icon-base ti tabler-underline"></i></button>
                                <span class="border-end mx-1"></span>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('formatBlock', '<h2>')" title="Heading 2">H2</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('formatBlock', '<h3>')" title="Heading 3">H3</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('formatBlock', '<p>')" title="Paragraph">P</button>
                                <span class="border-end mx-1"></span>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('insertUnorderedList')" title="Bullet List"><i class="icon-base ti tabler-list"></i></button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('insertOrderedList')" title="Numbered List"><i class="icon-base ti tabler-list-numbers"></i></button>
                                <span class="border-end mx-1"></span>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertLinkPrompt()" title="Insert Link"><i class="icon-base ti tabler-link"></i></button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="formatDoc('removeFormat')" title="Clear Formatting"><i class="icon-base ti tabler-clear-formatting"></i></button>
                            </div>
                            <div class="editor-content" id="richEditor" contenteditable="true" spellcheck="false">
                                <p>Hello <strong>@{{business_name}}</strong> Team,</p>
                                <p>I came across your workshop in <strong>@{{city}}</strong> and wanted to reach out because we help automotive garages, tyre centers, and repair shops streamline operations with our cloud-based <strong>POS &amp; Garage Management SaaS</strong>.</p>
                                <p>Our platform handles everything your workshop needs in one place:</p>
                                <ul>
                                    <li>🚗 <strong>Digital Job Cards &amp; Estimates:</strong> Create fast estimates and get customer WhatsApp/SMS approval.</li>
                                    <li>🧾 <strong>Instant POS Invoicing &amp; Billing:</strong> Point-of-sale invoicing, split labor/parts, and tax reports.</li>
                                    <li>📦 <strong>Inventory &amp; Oil/Filter Tracking:</strong> Real-time alerts for spare parts and fluid levels.</li>
                                    <li>⏰ <strong>Automatic Service Reminders:</strong> Bring customers back automatically for scheduled maintenance.</li>
                                </ul>
                                <p>Would you have 5 minutes this week for a quick walkthrough or to activate a 14-day free trial?</p>
                                <p>Best regards,<br><strong>@{{sender_name}}</strong><br>@{{sender_company}}</p>
                            </div>
                            <textarea name="body" id="hiddenBodyInput" class="d-none"></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="previewCurrentDraft()">
                                <i class="icon-base ti tabler-eye me-1"></i> Live Preview
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="switchToLibrary()">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-primary" id="btnSaveTemplate">
                                    <i class="icon-base ti tabler-device-floppy me-1"></i> Save Template
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. SENT OUTREACH LOGS TAB -->
        <div class="tab-pane fade" id="logsTabPane" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3"><i class="icon-base ti tabler-mail me-1 text-primary"></i> Recipient</th>
                            <th><i class="icon-base ti tabler-building me-1 text-secondary"></i> Lead / Garage</th>
                            <th><i class="icon-base ti tabler-file-text me-1 text-info"></i> Subject</th>
                            <th><i class="icon-base ti tabler-activity me-1 text-warning"></i> Status</th>
                            <th><i class="icon-base ti tabler-template me-1 text-primary"></i> Template</th>
                            <th class="pe-3 text-end"><i class="icon-base ti tabler-clock me-1 text-muted"></i> Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    <div class="fw-semibold small text-heading">
                                        <i class="icon-base ti tabler-mail me-1 text-muted"></i>{{ $log->recipient_email }}
                                    </div>
                                </td>
                                <td>{{ $log->recipient_name ?: ($log->lead?->business_name ?? '—') }}</td>
                                <td><span class="small">{{ Str::limit($log->subject, 45) }}</span></td>
                                <td>
                                    @if ($log->status === 'sent')
                                        <span class="badge bg-label-success"><i class="icon-base ti tabler-check me-1"></i>Sent</span>
                                    @else
                                        <span class="badge bg-label-danger" title="{{ $log->error_message }}"><i class="icon-base ti tabler-alert-circle me-1"></i>Failed</span>
                                    @endif
                                </td>
                                <td><span class="small text-muted">{{ $log->template?->name ?? 'Custom Draft' }}</span></td>
                                <td><small class="text-muted">{{ $log->created_at->format('M d, Y H:i') }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="icon-base ti tabler-inbox display-6 mb-2"></i>
                                    <h6>No emails sent yet</h6>
                                    <p class="small mb-0">Select leads in the Extracted Leads database and use "Send Email" to begin outreach.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->total() > 0)
                <div class="card-footer border-top py-3">
                    {{ $logs->links('vendor.pagination.pos') }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-light-subtle">
                <h5 class="modal-title"><i class="icon-base ti tabler-eye me-1 text-primary"></i> Email Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="email-preview-window mb-2">
                    <div class="email-preview-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="badge bg-label-primary me-2">Sample Automotive Lead</span>
                                <small class="text-muted">Simulated Render with Real Values</small>
                            </div>
                            <span class="badge bg-label-success"><i class="icon-base ti tabler-circle-check me-1"></i>Live Placeholders OK</span>
                        </div>
                        <div class="mb-1 small"><strong>To:</strong> <span class="text-muted" id="previewTo">Apex Auto Garage &amp; Tyre Services &lt;service@apexgarage.com&gt;</span></div>
                        <div class="mb-1 small"><strong>From:</strong> <span class="text-muted">{{ Auth::user()?->name ?? 'Outreach Specialist' }} &lt;{{ Auth::user()?->email ?? 'pos@obtainsolutions.com' }}&gt;</span></div>
                        <div class="small"><strong>Subject:</strong> <span class="fw-semibold text-heading" id="previewSubject"></span></div>
                    </div>
                    <div class="p-4 bg-white" id="previewBody" style="min-height: 220px; font-size: 0.95rem; line-height: 1.6;"></div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('richEditor');
    const hiddenInput = document.getElementById('hiddenBodyInput');
    const form = document.getElementById('templateForm');

    form.addEventListener('submit', () => {
        hiddenInput.value = editor.innerHTML;
    });

    const btnNew = document.getElementById('btnCreateNewTemplate');
    if (btnNew) {
        btnNew.addEventListener('click', () => {
            resetBuilderForm();
            switchToBuilder();
        });
    }
});

function selectCategoryFilter(category, btn) {
    document.querySelectorAll('.category-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const searchVal = (document.getElementById('templateSearchInput').value || '').toLowerCase().trim();
    const items = document.querySelectorAll('.template-grid-item');

    items.forEach(item => {
        const itemCat = item.getAttribute('data-category');
        const itemName = item.getAttribute('data-name');
        const itemSubj = item.getAttribute('data-subject');

        const catMatches = (category === 'all' || itemCat === category);
        const searchMatches = (!searchVal || itemName.includes(searchVal) || itemSubj.includes(searchVal));

        item.style.display = (catMatches && searchMatches) ? '' : 'none';
    });
}

function filterTemplates() {
    const activeBtn = document.querySelector('.category-filter-btn.active');
    const currentCat = activeBtn ? activeBtn.getAttribute('data-category') : 'all';
    selectCategoryFilter(currentCat, activeBtn || document.querySelector('.category-filter-btn'));
}

function formatDoc(cmd, val = null) {
    document.execCommand(cmd, false, val);
    document.getElementById('richEditor').focus();
}

function insertLinkPrompt() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Insert Link',
            input: 'url',
            inputLabel: 'Enter Web Link URL',
            inputPlaceholder: 'https://example.com',
            showCancelButton: true,
            confirmButtonText: 'Insert Link',
            customClass: {
                popup: 'pos-swal-popup pos-glass-card',
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
        }).then(result => {
            if (result.isConfirmed && result.value) {
                formatDoc('createLink', result.value);
            }
        });
    } else {
        const url = prompt('Enter link URL (e.g. https://yourwebsite.com):');
        if (url) {
            formatDoc('createLink', url);
        }
    }
}

function insertVariable(tag) {
    const editor = document.getElementById('richEditor');
    editor.focus();
    document.execCommand('insertText', false, tag);
    if (typeof appNotify !== 'undefined') {
        appNotify.info(`Inserted placeholder: ${tag}`);
    } else if (typeof window.showToast === 'function') {
        window.showToast('info', `Inserted placeholder: ${tag}`, 'Template Editor');
    }
}

function confirmRestoreDefaults(event) {
    event.preventDefault();
    const form = document.getElementById('restoreDefaultsForm');
    if (window.PosConfirm) {
        window.PosConfirm.open({
            title: 'Restore Standard Templates?',
            message: 'This will ensure all 6 Automobile, Garage, Oil & Tyre POS email templates are populated for your workspace.',
            confirmText: 'Yes, Restore Templates',
            tone: 'primary'
        }).then(ok => {
            if (ok) form.submit();
        });
    } else if (typeof window.showConfirm === 'function') {
        window.showConfirm(
            'Restore Standard Templates?',
            'This will ensure all 6 Automobile, Garage, Oil & Tyre POS email templates are populated for your workspace.',
            'Yes, Restore Templates'
        ).then(result => {
            if (result && result.isConfirmed) form.submit();
        });
    } else {
        if (confirm('Restore standard Automobile & Garage POS email templates?')) {
            form.submit();
        }
    }
}

function confirmDeleteTemplate(event, form) {
    event.preventDefault();
    if (window.PosConfirm) {
        window.PosConfirm.open({
            title: 'Delete Template?',
            message: 'Are you sure you want to delete this outreach email template? This action cannot be undone.',
            confirmText: 'Yes, Delete Template',
            tone: 'danger'
        }).then(ok => {
            if (ok) form.submit();
        });
    } else if (typeof window.showConfirm === 'function') {
        window.showConfirm(
            'Delete Template?',
            'Are you sure you want to delete this outreach email template? This action cannot be undone.',
            'Yes, Delete Template',
            true
        ).then(result => {
            if (result && result.isConfirmed) {
                form.submit();
            }
        });
    } else {
        if (confirm('Are you sure you want to delete this email template?')) {
            form.submit();
        }
    }
    return false;
}

function switchToBuilder() {
    const tabTrigger = new bootstrap.Tab(document.getElementById('builder-tab'));
    tabTrigger.show();
}

function switchToLibrary() {
    const tabTrigger = new bootstrap.Tab(document.getElementById('library-tab'));
    tabTrigger.show();
}

function resetBuilderForm() {
    const form = document.getElementById('templateForm');
    form.action = "{{ route('email-templates.store') }}";
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('templateId').value = '';
    document.getElementById('templateName').value = '';
    document.getElementById('templateCategory').value = 'Auto Repair & Garage';
    document.getElementById('templateDescription').value = '';
    document.getElementById('templateIsDefault').checked = false;
    document.getElementById('templateSubject').value = '';
    document.getElementById('builderFormTitle').innerHTML = '<i class="icon-base ti tabler-edit me-1 text-primary"></i> Create Email Template';
    document.getElementById('btnSaveTemplate').innerHTML = '<i class="icon-base ti tabler-device-floppy me-1"></i> Save Template';
    document.getElementById('richEditor').innerHTML = `
        <p>Hello <strong>@{{business_name}}</strong> Team,</p>
        <p>I came across your workshop in <strong>@{{city}}</strong> and wanted to reach out because we help automotive repair garages and service centers simplify daily operations and scale their business.</p>
        <p>Our cloud-based <strong>Automotive POS &amp; Garage Management System</strong> gives you everything needed to run your workshop effortlessly from any device:</p>
        <ul>
            <li>🚗 <strong>Digital Job Cards &amp; Estimates:</strong> Create estimates in 30 seconds and send them for instant WhatsApp/SMS approval.</li>
            <li>🧾 <strong>Instant POS Invoicing &amp; Billing:</strong> Split labor and parts, record taxes, and print/email invoices.</li>
            <li>📦 <strong>Live Spare Parts &amp; Oil Tracking:</strong> Automatic low stock alerts for fluids, filters, and spare parts.</li>
            <li>⏰ <strong>Automated Service Reminders:</strong> Bring customers back for regular oil and maintenance service.</li>
        </ul>
        <p>We are offering a <strong>complimentary 14-day full access demo</strong> for @{{business_name}}.</p>
        <p>Would you be open for a quick 5-minute chat or demo setup this week?</p>
        <p>Best regards,<br><strong>@{{sender_name}}</strong><br>@{{sender_company}} | Automotive SaaS Solutions<br>Phone: @{{phone}}</p>
    `;
}

function editTemplate(tmpl) {
    resetBuilderForm();
    const form = document.getElementById('templateForm');
    form.action = `/email-templates/${tmpl.id}`;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('templateId').value = tmpl.id;
    document.getElementById('templateName').value = tmpl.name;
    document.getElementById('templateCategory').value = tmpl.category || 'Auto Repair & Garage';
    document.getElementById('templateDescription').value = tmpl.description || '';
    document.getElementById('templateIsDefault').checked = Boolean(tmpl.is_default);
    document.getElementById('templateSubject').value = tmpl.subject;
    document.getElementById('richEditor').innerHTML = tmpl.body;
    document.getElementById('builderFormTitle').innerHTML = `<i class="icon-base ti tabler-edit me-1 text-primary"></i> Edit Template: ${tmpl.name}`;
    document.getElementById('btnSaveTemplate').innerHTML = '<i class="icon-base ti tabler-check me-1"></i> Update Template';
    switchToBuilder();
}

function duplicateTemplate(tmpl) {
    resetBuilderForm();
    document.getElementById('templateName').value = `${tmpl.name} (Copy)`;
    document.getElementById('templateCategory').value = tmpl.category || 'Auto Repair & Garage';
    document.getElementById('templateDescription').value = tmpl.description || '';
    document.getElementById('templateIsDefault').checked = false;
    document.getElementById('templateSubject').value = tmpl.subject;
    document.getElementById('richEditor').innerHTML = tmpl.body;
    document.getElementById('builderFormTitle').innerHTML = `<i class="icon-base ti tabler-copy me-1 text-primary"></i> Duplicate Template: ${tmpl.name}`;
    switchToBuilder();
    if (typeof appNotify !== 'undefined') {
        appNotify.info('Template copied into editor. Adjust details and save.');
    }
}

function renderMockPlaceholders(text) {
    const mock = {
        '@{{business_name}}': 'Apex Auto Garage & Tyre Services',
        '@{{email}}': 'service@apexgarage.com',
        '@{{phone}}': '+1 (555) 728-1920',
        '@{{website}}': 'https://apexgarage.com',
        '@{{category}}': 'Car Garage & Tyre Services',
        '@{{address}}': '420 West Industrial Blvd',
        '@{{city}}': 'Chicago, IL',
        '@{{rating}}': '4.9',
        '@{{reviews}}': '214',
        '@{{sender_name}}': '{{ Auth::user()?->name ?? "David Miller" }}',
        '@{{sender_company}}': '{{ Auth::user()?->tenant?->name ?? "Obtain POS" }}',
        '@{{demo_website_url}}': 'https://vektorleads.io/preview/demo-spec-preview-link',
    };
    let out = text;
    for (const [k, v] of Object.entries(mock)) {
        out = out.split(k).join(v);
    }
    return out;
}

function previewTemplateModal(tmpl) {
    document.getElementById('previewSubject').textContent = renderMockPlaceholders(tmpl.subject);
    document.getElementById('previewBody').innerHTML = renderMockPlaceholders(tmpl.body);
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function previewCurrentDraft() {
    const subject = document.getElementById('templateSubject').value || 'No Subject';
    const body = document.getElementById('richEditor').innerHTML;
    previewTemplateModal({ subject, body });
}
</script>
@endpush

