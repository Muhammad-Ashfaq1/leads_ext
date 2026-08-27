'use strict';

(function () {
  const fallbackNotify = {
    success: function (message) {
      if (typeof window.toastr !== 'undefined') window.toastr.success(message);
      else console.log('[Success]', message);
    },
    error: function (message) {
      if (typeof window.toastr !== 'undefined') window.toastr.error(message);
      else console.error('[Error]', message);
    },
    info: function (message) {
      if (typeof window.toastr !== 'undefined') window.toastr.info(message);
      else console.log('[Info]', message);
    },
    warning: function (message) {
      if (typeof window.toastr !== 'undefined') window.toastr.warning(message);
      else console.warn('[Warning]', message);
    }
  };

  const notifier = function () {
    return typeof window.Notiflix !== 'undefined' && window.Notiflix.Notify
      ? window.Notiflix.Notify
      : fallbackNotify;
  };

  window.appNotify = function (type, message) {
    if (! message) {
      return;
    }

    const notify = notifier();
    const methodMap = {
      success: 'success',
      error: 'failure',
      danger: 'failure',
      warning: 'warning',
      info: 'info'
    };
    const method = methodMap[type] && typeof notify[methodMap[type]] === 'function'
      ? methodMap[type]
      : 'info';

    notify[method](message);
  };

  // Backward compatibility alias for showToast
  window.showToast = function (type, message, title) {
    window.appNotify(type, message);
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.Notiflix !== 'undefined' && window.Notiflix.Notify) {
      window.Notiflix.Notify.init({
        width: '340px',
        position: 'right-top',
        distance: '20px',
        opacity: 1,
        borderRadius: '0.85rem',
        timeout: 3500,
        cssAnimationStyle: 'from-right',
        fontFamily: 'inherit',
        success: {
          background: 'rgba(40, 199, 111, 0.94)',
          textColor: '#ffffff',
          notiflixIconColor: 'rgba(255,255,255,0.9)'
        },
        failure: {
          background: 'rgba(234, 84, 85, 0.94)',
          textColor: '#ffffff',
          notiflixIconColor: 'rgba(255,255,255,0.9)'
        },
        warning: {
          background: 'rgba(255, 159, 67, 0.94)',
          textColor: '#ffffff',
          notiflixIconColor: 'rgba(255,255,255,0.9)'
        },
        info: {
          background: 'rgba(0, 207, 232, 0.94)',
          textColor: '#ffffff',
          notiflixIconColor: 'rgba(255,255,255,0.9)'
        }
      });
    }

    if (! window.sessionMessages || typeof window.sessionMessages !== 'object') {
      return;
    }

    ['success', 'error', 'info', 'warning'].forEach(function (type) {
      if (window.sessionMessages[type]) {
        window.appNotify(type, window.sessionMessages[type]);
      }
    });

    if (window.sessionMessages.status) {
      window.appNotify('success', window.sessionMessages.status);
    }

    if (Array.isArray(window.sessionMessages.errors)) {
      window.sessionMessages.errors.forEach(function (message) {
        window.appNotify('error', message);
      });
    }
  });
})();

