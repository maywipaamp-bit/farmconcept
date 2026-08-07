/* TheFarmConcept — Toast notification helper */
(function () {
  var icons = {
    success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
    warning: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
    danger: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
    info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>'
  };

  function getContainer() {
    var container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  function showToast(message, type, duration) {
    type = type || 'info';
    duration = duration || 3200;
    var container = getContainer();

    var toast = document.createElement('div');
    toast.className = 'toast is-' + type;
    toast.setAttribute('role', 'status');
    toast.innerHTML =
      '<span class="toast-icon">' + (icons[type] || icons.info) + '</span>' +
      '<span class="toast-message"></span>' +
      '<button type="button" class="toast-close" aria-label="ปิดการแจ้งเตือน">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
      '</button>';
    toast.querySelector('.toast-message').textContent = message;

    container.appendChild(toast);

    var timer = setTimeout(function () { removeToast(toast); }, duration);

    toast.querySelector('.toast-close').addEventListener('click', function () {
      clearTimeout(timer);
      removeToast(toast);
    });
  }

  function removeToast(toast) {
    if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
  }

  window.TFC = window.TFC || {};
  window.TFC.showToast = showToast;

  /* Demo trigger: <button data-toast-demo="success" data-toast-message="..."> */
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-toast-demo]');
    if (trigger) {
      showToast(trigger.getAttribute('data-toast-message') || 'แจ้งเตือน', trigger.getAttribute('data-toast-demo'));
    }
  });
})();
