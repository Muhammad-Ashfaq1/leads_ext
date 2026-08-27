(() => {
    const cfg = window.ExtractorConfig || {};
    const STORAGE_KEY = 'leads_extractor_live_session';
    const state = {
        jobId: null,
        source: null,
        leads: new Map(),
        selectedKeys: new Set(),
        running: false,
        leadCounter: 0,
        isSaved: false,
        filters: {
            query: '',
            email: 'all',
            website: 'all',
            phone: 'all',
            rating: 'all',
            sort: 'newest',
            quick: new Set(),
        },
    };

    const els = {
        // Engine Mode controls
        engineGoogleApi: document.getElementById('engineGoogleApi'),
        engineBrowser: document.getElementById('engineBrowser'),
        customOptionGoogleApi: document.getElementById('customOptionGoogleApi'),
        customOptionBrowser: document.getElementById('customOptionBrowser'),
        promptLabel: document.getElementById('promptLabel'),
        promptInputCol: document.getElementById('promptInputCol'),
        locationInputCol: document.getElementById('locationInputCol'),
        locationInput: document.getElementById('locationInput'),
        apiKeyRow: document.getElementById('apiKeyRow'),
        toggleApiKeyBtn: document.getElementById('toggleApiKeyBtn'),
        customApiKeyInput: document.getElementById('customApiKeyInput'),
        toggleKeyVisibilityBtn: document.getElementById('toggleKeyVisibilityBtn'),
        verifyToggleWrap: document.getElementById('verifyToggleWrap'),

        // Pre-Extraction Filters (API Calling)
        preFiltersContainer: document.getElementById('preFiltersContainer'),
        preReqWebsite: document.getElementById('preReqWebsite'),
        preReqPhone: document.getElementById('preReqPhone'),
        preReqEmail: document.getElementById('preReqEmail'),
        preMinRating: document.getElementById('preMinRating'),

        // Extraction inputs & controls
        prompt: document.getElementById('promptInput'),
        limit: document.getElementById('limitInput'),
        start: document.getElementById('startBtn'),
        stop: document.getElementById('stopBtn'),
        stopVerify: document.getElementById('stopFromVerifyBtn'),
        newExtraction: document.getElementById('newExtractionBtn'),
        summaryNew: document.getElementById('summaryNewBtn'),
        clear: document.getElementById('clearBtn'),
        exportSummary: document.getElementById('exportBtn'),
        mock: document.getElementById('mockToggle'),
        verify: document.getElementById('verifyToggle'),
        completeMock: document.getElementById('completeMockVerifyBtn'),
        openVerification: document.getElementById('openVerificationBtn'),
        verificationCard: document.getElementById('verificationCard'),
        verificationHint: document.getElementById('verificationHint'),
        summaryCard: document.getElementById('summaryCard'),
        summaryStats: document.getElementById('summaryStats'),
        statusDot: document.getElementById('statusDot'),
        statusLabel: document.getElementById('statusLabel'),
        searchLabel: document.getElementById('searchLabel'),
        activity: document.getElementById('activityLabel'),
        alert: document.getElementById('statusAlert'),
        kpiLeads: document.getElementById('kpiLeads'),
        kpiSeen: document.getElementById('kpiSeen'),
        kpiEmails: document.getElementById('kpiEmails'),
        kpiWebsites: document.getElementById('kpiWebsites'),

        // Leads section
        leadsGrid: document.getElementById('leadsGrid'),
        leadsEmpty: document.getElementById('leadsEmpty'),
        noFilterResults: document.getElementById('noFilterResults'),
        leadCountBadge: document.getElementById('leadCountBadge'),
        leadFilterBadge: document.getElementById('leadFilterBadge'),
        leadSelectedBadge: document.getElementById('leadSelectedBadge'),
        selectedRatioText: document.getElementById('selectedRatioText'),
        leadsSummaryText: document.getElementById('leadsSummaryText'),
        masterCheckbox: document.getElementById('masterCheckbox'),
        masterCheckboxLabel: document.getElementById('masterCheckboxLabel'),
        saveAllDiscoveredBtn: document.getElementById('saveAllDiscoveredBtn'),
        saveAllCount: document.getElementById('saveAllCount'),

        // Filter controls
        searchInput: document.getElementById('leadSearchInput'),
        searchClear: document.getElementById('leadSearchClear'),
        filterEmail: document.getElementById('filterEmail'),
        filterWebsite: document.getElementById('filterWebsite'),
        filterPhone: document.getElementById('filterPhone'),
        filterRating: document.getElementById('filterRating'),
        sortLeads: document.getElementById('sortLeads'),
        filterChips: document.querySelectorAll('.extractor-filter-chip'),
        resetFiltersBtn: document.getElementById('resetFiltersBtn'),
        noFilterResetBtn: document.getElementById('noFilterResetBtn'),

        // Bulk toolbar
        bulkBar: document.getElementById('bulkBar'),
        selectAllCheckbox: document.getElementById('selectAllCheckbox'),
        bulkCountLabel: document.getElementById('bulkCountLabel'),
        selectAllFilteredBtn: document.getElementById('selectAllFilteredBtn'),
        bulkFilteredCount: document.getElementById('bulkFilteredCount'),
        bulkDeselectBtn: document.getElementById('bulkDeselectBtn'),
        bulkSendEmailBtn: document.getElementById('bulkSendEmailBtn'),
        bulkSaveBtn: document.getElementById('bulkSaveBtn'),
        bulkExportDropdownBtn: document.getElementById('bulkExportDropdownBtn'),
        bulkExportExcelBtn: document.getElementById('bulkExportExcelBtn'),
        bulkExportCsvBtn: document.getElementById('bulkExportCsvBtn'),
        bulkExportJsonBtn: document.getElementById('bulkExportJsonBtn'),
        bulkCopyEmailsBtn: document.getElementById('bulkCopyEmailsBtn'),
        bulkCopyPhonesBtn: document.getElementById('bulkCopyPhonesBtn'),
        bulkDiscardBtn: document.getElementById('bulkDiscardBtn'),

        // Email Modal Elements
        extractorSendEmailModalEl: document.getElementById('extractorSendEmailModal'),
        extractorModalRecipients: document.getElementById('extractorModalRecipients'),
        extractorModalEmailBadge: document.getElementById('extractorModalEmailBadge'),
        extractorTemplateSelect: document.getElementById('extractorTemplateSelect'),
        extractorModalSubject: document.getElementById('extractorModalSubject'),
        extractorModalEditor: document.getElementById('extractorModalEditor'),
        btnExtractorConfirmSend: document.getElementById('btnExtractorConfirmSend'),

        // Export Dropdown
        exportDropdownBtn: document.getElementById('exportDropdownBtn'),
        exportAllExcelBtn: document.getElementById('exportAllExcelBtn'),
        exportAllCsvBtn: document.getElementById('exportAllCsvBtn'),
        exportAllJsonBtn: document.getElementById('exportAllJsonBtn'),
        exportFilteredExcelBtn: document.getElementById('exportFilteredExcelBtn'),
        exportFilteredCsvBtn: document.getElementById('exportFilteredCsvBtn'),
        exportFilteredJsonBtn: document.getElementById('exportFilteredJsonBtn'),

        // Toast
        toastEl: document.getElementById('extractorToast'),
        toastMessage: document.getElementById('toastMessage'),
    };

    let toastInstance = null;
    if (els.toastEl && window.bootstrap && window.bootstrap.Toast) {
        toastInstance = new window.bootstrap.Toast(els.toastEl, { delay: 3500 });
    }

    const labels = {
        ready: 'Ready',
        starting: 'Starting',
        searching: 'Searching Google Maps',
        extracting: 'Extraction Running',
        enriching: 'Enriching website',
        waiting_for_human_verification: 'Waiting for Human Verification',
        completed: 'Completed',
        cancelled: 'Cancelled',
        error: 'Error',
        verification_timeout: 'Verification timed out',
        blocked: 'Blocked',
    };

    function showToast(message, isDanger = false) {
        if (!els.toastEl || !els.toastMessage) return;
        els.toastMessage.textContent = message;
        els.toastEl.className = `toast align-items-center border-0 ${isDanger ? 'text-bg-danger' : 'text-bg-primary'}`;
        if (toastInstance) {
            toastInstance.show();
        }
    }

    function jobUrl(template) {
        return template.replace('__JOB__', encodeURIComponent(state.jobId));
    }

    function headers(json = true) {
        const out = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': cfg.csrf || '',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (json) out['Content-Type'] = 'application/json';
        return out;
    }

    function setStatus(status, activity) {
        els.statusDot.dataset.status = status;
        els.statusLabel.textContent = labels[status] || status;
        if (activity) els.activity.textContent = `Current Activity: ${activity}`;
    }

    function setAlert(type, message, show = true) {
        if (els.alert) {
            els.alert.className = `alert alert-${type}`;
            els.alert.textContent = message;
            els.alert.classList.toggle('d-none', !show);
        }
        if (show && message && typeof window.showToast === 'function') {
            const toastType = (type === 'danger' ? 'error' : (type === 'secondary' ? 'info' : type));
            window.showToast(toastType, message);
        }
    }

    function setRunning(running) {
        state.running = running;
        els.start.disabled = running;
        els.prompt.disabled = running;
        if (els.locationInput) els.locationInput.disabled = running;
        els.limit.disabled = running;
        if (els.engineGoogleApi) els.engineGoogleApi.disabled = running;
        if (els.engineBrowser) els.engineBrowser.disabled = running;
        if (els.preReqWebsite) els.preReqWebsite.disabled = running;
        if (els.preReqPhone) els.preReqPhone.disabled = running;
        if (els.preReqEmail) els.preReqEmail.disabled = running;
        if (els.preMinRating) els.preMinRating.disabled = running;
        els.stop.classList.toggle('d-none', !running);
        els.newExtraction.classList.toggle('d-none', running);
    }

    function showVerification(show) {
        els.verificationCard.classList.toggle('d-none', !show);
        if (els.completeMock) {
            els.completeMock.classList.toggle('d-none', !(show && cfg.allowMock && els.mock?.checked));
        }
    }

    function dash(value) {
        if (value === null || value === undefined || value === '') return '—';
        return String(value);
    }

    function leadKey(lead) {
        return lead.place_id || `${(lead.business_name || '').toLowerCase()}|${(lead.address || '').toLowerCase()}`;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/'/g, '&#39;');
    }

    function renderStars(rating) {
        const value = Number(rating);
        if (!Number.isFinite(value) || value <= 0) {
            return '<span class="extractor-stars extractor-stars-empty" aria-hidden="true"></span>';
        }
        const full = Math.floor(value);
        const half = value - full >= 0.5;
        let html = '';
        for (let i = 0; i < 5; i += 1) {
            if (i < full) {
                html += '<span class="extractor-star extractor-star-full"></span>';
            } else if (i === full && half) {
                html += '<span class="extractor-star extractor-star-half"></span>';
            } else {
                html += '<span class="extractor-star extractor-star-empty"></span>';
            }
        }
        return html;
    }

    function formatReviewCount(count) {
        const value = Number(count);
        if (!Number.isFinite(value) || value <= 0) return '';
        return `(${value.toLocaleString()})`;
    }

    function initials(name) {
        const parts = String(name || 'B').trim().split(/\s+/).slice(0, 2);
        return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'B';
    }

    function buildLeadCard(lead, key) {
        const isSelected = state.selectedKeys.has(key);
        const rating = lead.rating != null && lead.rating > 0 ? Number(lead.rating).toFixed(1) : null;
        const reviews = formatReviewCount(lead.review_count);
        const emails = Array.isArray(lead.emails) ? lead.emails.filter(Boolean) : [];
        const emailHtml = emails.length
            ? emails.map((email) => `<a href="mailto:${escapeAttr(email)}" class="extractor-lead-contact-link" title="${escapeAttr(email)}">${escapeHtml(email)}</a>`).join(', ')
            : '<span class="text-muted">No email listed</span>';
        const phoneHtml = lead.phone
            ? `<a href="tel:${escapeAttr(lead.phone)}" class="extractor-lead-contact-link" title="${escapeAttr(lead.phone)}">${escapeHtml(lead.phone)}</a>`
            : '<span class="text-muted">No phone listed</span>';
        const category = lead.category || '';
        const sourceTag = lead.source && lead.source !== 'Google Maps'
            ? `<span class="extractor-lead-tag extractor-lead-tag-muted">${escapeHtml(lead.source)}</span>`
            : '';
        const websiteBtn = lead.website
            ? `<a href="${escapeAttr(lead.website)}" target="_blank" rel="noopener" class="btn btn-xs btn-label-info d-inline-flex align-items-center" title="Visit Website"><i class="icon-base ti tabler-world"></i></a>`
            : '';
        const mapsBtn = lead.google_maps_url
            ? `<a href="${escapeAttr(lead.google_maps_url)}" target="_blank" rel="noopener" class="btn btn-xs btn-label-danger d-inline-flex align-items-center" title="View on Google Maps"><i class="icon-base ti tabler-map-pin"></i></a>`
            : '';

        const avatarImg = lead.avatar_url
            ? `<img src="${escapeAttr(lead.avatar_url)}" alt="${escapeAttr(lead.business_name || '')}" class="extractor-lead-avatar-img" onerror="this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
               <span class="extractor-lead-avatar-fallback" style="display:none;">${escapeHtml(initials(lead.business_name))}</span>`
            : `<span class="extractor-lead-avatar-fallback">${escapeHtml(initials(lead.business_name))}</span>`;

        const socialLinks = lead.social_links && typeof lead.social_links === 'object' ? lead.social_links : {};
        let socialsHtml = '';
        const socialIcons = [];
        if (socialLinks.linkedin) socialIcons.push(`<a href="${escapeAttr(socialLinks.linkedin)}" target="_blank" rel="noopener" class="text-primary me-2 fs-6" title="LinkedIn"><i class="icon-base ti tabler-brand-linkedin"></i></a>`);
        if (socialLinks.facebook) socialIcons.push(`<a href="${escapeAttr(socialLinks.facebook)}" target="_blank" rel="noopener" class="text-info me-2 fs-6" title="Facebook"><i class="icon-base ti tabler-brand-facebook"></i></a>`);
        if (socialLinks.instagram) socialIcons.push(`<a href="${escapeAttr(socialLinks.instagram)}" target="_blank" rel="noopener" class="text-danger me-2 fs-6" title="Instagram"><i class="icon-base ti tabler-brand-instagram"></i></a>`);
        if (socialLinks.twitter) socialIcons.push(`<a href="${escapeAttr(socialLinks.twitter)}" target="_blank" rel="noopener" class="text-dark me-2 fs-6" title="Twitter / X"><i class="icon-base ti tabler-brand-x"></i></a>`);
        if (socialLinks.youtube) socialIcons.push(`<a href="${escapeAttr(socialLinks.youtube)}" target="_blank" rel="noopener" class="text-danger me-2 fs-6" title="YouTube"><i class="icon-base ti tabler-brand-youtube"></i></a>`);
        if (socialIcons.length > 0) {
            socialsHtml = `<div class="extractor-lead-detail-row has-value mt-1"><i class="icon-base ti tabler-share"></i><span class="extractor-lead-detail-text">${socialIcons.join('')}</span></div>`;
        }

        let verificationBadge = '';
        const vStatus = lead.email_verification_status && typeof lead.email_verification_status === 'object' ? lead.email_verification_status : {};
        const hasVerifiedEmail = Object.values(vStatus).some((v) => v && v.is_valid);
        if (hasVerifiedEmail) {
            verificationBadge = '<span class="badge bg-label-success" style="font-size: 0.68rem; padding: 0.2rem 0.4rem;" title="Email Verified (MX Valid)"><i class="icon-base ti tabler-mail-check me-1"></i>Verified</span>';
        }

        return `
            <article class="extractor-lead-card ${isSelected ? 'is-selected' : ''}" data-key="${escapeAttr(key)}">
                <div class="extractor-lead-header">
                    <div class="extractor-lead-check-wrap">
                        <input class="form-check-input extractor-card-checkbox" type="checkbox" ${isSelected ? 'checked' : ''} data-key="${escapeAttr(key)}" aria-label="Select lead">
                    </div>
                    <div class="extractor-lead-avatar" title="${escapeAttr(lead.business_name || '')}">
                        ${avatarImg}
                    </div>
                    <div class="extractor-lead-title-area">
                        <h6 class="extractor-lead-name" title="${escapeAttr(lead.business_name || '')}">${escapeHtml(dash(lead.business_name))}</h6>
                        <div class="extractor-lead-badges">
                            ${category ? `<span class="extractor-lead-tag" title="${escapeAttr(category)}">${escapeHtml(category)}</span>` : ''}
                            ${sourceTag}
                        </div>
                    </div>
                </div>

                <div class="extractor-lead-rating-row">
                    <div class="extractor-stars" aria-label="${rating ? `${rating} out of 5 stars` : 'No rating'}">${renderStars(lead.rating)}</div>
                    ${rating ? `<span class="extractor-lead-rating-value">${escapeHtml(rating)}</span>` : ''}
                    ${reviews ? `<span class="extractor-lead-review-count">${escapeHtml(reviews)}</span>` : ''}
                </div>

                <div class="extractor-lead-details">
                    <div class="extractor-lead-detail-row ${lead.address ? 'has-value' : ''}">
                        <i class="icon-base ti tabler-map-pin"></i>
                        <span class="extractor-lead-detail-text is-address" title="${escapeAttr(lead.address || '')}">${escapeHtml(dash(lead.address))}</span>
                    </div>
                    <div class="extractor-lead-detail-row ${lead.phone ? 'has-value' : ''}">
                        <i class="icon-base ti tabler-phone"></i>
                        <span class="extractor-lead-detail-text">${phoneHtml}</span>
                    </div>
                    <div class="extractor-lead-detail-row ${emails.length ? 'has-value' : ''}">
                        <i class="icon-base ti tabler-mail"></i>
                        <span class="extractor-lead-detail-text">${emailHtml}</span>
                    </div>
                    ${socialsHtml}
                </div>

                <div class="extractor-lead-footer">
                    <div class="extractor-lead-footer-status">
                        ${(lead.is_saved || lead.status === 'saved')
                            ? '<span class="badge bg-label-success" style="font-size: 0.68rem; padding: 0.2rem 0.4rem;" title="Lead saved in database"><i class="icon-base ti tabler-device-floppy me-1"></i>Saved</span>'
                            : '<span class="badge bg-label-info" style="font-size: 0.68rem; padding: 0.2rem 0.4rem;">Discovered</span>'
                        }
                        ${verificationBadge}
                    </div>
                    <div class="extractor-lead-btn-group">
                        ${emails.length > 0 ? `<button type="button" class="btn btn-xs btn-label-primary btn-lead-send-email d-inline-flex align-items-center" title="Send Outreach Email" data-key="${escapeAttr(key)}"><i class="icon-base ti tabler-send"></i></button>` : ''}
                        ${mapsBtn}
                        ${websiteBtn}
                    </div>
                </div>
            </article>
        `;
    }

    // Filter matching logic
    function matchLead(lead) {
        const f = state.filters;
        const q = f.query.toLowerCase().trim();

        if (q) {
            const name = (lead.business_name || '').toLowerCase();
            const address = (lead.address || '').toLowerCase();
            const category = (lead.category || '').toLowerCase();
            const phone = (lead.phone || '').toLowerCase();
            const emails = Array.isArray(lead.emails) ? lead.emails.join(' ').toLowerCase() : '';
            if (!name.includes(q) && !address.includes(q) && !category.includes(q) && !phone.includes(q) && !emails.includes(q)) {
                return false;
            }
        }

        // Email filter
        const hasEmail = Array.isArray(lead.emails) && lead.emails.length > 0;
        if (f.email === 'has' && !hasEmail) return false;
        if (f.email === 'none' && hasEmail) return false;
        if (f.quick.has('email') && !hasEmail) return false;

        // Website filter
        const hasWebsite = Boolean(lead.website && lead.website.trim());
        if (f.website === 'has' && !hasWebsite) return false;
        if (f.website === 'none' && hasWebsite) return false;
        if (f.quick.has('website') && !hasWebsite) return false;

        // Phone filter
        const hasPhone = Boolean(lead.phone && lead.phone.trim());
        if (f.phone === 'has' && !hasPhone) return false;
        if (f.phone === 'none' && hasPhone) return false;
        if (f.quick.has('phone') && !hasPhone) return false;

        // Rating filter
        const rating = Number(lead.rating) || 0;
        if (f.rating === '4.5' && rating < 4.5) return false;
        if (f.rating === '4.0' && rating < 4.0) return false;
        if (f.rating === '3.5' && rating < 3.5) return false;
        if (f.rating === 'has' && rating <= 0) return false;
        if (f.quick.has('high_rating') && rating < 4.0) return false;

        return true;
    }

    function sortLeadsList(list) {
        const sort = state.filters.sort;
        return [...list].sort((a, b) => {
            if (sort === 'newest') return (b.lead._index || 0) - (a.lead._index || 0);
            if (sort === 'oldest') return (a.lead._index || 0) - (b.lead._index || 0);
            if (sort === 'rating_desc') return (Number(b.lead.rating) || 0) - (Number(a.lead.rating) || 0);
            if (sort === 'reviews_desc') return (Number(b.lead.review_count) || 0) - (Number(a.lead.review_count) || 0);
            if (sort === 'name_asc') return (a.lead.business_name || '').localeCompare(b.lead.business_name || '');
            return 0;
        });
    }

    function getFilteredLeads() {
        const matched = [];
        for (const [key, lead] of state.leads.entries()) {
            if (matchLead(lead)) {
                matched.push({ key, lead });
            }
        }
        return sortLeadsList(matched);
    }

    function isFilterActive() {
        const f = state.filters;
        return Boolean(
            f.query.trim() ||
            f.email !== 'all' ||
            f.website !== 'all' ||
            f.phone !== 'all' ||
            f.rating !== 'all' ||
            f.sort !== 'newest' ||
            f.quick.size > 0
        );
    }

    function renderLeads() {
        const total = state.leads.size;
        const filtered = getFilteredLeads();
        const filterActive = isFilterActive();

        // Update counts
        els.leadCountBadge.textContent = `${total} total`;
        els.exportDropdownBtn.classList.toggle('disabled', total === 0);
        els.masterCheckbox.disabled = filtered.length === 0;

        if (filterActive) {
            els.leadFilterBadge.classList.remove('d-none');
            els.leadFilterBadge.textContent = `${filtered.length} shown`;
            els.leadsSummaryText.textContent = `Showing ${filtered.length} of ${total} leads`;
            els.resetFiltersBtn.classList.remove('d-none');
        } else {
            els.leadFilterBadge.classList.add('d-none');
            els.leadsSummaryText.textContent = `${total} lead${total === 1 ? '' : 's'} found`;
            els.resetFiltersBtn.classList.add('d-none');
        }

        if (total === 0) {
            els.leadsGrid.innerHTML = `
                <div id="leadsEmpty" class="extractor-leads-empty">
                    <i class="icon-base ti tabler-building-store display-4 mb-2 text-muted"></i>
                    <p class="mb-0 text-muted">No leads yet. Start an extraction to stream results here.</p>
                </div>
            `;
            els.noFilterResults.classList.add('d-none');
            updateSelectionUi();
            return;
        }

        if (filtered.length === 0) {
            els.leadsGrid.innerHTML = '';
            els.noFilterResults.classList.remove('d-none');
            updateSelectionUi();
            return;
        }

        els.noFilterResults.classList.add('d-none');
        const fragmentHtml = filtered.map(({ key, lead }) => buildLeadCard(lead, key)).join('');
        els.leadsGrid.innerHTML = fragmentHtml;

        updateSelectionUi();
    }

    function updateSelectionUi() {
        const selectedCount = state.selectedKeys.size;
        const total = state.leads.size;
        const filtered = getFilteredLeads();
        const filteredKeys = new Set(filtered.map((item) => item.key));

        let selectedInFiltered = 0;
        for (const key of filteredKeys) {
            if (state.selectedKeys.has(key)) selectedInFiltered += 1;
        }

        // Master checkbox state in leads topbar
        if (els.masterCheckbox) {
            els.masterCheckbox.disabled = filtered.length === 0;
            if (filtered.length > 0 && selectedInFiltered === filtered.length) {
                els.masterCheckbox.checked = true;
                els.masterCheckbox.indeterminate = false;
            } else if (selectedInFiltered > 0) {
                els.masterCheckbox.checked = false;
                els.masterCheckbox.indeterminate = true;
            } else {
                els.masterCheckbox.checked = false;
                els.masterCheckbox.indeterminate = false;
            }
        }

        // Master checkbox in bulk floating bar
        if (els.selectAllCheckbox) {
            if (filtered.length > 0 && selectedInFiltered === filtered.length) {
                els.selectAllCheckbox.checked = true;
                els.selectAllCheckbox.indeterminate = false;
            } else if (selectedInFiltered > 0) {
                els.selectAllCheckbox.checked = false;
                els.selectAllCheckbox.indeterminate = true;
            } else {
                els.selectAllCheckbox.checked = false;
                els.selectAllCheckbox.indeterminate = false;
            }
        }

        if (els.masterCheckboxLabel) {
            els.masterCheckboxLabel.textContent = selectedInFiltered === filtered.length && filtered.length > 0
                ? `Deselect All (${filtered.length})`
                : `Select All (${filtered.length})`;
        }

        if (els.saveAllDiscoveredBtn) {
            els.saveAllDiscoveredBtn.classList.toggle('d-none', total === 0);
            if (els.saveAllCount) els.saveAllCount.textContent = String(total);
        }

        // Bulk bar & badge
        if (selectedCount > 0) {
            els.bulkBar.classList.remove('d-none');
            els.leadSelectedBadge.classList.remove('d-none');
            const ratioText = `${selectedCount} / ${total} selected`;
            if (els.selectedRatioText) {
                els.selectedRatioText.textContent = ratioText;
            } else {
                els.leadSelectedBadge.textContent = ratioText;
            }
            els.bulkCountLabel.textContent = `${selectedCount} selected`;
            els.bulkFilteredCount.textContent = String(filtered.length);
        } else {
            els.bulkBar.classList.add('d-none');
            els.leadSelectedBadge.classList.add('d-none');
        }
    }

    function upsertLead(lead) {
        const key = leadKey(lead);
        if (state.leads.has(key)) return;
        state.leadCounter += 1;
        lead._index = state.leadCounter;
        state.leads.set(key, lead);

        renderLeads();
    }

    // Bulk selection helpers
    function toggleLeadSelection(key, force) {
        const shouldSelect = force !== undefined ? Boolean(force) : !state.selectedKeys.has(key);
        if (shouldSelect) {
            state.selectedKeys.add(key);
        } else {
            state.selectedKeys.delete(key);
        }

        const cards = els.leadsGrid.querySelectorAll('.extractor-lead-card');
        for (const card of cards) {
            if (card.dataset.key === key) {
                card.classList.toggle('is-selected', shouldSelect);
                const cb = card.querySelector('.extractor-card-checkbox');
                if (cb) cb.checked = shouldSelect;
                break;
            }
        }

        updateSelectionUi();
    }

    function selectAllFiltered() {
        const filtered = getFilteredLeads();
        for (const item of filtered) {
            state.selectedKeys.add(item.key);
        }
        const cards = els.leadsGrid.querySelectorAll('.extractor-lead-card');
        cards.forEach((card) => {
            card.classList.add('is-selected');
            const cb = card.querySelector('.extractor-card-checkbox');
            if (cb) cb.checked = true;
        });
        updateSelectionUi();
    }

    function deselectFiltered() {
        const filtered = getFilteredLeads();
        for (const item of filtered) {
            state.selectedKeys.delete(item.key);
        }
        const cards = els.leadsGrid.querySelectorAll('.extractor-lead-card');
        cards.forEach((card) => {
            card.classList.remove('is-selected');
            const cb = card.querySelector('.extractor-card-checkbox');
            if (cb) cb.checked = false;
        });
        updateSelectionUi();
    }

    function deselectAll() {
        state.selectedKeys.clear();
        const cards = els.leadsGrid.querySelectorAll('.extractor-lead-card');
        cards.forEach((card) => {
            card.classList.remove('is-selected');
            const cb = card.querySelector('.extractor-card-checkbox');
            if (cb) cb.checked = false;
        });
        updateSelectionUi();
    }

    // Excel & JSON Export Utilities
    function downloadBlob(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function exportToExcel(leadsList, filename = 'leads-export.xlsx') {
        if (!leadsList.length) {
            showToast('No leads to export.', true);
            return;
        }

        if (window.XLSX && window.XLSX.utils) {
            const dataRows = leadsList.map((lead) => {
                const s = lead.social_links && typeof lead.social_links === 'object' ? lead.social_links : {};
                return {
                    'Business Name': lead.business_name || '',
                    'Address': lead.address || '',
                    'Email(s)': Array.isArray(lead.emails) ? lead.emails.join('; ') : '',
                    'Phone': lead.phone || '',
                    'Website': lead.website || '',
                    'LinkedIn': s.linkedin || '',
                    'Facebook': s.facebook || '',
                    'Instagram': s.instagram || '',
                    'Twitter / X': s.twitter || '',
                    'YouTube': s.youtube || '',
                    'Category': lead.category || '',
                    'Rating': lead.rating != null ? Number(lead.rating) : '',
                    'Reviews': lead.review_count != null ? Number(lead.review_count) : '',
                    'Google Maps URL': lead.google_maps_url || '',
                    'Source': lead.source || 'Google Maps',
                };
            });

            const ws = window.XLSX.utils.json_to_sheet(dataRows);
            ws['!cols'] = [
                { wch: 30 }, // Business Name
                { wch: 35 }, // Address
                { wch: 28 }, // Email(s)
                { wch: 18 }, // Phone
                { wch: 28 }, // Website
                { wch: 25 }, // LinkedIn
                { wch: 25 }, // Facebook
                { wch: 25 }, // Instagram
                { wch: 25 }, // Twitter / X
                { wch: 25 }, // YouTube
                { wch: 20 }, // Category
                { wch: 10 }, // Rating
                { wch: 10 }, // Reviews
                { wch: 40 }, // Maps URL
                { wch: 18 }, // Source
            ];

            const wb = window.XLSX.utils.book_new();
            window.XLSX.utils.book_append_sheet(wb, ws, 'Leads');
            window.XLSX.writeFile(wb, filename);
            showToast(`Exported ${leadsList.length} lead${leadsList.length === 1 ? '' : 's'} to Excel (.xlsx).`);
            return;
        }

        // Fallback: Excel XML Spreadsheet
        let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
        xml += '<?mso-application progid="Excel.Sheet"?>\n';
        xml += '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"\n';
        xml += ' xmlns:o="urn:schemas-microsoft-com:office:office"\n';
        xml += ' xmlns:x="urn:schemas-microsoft-com:office:excel"\n';
        xml += ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">\n';
        xml += ' <Styles>\n';
        xml += '  <Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#696CFF" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>\n';
        xml += ' </Styles>\n';
        xml += ' <Worksheet ss:Name="Leads">\n  <Table>\n';

        const headers = ['Business Name', 'Address', 'Email(s)', 'Phone', 'Website', 'Category', 'Rating', 'Reviews', 'Google Maps URL', 'Source'];
        xml += '   <Row ss:StyleID="Header">\n';
        for (const h of headers) {
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(h)}</Data></Cell>\n`;
        }
        xml += '   </Row>\n';

        for (const lead of leadsList) {
            const emails = Array.isArray(lead.emails) ? lead.emails.join('; ') : '';
            xml += '   <Row>\n';
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(lead.business_name || '')}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(lead.address || '')}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(emails)}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(lead.phone || '')}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(lead.website || '')}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(lead.category || '')}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="${lead.rating != null ? 'Number' : 'String'}">${lead.rating != null ? Number(lead.rating) : ''}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="${lead.review_count != null ? 'Number' : 'String'}">${lead.review_count != null ? Number(lead.review_count) : ''}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(lead.google_maps_url || '')}</Data></Cell>\n`;
            xml += `    <Cell><Data ss:Type="String">${escapeHtml(lead.source || 'Google Maps')}</Data></Cell>\n`;
            xml += '   </Row>\n';
        }

        xml += '  </Table>\n </Worksheet>\n</Workbook>\n';
        downloadBlob(xml, filename.replace('.xlsx', '.xls'), 'application/vnd.ms-excel;charset=utf-8;');
        showToast(`Exported ${leadsList.length} lead${leadsList.length === 1 ? '' : 's'} to Excel.`);
    }

    function exportToJson(leadsList, filename = 'leads-export.json') {
        if (!leadsList.length) {
            showToast('No leads to export.', true);
            return;
        }

        const cleanList = leadsList.map((lead) => ({
            business_name: lead.business_name || null,
            address: lead.address || null,
            emails: Array.isArray(lead.emails) ? lead.emails : [],
            phone: lead.phone || null,
            website: lead.website || null,
            avatar_url: lead.avatar_url || null,
            category: lead.category || null,
            rating: lead.rating != null ? Number(lead.rating) : null,
            review_count: lead.review_count != null ? Number(lead.review_count) : null,
            google_maps_url: lead.google_maps_url || null,
            source: lead.source || 'Google Maps',
        }));

        const jsonContent = JSON.stringify(cleanList, null, 2);
        downloadBlob(jsonContent, filename, 'application/json;charset=utf-8;');
        showToast(`Exported ${leadsList.length} lead${leadsList.length === 1 ? '' : 's'} as JSON.`);
    }

    function exportToCsv(leadsList, filename = 'leads-export.csv') {
        if (!leadsList.length) {
            showToast('No leads to export.', true);
            return;
        }

        const headers = ['Business Name', 'Address', 'Email(s)', 'Phone', 'Website', 'Category', 'Rating', 'Reviews', 'Google Maps URL', 'Source'];
        const rows = [headers];

        for (const lead of leadsList) {
            const emails = Array.isArray(lead.emails) ? lead.emails.join('; ') : '';
            rows.push([
                lead.business_name || '',
                lead.address || '',
                emails,
                lead.phone || '',
                lead.website || '',
                lead.category || '',
                lead.rating != null ? String(lead.rating) : '',
                lead.review_count != null ? String(lead.review_count) : '',
                lead.google_maps_url || '',
                lead.source || 'Google Maps',
            ]);
        }

        const csvContent = rows
            .map((row) =>
                row
                    .map((val) => `"${String(val).replace(/"/g, '""')}"`)
                    .join(',')
            )
            .join('\r\n');

        downloadBlob('\uFEFF' + csvContent, filename, 'text/csv;charset=utf-8;');
        showToast(`Exported ${leadsList.length} lead${leadsList.length === 1 ? '' : 's'} to CSV.`);
    }

    async function executeBulkAction(action, customLeadIds = null, confirmMessage = null) {
        if (confirmMessage && !window.confirm(confirmMessage)) {
            return;
        }

        const selectedLeads = getSelectedLeadsList();
        let targetLeadIds = [];

        if (Array.isArray(customLeadIds) && customLeadIds.length > 0) {
            targetLeadIds = customLeadIds;
        } else if (action === 'save_all') {
            targetLeadIds = Array.from(state.leads.values())
                .map((l) => l.id)
                .filter(Boolean);
        } else {
            targetLeadIds = selectedLeads.map((l) => l.id).filter(Boolean);
        }

        // Optimistic UI updates
        if (action === 'discard') {
            const keysToDiscard = Array.from(state.selectedKeys);
            for (const key of keysToDiscard) {
                state.leads.delete(key);
                state.selectedKeys.delete(key);
            }
            renderLeads();
            updateSelectionUi();
        }

        const payload = {
            action,
            job_id: state.jobId,
        };

        if (targetLeadIds.length > 0) {
            payload.lead_ids = targetLeadIds;
        }

        const endpoint = cfg.bulkActionUrl || '/api/leads/bulk-action';

        try {
            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(payload),
            });

            const data = await resp.json();
            if (!resp.ok) {
                throw new Error(data.message || `Failed to execute ${action}.`);
            }

            if (action === 'save' || action === 'save_all') {
                if (action === 'save_all' || selectedLeads.length === state.leads.size) {
                    state.isSaved = true;
                    for (const lead of state.leads.values()) {
                        lead.status = 'saved';
                        lead.is_saved = true;
                    }
                } else {
                    for (const lead of selectedLeads) {
                        lead.status = 'saved';
                        lead.is_saved = true;
                    }
                }
                renderLeads();
                saveStateToStorage();
                showToast(data.message || `Saved ${data.affected ?? targetLeadIds.length} lead(s) to master database.`);
            } else if (action === 'discard') {
                saveStateToStorage();
            } else if (action === 'delete') {
                const keysToDelete = Array.from(state.selectedKeys);
                for (const key of keysToDelete) {
                    state.leads.delete(key);
                    state.selectedKeys.delete(key);
                }
                renderLeads();
                updateSelectionUi();
                saveStateToStorage();
            }
        } catch (err) {
            showToast(err.message || 'Action failed.', true);
        }
    }

    function copyToClipboard(text, successMessage) {
        if (!text) {
            showToast('No information found to copy.', true);
            return;
        }
        navigator.clipboard.writeText(text).then(
            () => showToast(successMessage),
            () => {
                const input = document.createElement('textarea');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showToast(successMessage);
            }
        );
    }

    function getSelectedLeadsList() {
        const out = [];
        for (const key of state.selectedKeys) {
            if (state.leads.has(key)) {
                out.push(state.leads.get(key));
            }
        }
        return out;
    }

    function getAllLeadsList() {
        return Array.from(state.leads.values());
    }

    function updateCounts(event) {
        if (event.leads_extracted !== undefined) els.kpiLeads.textContent = event.leads_extracted;
        else els.kpiLeads.textContent = String(state.leads.size);
        if (event.businesses_seen !== undefined) els.kpiSeen.textContent = event.businesses_seen;
        if (event.emails_found !== undefined) els.kpiEmails.textContent = event.emails_found;
        if (event.websites_found !== undefined) els.kpiWebsites.textContent = event.websites_found;
    }

    function showSummary(event) {
        els.summaryCard.classList.remove('d-none');
        const scanned = event.businesses_seen ?? els.kpiSeen.textContent;
        const extracted = event.leads_extracted ?? state.leads.size;
        const emails = event.emails_found ?? els.kpiEmails.textContent;
        const websites = event.websites_found ?? els.kpiWebsites.textContent;
        els.summaryStats.innerHTML = `
            <div class="col-6 col-md-3"><div class="text-muted small">Businesses Scanned</div><div class="fw-bold fs-5">${scanned}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">Leads Extracted</div><div class="fw-bold fs-5">${extracted}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">Emails Found</div><div class="fw-bold fs-5">${emails}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">Websites Found</div><div class="fw-bold fs-5">${websites}</div></div>
        `;
        if (state.jobId) {
            els.exportSummary.classList.remove('disabled');
            els.exportSummary.href = jobUrl(cfg.exportUrl) + '?format=excel';
        }
    }

    function handleEvent(event) {
        if (!event || !event.type) return;
        updateCounts(event);

        switch (event.type) {
            case 'started':
                setStatus(event.status || 'starting', event.message || 'Extraction started.');
                break;
            case 'searching':
                setStatus('searching', event.message || 'Searching Google Maps');
                if (event.query) els.searchLabel.textContent = `Search: ${event.query}`;
                break;
            case 'progress':
                setStatus(event.status || 'extracting', event.current_activity || event.message);
                break;
            case 'lead':
                if (event.lead) upsertLead(event.lead);
                setStatus(event.status || 'extracting', event.lead?.business_name);
                showVerification(false);
                saveStateThrottled();
                break;
            case 'human_verification_required':
                setStatus('waiting_for_human_verification', event.message);
                showVerification(true);
                setAlert('warning', event.message || 'Google Maps requires human verification.', true);
                break;
            case 'verification_completed':
                showVerification(false);
                setStatus('extracting', event.message || 'Verification completed. Extraction resumed.');
                setAlert('success', event.message || 'Verification completed. Extraction resumed.', true);
                break;
            case 'warning':
                setAlert('warning', event.message || 'Warning', true);
                break;
            case 'error':
                setRunning(false);
                setStatus('error', event.message);
                setAlert('danger', event.message || event.error || 'Extraction failed.', true);
                saveStateToStorage();
                closeStream();
                break;
            case 'completed':
                setRunning(false);
                setStatus('completed', event.message || 'Extraction completed.');
                setAlert('success', event.message || 'Extraction completed.', true);
                showVerification(false);
                showSummary(event);
                saveStateToStorage();
                closeStream();
                break;
            case 'cancelled':
                setRunning(false);
                setStatus('cancelled', event.message || 'Extraction stopped.');
                setAlert('secondary', event.message || 'Extraction stopped. Previously extracted leads have been preserved.', true);
                showVerification(false);
                showSummary(event);
                saveStateToStorage();
                closeStream();
                break;
            case 'verification_timeout':
                setRunning(false);
                setStatus('verification_timeout', event.message);
                setAlert('warning', event.message || 'Human verification was not completed within the allowed time. Extraction has stopped. Previously extracted leads have been preserved.', true);
                showVerification(false);
                showSummary(event);
                saveStateToStorage();
                closeStream();
                break;
            default:
                break;
        }
    }

    let saveTimeout = null;
    function saveStateThrottled() {
        if (saveTimeout) return;
        saveTimeout = setTimeout(() => {
            saveTimeout = null;
            saveStateToStorage();
        }, 800);
    }

    function closeStream() {
        if (state.source) {
            state.source.close();
            state.source = null;
        }
    }

    function connectStream() {
        closeStream();
        const url = jobUrl(cfg.streamUrl);
        const source = new EventSource(url);
        state.source = source;
        source.onmessage = (message) => {
            try {
                handleEvent(JSON.parse(message.data));
            } catch (error) {
                console.warn('Unable to parse extractor event', error);
            }
        };
        source.onerror = () => {
            if (!state.running) return;
            setAlert('danger', 'Lost the live event stream. Existing leads are still available.', true);
        };
    }

    function updateEngineModeUi() {
        const isGoogleApi = els.engineGoogleApi?.checked ?? true;

        if (els.customOptionGoogleApi) els.customOptionGoogleApi.classList.toggle('checked', isGoogleApi);
        if (els.customOptionBrowser) els.customOptionBrowser.classList.toggle('checked', !isGoogleApi);

        if (isGoogleApi) {
            if (els.locationInputCol) els.locationInputCol.classList.remove('d-none');
            if (els.promptInputCol) {
                els.promptInputCol.classList.remove('col-12');
                els.promptInputCol.classList.add('col-lg-7');
            }
            if (els.preFiltersContainer) els.preFiltersContainer.classList.remove('d-none');
            if (els.promptLabel) els.promptLabel.innerHTML = '<i class="icon-base ti tabler-category me-1 text-primary"></i>Industry / Business Category <span class="text-danger">*</span>';
            if (els.prompt) els.prompt.placeholder = 'e.g. Dentists, Real Estate, Plumbers, Software Companies, Law Firms';
            if (els.verifyToggleWrap) els.verifyToggleWrap.classList.add('d-none');
        } else {
            if (els.locationInputCol) els.locationInputCol.classList.add('d-none');
            if (els.promptInputCol) {
                els.promptInputCol.classList.remove('col-lg-7');
                els.promptInputCol.classList.add('col-12');
            }
            if (els.preFiltersContainer) els.preFiltersContainer.classList.add('d-none');
            if (els.promptLabel) els.promptLabel.innerHTML = '<i class="icon-base ti tabler-category me-1 text-primary"></i>Search Query (e.g. "Dentists in Beverly Hills, CA") <span class="text-danger">*</span>';
            if (els.prompt) els.prompt.placeholder = 'e.g. Dentists in Beverly Hills, CA 90210';
            if (els.verifyToggleWrap) els.verifyToggleWrap.classList.remove('d-none');
            if (els.apiKeyRow) els.apiKeyRow.classList.add('d-none');
        }
    }

    async function startExtraction() {
        const prompt = (els.prompt?.value || '').trim();
        const location = (els.locationInput?.value || '').trim();
        const isGoogleApi = els.engineGoogleApi?.checked ?? false;
        const customApiKey = (els.customApiKeyInput?.value || '').trim();

        if (prompt.length < 2) {
            setAlert('warning', 'Please enter an industry or business category (e.g. “Dentists”, “Real Estate”).', true);
            if (els.prompt) els.prompt.focus();
            return;
        }

        let mode = 'live';
        let filters = {};

        if (els.mock?.checked) {
            mode = 'mock';
        } else if (isGoogleApi) {
            mode = 'google_api';
            if (!cfg.hasGoogleApiKey && !customApiKey) {
                if (els.apiKeyRow) els.apiKeyRow.classList.remove('d-none');
                setAlert('warning', 'Google Maps API key is required. Enter your API key below or switch to Browser Extractor mode.', true);
                if (els.customApiKeyInput) els.customApiKeyInput.focus();
                return;
            }

            filters = {
                require_website: Boolean(els.preReqWebsite?.checked),
                require_phone: Boolean(els.preReqPhone?.checked),
                require_email: Boolean(els.preReqEmail?.checked),
                min_rating: Number(els.preMinRating?.value || 0),
            };
        }

        setAlert('info', '', false);
        els.summaryCard.classList.add('d-none');
        showVerification(false);
        setStatus('starting', 'Starting extraction');
        setRunning(true);

        state.leads.clear();
        state.selectedKeys.clear();
        state.leadCounter = 0;
        state.isSaved = false;
        renderLeads();
        updateSelectionUi();

        try {
            const payloadData = {
                prompt,
                location: location || undefined,
                limit: Number(els.limit?.value || 100),
                mode,
                simulate_verification: Boolean(els.verify?.checked),
            };

            if (customApiKey) {
                payloadData.api_key = customApiKey;
            }
            if (isGoogleApi && Object.keys(filters).length > 0) {
                payloadData.filters = filters;
            }

            const response = await fetch(cfg.startUrl, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(payloadData),
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to start extraction.');
            }
            state.jobId = payload.job_id;
            els.searchLabel.textContent = `Search: ${payload.query || prompt}`;
            saveStateToStorage();
            connectStream();
        } catch (error) {
            setRunning(false);
            setStatus('error', error.message);
            setAlert('danger', error.message || 'Extractor service is unavailable.', true);
        }
    }

    async function stopExtraction() {
        const currentJobId = state.jobId;

        // Immediately halt live stream and transition UI to stopped state
        closeStream();
        setRunning(false);
        setStatus('cancelled', 'Extraction stopped.');
        setAlert('secondary', 'Extraction stopped. Previously extracted leads have been preserved.', true);
        showVerification(false);
        showSummary({
            leads_extracted: state.leads.size,
            businesses_seen: els.kpiSeen ? els.kpiSeen.textContent : '0',
            emails_found: els.kpiEmails ? els.kpiEmails.textContent : '0',
            websites_found: els.kpiWebsites ? els.kpiWebsites.textContent : '0',
        });
        saveStateToStorage();

        if (currentJobId) {
            try {
                await fetch(jobUrl(cfg.stopUrl), { method: 'POST', headers: headers() });
            } catch (error) {
                console.warn('Stop signal notification:', error);
            }
        }
    }

    async function openVerification() {
        if (!state.jobId) return;
        try {
            await fetch(jobUrl(cfg.focusUrl), { method: 'POST', headers: headers() });
        } catch (_) {
            /* Handled */
        }
        els.verificationHint.textContent = 'Complete the verification in the Google Maps browser window, then extraction will resume automatically.';
    }

    async function completeMockVerification() {
        if (!state.jobId) return;
        await fetch(jobUrl(cfg.verifyCompleteUrl), { method: 'POST', headers: headers() });
    }

    async function clearResults() {
        if (state.leads && state.leads.size > 0 && !state.isSaved) {
            if (typeof window.showConfirm === 'function') {
                const confirmed = await window.showConfirm(
                    'Clear Discovered Leads?',
                    `You have ${state.leads.size} discovered lead(s) on screen that will be cleared. Do you want to continue?`,
                    'Yes, Clear Results',
                    false
                );
                if (!confirmed.isConfirmed) return;
            }
        }
        closeStream();
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (_) {}

        state.leads.clear();
        state.selectedKeys.clear();
        state.leadCounter = 0;
        state.isSaved = false;
        renderLeads();

        if (els.kpiLeads) els.kpiLeads.textContent = '0';
        if (els.kpiSeen) els.kpiSeen.textContent = '0';
        if (els.kpiEmails) els.kpiEmails.textContent = '0';
        if (els.kpiWebsites) els.kpiWebsites.textContent = '0';
        if (els.summaryCard) els.summaryCard.classList.add('d-none');
        showVerification(false);
        setAlert('info', '', false);
        setStatus('ready', 'Waiting for a search.');
        if (els.searchLabel) els.searchLabel.textContent = 'Search: —';
        if (els.exportSummary) {
            els.exportSummary.classList.add('disabled');
            els.exportSummary.href = '#';
        }
        setRunning(false);
        if (typeof window.showToast === 'function') {
            window.showToast('info', 'Extraction results cleared.', 'Extractor');
        }
    }

    function resetFilterValues() {
        state.filters = {
            query: '',
            email: 'all',
            website: 'all',
            phone: 'all',
            rating: 'all',
            sort: 'newest',
            quick: new Set(),
        };

        if (els.searchInput) els.searchInput.value = '';
        if (els.searchClear) els.searchClear.classList.add('d-none');
        if (els.filterEmail) els.filterEmail.value = 'all';
        if (els.filterWebsite) els.filterWebsite.value = 'all';
        if (els.filterPhone) els.filterPhone.value = 'all';
        if (els.filterRating) els.filterRating.value = 'all';
        if (els.sortLeads) els.sortLeads.value = 'newest';

        els.filterChips.forEach((chip) => chip.classList.remove('active'));
    }

    // Engine Mode Listeners
    if (els.engineGoogleApi) els.engineGoogleApi.addEventListener('change', updateEngineModeUi);
    if (els.engineBrowser) els.engineBrowser.addEventListener('change', updateEngineModeUi);
    if (els.toggleApiKeyBtn) {
        els.toggleApiKeyBtn.addEventListener('click', () => {
            if (els.apiKeyRow) els.apiKeyRow.classList.toggle('d-none');
        });
    }
    if (els.toggleKeyVisibilityBtn && els.customApiKeyInput) {
        els.toggleKeyVisibilityBtn.addEventListener('click', () => {
            const isPassword = els.customApiKeyInput.type === 'password';
            els.customApiKeyInput.type = isPassword ? 'text' : 'password';
        });
    }

    // Grid event delegation for card checkbox
    els.leadsGrid.addEventListener('change', (event) => {
        if (event.target.matches('.extractor-card-checkbox')) {
            const key = event.target.dataset.key;
            if (key) toggleLeadSelection(key, event.target.checked);
        }
    });

    // Grid event delegation for clicking on card body
    els.leadsGrid.addEventListener('click', (event) => {
        if (event.target.closest('a, button, .btn, .dropdown-menu, input, label, select')) {
            return;
        }
        const card = event.target.closest('.extractor-lead-card');
        if (!card) return;
        const key = card.dataset.key;
        if (key) {
            toggleLeadSelection(key);
        }
    });

    // Master checkbox in leads topbar
    if (els.masterCheckbox) {
        els.masterCheckbox.addEventListener('change', () => {
            if (els.masterCheckbox.checked) {
                selectAllFiltered();
            } else {
                deselectFiltered();
            }
        });
    }

    // Bulk floating bar checkbox
    if (els.selectAllCheckbox) {
        els.selectAllCheckbox.addEventListener('change', () => {
            if (els.selectAllCheckbox.checked) {
                selectAllFiltered();
            } else {
                deselectFiltered();
            }
        });
    }

    if (els.selectAllFilteredBtn) {
        els.selectAllFilteredBtn.addEventListener('click', selectAllFiltered);
    }

    if (els.bulkDeselectBtn) {
        els.bulkDeselectBtn.addEventListener('click', deselectAll);
    }

    if (els.bulkSaveBtn) {
        els.bulkSaveBtn.addEventListener('click', () => {
            const selected = getSelectedLeadsList();
            if (selected.length === 0) {
                showToast('No leads selected to save.', true);
                return;
            }
            executeBulkAction('save');
        });
    }

    if (els.bulkDiscardBtn) {
        els.bulkDiscardBtn.addEventListener('click', () => {
            const count = state.selectedKeys.size;
            if (count === 0) {
                showToast('No leads selected to discard.', true);
                return;
            }
            executeBulkAction('discard', null, `Discard ${count} selected lead(s) from current extraction view?`);
        });
    }

    if (els.saveAllDiscoveredBtn) {
        els.saveAllDiscoveredBtn.addEventListener('click', () => {
            const total = state.leads.size;
            if (total === 0) {
                showToast('No leads discovered yet.', true);
                return;
            }
            executeBulkAction('save_all', null, `Save all ${total} discovered leads to the master database?`);
        });
    }

    if (els.bulkExportExcelBtn) {
        els.bulkExportExcelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const selected = getSelectedLeadsList();
            exportToExcel(selected, `selected-leads-${Date.now()}.xlsx`);
        });
    }

    if (els.bulkExportCsvBtn) {
        els.bulkExportCsvBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const selected = getSelectedLeadsList();
            exportToCsv(selected, `selected-leads-${Date.now()}.csv`);
        });
    }

    if (els.bulkExportJsonBtn) {
        els.bulkExportJsonBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const selected = getSelectedLeadsList();
            exportToJson(selected, `selected-leads-${Date.now()}.json`);
        });
    }

    els.bulkCopyEmailsBtn.addEventListener('click', () => {
        const selected = getSelectedLeadsList();
        const emails = new Set();
        for (const lead of selected) {
            if (Array.isArray(lead.emails)) {
                for (const em of lead.emails) {
                    if (em && em.trim()) emails.add(em.trim());
                }
            }
        }
        if (emails.size === 0) {
            showToast('No emails found in selected leads.', true);
            return;
        }
        copyToClipboard(Array.from(emails).join(', '), `Copied ${emails.size} email${emails.size === 1 ? '' : 's'} to clipboard.`);
    });

    els.bulkCopyPhonesBtn.addEventListener('click', () => {
        const selected = getSelectedLeadsList();
        const phones = new Set();
        for (const lead of selected) {
            if (lead.phone && lead.phone.trim()) {
                phones.add(lead.phone.trim());
            }
        }
        if (phones.size === 0) {
            showToast('No phone numbers found in selected leads.', true);
            return;
        }
        copyToClipboard(Array.from(phones).join(', '), `Copied ${phones.size} phone number${phones.size === 1 ? '' : 's'} to clipboard.`);
    });

    // Export Dropdown Events
    els.exportAllExcelBtn.addEventListener('click', (e) => {
        e.preventDefault();
        exportToExcel(getAllLeadsList(), `all-leads-${Date.now()}.xlsx`);
    });

    if (els.exportAllCsvBtn) {
        els.exportAllCsvBtn.addEventListener('click', (e) => {
            e.preventDefault();
            exportToCsv(getAllLeadsList(), `all-leads-${Date.now()}.csv`);
        });
    }

    els.exportAllJsonBtn.addEventListener('click', (e) => {
        e.preventDefault();
        exportToJson(getAllLeadsList(), `all-leads-${Date.now()}.json`);
    });

    els.exportFilteredExcelBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const filtered = getFilteredLeads().map((item) => item.lead);
        exportToExcel(filtered, `filtered-leads-${Date.now()}.xlsx`);
    });

    if (els.exportFilteredCsvBtn) {
        els.exportFilteredCsvBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const filtered = getFilteredLeads().map((item) => item.lead);
            exportToCsv(filtered, `filtered-leads-${Date.now()}.csv`);
        });
    }

    els.exportFilteredJsonBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const filtered = getFilteredLeads().map((item) => item.lead);
        exportToJson(filtered, `filtered-leads-${Date.now()}.json`);
    });

    // Filter Listeners
    let searchDebounceTimer = null;
    els.searchInput.addEventListener('input', () => {
        const query = els.searchInput.value;
        els.searchClear.classList.toggle('d-none', !query);
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            state.filters.query = query;
            renderLeads();
        }, 150);
    });

    els.searchClear.addEventListener('click', () => {
        els.searchInput.value = '';
        els.searchClear.classList.add('d-none');
        state.filters.query = '';
        renderLeads();
    });

    els.filterEmail.addEventListener('change', (e) => {
        state.filters.email = e.target.value;
        renderLeads();
    });

    els.filterWebsite.addEventListener('change', (e) => {
        state.filters.website = e.target.value;
        renderLeads();
    });

    els.filterPhone.addEventListener('change', (e) => {
        state.filters.phone = e.target.value;
        renderLeads();
    });

    els.filterRating.addEventListener('change', (e) => {
        state.filters.rating = e.target.value;
        renderLeads();
    });

    els.sortLeads.addEventListener('change', (e) => {
        state.filters.sort = e.target.value;
        renderLeads();
    });

    // Quick filter chips
    els.filterChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const filterType = chip.dataset.filter;
            const isActive = chip.classList.toggle('active');
            if (isActive) {
                state.filters.quick.add(filterType);
            } else {
                state.filters.quick.delete(filterType);
            }
            renderLeads();
        });
    });

    const resetFiltersHandler = () => {
        resetFilterValues();
        renderLeads();
    };

    els.resetFiltersBtn.addEventListener('click', resetFiltersHandler);
    els.noFilterResetBtn.addEventListener('click', resetFiltersHandler);

    // Standard Buttons
    els.start.addEventListener('click', startExtraction);
    els.prompt.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') startExtraction();
    });
    if (els.locationInput) {
        els.locationInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') startExtraction();
        });
    }
    els.stop.addEventListener('click', stopExtraction);
    els.stopVerify.addEventListener('click', stopExtraction);
    els.openVerification.addEventListener('click', openVerification);
    els.clear.addEventListener('click', clearResults);
    els.newExtraction.addEventListener('click', () => {
        clearResults();
        els.prompt.focus();
    });
    els.summaryNew.addEventListener('click', () => {
        clearResults();
        els.prompt.focus();
    });
    if (els.completeMock) {
        els.completeMock.addEventListener('click', completeMockVerification);
    }

    // ==========================================
    // Extractor Email Outreach System
    // ==========================================
    let extractorLoadedTemplates = [];
    let activeExtractorTargetLeads = [];
    let extractorModalObj = null;

    async function loadExtractorTemplates() {
        try {
            const resp = await fetch(cfg.emailTemplatesUrl || '/api/email-templates/list');
            if (resp.ok) {
                extractorLoadedTemplates = await resp.json();
                if (els.extractorTemplateSelect) {
                    els.extractorTemplateSelect.innerHTML = '<option value="">-- Choose a template (optional) --</option>';
                    extractorLoadedTemplates.forEach((t) => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = `${t.name} (${t.category || 'Outreach'})${t.is_default ? ' ★ Default' : ''}`;
                        els.extractorTemplateSelect.appendChild(opt);
                    });
                }
            }
        } catch (_) {}
    }

    if (els.extractorTemplateSelect) {
        els.extractorTemplateSelect.addEventListener('change', () => {
            const selectedId = parseInt(els.extractorTemplateSelect.value, 10);
            const tmpl = extractorLoadedTemplates.find((t) => t.id === selectedId);
            if (tmpl) {
                if (els.extractorModalSubject) els.extractorModalSubject.value = tmpl.subject;
                if (els.extractorModalEditor) els.extractorModalEditor.innerHTML = tmpl.body;
            }
        });
    }

    function openExtractorEmailModal(leads) {
        if (!els.extractorSendEmailModalEl) return;
        if (!extractorModalObj && window.bootstrap && window.bootstrap.Modal) {
            extractorModalObj = new window.bootstrap.Modal(els.extractorSendEmailModalEl);
        }

        activeExtractorTargetLeads = leads;

        if (leads.length === 1) {
            const l = leads[0];
            const email = (Array.isArray(l.emails) ? l.emails[0] : '') || '';
            if (els.extractorModalRecipients) {
                els.extractorModalRecipients.textContent = `${l.business_name || 'Business'} <${email}>`;
            }
            if (els.extractorModalEmailBadge) {
                els.extractorModalEmailBadge.textContent = '1 recipient';
            }
        } else {
            if (els.extractorModalRecipients) {
                els.extractorModalRecipients.textContent = `Sending outreach to ${leads.length} selected lead(s)`;
            }
            if (els.extractorModalEmailBadge) {
                els.extractorModalEmailBadge.textContent = `${leads.length} with email`;
            }
        }

        const defaultTmpl = extractorLoadedTemplates.find((t) => t.is_default) || extractorLoadedTemplates[0];
        if (defaultTmpl) {
            if (els.extractorTemplateSelect) els.extractorTemplateSelect.value = defaultTmpl.id;
            if (els.extractorModalSubject) els.extractorModalSubject.value = defaultTmpl.subject;
            if (els.extractorModalEditor) els.extractorModalEditor.innerHTML = defaultTmpl.body;
        } else {
            if (els.extractorModalSubject) els.extractorModalSubject.value = `Quick inquiry regarding {{business_name}}`;
            if (els.extractorModalEditor) {
                els.extractorModalEditor.innerHTML = `
                    <p>Hi <strong>{{business_name}}</strong> Team,</p>
                    <p>I came across your business in {{city}} and wanted to reach out regarding our lead generation and growth services.</p>
                    <p>Would you have 5 minutes this week for a brief call?</p>
                    <p>Best regards,<br><strong>{{sender_name}}</strong></p>
                `;
            }
        }

        if (extractorModalObj) extractorModalObj.show();
    }

    if (els.bulkSendEmailBtn) {
        els.bulkSendEmailBtn.addEventListener('click', () => {
            const selected = getSelectedLeadsList().filter((l) => {
                const emails = Array.isArray(l.emails) ? l.emails : [];
                return emails.length > 0 && emails[0];
            });

            if (selected.length === 0) {
                showToast('None of the selected leads have an email address.', true);
                return;
            }

            openExtractorEmailModal(selected);
        });
    }

    if (els.btnExtractorConfirmSend) {
        els.btnExtractorConfirmSend.addEventListener('click', async () => {
            const subject = (els.extractorModalSubject ? els.extractorModalSubject.value : '').trim();
            const body = els.extractorModalEditor ? els.extractorModalEditor.innerHTML.trim() : '';

            if (!subject) {
                showToast('Please enter an email subject.', true);
                if (els.extractorModalSubject) els.extractorModalSubject.focus();
                return;
            }
            if (!body || body === '<p><br></p>') {
                showToast('Please enter an email body message.', true);
                if (els.extractorModalEditor) els.extractorModalEditor.focus();
                return;
            }

            els.btnExtractorConfirmSend.disabled = true;
            els.btnExtractorConfirmSend.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

            try {
                // Ensure unsaved leads are saved to obtain IDs
                const unsaved = activeExtractorTargetLeads.filter((l) => !l.id);
                if (unsaved.length > 0) {
                    await fetch(cfg.bulkActionUrl || '/api/leads/bulk-action', {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify({
                            action: 'save',
                            leads: unsaved,
                            job_id: state.jobId || undefined,
                        }),
                    });
                }

                let ids = activeExtractorTargetLeads.map((l) => l.id).filter(Boolean);

                const sendResp = await fetch(cfg.sendEmailUrl || '/api/leads/send-email', {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify({
                        lead_ids: ids.length > 0 ? ids : undefined,
                        lead_id: ids.length === 1 ? ids[0] : undefined,
                        template_id: els.extractorTemplateSelect && els.extractorTemplateSelect.value ? parseInt(els.extractorTemplateSelect.value, 10) : null,
                        subject,
                        body,
                    }),
                });

                const sendData = await sendResp.json();
                if (sendResp.ok && sendData.success) {
                    if (extractorModalObj) extractorModalObj.hide();
                    showToast(sendData.message || `Dispatched ${sendData.sent_count} email(s) successfully!`);
                } else {
                    showToast(sendData.message || 'Failed to dispatch email.', true);
                }
            } catch (err) {
                showToast('Network error while sending email.', true);
            } finally {
                els.btnExtractorConfirmSend.disabled = false;
                els.btnExtractorConfirmSend.innerHTML = '<i class="icon-base ti tabler-send me-1"></i> Send Outreach Email';
            }
        });
    }

    // Grid Delegation for card email button
    els.leadsGrid.addEventListener('click', (event) => {
        const sendBtn = event.target.closest('.btn-lead-send-email');
        if (sendBtn) {
            event.stopPropagation();
            const key = sendBtn.dataset.key;
            if (key && state.leads.has(key)) {
                openExtractorEmailModal([state.leads.get(key)]);
            }
        }
    });

    window.formatExtractorDoc = function (cmd, val = null) {
        document.execCommand(cmd, false, val);
        if (els.extractorModalEditor) els.extractorModalEditor.focus();
    };

    window.insertExtractorVariable = function (tag) {
        if (els.extractorModalEditor) {
            els.extractorModalEditor.focus();
            document.execCommand('insertText', false, tag);
        }
    };

    // Quick suggestion pills
    document.querySelectorAll('.loc-suggestion-pill').forEach((pill) => {
        pill.addEventListener('click', () => {
            if (pill.dataset.cat && els.prompt) els.prompt.value = pill.dataset.cat;
            if (pill.dataset.loc && els.locationInput) els.locationInput.value = pill.dataset.loc;
            if (els.prompt) els.prompt.focus();
        });
    });

    function saveStateToStorage() {
        try {
            if (!state.leads || state.leads.size === 0) {
                localStorage.removeItem(STORAGE_KEY);
                return;
            }

            const data = {
                jobId: state.jobId,
                prompt: els.prompt ? els.prompt.value : '',
                location: els.locationInput ? els.locationInput.value : '',
                limit: els.limit ? els.limit.value : '100',
                engineGoogleApi: els.engineGoogleApi ? els.engineGoogleApi.checked : true,
                leads: Array.from(state.leads.entries()),
                selectedKeys: Array.from(state.selectedKeys),
                leadCounter: state.leadCounter,
                status: els.statusDot ? (els.statusDot.dataset.status || 'ready') : 'ready',
                activity: els.activity ? els.activity.textContent : '',
                searchLabel: els.searchLabel ? els.searchLabel.textContent : '',
                kpi: {
                    leads: els.kpiLeads ? els.kpiLeads.textContent : '0',
                    seen: els.kpiSeen ? els.kpiSeen.textContent : '0',
                    emails: els.kpiEmails ? els.kpiEmails.textContent : '0',
                    websites: els.kpiWebsites ? els.kpiWebsites.textContent : '0',
                },
                isSaved: Boolean(state.isSaved),
                running: Boolean(state.running),
                timestamp: Date.now(),
            };

            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (err) {
            console.warn('Unable to persist extractor session:', err);
        }
    }

    async function restoreStateFromStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;

            const session = JSON.parse(raw);
            if (!session || !Array.isArray(session.leads) || session.leads.length === 0) {
                localStorage.removeItem(STORAGE_KEY);
                return;
            }

            if (session.isSaved && !session.running) {
                localStorage.removeItem(STORAGE_KEY);
                return;
            }

            if (session.prompt && els.prompt) els.prompt.value = session.prompt;
            if (session.location && els.locationInput) els.locationInput.value = session.location;
            if (session.limit && els.limit) els.limit.value = session.limit;
            if (els.engineGoogleApi && els.engineBrowser) {
                els.engineGoogleApi.checked = session.engineGoogleApi !== false;
                els.engineBrowser.checked = session.engineGoogleApi === false;
                updateEngineModeUi();
            }

            state.jobId = session.jobId || null;
            state.leadCounter = session.leadCounter || session.leads.length;
            state.isSaved = Boolean(session.isSaved);

            state.leads.clear();
            for (const [key, lead] of session.leads) {
                state.leads.set(key, lead);
            }

            state.selectedKeys.clear();
            if (Array.isArray(session.selectedKeys)) {
                for (const k of session.selectedKeys) {
                    state.selectedKeys.add(k);
                }
            }

            if (session.kpi) {
                if (els.kpiLeads) els.kpiLeads.textContent = session.kpi.leads || String(state.leads.size);
                if (els.kpiSeen) els.kpiSeen.textContent = session.kpi.seen || '0';
                if (els.kpiEmails) els.kpiEmails.textContent = session.kpi.emails || '0';
                if (els.kpiWebsites) els.kpiWebsites.textContent = session.kpi.websites || '0';
            }

            if (session.searchLabel && els.searchLabel) {
                els.searchLabel.textContent = session.searchLabel;
            }

            if (session.status) {
                const displayStatus = session.status === 'extracting' ? 'completed' : session.status;
                const activityText = session.activity ? session.activity.replace(/^Current Activity:\s*/, '') : null;
                setStatus(displayStatus, activityText);
            }

            renderLeads();
            updateSelectionUi();

            if (session.running && session.jobId) {
                try {
                    const statusUrl = jobUrl(cfg.statusUrl);
                    const resp = await fetch(statusUrl, { headers: headers() });
                    if (resp.ok) {
                        const statusData = await resp.json();
                        if (statusData.status === 'extracting' || statusData.status === 'searching' || statusData.status === 'starting') {
                            setRunning(true);
                            connectStream();
                        }
                    }
                } catch (_) {}
            }
        } catch (err) {
            console.warn('Unable to restore extractor session:', err);
        }
    }

    // Init UI state & restore persisted session
    updateEngineModeUi();
    loadExtractorTemplates();
    restoreStateFromStorage();
})();
