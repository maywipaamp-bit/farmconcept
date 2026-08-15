/* TheFarmConcept — Action Menu: shared "..." row-action dropdown for data tables.
   Usage: <button type="button" class="btn btn-icon btn-sm" data-action-menu='[{"key":"view","icon":"eye","label":"ดูรายละเอียด","href":"detail.html"}]' aria-label="เมนือื่นๆ" aria-haspopup="true" aria-expanded="false">...three-dot icon...</button>
   Item fields: key (string), label (string), icon (see ICONS below), href (navigate) OR modal (open existing .modal-overlay by id), perm (permission key from roles.permissions; omit to always show), danger (true = red/destructive style). */
(function () {
  var ICONS = {
    view: '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
    edit: '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>',
    users: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
    record: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>',
    checkin: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>',
    evaluation: '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
    star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
    report: '<path d="M3 3v18h18"/><path d="M7 13l4-4 3 3 5-6"/>',
    export: '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/>',
    status: '<path d="M4 11a9 9 0 019-9M13 2v6h6"/><path d="M20 13a9 9 0 01-9 9M11 22v-6H5"/>',
    payment: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    sessions: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    publish: '<path d="M4 11a9 9 0 019-9M13 2v6h6"/><path d="M20 13a9 9 0 01-9 9M11 22v-6H5"/>',
    qr: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM21 14h.01M17 21h.01M21 21h.01M14 21h.01"/>',
    delete: '<path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/>'
  };

  var menu = null;
  var activeTrigger = null;

  function ensureMenu() {
    if (menu) return menu;
    menu = document.createElement('div');
    menu.className = 'dropdown-menu action-menu';
    menu.setAttribute('role', 'menu');
    document.body.appendChild(menu);
    return menu;
  }

  function iconSvg(key) {
    var d = ICONS[key] || ICONS.view;
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + d + '</svg>';
  }

  function closeMenu() {
    if (!menu) return;
    menu.classList.remove('is-open');
    if (activeTrigger) activeTrigger.setAttribute('aria-expanded', 'false');
    activeTrigger = null;
  }

  function positionMenu(trigger) {
    var rect = trigger.getBoundingClientRect();
    var menuWidth = 220;
    menu.style.position = 'fixed';
    menu.style.minWidth = menuWidth + 'px';

    var left = rect.right - menuWidth;
    if (left < 8) left = 8;
    if (left + menuWidth > window.innerWidth - 8) left = window.innerWidth - menuWidth - 8;
    menu.style.left = left + 'px';
    menu.style.right = 'auto';

    var estHeight = Math.min(menu.scrollHeight || 240, 320);
    var openUpward = rect.bottom + estHeight + 8 > window.innerHeight && rect.top > estHeight;
    if (openUpward) {
      menu.style.top = 'auto';
      menu.style.bottom = (window.innerHeight - rect.top + 6) + 'px';
    } else {
      menu.style.bottom = 'auto';
      menu.style.top = (rect.bottom + 6) + 'px';
    }
  }

  function openMenu(trigger) {
    var items;
    try {
      items = JSON.parse(trigger.getAttribute('data-action-menu') || '[]');
    } catch (err) {
      items = [];
    }

    var visibleItems = items.filter(function (item) {
      return window.TFC.hasPermission(item.perm);
    });

    ensureMenu();
    menu.innerHTML = visibleItems.map(function (item) {
      var tag = item.href ? 'a' : 'button';
      var attrs = item.href ? ' href="' + item.href + '"' : ' type="button"';
      return '<' + tag + attrs +
        ' class="dropdown-item' + (item.danger ? ' is-danger' : '') + '"' +
        ' role="menuitem"' +
        ' data-action-key="' + window.TFC.escapeHtml(item.key || '') + '"' +
        (item.href ? '' : ' data-action-href=""') +
        (item.modal ? ' data-action-modal="' + window.TFC.escapeHtml(item.modal) + '"' : '') +
        '>' + iconSvg(item.icon) + '<span>' + window.TFC.escapeHtml(item.label) + '</span></' + tag + '>';
    }).join('') || '<div class="notification-empty">ไม่มีสิทธิ์ดำเนินการ</div>';

    positionMenu(trigger);
    menu.classList.add('is-open');
    trigger.setAttribute('aria-expanded', 'true');
    activeTrigger = trigger;
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-menu]');
    if (trigger) {
      e.stopPropagation();
      if (activeTrigger === trigger) {
        closeMenu();
      } else {
        openMenu(trigger);
      }
      return;
    }

    var menuItem = e.target.closest('.dropdown-item[data-action-key]');
    if (menuItem && menu && menu.contains(menuItem)) {
      var modalId = menuItem.getAttribute('data-action-modal');
      var label = menuItem.textContent.trim();
      closeMenu();
      if (modalId) {
        if (window.TFC && window.TFC.openModal) window.TFC.openModal(modalId);
      } else if (!menuItem.hasAttribute('href')) {
        if (window.TFC && window.TFC.showToast) {
          window.TFC.showToast(label + ' (ฟีเจอร์นี้จะเชื่อมต่อจริงในระยะพัฒนา Backend)', 'info');
        }
      }
      return;
    }

    if (menu && !menu.contains(e.target)) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });

  window.addEventListener('scroll', function () {
    if (activeTrigger) closeMenu();
  }, true);

  window.addEventListener('resize', function () {
    if (activeTrigger) closeMenu();
  });

  /* Shared trigger-button markup so pages never hand-write the three-dot button.
     items: [{ key, label, icon, href|modal, perm, danger }] */
  window.TFC = window.TFC || {};
  window.TFC.actionMenuTrigger = function (items) {
    var json = JSON.stringify(items).replace(/'/g, '&#39;');
    return '<button type="button" class="btn btn-icon btn-sm action-menu-btn" aria-label="เมนูการจัดการ" aria-haspopup="true" aria-expanded="false" data-action-menu=\'' + json + '\'>' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>' +
      '</button>';
  };
})();
