/* TheFarmConcept — Modal / Drawer open-close controller (Escape + backdrop + trigger attributes) */
(function () {
  function openOverlay(overlay) {
    if (!overlay) return;
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeOverlay(overlay) {
    if (!overlay) return;
    overlay.classList.remove('is-open');
    var anyOpen = document.querySelector('.modal-overlay.is-open, .drawer-overlay.is-open');
    if (!anyOpen) document.body.style.overflow = '';
  }

  document.addEventListener('click', function (e) {
    var openTrigger = e.target.closest('[data-open-modal]');
    if (openTrigger) {
      var overlay = document.getElementById(openTrigger.getAttribute('data-open-modal'));
      openOverlay(overlay);
      return;
    }

    var closeTrigger = e.target.closest('[data-close-modal]');
    if (closeTrigger) {
      var overlayToClose = closeTrigger.closest('.modal-overlay, .drawer-overlay');
      closeOverlay(overlayToClose);
      return;
    }

    if (e.target.classList.contains('modal-overlay') || e.target.classList.contains('drawer-overlay')) {
      closeOverlay(e.target);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var openOne = document.querySelector('.modal-overlay.is-open, .drawer-overlay.is-open');
      closeOverlay(openOne);
    }
  });

  window.TFC = window.TFC || {};
  window.TFC.openModal = function (id) { openOverlay(document.getElementById(id)); };
  window.TFC.closeModal = function (id) { closeOverlay(document.getElementById(id)); };
})();
