/**
 * PosConfirm — the application's unified confirmation / prompt dialog.
 *
 * Replaces native `confirm()` and `prompt()`. Provides high-fidelity glassmorphic
 * modals, action spinners, tone badges, and declarative data-pos-confirm bindings.
 */
(function () {
    'use strict';

    if (window.PosConfirm) return;

    var STYLE_ID = 'pos-confirm-styles';
    var Z = 20000;

    var state = {
        root: null,
        resolve: null,
        lastFocus: null,
        busy: false,
        isPrompt: false,
    };

    function injectStyles() {
        if (document.getElementById(STYLE_ID)) return;

        var css = ''
            + '.pos-confirm-overlay{position:fixed;inset:0;z-index:' + Z + ';display:flex;'
            + 'align-items:center;justify-content:center;padding:16px;'
            + 'background:rgba(15,23,42,.42);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);'
            + 'opacity:0;transition:opacity .14s ease}'
            + '.pos-confirm-overlay.is-open{opacity:1}'
            + '.pos-confirm-box{width:100%;max-width:460px;border-radius:1rem;'
            + 'border:1px solid rgba(255,255,255,.85);'
            + 'background-color:rgba(255,255,255,.94);'
            + 'background-image:linear-gradient(135deg,rgba(115,103,240,.1) 0%,rgba(115,103,240,.02) 45%,rgba(255,255,255,.4) 80%);'
            + '-webkit-backdrop-filter:blur(18px) saturate(160%);backdrop-filter:blur(18px) saturate(160%);'
            + 'box-shadow:0 24px 48px -18px rgba(115,103,240,.35),0 2px 6px rgba(47,43,61,.06),inset 0 1px 1.5px rgba(255,255,255,.95);'
            + 'transform:translateY(8px);transition:transform .14s ease;max-height:calc(100vh - 32px);overflow:auto}'
            + '.pos-confirm-overlay.is-open .pos-confirm-box{transform:none}'
            + '.pos-confirm-body{display:flex;gap:14px;padding:24px 24px 4px}'
            + '.pos-confirm-icon{flex:0 0 40px;width:40px;height:40px;border-radius:50%;'
            + 'display:inline-flex;align-items:center;justify-content:center;font-size:1.25rem}'
            + '.pos-confirm-icon.is-danger{background:rgba(234,84,85,.14);color:#ea5455}'
            + '.pos-confirm-icon.is-warning{background:rgba(255,159,67,.16);color:#ff9f43}'
            + '.pos-confirm-icon.is-primary{background:rgba(115,103,240,.14);color:#7367f0}'
            + '.pos-confirm-text{flex:1 1 auto;min-width:0}'
            + '.pos-confirm-title{margin:0 0 6px;font-size:1.05rem;font-weight:600;color:#444050;line-height:1.35}'
            + '.pos-confirm-message{margin:0;font-size:.9rem;line-height:1.5;color:#6f6b7d;overflow-wrap:anywhere}'
            + '.pos-confirm-field{margin-top:14px}'
            + '.pos-confirm-label{display:block;margin-bottom:4px;font-size:.8rem;color:#6f6b7d}'
            + '.pos-confirm-input{width:100%;padding:8px 10px;border:1px solid rgba(115,103,240,.22);border-radius:.5rem;'
            + 'font-size:.9rem;color:#444050;background:rgba(255,255,255,.78)}'
            + '.pos-confirm-input:focus{outline:none;border-color:rgba(115,103,240,.55);box-shadow:0 0 0 .15rem rgba(115,103,240,.12)}'
            + '.pos-confirm-error{display:none;margin:12px 24px 0;padding:8px 10px;border-radius:.5rem;'
            + 'background:rgba(234,84,85,.1);color:#b23b32;font-size:.82rem;line-height:1.4}'
            + '.pos-confirm-error.is-shown{display:block}'
            + '.pos-confirm-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;'
            + 'padding:18px 24px 22px;border-top:1px solid rgba(115,103,240,.1);margin-top:12px;'
            + 'background:rgba(115,103,240,.03)}'
            + '.pos-confirm-btn{border:1px solid transparent;border-radius:.5rem;padding:9px 18px;'
            + 'font-size:.9rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px}'
            + '.pos-confirm-btn:disabled{opacity:.65;cursor:not-allowed}'
            + '.pos-confirm-cancel{background:rgba(168,170,174,.12);border-color:transparent;color:#6f6b7d}'
            + '.pos-confirm-cancel:hover:not(:disabled){background:rgba(168,170,174,.2)}'
            + '.pos-confirm-ok{color:#fff}'
            + '.pos-confirm-ok.is-danger{background:#ea5455}'
            + '.pos-confirm-ok.is-danger:hover:not(:disabled){background:#d64445}'
            + '.pos-confirm-ok.is-warning{background:#ff9f43}'
            + '.pos-confirm-ok.is-primary{background:#7367f0}'
            + '.pos-confirm-ok.is-primary:hover:not(:disabled){background:#6258d1}'
            + '.pos-confirm-spin{width:14px;height:14px;border:2px solid rgba(255,255,255,.45);'
            + 'border-top-color:#fff;border-radius:50%;animation:pos-confirm-spin .7s linear infinite}'
            + '@keyframes pos-confirm-spin{to{transform:rotate(360deg)}}'
            + '[data-bs-theme=dark] .pos-confirm-box{background-color:rgba(37,41,60,.94);border-color:rgba(255,255,255,.12);'
            + 'background-image:linear-gradient(135deg,rgba(115,103,240,.18) 0%,rgba(115,103,240,.04) 45%,rgba(255,255,255,.02) 80%)}'
            + '[data-bs-theme=dark] .pos-confirm-title{color:#e7e3fc}'
            + '[data-bs-theme=dark] .pos-confirm-message,[data-bs-theme=dark] .pos-confirm-label{color:#b0acc7}'
            + '[data-bs-theme=dark] .pos-confirm-actions{background:rgba(255,255,255,.03);border-top-color:rgba(255,255,255,.1)}'
            + '[data-bs-theme=dark] .pos-confirm-cancel{background:rgba(255,255,255,.08);color:#cfc8e3}'
            + '@media (max-width:575.98px){.pos-confirm-body{padding:20px 18px 4px}'
            + '.pos-confirm-actions{padding:16px 18px 20px;flex-direction:column-reverse;align-items:stretch}'
            + '.pos-confirm-btn{justify-content:center}}';

        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = css;
        document.head.appendChild(style);
    }

    function build() {
        injectStyles();

        if (state.root) {
            if (!state.root.isConnected) document.body.appendChild(state.root);
            return state.root;
        }

        var overlay = document.createElement('div');
        overlay.className = 'pos-confirm-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'posConfirmTitle');
        overlay.setAttribute('aria-describedby', 'posConfirmMessage');
        overlay.hidden = true;

        overlay.innerHTML = ''
            + '<div class="pos-confirm-box">'
            + '  <div class="pos-confirm-body">'
            + '    <span class="pos-confirm-icon" data-pos-icon aria-hidden="true"></span>'
            + '    <div class="pos-confirm-text">'
            + '      <h5 class="pos-confirm-title" id="posConfirmTitle"></h5>'
            + '      <p class="pos-confirm-message" id="posConfirmMessage"></p>'
            + '      <div class="pos-confirm-field" data-pos-field hidden>'
            + '        <label class="pos-confirm-label" for="posConfirmInput"></label>'
            + '        <input class="pos-confirm-input" id="posConfirmInput" type="text">'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '  <p class="pos-confirm-error" data-pos-error role="alert"></p>'
            + '  <div class="pos-confirm-actions">'
            + '    <button type="button" class="pos-confirm-btn pos-confirm-cancel" data-pos-cancel></button>'
            + '    <button type="button" class="pos-confirm-btn pos-confirm-ok" data-pos-ok></button>'
            + '  </div>'
            + '</div>';

        document.body.appendChild(overlay);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay && !state.busy) settle(false);
        });

        overlay.querySelector('[data-pos-cancel]').addEventListener('click', function () {
            if (!state.busy) settle(false);
        });

        overlay.querySelector('[data-pos-ok]').addEventListener('click', onConfirmClick);

        overlay.querySelector('#posConfirmInput').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); onConfirmClick(); }
        });

        state.root = overlay;

        return overlay;
    }

    function focusables() {
        return Array.prototype.filter.call(
            state.root.querySelectorAll('button, input, a[href], [tabindex]:not([tabindex="-1"])'),
            function (el) { return !el.disabled && el.offsetParent !== null; }
        );
    }

    function onKeydown(e) {
        if (!state.root || state.root.hidden) return;

        if (e.key === 'Escape') {
            if (!state.busy) { e.preventDefault(); settle(false); }
            return;
        }

        if (e.key !== 'Tab') return;

        var items = focusables();
        if (items.length === 0) return;

        var first = items[0];
        var last = items[items.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function setBusy(busy) {
        state.busy = busy;

        var ok = state.root.querySelector('[data-pos-ok]');
        var cancel = state.root.querySelector('[data-pos-cancel]');

        ok.disabled = busy;
        cancel.disabled = busy;

        var spin = ok.querySelector('.pos-confirm-spin');

        if (busy && !spin) {
            spin = document.createElement('span');
            spin.className = 'pos-confirm-spin';
            ok.insertBefore(spin, ok.firstChild);
        } else if (!busy && spin) {
            spin.remove();
        }
    }

    function showError(message) {
        var el = state.root.querySelector('[data-pos-error]');
        el.textContent = message;
        el.classList.add('is-shown');
    }

    function clearError() {
        var el = state.root.querySelector('[data-pos-error]');
        el.textContent = '';
        el.classList.remove('is-shown');
    }

    function onConfirmClick() {
        if (state.busy) return;

        clearError();

        var value = state.isPrompt ? state.root.querySelector('#posConfirmInput').value.trim() : true;

        if (state.isPrompt && state.required && value === '') {
            showError('Please enter a value.');
            return;
        }

        if (typeof state.onConfirm !== 'function') {
            settle(value);
            return;
        }

        setBusy(true);

        Promise.resolve()
            .then(function () { return state.onConfirm(value); })
            .then(function () { setBusy(false); settle(value); })
            .catch(function (err) {
                setBusy(false);
                showError((err && err.message) ? err.message : 'That didn’t work. Please try again.');
            });
    }

    function settle(result) {
        var resolve = state.resolve;

        state.resolve = null;
        state.onConfirm = null;
        state.busy = false;

        document.removeEventListener('keydown', onKeydown, true);

        state.root.classList.remove('is-open');

        window.setTimeout(function () {
            if (!state.resolve) state.root.hidden = true;
        }, 120);

        if (state.lastFocus && typeof state.lastFocus.focus === 'function') {
            try { state.lastFocus.focus(); } catch (e) {}
        }
        state.lastFocus = null;

        if (typeof resolve === 'function') resolve(result);
    }

    var ICONS = {
        danger: '⚠',
        warning: '⚠',
        primary: 'ℹ',
    };

    function open(options) {
        options = options || {};

        var root = build();

        if (typeof state.resolve === 'function') settle(false);

        state.isPrompt = options.isPrompt === true;
        state.required = options.required !== false;
        state.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
        state.lastFocus = document.activeElement;

        var tone = options.tone === 'danger' || options.tone === 'warning' ? options.tone : 'primary';

        var icon = root.querySelector('[data-pos-icon]');
        icon.className = 'pos-confirm-icon is-' + tone;
        icon.textContent = options.icon || ICONS[tone];
        icon.hidden = options.icon === false;

        root.querySelector('#posConfirmTitle').textContent = options.title || 'Are you sure?';

        var message = root.querySelector('#posConfirmMessage');
        message.textContent = options.message || '';
        message.hidden = !options.message;

        var field = root.querySelector('[data-pos-field]');
        var input = root.querySelector('#posConfirmInput');

        field.hidden = !state.isPrompt;
        if (state.isPrompt) {
            root.querySelector('.pos-confirm-label').textContent = options.label || '';
            input.type = options.inputType || 'text';
            input.value = options.value || '';
            input.placeholder = options.placeholder || '';
        }

        var ok = root.querySelector('[data-pos-ok]');
        ok.className = 'pos-confirm-btn pos-confirm-ok is-' + tone;
        ok.textContent = options.confirmText || 'Confirm';

        root.querySelector('[data-pos-cancel]').textContent = options.cancelText || 'Cancel';

        clearError();
        setBusy(false);

        root.hidden = false;
        window.requestAnimationFrame(function () { root.classList.add('is-open'); });

        document.addEventListener('keydown', onKeydown, true);

        window.setTimeout(function () {
            if (state.isPrompt) { input.focus(); input.select(); }
            else ok.focus();
        }, 20);

        return new Promise(function (resolve) { state.resolve = resolve; });
    }

    window.PosConfirm = {
        /** @returns {Promise<boolean>} */
        open: function (options) {
            return open(options).then(function (r) { return r === true; });
        },

        /** @returns {Promise<string|null>} */
        prompt: function (options) {
            options = options || {};
            options.isPrompt = true;
            options.confirmText = options.confirmText || 'OK';

            return open(options).then(function (r) {
                return typeof r === 'string' ? r : null;
            });
        },
    };

    function optionsFrom(el) {
        return {
            title: el.getAttribute('data-pos-confirm-title') || 'Are you sure?',
            message: el.getAttribute('data-pos-confirm') || '',
            confirmText: el.getAttribute('data-pos-confirm-text') || 'Confirm',
            cancelText: el.getAttribute('data-pos-confirm-cancel') || 'Cancel',
            tone: el.getAttribute('data-pos-confirm-tone') || 'danger',
        };
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.matches || !form.matches('[data-pos-confirm]')) return;
        if (form.dataset.posConfirmed === '1') return;

        e.preventDefault();
        e.stopPropagation();

        window.PosConfirm.open(optionsFrom(form)).then(function (ok) {
            if (!ok) return;
            form.dataset.posConfirmed = '1';
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        });
    }, true);

    document.addEventListener('click', function (e) {
        var el = e.target.closest ? e.target.closest('a[data-pos-confirm], button[data-pos-confirm]') : null;
        if (!el) return;
        if (el.closest('form[data-pos-confirm]')) return;
        if (el.dataset.posConfirmed === '1') { delete el.dataset.posConfirmed; return; }

        e.preventDefault();
        e.stopPropagation();

        window.PosConfirm.open(optionsFrom(el)).then(function (ok) {
            if (!ok) return;
            el.dataset.posConfirmed = '1';
            el.click();
        });
    }, true);
})();

