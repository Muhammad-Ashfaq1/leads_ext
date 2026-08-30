@extends('layouts.app')

@section('title', 'Email Templates & Outreach')

@push('styles')
<style>
.editor-toolbar {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
    padding: 0.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}
.editor-content {
    min-height: 220px;
    border: 1px solid #dee2e6;
    border-bottom-left-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
    padding: 0.75rem;
    background: #fff;
    outline: none;
    overflow-y: auto;
    max-height: 450px;
}
.editor-content:focus {
    border-color: #696cff;
    box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
}
.var-pill {
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}
.var-pill:hover {
    transform: translateY(-1px);
    background-color: #696cff !important;
    color: #fff !important;
}
.template-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.template-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}
</style>
@endpush

@section('content')
<div class="pos-glass-card pos-tone-primary mb-4">
    <div class="pos-glass-intro border-bottom">
        <div class="pos-glass-intro-copy">
            <h4 class="pos-glass-intro-title">
                <i class="icon-base ti tabler-template me-1 text-primary"></i> Email Templates &amp; Outreach
            </h4>
            <p class="pos-glass-intro-subtitle">
                Create rich-text outreach templates with dynamic placeholders to contact prospects and businesses individually or in bulk.
            </p>
        </div>
        <div class="pos-glass-intro-actions d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-primary" id="btnCreateNewTemplate">
                <i class="icon-base ti tabler-plus me-1"></i> New Template
            </button>
            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="icon-base ti tabler-users me-1"></i> Prospects Directory
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible m-3 mb-0" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="p-3 border-bottom bg-light-subtle">
        <ul class="nav nav-pills" id="emailTemplatesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active btn-sm" id="library-tab" data-bs-toggle="pill" data-bs-target="#libraryTabPane" type="button" role="tab">
                    <i class="icon-base ti tabler-layout-grid me-1"></i> Template Library ({{ count($templates) }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm" id="builder-tab" data-bs-toggle="pill" data-bs-target="#builderTabPane" type="button" role="tab">
                    <i class="icon-base ti tabler-edit me-1"></i> Template Builder & Editor
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
            @if ($templates->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="icon-base ti tabler-mail-cancel display-4 mb-2"></i>
                    <h5>No email templates yet</h5>
                    <p class="small mb-3">Create your first outreach template to start sending personalized emails to your leads.</p>
                    <button type="button" class="btn btn-sm btn-primary" onclick="switchToBuilder()">
                        <i class="icon-base ti tabler-plus me-1"></i> Create Template
                    </button>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($templates as $tmpl)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card h-100 border shadow-none template-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <span class="badge bg-label-primary mb-1">{{ $tmpl->category ?: 'Outreach' }}</span>
                                            @if ($tmpl->is_default)
                                                <span class="badge bg-label-success mb-1 ms-1"><i class="icon-base ti tabler-check me-1"></i>Default</span>
                                            @endif
                                            <h6 class="card-title mb-0 fw-bold text-heading">{{ $tmpl->name }}</h6>
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
                                        <strong>Subject:</strong> {{ Str::limit($tmpl->subject, 55) }}
                                    </div>
                                    <div class="small text-muted mb-3 flex-grow-1 border rounded p-2 bg-light" style="max-height: 100px; overflow: hidden; font-size: 0.8rem;">
                                        {!! Str::limit(strip_tags($tmpl->body), 140) !!}
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
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="resetBuilderForm()">
                        <i class="icon-base ti tabler-rotate me-1"></i> Reset Form
                    </button>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form id="templateForm" method="POST" action="{{ route('email-templates.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">
                        <input type="hidden" name="template_id" id="templateId" value="">

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="templateName">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="templateName" name="name" placeholder="e.g. B2B Outreach / Partnership" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold" for="templateCategory">Category</label>
                                <select class="form-select" id="templateCategory" name="category">
                                    <option value="Outreach" selected>Outreach</option>
                                    <option value="Proposal">Proposal</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Partnership">Partnership</option>
                                    <option value="Introduction">Introduction</option>
                                    <option value="Special Offer">Special Offer</option>
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
                            <label class="form-label fw-semibold" for="templateSubject">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="templateSubject" name="subject" placeholder="e.g. Quick inquiry regarding @{{business_name}}" required>
                        </div>

                        <!-- Dynamic Variable Insert Bar -->
                        <div class="mb-3 p-2 bg-light-subtle border rounded">
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <small class="fw-bold text-muted me-2"><i class="icon-base ti tabler-code me-1"></i>Insert Dynamic Tag:</small>
                                <span class="badge bg-label-primary var-pill" onclick="insertVariable('@{{business_name}}')">@{{business_name}}</span>
                                <span class="badge bg-label-info var-pill" onclick="insertVariable('@{{email}}')">@{{email}}</span>
                                <span class="badge bg-label-secondary var-pill" onclick="insertVariable('@{{phone}}')">@{{phone}}</span>
                                <span class="badge bg-label-success var-pill" onclick="insertVariable('@{{city}}')">@{{city}}</span>
                                <span class="badge bg-label-warning var-pill" onclick="insertVariable('@{{category}}')">@{{category}}</span>
                                <span class="badge bg-label-dark var-pill" onclick="insertVariable('@{{website}}')">@{{website}}</span>
                                <span class="badge bg-label-danger var-pill" onclick="insertVariable('@{{rating}}')">@{{rating}}</span>
                                <span class="badge bg-label-primary var-pill" onclick="insertVariable('@{{sender_name}}')">@{{sender_name}}</span>
                                <span class="badge bg-label-secondary var-pill" onclick="insertVariable('@{{sender_company}}')">@{{sender_company}}</span>
                                <span class="badge bg-label-success var-pill" onclick="insertVariable('@{{demo_website_url}}')">✨ @{{demo_website_url}}</span>
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
                                <p>Hi <strong>@{{business_name}}</strong> Team,</p>
                                <p>I came across your business in @{{city}} and was very impressed by your services.</p>
                                <p>We specialize in helping @{{category}} companies scale their customer acquisition and digital presence.</p>
                                <p>Would you be open for a quick 5-minute chat this week to explore how we can help you grow?</p>
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
                            <th><i class="icon-base ti tabler-building me-1 text-secondary"></i> Lead Name</th>
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
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="icon-base ti tabler-eye me-1 text-primary"></i> Email Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="border rounded p-3 mb-3 bg-light-subtle">
                    <div class="mb-2"><strong>To:</strong> <span class="text-muted" id="previewTo">Apex Dental Clinic &lt;contact@apexdental.com&gt;</span></div>
                    <div><strong>Subject:</strong> <span class="fw-semibold text-heading" id="previewSubject"></span></div>
                </div>
                <div class="border rounded p-3 bg-white" id="previewBody" style="min-height: 200px;"></div>
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
    if (typeof window.showToast === 'function') {
        window.showToast('info', `Inserted placeholder: ${tag}`, 'Template Editor');
    }
}

function confirmDeleteTemplate(event, form) {
    event.preventDefault();
    if (typeof window.showConfirm === 'function') {
        window.showConfirm(
            'Delete Template?',
            'Are you sure you want to delete this outreach email template? This action cannot be undone.',
            'Yes, Delete Template',
            true
        ).then(result => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    } else {
        if (confirm('Are you sure you want to delete this template?')) {
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
    document.getElementById('templateCategory').value = 'Outreach';
    document.getElementById('templateIsDefault').checked = false;
    document.getElementById('templateSubject').value = '';
    document.getElementById('builderFormTitle').innerHTML = '<i class="icon-base ti tabler-edit me-1 text-primary"></i> Create Email Template';
    document.getElementById('btnSaveTemplate').innerHTML = '<i class="icon-base ti tabler-device-floppy me-1"></i> Save Template';
    document.getElementById('richEditor').innerHTML = `
        <p>Hi <strong>@{{business_name}}</strong> Team,</p>
        <p>I came across your business in @{{city}} and was very impressed by your services.</p>
        <p>We specialize in helping @{{category}} companies scale their customer acquisition and digital presence.</p>
        <p>Would you be open for a quick 5-minute chat this week to explore how we can help you grow?</p>
        <p>Best regards,<br><strong>@{{sender_name}}</strong><br>@{{sender_company}}</p>
    `;
}

function editTemplate(tmpl) {
    resetBuilderForm();
    const form = document.getElementById('templateForm');
    form.action = `/email-templates/${tmpl.id}`;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('templateId').value = tmpl.id;
    document.getElementById('templateName').value = tmpl.name;
    document.getElementById('templateCategory').value = tmpl.category || 'Outreach';
    document.getElementById('templateIsDefault').checked = Boolean(tmpl.is_default);
    document.getElementById('templateSubject').value = tmpl.subject;
    document.getElementById('richEditor').innerHTML = tmpl.body;
    document.getElementById('builderFormTitle').innerHTML = `<i class="icon-base ti tabler-edit me-1 text-primary"></i> Edit Template: ${tmpl.name}`;
    document.getElementById('btnSaveTemplate').innerHTML = '<i class="icon-base ti tabler-check me-1"></i> Update Template';
    switchToBuilder();
}

function renderMockPlaceholders(text) {
    const mock = {
        '@{{business_name}}': 'Apex Dental Clinic',
        '@{{email}}': 'contact@apexdental.com',
        '@{{phone}}': '+1 (555) 392-8192',
        '@{{website}}': 'https://apexdental.com',
        '@{{category}}': 'Dentistry & Oral Care',
        '@{{address}}': '124 Medical Center Blvd',
        '@{{city}}': 'Chicago',
        '@{{rating}}': '4.9',
        '@{{reviews}}': '128',
        '@{{sender_name}}': '{{ Auth::user()?->name ?? "Obtain Team" }}',
        '@{{sender_company}}': '{{ Auth::user()?->tenant?->name ?? "VektorLeads" }}',
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
