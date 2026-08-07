/* TheFarmConcept — Navigation: Sidebar drawer/collapse, Breadcrumb active state, Profile dropdown.
   The sidebar markup itself is built earlier by assets/js/sidebar-render.js (loaded right after the
   sidebar's </aside> so it paints without flashing); this file only wires up the interactions. */
(function () {
  var appShell = document.querySelector('.app-shell');

  if (appShell) {
    var sidebarToggle = document.querySelector('[data-sidebar-collapse-toggle]');
    var menuBtn = document.querySelector('[data-sidebar-open]');
    var overlay = document.querySelector('.sidebar-overlay');

    var openMobileSidebar = function () {
      appShell.classList.add('is-sidebar-open');
    };

    var closeMobileSidebar = function () {
      appShell.classList.remove('is-sidebar-open');
    };

    if (menuBtn) {
      menuBtn.addEventListener('click', openMobileSidebar);
    }

    if (overlay) {
      overlay.addEventListener('click', closeMobileSidebar);
    }

    if (sidebarToggle) {
      if (localStorage.getItem('tfc-sidebar-collapsed') === '1') {
        appShell.classList.add('is-sidebar-collapsed');
      }
      sidebarToggle.addEventListener('click', function () {
        appShell.classList.toggle('is-sidebar-collapsed');
        localStorage.setItem(
          'tfc-sidebar-collapsed',
          appShell.classList.contains('is-sidebar-collapsed') ? '1' : '0'
        );
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMobileSidebar();
    });

    /* Tooltip for collapsed-sidebar nav icons (Desktop/Tablet only — Mobile drawer always shows full labels).
       Rendered as a single body-level element so it is never clipped by the scrollable .sidebar-nav. */
    var navTooltip = null;
    function ensureNavTooltip() {
      if (!navTooltip) {
        navTooltip = document.createElement('div');
        navTooltip.className = 'nav-tooltip';
        document.body.appendChild(navTooltip);
      }
      return navTooltip;
    }

    function showNavTooltip(item) {
      if (!appShell.classList.contains('is-sidebar-collapsed')) return;
      if (window.innerWidth < 768) return;
      var label = item.querySelector('.nav-label');
      if (!label) return;
      var tooltip = ensureNavTooltip();
      tooltip.textContent = label.textContent;
      var rect = item.getBoundingClientRect();
      tooltip.style.top = (rect.top + rect.height / 2) + 'px';
      tooltip.style.left = (rect.right + 8) + 'px';
      tooltip.classList.add('is-visible');
    }

    function hideNavTooltip() {
      if (navTooltip) navTooltip.classList.remove('is-visible');
    }

    document.querySelectorAll('.nav-item').forEach(function (item) {
      item.addEventListener('mouseenter', function () { showNavTooltip(item); });
      item.addEventListener('mouseleave', hideNavTooltip);
      item.addEventListener('focus', function () { showNavTooltip(item); });
      item.addEventListener('blur', hideNavTooltip);
    });

    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', hideNavTooltip);
    }
  }

  /* Public site mobile nav toggle (pages/public/*.html has no .app-shell) */
  var publicMenuBtn = document.querySelector('.public-menu-btn');
  var publicNav = document.querySelector('.public-nav');
  if (publicMenuBtn && publicNav) {
    publicMenuBtn.addEventListener('click', function () {
      publicNav.classList.toggle('is-open');
    });
  }

  /* Dropdown menus (profile, row actions) */
  document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
    var menu = btn.parentElement.querySelector('.dropdown-menu');
    if (!menu) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = menu.classList.contains('is-open');
      document.querySelectorAll('.dropdown-menu.is-open').forEach(function (m) {
        m.classList.remove('is-open');
      });
      menu.classList.toggle('is-open', !isOpen);
    });
  });

  document.addEventListener('click', function () {
    document.querySelectorAll('.dropdown-menu.is-open').forEach(function (m) {
      m.classList.remove('is-open');
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.dropdown-menu.is-open').forEach(function (m) {
        m.classList.remove('is-open');
      });
    }
  });

  /* Nav submenu expand/collapse (also used by the sidebar's 2-level accordion, rendered above) */
  document.querySelectorAll('[data-nav-submenu-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var submenu = document.getElementById(btn.getAttribute('data-nav-submenu-toggle'));
      if (!submenu) return;
      var nowHidden = submenu.classList.toggle('hidden');
      btn.setAttribute('aria-expanded', String(!nowHidden));
    });
  });

  /* Tabs
     ค่าเริ่มต้น: หา panel ทั้งหน้า (พฤติกรรมเดิมของทุกหน้าที่ใช้อยู่)
     ถ้าใส่ data-tab-panels="#selector" ที่แถบแท็บ จะหา panel เฉพาะใน element นั้น
     -> ใช้ทำแท็บซ้อนแท็บได้ (แท็บย่อยไม่ไปปิด panel ของแท็บหลัก) เช่นหน้ารายละเอียดกิจกรรม
     แต่ละแท็บที่ถูกเปิดจะยิง event 'tfc:tabshown' (detail = ชื่อ panel) ให้หน้าที่ต้องการรู้จังหวะ เช่นตอนวาดกราฟ */
  document.querySelectorAll('[data-tabs]').forEach(function (tabGroup) {
    var tabs = tabGroup.querySelectorAll('.tab-item');
    var panelScopeSelector = tabGroup.getAttribute('data-tab-panels');

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        tabs.forEach(function (t) { t.classList.remove('is-active'); });
        tab.classList.add('is-active');

        var targetId = tab.getAttribute('data-tab-target');
        var scope = panelScopeSelector ? document.querySelector(panelScopeSelector) : document;
        if (!scope) return;

        /* ในโหมด scope จะรับเฉพาะ panel ที่เป็นลูกโดยตรง เพื่อไม่ไปปิด panel ของแท็บย่อยที่ซ้อนอยู่ข้างใน */
        var panels = panelScopeSelector
          ? scope.querySelectorAll(':scope > [data-tab-panel]')
          : scope.querySelectorAll('[data-tab-panel]');

        panels.forEach(function (panel) {
          panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== targetId);
        });

        document.dispatchEvent(new CustomEvent('tfc:tabshown', { detail: targetId }));
      });
    });
  });
})();
