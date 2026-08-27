'use strict';

(function (window, document, $) {
  const ensureHelpers = function () {
    window.Helpers = window.Helpers || {};
    return window.Helpers;
  };

  const resolveButton = function (button) {
    if (!button) {
      return null;
    }

    if ($ && button.jquery) {
      return button;
    }

    return $(button);
  };

  const defaultButtonHtml = function ($button) {
    const storedHtml = $button.data('default-html');

    if (storedHtml !== undefined) {
      return storedHtml;
    }

    const html = $button.html();
    $button.data('default-html', html);
    return html;
  };

  const setButtonLoading = function (button, isLoading, loadingText, defaultHtml) {
    if (!$) {
      return;
    }

    const $button = resolveButton(button);

    if (!$button || !$button.length) {
      return;
    }

    const originalHtml = defaultHtml || defaultButtonHtml($button);

    if (isLoading) {
      $button.prop('disabled', true);
      if (loadingText) {
        $button.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + loadingText);
      }
      return;
    }

    $button.prop('disabled', false).html(originalHtml);
  };

  const makeModalsStatic = function (root) {
    const scope = root || document;

    scope.querySelectorAll('.modal').forEach(function (modal) {
      if (modal.dataset.allowOutsideClose === 'true') {
        return;
      }

      modal.setAttribute('data-bs-backdrop', 'static');
      modal.setAttribute('data-bs-keyboard', 'false');
    });
  };

  const initToolTip = function (root) {
    if (typeof window.bootstrap === 'undefined' || !window.bootstrap.Tooltip) {
      return;
    }

    const scope = root && root.querySelectorAll ? root : document;
    const tooltipTriggerList = [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'));

    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      const existing = window.bootstrap.Tooltip.getInstance(tooltipTriggerEl);
      if (existing) {
        existing.dispose();
      }
      new window.bootstrap.Tooltip(tooltipTriggerEl);
    });
  };

  const helpers = ensureHelpers();
  helpers.setButtonLoading = setButtonLoading;
  helpers.makeModalsStatic = makeModalsStatic;
  helpers.initToolTip = initToolTip;
  window.appSetButtonLoading = setButtonLoading;

  // Global showConfirm helper wrapping PosConfirm and Swal
  window.showConfirm = function(title, text, confirmButtonText = 'Yes, Proceed', isDanger = true) {
    if (window.PosConfirm) {
      return window.PosConfirm.open({
        title: title,
        message: text,
        confirmText: confirmButtonText,
        tone: isDanger ? 'danger' : 'primary'
      }).then(function(confirmed) {
        return { isConfirmed: confirmed === true };
      });
    }

    if (typeof Swal !== 'undefined') {
      return Swal.fire({
        title: title,
        text: text,
        icon: isDanger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: isDanger ? 'btn btn-danger me-2' : 'btn btn-primary me-2',
          cancelButton: 'btn btn-outline-secondary'
        },
        buttonsStyling: false
      });
    }

    return Promise.resolve({ isConfirmed: window.confirm(title + '\n\n' + text) });
  };

  document.addEventListener('DOMContentLoaded', function () {
    makeModalsStatic(document);

    if (typeof window.Swal !== 'undefined' && typeof window.Swal.mixin === 'function') {
      window.Swal = window.Swal.mixin({
        allowOutsideClick: false,
        allowEscapeKey: false
      });
    }
  });
})(window, document, window.jQuery);

