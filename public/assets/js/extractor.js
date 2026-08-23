(() => {
    const cfg = window.ExtractorConfig || {};
    const state = {
        jobId: null,
        source: null,
        leads: new Map(),
        running: false,
    };

    const els = {
        prompt: document.getElementById('promptInput'),
        limit: document.getElementById('limitInput'),
        start: document.getElementById('startBtn'),
        stop: document.getElementById('stopBtn'),
        stopVerify: document.getElementById('stopFromVerifyBtn'),
        newExtraction: document.getElementById('newExtractionBtn'),
        summaryNew: document.getElementById('summaryNewBtn'),
        clear: document.getElementById('clearBtn'),
        export: document.getElementById('exportBtn'),
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
        leadsGrid: document.getElementById('leadsGrid'),
        leadsEmpty: document.getElementById('leadsEmpty'),
        leadCount: document.getElementById('leadCountBadge'),
    };

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
        els.alert.className = `alert alert-${type}`;
        els.alert.textContent = message;
        els.alert.classList.toggle('d-none', !show);
    }

    function setRunning(running) {
        state.running = running;
        els.start.disabled = running;
        els.prompt.disabled = running;
        els.limit.disabled = running;
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
        return `(${value.toLocaleString()} review${value === 1 ? '' : 's'})`;
    }

    function initials(name) {
        const parts = String(name || 'B').trim().split(/\s+/).slice(0, 2);
        return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'B';
    }

    function buildLeadCard(lead) {
        const rating = lead.rating != null ? Number(lead.rating).toFixed(1) : null;
        const reviews = formatReviewCount(lead.review_count);
        const emails = Array.isArray(lead.emails) ? lead.emails.filter(Boolean) : [];
        const emailHtml = emails.length
            ? emails.map((email) => `<a href="mailto:${escapeAttr(email)}" class="extractor-lead-contact-link">${escapeHtml(email)}</a>`).join('')
            : '<span class="text-muted">No email listed</span>';
        const phoneHtml = lead.phone
            ? `<a href="tel:${escapeAttr(lead.phone)}" class="extractor-lead-contact-link">${escapeHtml(lead.phone)}</a>`
            : '<span class="text-muted">No phone listed</span>';
        const category = lead.category ? `<span class="extractor-lead-tag">${escapeHtml(lead.category)}</span>` : '';
        const sourceTag = lead.source && lead.source !== 'Google Maps'
            ? `<span class="extractor-lead-tag extractor-lead-tag-muted">${escapeHtml(lead.source)}</span>`
            : '';
        const websiteBtn = lead.website
            ? `<a href="${escapeAttr(lead.website)}" target="_blank" rel="noopener" class="btn extractor-lead-cta"><i class="icon-base ti tabler-world-www me-1"></i>Visit Website</a>`
            : '';
        const mapsBtn = lead.google_maps_url
            ? `<a href="${escapeAttr(lead.google_maps_url)}" target="_blank" rel="noopener" class="btn btn-outline-secondary extractor-lead-secondary"><i class="icon-base ti tabler-map-pin me-1"></i>Google Maps</a>`
            : '';
        const snippet = lead.address
            ? escapeHtml(lead.address)
            : 'Public business listing from Google Maps.';

        return `
            <article class="extractor-lead-card">
                <div class="extractor-lead-media" aria-hidden="true">
                    <div class="extractor-lead-avatar">${escapeHtml(initials(lead.business_name))}</div>
                </div>
                <div class="extractor-lead-body">
                    <div class="extractor-lead-top">
                        <h6 class="extractor-lead-name">${escapeHtml(dash(lead.business_name))}</h6>
                        <div class="extractor-lead-address">${escapeHtml(dash(lead.address))}</div>
                    </div>
                    <div class="extractor-lead-rating-row">
                        <div class="extractor-stars" aria-label="${rating ? `${rating} out of 5 stars` : 'No rating'}">${renderStars(lead.rating)}</div>
                        ${rating ? `<span class="extractor-lead-rating-value">${escapeHtml(rating)}</span>` : ''}
                        ${reviews ? `<span class="extractor-lead-review-count">${escapeHtml(reviews)}</span>` : ''}
                    </div>
                    <div class="extractor-lead-tags">${category}${sourceTag}</div>
                    <div class="extractor-lead-meta">
                        <div class="extractor-lead-meta-item">
                            <i class="icon-base ti tabler-mail"></i>
                            <div>${emailHtml}</div>
                        </div>
                        <div class="extractor-lead-meta-item">
                            <i class="icon-base ti tabler-phone"></i>
                            <div>${phoneHtml}</div>
                        </div>
                    </div>
                    <p class="extractor-lead-snippet">${snippet}</p>
                    <div class="extractor-lead-actions">
                        <div class="extractor-lead-actions-left">
                            <span class="badge bg-label-success">Extracted</span>
                        </div>
                        <div class="extractor-lead-actions-right">
                            ${mapsBtn}
                            ${websiteBtn}
                        </div>
                    </div>
                </div>
            </article>
        `;
    }

    function upsertLead(lead) {
        const key = leadKey(lead);
        if (state.leads.has(key)) return;
        state.leads.set(key, lead);
        if (els.leadsEmpty) els.leadsEmpty.remove();

        const card = document.createElement('div');
        card.className = 'extractor-lead-card-wrap';
        card.innerHTML = buildLeadCard(lead);
        els.leadsGrid.prepend(card.firstElementChild);
        els.leadCount.textContent = String(state.leads.size);
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
            els.export.classList.remove('disabled');
            els.export.href = jobUrl(cfg.exportUrl);
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
                closeStream();
                break;
            case 'completed':
                setRunning(false);
                setStatus('completed', event.message || 'Extraction completed.');
                setAlert('success', event.message || 'Extraction completed.', true);
                showVerification(false);
                showSummary(event);
                closeStream();
                break;
            case 'cancelled':
                setRunning(false);
                setStatus('cancelled', event.message || 'Extraction stopped.');
                setAlert('secondary', event.message || 'Extraction stopped. Previously extracted leads have been preserved.', true);
                showVerification(false);
                showSummary(event);
                closeStream();
                break;
            case 'verification_timeout':
                setRunning(false);
                setStatus('verification_timeout', event.message);
                setAlert('warning', event.message || 'Human verification was not completed within the allowed time. Extraction has stopped. Previously extracted leads have been preserved.', true);
                showVerification(false);
                showSummary(event);
                closeStream();
                break;
            default:
                break;
        }
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

    async function startExtraction() {
        const prompt = (els.prompt.value || '').trim();
        if (prompt.length < 2) {
            setAlert('warning', 'Enter a search prompt such as “Find dentists in Lahore”.', true);
            els.prompt.focus();
            return;
        }

        setAlert('info', '', false);
        els.summaryCard.classList.add('d-none');
        showVerification(false);
        setStatus('starting', 'Starting extraction');
        setRunning(true);

        try {
            const response = await fetch(cfg.startUrl, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({
                    prompt,
                    limit: Number(els.limit.value || 100),
                    mode: els.mock?.checked ? 'mock' : 'live',
                    simulate_verification: Boolean(els.verify?.checked),
                }),
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to start extraction.');
            }
            state.jobId = payload.job_id;
            els.searchLabel.textContent = `Search: ${payload.query || prompt}`;
            connectStream();
        } catch (error) {
            setRunning(false);
            setStatus('error', error.message);
            setAlert('danger', error.message || 'Extractor service is unavailable. Please start the Python extractor service.', true);
        }
    }

    async function stopExtraction() {
        if (!state.jobId) return;
        try {
            await fetch(jobUrl(cfg.stopUrl), { method: 'POST', headers: headers() });
        } catch (error) {
            setAlert('danger', error.message || 'Unable to stop extraction.', true);
        }
    }

    async function openVerification() {
        if (!state.jobId) return;
        try {
            await fetch(jobUrl(cfg.focusUrl), { method: 'POST', headers: headers() });
        } catch (_) {
            /* The Playwright window is already open locally. */
        }
        els.verificationHint.textContent = 'Complete the verification in the Google Maps browser window, then extraction will resume automatically.';
    }

    async function completeMockVerification() {
        if (!state.jobId) return;
        await fetch(jobUrl(cfg.verifyCompleteUrl), { method: 'POST', headers: headers() });
    }

    function clearResults() {
        closeStream();
        state.jobId = null;
        state.leads.clear();
        els.leadsGrid.innerHTML = `
            <div id="leadsEmpty" class="extractor-leads-empty">
                <i class="icon-base ti tabler-building-store display-4 mb-2 text-muted"></i>
                <p class="mb-0 text-muted">No leads yet. Start an extraction to stream results here.</p>
            </div>
        `;
        els.leadsEmpty = document.getElementById('leadsEmpty');
        els.leadCount.textContent = '0';
        els.kpiLeads.textContent = '0';
        els.kpiSeen.textContent = '0';
        els.kpiEmails.textContent = '0';
        els.kpiWebsites.textContent = '0';
        els.summaryCard.classList.add('d-none');
        showVerification(false);
        setAlert('info', '', false);
        setStatus('ready', 'Waiting for a search.');
        els.searchLabel.textContent = 'Search: —';
        els.export.classList.add('disabled');
        els.export.href = '#';
        setRunning(false);
    }

    els.start.addEventListener('click', startExtraction);
    els.prompt.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') startExtraction();
    });
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
})();
