@extends('layouts.app')

@section('title', 'Email Inbox & Communications')

@section('content')
<div class="container-fluid p-0" id="gmailAppContainer">
    <!-- Header Card -->
    <div class="pos-glass-card pos-glass-card--scrollable pos-tone-primary mb-4" style="overflow: visible !important; z-index: 105;">
        <div class="pos-glass-intro border-bottom" style="overflow: visible !important;">
            <div class="pos-glass-intro-copy">
                <h4 class="pos-glass-intro-title">
                    <i class="icon-base ti tabler-mail me-1 text-primary"></i> Email Inbox &amp; Outreach Hub
                </h4>
                <p class="pos-glass-intro-subtitle">
                    View incoming customer replies, copy email contents with 1-click, and send direct replies via your Hostinger or Google account.
                </p>
            </div>
            <div class="pos-glass-intro-actions d-flex flex-wrap align-items-center gap-2" style="overflow: visible !important;">
                @if ($account)
                    <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-1 border shadow-xs">
                        <span class="badge {{ $account->isHostinger() ? 'bg-label-primary' : 'bg-label-danger' }} me-2 text-uppercase" style="font-size: 0.68rem;">
                            {{ $account->isHostinger() ? 'Hostinger' : 'Gmail' }}
                        </span>
                        <i class="icon-base ti tabler-circle-check-filled text-success me-1"></i>
                        <span class="small fw-semibold text-truncate" style="max-width: 200px;" title="{{ $account->email }}">
                            {{ $account->email }}
                        </span>
                    </div>

                    <!-- Auto-Sync Dropdown -->
                    <div class="dropdown d-inline-block" style="position: relative; z-index: 1060;">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1 shadow-xs" type="button" id="autoSyncDropdown" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <i class="icon-base ti tabler-clock text-primary"></i>
                            <span id="autoSyncLabel">Auto-sync: 5m</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="z-index: 1070; min-width: 230px; box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;" aria-labelledby="autoSyncDropdown">
                            <li><h6 class="dropdown-header text-uppercase small">Auto-Sync Frequency</h6></li>
                            <li><a class="dropdown-item auto-sync-opt" href="javascript:void(0);" data-interval="300" onclick="setAutoSyncInterval(300, '5m (Recommended)')"><i class="icon-base ti tabler-check me-2 check-5m"></i> Every 5 Minutes (Default)</a></li>
                            <li><a class="dropdown-item auto-sync-opt" href="javascript:void(0);" data-interval="600" onclick="setAutoSyncInterval(600, '10m')"><i class="icon-base ti tabler-check me-2 check-10m d-none"></i> Every 10 Minutes</a></li>
                            <li><a class="dropdown-item auto-sync-opt" href="javascript:void(0);" data-interval="900" onclick="setAutoSyncInterval(900, '15m')"><i class="icon-base ti tabler-check me-2 check-15m d-none"></i> Every 15 Minutes</a></li>
                            <li><a class="dropdown-item auto-sync-opt" href="javascript:void(0);" data-interval="1800" onclick="setAutoSyncInterval(1800, '30m')"><i class="icon-base ti tabler-check me-2 check-30m d-none"></i> Every 30 Minutes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item auto-sync-opt text-secondary" href="javascript:void(0);" data-interval="0" onclick="setAutoSyncInterval(0, 'Off (Manual)')"><i class="icon-base ti tabler-check me-2 check-0 d-none"></i> Off (Manual Sync Only)</a></li>
                        </ul>
                    </div>

                    <button type="button" id="btnSyncGmail" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-xs">
                        <i class="icon-base ti tabler-refresh" id="syncSpinnerIcon"></i>
                        <span>Sync Now</span>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-primary shadow-xs" data-bs-toggle="modal" data-bs-target="#connectHostingerModal" title="Account settings">
                        <i class="icon-base ti tabler-settings"></i>
                    </button>

                    <form action="{{ route('gmail.disconnect', $account->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Disconnect this email account?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger shadow-xs" title="Disconnect Email Account">
                            <i class="icon-base ti tabler-plug-connected-x"></i>
                        </button>
                    </form>
                @else
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#connectHostingerModal">
                        <i class="icon-base ti tabler-mail-plus"></i> Connect Hostinger Email
                    </button>
                    @if ($isConfigured)
                        <a href="{{ route('gmail.connect') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="icon-base ti tabler-brand-google"></i> Connect Gmail
                        </a>
                    @endif
                @endif
            </div>
        </div>

        @if (!$account)
            <div class="p-4 text-center">
                <div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
                    <i class="icon-base ti tabler-mail-fast fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">No Email Account Connected</h5>
                <p class="text-secondary mx-auto mb-3" style="max-width: 540px;">
                    Connect your <strong>Hostinger Business Email</strong> or Google account to automatically pull in customer replies, view lead emails in one dashboard, and reply directly from the app.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#connectHostingerModal">
                        <i class="icon-base ti tabler-mail-plus me-1"></i> Connect Hostinger Email (IMAP &amp; SMTP)
                    </button>
                    @if ($isConfigured)
                        <a href="{{ route('gmail.connect') }}" class="btn btn-outline-primary px-4 py-2">
                            <i class="icon-base ti tabler-brand-google me-1"></i> Connect Google Account
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if ($account)
        <!-- Inbox Layout: Split Pane -->
        <div class="row g-3">
            <!-- Left Pane: Messages List -->
            <div class="col-12 col-lg-5 col-xl-4">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100" style="min-height: 640px;">
                    <!-- Filter and Search Header -->
                    <div class="p-3 border-bottom bg-light">
                        <form method="GET" action="{{ route('gmail.index') }}" class="mb-3">
                            @if ($folder !== 'all')
                                <input type="hidden" name="folder" value="{{ $folder }}">
                            @endif
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="icon-base ti tabler-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search emails, sender, lead..." value="{{ $search }}">
                                @if (!empty($search))
                                    <a href="{{ route('gmail.index', ['folder' => $folder]) }}" class="btn btn-outline-secondary">
                                        <i class="icon-base ti tabler-x"></i>
                                    </a>
                                @endif
                            </div>
                        </form>

                        <!-- Clean Spaced Filter Pills -->
                        <div class="filter-pills-row d-flex flex-wrap align-items-center gap-2">
                            <a href="{{ route('gmail.index', ['folder' => 'all', 'search' => $search]) }}" 
                               class="filter-pill-badge {{ $folder === 'all' ? 'active' : '' }}">
                                <span>All</span>
                                <span class="pill-count">{{ $totalCount }}</span>
                            </a>
                            <a href="{{ route('gmail.index', ['folder' => 'unread', 'search' => $search]) }}" 
                               class="filter-pill-badge {{ $folder === 'unread' ? 'active' : '' }}">
                                <span>Unread</span>
                                <span class="pill-count {{ $unreadCount > 0 ? 'bg-danger text-white' : '' }}">{{ $unreadCount }}</span>
                            </a>
                            <a href="{{ route('gmail.index', ['folder' => 'matched_leads', 'search' => $search]) }}" 
                               class="filter-pill-badge {{ $folder === 'matched_leads' ? 'active' : '' }}">
                                <i class="icon-base ti tabler-target"></i>
                                <span>Leads</span>
                                <span class="pill-count">{{ $matchedLeadsCount }}</span>
                            </a>
                            <a href="{{ route('gmail.index', ['folder' => 'starred', 'search' => $search]) }}" 
                               class="filter-pill-badge {{ $folder === 'starred' ? 'active' : '' }}">
                                <i class="icon-base ti tabler-star text-warning"></i>
                                <span>Starred</span>
                                <span class="pill-count">{{ $starredCount }}</span>
                            </a>
                        </div>
                    </div>

                    <!-- Messages List Scrollable Container -->
                    <div class="list-group list-group-flush overflow-auto" id="messageListContainer" style="max-height: 680px;">
                        @forelse ($messages as $msg)
                            @php
                                $senderInitials = strtoupper(substr($msg->sender_name ?: $msg->sender_email, 0, 2));
                            @endphp
                            <div class="list-group-item list-group-item-action p-3 message-list-item {{ !$msg->is_read ? 'bg-light-subtle fw-medium border-start border-primary border-3' : '' }}"
                                 role="button"
                                 data-message-id="{{ $msg->id }}"
                                 onclick="loadMessageDetail({{ $msg->id }})">
                                <div class="d-flex align-items-start justify-content-between mb-1">
                                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                        <div class="avatar avatar-xs bg-label-secondary rounded-circle d-flex align-items-center justify-content-center text-uppercase fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            {{ $senderInitials }}
                                        </div>
                                        <span class="text-truncate fw-semibold text-heading" style="max-width: 140px;" title="{{ $msg->sender_name ?: $msg->sender_email }}">
                                            {{ $msg->sender_name ?: $msg->sender_email }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-icon p-0 text-secondary star-toggle-btn" onclick="event.stopPropagation(); toggleStar({{ $msg->id }}, this)">
                                            <i class="icon-base ti {{ $msg->is_starred ? 'tabler-star-filled text-warning' : 'tabler-star' }}"></i>
                                        </button>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            {{ $msg->received_at ? $msg->received_at->diffForHumans(null, true) : '' }}
                                        </small>
                                    </div>
                                </div>

                                <div class="text-truncate small text-dark fw-semibold mb-1">
                                    {{ $msg->subject ?: '(No Subject)' }}
                                </div>

                                <p class="text-muted small mb-2 text-truncate" style="font-size: 0.8125rem;">
                                    {{ $msg->snippet ?: 'No preview available' }}
                                </p>

                                <div class="d-flex align-items-center justify-content-between">
                                    @if ($msg->extractedLead)
                                        <span class="badge bg-label-info py-1 px-2 text-truncate" style="max-width: 180px;" title="{{ $msg->extractedLead->business_name }}">
                                            <i class="icon-base ti tabler-user-check me-1"></i> {{ $msg->extractedLead->business_name }}
                                        </span>
                                    @else
                                        <span></span>
                                    @endif

                                    @if (!$msg->is_read)
                                        <span class="badge rounded-pill bg-primary" style="font-size: 0.65rem;">NEW</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-5 text-center text-muted">
                                <i class="icon-base ti tabler-inbox-off fs-1 text-secondary mb-2"></i>
                                <h6>No emails found</h6>
                                <p class="small text-secondary mb-0">No emails matched your current filter.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($messages->hasPages())
                        <div class="p-2 border-top bg-light text-center">
                            {{ $messages->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Pane: Message Preview & Actions -->
            <div class="col-12 col-lg-7 col-xl-8">
                <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden" id="messagePreviewCard" style="min-height: 640px;">
                    <!-- Placeholder State -->
                    <div id="previewEmptyState" class="d-flex flex-column align-items-center justify-content-center h-100 p-5 text-center text-muted">
                        <div class="avatar avatar-xl bg-label-secondary rounded-circle mb-3 d-flex align-items-center justify-content-center">
                            <i class="icon-base ti tabler-mail-opened fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-heading">Select an Email to Preview</h5>
                        <p class="text-secondary small" style="max-width: 420px;">
                            Choose an email from the left list to view the conversation, copy email text, and reply directly via your Hostinger account.
                        </p>
                    </div>

                    <!-- Active Message Content Container -->
                    <div id="previewContentContainer" class="d-none d-flex flex-column h-100">
                        <!-- Top Action Toolbar -->
                        <div class="p-3 border-bottom bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <!-- Reply & 1-Click Copy Actions Group -->
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-success shadow-xs fw-semibold" id="btnOpenReplyModal" onclick="openReplyModal()" title="Reply directly to this email">
                                    <i class="icon-base ti tabler-arrow-back-up me-1"></i> Reply to Email
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary shadow-xs" id="btnCopyEmail" onclick="copySenderEmail()" title="Copy sender email address to clipboard">
                                    <i class="icon-base ti tabler-copy me-1"></i> Copy Email
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary shadow-xs" id="btnCopyBody" onclick="copyEmailBody()" title="Copy plain text message body to clipboard">
                                    <i class="icon-base ti tabler-clipboard-text me-1"></i> Copy Body Text
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary shadow-xs" id="btnCopySubject" onclick="copyEmailSubject()" title="Copy subject line to clipboard">
                                    <i class="icon-base ti tabler-copy me-1"></i> Copy Subject
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary shadow-xs" id="btnCopyFull" onclick="copyFullContent()" title="Copy entire email breakdown to clipboard">
                                    <i class="icon-base ti tabler-file-text me-1"></i> Copy Full Info
                                </button>
                            </div>

                            <!-- Star and Options -->
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" id="previewStarBtn" onclick="toggleActiveStar()" title="Toggle Star">
                                    <i class="icon-base ti tabler-star" id="previewStarIcon"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" id="previewDeleteBtn" onclick="deleteActiveMessage()" title="Delete from view">
                                    <i class="icon-base ti tabler-trash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Matched Lead Alert Banner -->
                        <div id="leadBanner" class="p-3 bg-info-subtle border-bottom d-none">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary rounded-pill p-2">
                                        <i class="icon-base ti tabler-target fs-5"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold text-dark" id="leadNameText">Prospect Name</div>
                                        <div class="small text-secondary" id="leadMetaText">Category • City • Phone</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="#" id="leadPreviewLink" target="_blank" class="btn btn-xs btn-outline-primary d-none">
                                        <i class="icon-base ti tabler-device-laptop me-1"></i> Demo Website
                                    </a>
                                    <a href="#" id="leadDirectoryLink" class="btn btn-xs btn-primary">
                                        <i class="icon-base ti tabler-external-link me-1"></i> View Lead
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Email Header & Subject -->
                        <div class="p-3 border-bottom">
                            <h5 class="fw-bold text-heading mb-2" id="previewSubjectText">(Subject)</h5>
                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-md bg-label-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" id="previewAvatar">
                                        JD
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-heading" id="previewSenderName">Sender Name</div>
                                        <div class="small text-secondary" id="previewSenderEmail">sender@example.com</div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="small fw-medium text-dark" id="previewDate">Sep 05, 2026 12:00 AM</div>
                                    <div class="small text-muted" id="previewDateDiff">10 minutes ago</div>
                                </div>
                            </div>
                        </div>

                        <!-- Email Content Body -->
                        <div class="p-3 flex-grow-1 overflow-auto" style="min-height: 320px;">
                            <ul class="nav nav-tabs nav-tabs-sm mb-3" id="bodyViewTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active py-1 px-3" id="html-tab" data-bs-toggle="tab" data-bs-target="#tabHtml" type="button">
                                        <i class="icon-base ti tabler-code me-1"></i> Formatted View
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-1 px-3" id="text-tab" data-bs-toggle="tab" data-bs-target="#tabText" type="button">
                                        <i class="icon-base ti tabler-file-text me-1"></i> Plain Text (Clean)
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content border-0 p-0">
                                <div class="tab-pane fade show active" id="tabHtml" role="tabpanel">
                                    <div id="previewHtmlContainer" class="p-2 bg-white rounded border" style="min-height: 250px; overflow-x: auto;"></div>
                                </div>
                                <div class="tab-pane fade" id="tabText" role="tabpanel">
                                    <div class="position-relative">
                                        <pre id="previewTextContainer" class="p-3 bg-light rounded text-dark font-monospace small" style="white-space: pre-wrap; word-break: break-word; min-height: 250px;"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Connect Hostinger Modal -->
<div class="modal fade" id="connectHostingerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('gmail.connect-hostinger') }}">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="icon-base ti tabler-mail-plus text-primary me-2"></i> Connect Hostinger Email Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="icon-base ti tabler-info-circle me-1"></i>
                        Enter your Hostinger email and password. All incoming messages will be synchronized via IMAP and replies will be sent via Hostinger SMTP.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hostinger Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="info@yourdomain.com" required value="{{ old('email', $account?->email) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
                            <input type="password" name="password" id="hostingerPasswordInput" class="form-control" placeholder="Hostinger email password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="const el = document.getElementById('hostingerPasswordInput'); el.type = el.type === 'password' ? 'text' : 'password';">
                                <i class="icon-base ti tabler-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sender Display Name (Optional)</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Sales Team" value="{{ old('name', $account?->name) }}">
                    </div>

                    <!-- Advanced Hostinger Server Settings (Collapsed by default) -->
                    <div class="mb-2">
                        <a class="small fw-semibold text-decoration-none" data-bs-toggle="collapse" href="#advancedServerSettings" role="button">
                            <i class="icon-base ti tabler-adjustments me-1"></i> Server Configuration (Default: Hostinger)
                        </a>
                    </div>
                    <div class="collapse" id="advancedServerSettings">
                        <div class="p-3 bg-light rounded border mt-2">
                            <div class="row g-2 mb-2">
                                <div class="col-8">
                                    <label class="form-label small fw-semibold">IMAP Host</label>
                                    <input type="text" name="imap_host" class="form-control form-control-sm" value="imap.hostinger.com">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">IMAP Port</label>
                                    <input type="number" name="imap_port" class="form-control form-control-sm" value="993">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-8">
                                    <label class="form-label small fw-semibold">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control form-control-sm" value="smtp.hostinger.com">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-control form-control-sm" value="465">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitHostinger">
                        <i class="icon-base ti tabler-plug-connected me-1"></i> Connect &amp; Sync Emails
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reply to Email Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="replyEmailForm" onsubmit="submitReply(event)">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="icon-base ti tabler-arrow-back-up text-success me-2"></i> Reply to Email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">To</label>
                        <input type="email" id="replyToInput" class="form-control form-control-sm bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject</label>
                        <input type="text" id="replySubjectInput" class="form-control form-control-sm" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Reply Message <span class="text-danger">*</span></label>
                        <textarea id="replyBodyInput" class="form-control" rows="8" placeholder="Type your response here..." required></textarea>
                    </div>

                    <div class="border rounded p-3 bg-light-subtle">
                        <div class="small fw-semibold text-secondary mb-1">
                            <i class="icon-base ti tabler-quote me-1"></i> Quoted Thread Context:
                        </div>
                        <div id="replyQuotedPreview" class="small text-muted font-monospace" style="max-height: 120px; overflow-y: auto; white-space: pre-wrap;"></div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="btnSendReply">
                        <i class="icon-base ti tabler-send me-1"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('page-style')
<style>
    /* Clean, well-spaced filter pills */
    .filter-pills-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem !important;
    }
    .filter-pill-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
        border-radius: 50rem;
        text-decoration: none;
        border: 1px solid rgba(105, 108, 255, 0.22);
        background: #ffffff;
        color: #566a7f;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
        line-height: 1.2;
    }
    .filter-pill-badge:hover {
        background: rgba(105, 108, 255, 0.08);
        color: #696cff;
        border-color: rgba(105, 108, 255, 0.4);
    }
    .filter-pill-badge.active {
        background: #696cff !important;
        color: #ffffff !important;
        border-color: #696cff !important;
        box-shadow: 0 2px 6px rgba(105, 108, 255, 0.38);
    }
    .filter-pill-badge .pill-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.25rem;
        height: 1.25rem;
        padding: 0 0.35rem;
        border-radius: 50rem;
        font-size: 0.72rem;
        font-weight: 600;
        background: rgba(105, 108, 255, 0.12);
        color: #696cff;
    }
    .filter-pill-badge.active .pill-count {
        background: rgba(255, 255, 255, 0.28) !important;
        color: #ffffff !important;
    }

    /* Ensure header card and dropdown container never clip child menus */
    #gmailAppContainer .pos-glass-card:first-child,
    #gmailAppContainer .pos-glass-card:first-child .pos-glass-intro,
    #gmailAppContainer .pos-glass-card:first-child .pos-glass-intro-actions {
        overflow: visible !important;
    }

    #autoSyncDropdown + .dropdown-menu {
        z-index: 9999 !important;
        margin-top: 0.5rem !important;
        border-radius: 0.75rem !important;
    }

    .message-list-item {
        transition: all 0.15s ease-in-out;
        cursor: pointer;
    }
    .message-list-item:hover {
        background-color: rgba(105, 108, 255, 0.05) !important;
    }
    .message-list-item.active-selected {
        background-color: rgba(105, 108, 255, 0.12) !important;
        border-inline-start: 4px solid #696cff !important;
    }
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

@push('page-script')
<script>
    let activeMessageData = null;
    let autoSyncTimer = null;
    let isSyncing = false;

    // Load message details via AJAX
    function loadMessageDetail(messageId) {
        document.querySelectorAll('.message-list-item').forEach(el => {
            el.classList.remove('active-selected');
        });
        const selectedEl = document.querySelector(`.message-list-item[data-message-id="${messageId}"]`);
        if (selectedEl) {
            selectedEl.classList.add('active-selected');
            const newBadge = selectedEl.querySelector('.badge.bg-primary');
            if (newBadge) newBadge.remove();
            selectedEl.classList.remove('fw-medium', 'border-primary', 'border-3');
        }

        fetch(`/gmail/messages/${messageId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.message) {
                renderMessageDetail(data.message);
            }
        })
        .catch(err => {
            console.error('Error fetching email details:', err);
            toastr.error('Failed to load message content.');
        });
    }

    function renderMessageDetail(msg) {
        activeMessageData = msg;

        document.getElementById('previewEmptyState').classList.add('d-none');
        document.getElementById('previewContentContainer').classList.remove('d-none');

        // Subject & Header
        document.getElementById('previewSubjectText').textContent = msg.subject || '(No Subject)';
        document.getElementById('previewSenderName').textContent = msg.sender_name || msg.sender_email;
        document.getElementById('previewSenderEmail').textContent = `<${msg.sender_email}>` + (msg.recipient_email ? ` to ${msg.recipient_email}` : '');
        document.getElementById('previewDate').textContent = msg.received_at || '';
        document.getElementById('previewDateDiff').textContent = msg.received_at_diff || '';

        const initials = (msg.sender_name || msg.sender_email || 'U').substring(0, 2).toUpperCase();
        document.getElementById('previewAvatar').textContent = initials;

        updateStarIcon(msg.is_starred);

        // Associated Lead Section
        const leadBanner = document.getElementById('leadBanner');
        if (msg.extracted_lead) {
            leadBanner.classList.remove('d-none');
            document.getElementById('leadNameText').textContent = msg.extracted_lead.business_name;
            document.getElementById('leadMetaText').textContent = [
                msg.extracted_lead.category,
                msg.extracted_lead.city,
                msg.extracted_lead.phone
            ].filter(Boolean).join(' • ');

            document.getElementById('leadDirectoryLink').href = `/leads?search=${encodeURIComponent(msg.extracted_lead.business_name)}`;

            const previewLink = document.getElementById('leadPreviewLink');
            if (msg.extracted_lead.preview_url) {
                previewLink.href = msg.extracted_lead.preview_url;
                previewLink.classList.remove('d-none');
            } else {
                previewLink.classList.add('d-none');
            }
        } else {
            leadBanner.classList.add('d-none');
        }

        // Body Contents
        const plainText = msg.body_text || msg.snippet || 'No message content available.';
        document.getElementById('previewTextContainer').textContent = plainText;

        const htmlContainer = document.getElementById('previewHtmlContainer');
        if (msg.body_html) {
            htmlContainer.innerHTML = msg.body_html;
        } else {
            htmlContainer.innerHTML = `<p class="text-muted">${escapeHtml(plainText).replace(/\n/g, '<br>')}</p>`;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function openReplyModal() {
        if (!activeMessageData) {
            toastr.warning('Please select an email first.');
            return;
        }

        document.getElementById('replyToInput').value = activeMessageData.sender_email;
        let subj = activeMessageData.subject || '';
        if (!subj.toLowerCase().startsWith('re:')) {
            subj = 'Re: ' + subj;
        }
        document.getElementById('replySubjectInput').value = subj;
        document.getElementById('replyBodyInput').value = '';

        const quoted = `From: ${activeMessageData.sender_name || activeMessageData.sender_email} <${activeMessageData.sender_email}>\n` +
                       `Date: ${activeMessageData.received_at}\n` +
                       `Subject: ${activeMessageData.subject}\n\n` +
                       (activeMessageData.body_text || activeMessageData.snippet || '');
        document.getElementById('replyQuotedPreview').textContent = quoted;

        const modal = new bootstrap.Modal(document.getElementById('replyModal'));
        modal.show();
    }

    function submitReply(event) {
        event.preventDefault();
        if (!activeMessageData) return;

        const body = document.getElementById('replyBodyInput').value.trim();
        const subject = document.getElementById('replySubjectInput').value.trim();
        const btn = document.getElementById('btnSendReply');

        if (!body) {
            toastr.warning('Please enter a reply message.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

        fetch(`/gmail/messages/${activeMessageData.id}/reply`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ body: body, subject: subject })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || 'Reply sent successfully!');
                const modalEl = document.getElementById('replyModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            } else {
                toastr.error(data.message || 'Failed to send reply.');
            }
        })
        .catch(err => {
            console.error('Reply dispatch error:', err);
            toastr.error('An error occurred while sending the reply.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="icon-base ti tabler-send me-1"></i> Send Reply';
        });
    }

    function copyToClipboard(text, buttonId, successMessage) {
        if (!text) {
            toastr.warning('Nothing to copy.');
            return;
        }

        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById(buttonId);
            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="icon-base ti tabler-check me-1 text-success"></i> Copied!';
                btn.classList.add('btn-success');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success');
                }, 2000);
            }
            toastr.success(successMessage || 'Copied to clipboard!');
        }).catch(err => {
            console.error('Clipboard error:', err);
            toastr.error('Failed to copy to clipboard.');
        });
    }

    function copySenderEmail() {
        if (!activeMessageData) return;
        copyToClipboard(activeMessageData.sender_email, 'btnCopyEmail', `Copied: ${activeMessageData.sender_email}`);
    }

    function copyEmailBody() {
        if (!activeMessageData) return;
        const body = activeMessageData.body_text || activeMessageData.snippet || '';
        copyToClipboard(body, 'btnCopyBody', 'Message body copied to clipboard!');
    }

    function copyEmailSubject() {
        if (!activeMessageData) return;
        copyToClipboard(activeMessageData.subject, 'btnCopySubject', 'Subject copied to clipboard!');
    }

    function copyFullContent() {
        if (!activeMessageData) return;
        const full = `From: ${activeMessageData.sender_name} <${activeMessageData.sender_email}>\n` +
                     `Date: ${activeMessageData.received_at}\n` +
                     `Subject: ${activeMessageData.subject}\n\n` +
                     `${activeMessageData.body_text || activeMessageData.snippet || ''}`;
        copyToClipboard(full, 'btnCopyFull', 'Full email breakdown copied!');
    }

    function updateStarIcon(isStarred) {
        const icon = document.getElementById('previewStarIcon');
        if (icon) {
            if (isStarred) {
                icon.className = 'icon-base ti tabler-star-filled text-warning';
            } else {
                icon.className = 'icon-base ti tabler-star';
            }
        }
    }

    function toggleActiveStar() {
        if (!activeMessageData) return;
        fetch(`/gmail/messages/${activeMessageData.id}/star`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                activeMessageData.is_starred = data.is_starred;
                updateStarIcon(data.is_starred);

                const starBtn = document.querySelector(`.message-list-item[data-message-id="${activeMessageData.id}"] .star-toggle-btn i`);
                if (starBtn) {
                    starBtn.className = `icon-base ti ${data.is_starred ? 'tabler-star-filled text-warning' : 'tabler-star'}`;
                }
            }
        });
    }

    function toggleStar(msgId, btn) {
        fetch(`/gmail/messages/${msgId}/star`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = `icon-base ti ${data.is_starred ? 'tabler-star-filled text-warning' : 'tabler-star'}`;
                }
                if (activeMessageData && activeMessageData.id === msgId) {
                    activeMessageData.is_starred = data.is_starred;
                    updateStarIcon(data.is_starred);
                }
            }
        });
    }

    function deleteActiveMessage() {
        if (!activeMessageData) return;
        if (!confirm('Remove this email from view?')) return;

        fetch(`/gmail/messages/${activeMessageData.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                toastr.success('Email removed.');
                const item = document.querySelector(`.message-list-item[data-message-id="${activeMessageData.id}"]`);
                if (item) item.remove();
                document.getElementById('previewEmptyState').classList.remove('d-none');
                document.getElementById('previewContentContainer').classList.add('d-none');
                activeMessageData = null;
            }
        });
    }

    // Auto-Sync and Manual Sync Routine
    function triggerSync(isManual = false) {
        if (isSyncing) return;
        isSyncing = true;

        const icon = document.getElementById('syncSpinnerIcon');
        const syncBtn = document.getElementById('btnSyncGmail');
        if (icon) icon.classList.add('ti-spin');
        if (syncBtn && isManual) syncBtn.disabled = true;

        fetch('/gmail/sync', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (isManual) {
                    toastr.success(`Synced ${data.synced_count} emails (${data.new_count} new)!`);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.new_count > 0) {
                    toastr.info(`📥 ${data.new_count} new email(s) received!`, 'Auto-Sync');
                    setTimeout(() => window.location.reload(), 1500);
                }
            } else if (isManual) {
                toastr.error(data.error || 'Failed to sync emails.');
            }
        })
        .catch(err => {
            if (isManual) toastr.error('Sync request failed.');
        })
        .finally(() => {
            if (icon) icon.classList.remove('ti-spin');
            if (syncBtn && isManual) syncBtn.disabled = false;
            isSyncing = false;
        });
    }

    function setAutoSyncInterval(seconds, labelText) {
        localStorage.setItem('email_auto_sync_interval', seconds);
        localStorage.setItem('email_auto_sync_label', labelText);

        document.getElementById('autoSyncLabel').textContent = `Auto-sync: ${labelText.split(' ')[0]}`;

        // Update checkmarks
        document.querySelectorAll('.dropdown-menu [class*="check-"]').forEach(el => el.classList.add('d-none'));
        const activeCheck = document.querySelector(`.check-${seconds === 300 ? '5m' : (seconds === 600 ? '10m' : (seconds === 900 ? '15m' : (seconds === 1800 ? '30m' : '0')))}`);
        if (activeCheck) activeCheck.classList.remove('d-none');

        if (autoSyncTimer) {
            clearInterval(autoSyncTimer);
            autoSyncTimer = null;
        }

        if (seconds > 0) {
            autoSyncTimer = setInterval(() => {
                triggerSync(false);
            }, seconds * 1000);
        }

        toastr.success(`Auto-sync frequency set to: ${labelText}`);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Manual Sync Button
        const syncBtn = document.getElementById('btnSyncGmail');
        if (syncBtn) {
            syncBtn.addEventListener('click', () => triggerSync(true));
        }

        // Initialize Auto-Sync timer
        const savedInterval = parseInt(localStorage.getItem('email_auto_sync_interval') ?? '300', 10);
        const savedLabel = localStorage.getItem('email_auto_sync_label') ?? '5m (Recommended)';

        const labelEl = document.getElementById('autoSyncLabel');
        if (labelEl) {
            labelEl.textContent = `Auto-sync: ${savedLabel.split(' ')[0]}`;
        }

        if (savedInterval > 0) {
            autoSyncTimer = setInterval(() => {
                triggerSync(false);
            }, savedInterval * 1000);
        }

        // Auto-select first message if available
        const firstMsg = document.querySelector('.message-list-item');
        if (firstMsg) {
            const firstId = firstMsg.getAttribute('data-message-id');
            if (firstId) {
                loadMessageDetail(firstId);
            }
        }
    });
</script>
@endpush
