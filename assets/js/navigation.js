/* TheFarmConcept — Navigation: Sidebar drawer/collapse, Breadcrumb active state, Profile dropdown.
   The sidebar markup itself is built earlier by assets/js/sidebar-render.js (loaded right after the
   sidebar's </aside> so it paints without flashing); this file only wires up the interactions. */
(function () {
  var appShell = document.querySelector('.app-shell');

  if (appShell) {
    var sidebarToggle = document.querySelector('[data-sidebar-collapse-toggle]');
    var overlay = document.querySelector('.sidebar-overlay');

    /* ปุ่มเปิดลิ้นชักเมนูของมือถือ — เดิมอยู่ในแถบบนซึ่งถูกถอดออกทั้งระบบแล้ว
       จึงสร้างเป็นปุ่มลอยแทน แสดงเฉพาะจอมือถือ (คุมด้วย CSS ใน responsive.css)
       สร้างจากที่นี่ที่เดียว ไม่ต้องไปเติม markup ซ้ำในทุกหน้า */
    var menuBtn = document.querySelector('[data-sidebar-open]');
    if (!menuBtn) {
      menuBtn = document.createElement('button');
      menuBtn.type = 'button';
      menuBtn.className = 'drawer-btn';
      menuBtn.setAttribute('data-sidebar-open', '');
      menuBtn.setAttribute('aria-label', 'เปิดเมนู');
      menuBtn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>';
      appShell.appendChild(menuBtn);
    }

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

  /* Nav submenu expand/collapse (also used by the sidebar's 2-level accordion, rendered above)
     เปิดได้ทีละหมวดเท่านั้น — เปิดหมวดใหม่แล้วหมวดที่ค้างอยู่จะปิดเอง
     ถ้าปล่อยให้กางค้างได้หลายหมวด รายการจะยาวจนต้องเลื่อนหาเมนูที่ต้องการ
     ซึ่งเสียประโยชน์ของการจัดกลุ่มไปทั้งหมด */
  var submenuToggles = [].slice.call(document.querySelectorAll('[data-nav-submenu-toggle]'));

  function closeSubmenu(btn) {
    var menu = document.getElementById(btn.getAttribute('data-nav-submenu-toggle'));
    if (menu) menu.classList.add('hidden');
    btn.setAttribute('aria-expanded', 'false');
  }

  submenuToggles.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var submenu = document.getElementById(btn.getAttribute('data-nav-submenu-toggle'));
      if (!submenu) return;
      var willOpen = submenu.classList.contains('hidden');

      /* ปิดหมวดอื่นก่อนเสมอ ทำเฉพาะตอนกำลังจะเปิด การกดปิดตัวเองไม่ต้องไปยุ่งกับใคร */
      if (willOpen) {
        submenuToggles.forEach(function (other) {
          if (other !== btn) closeSubmenu(other);
        });
      }

      submenu.classList.toggle('hidden', !willOpen);
      btn.setAttribute('aria-expanded', String(willOpen));
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

  /* ---------- ย้าย Breadcrumb ลงมาไว้ใต้ชื่อหน้า ----------
     ในหน้า breadcrumb เขียนไว้เป็นตัวแรกของ .content เพราะเป็นตำแหน่งเดียวที่
     ทุกหน้าเหมือนกัน (โครงหัวหน้าของแต่ละหน้าต่างกันหมด) แล้วย้ายมาไว้ใต้ชื่อหน้า
     ตอนรัน ที่นี่ทีเดียว แทนที่จะไปหาจุดแทรกเองในทั้ง 25 หน้า

     รอ DOMContentLoaded เพราะหลายหน้าสร้างหัวหน้าด้วย TFC.renderPageHeader
     ในสคริปต์ท้ายไฟล์ ซึ่งรันหลัง navigation.js ตอนนี้ h1 ยังไม่มีตัวตน */
  function moveBreadcrumb() {
    var main = document.querySelector('.content');
    var crumb = main && main.querySelector('.breadcrumb');
    if (!crumb || crumb.classList.contains('breadcrumb-sub')) return true;

    var title = main.querySelector('h1');
    if (!title) return false;

    /* วางต่อจากชื่อหน้าได้ก็ต่อเมื่อกล่องที่ครอบชื่ออยู่เรียงลงมาเป็นแนวตั้ง
       บางหน้าเอาชื่อหน้าไว้ในแถวเดียวกับปุ่ม (flex แนวนอน) ถ้าแทรกตรงนั้น
       breadcrumb จะไปยืนข้างปุ่มแทนที่จะอยู่ใต้ชื่อ — กรณีนั้นวางใต้ทั้งแถวหัวแทน */
    var parent = title.parentElement;
    var style = window.getComputedStyle(parent);
    var isRow = style.display.indexOf('flex') > -1 && style.flexDirection.indexOf('column') === -1;

    var anchor = title;
    if (isRow) {
      anchor = parent;
      while (anchor.parentElement && anchor.parentElement !== main) anchor = anchor.parentElement;
    }

    crumb.classList.add('breadcrumb-sub');
    anchor.insertAdjacentElement('afterend', crumb);
    return true;
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (moveBreadcrumb()) return;

    /* บางหน้าวาดหัวหน้าหลังโหลดข้อมูลเสร็จ h1 จึงมาช้ากว่านี้ — รอจนกว่าจะโผล่
       แล้วค่อยย้าย ตัดการเฝ้าดูทิ้งเมื่อย้ายได้ หรือเมื่อครบ 5 วินาทีก็เลิกรอ */
    var main = document.querySelector('.content');
    if (!main) return;
    var observer = new MutationObserver(function () {
      if (moveBreadcrumb()) observer.disconnect();
    });
    observer.observe(main, { childList: true, subtree: true });
    setTimeout(function () { observer.disconnect(); }, 5000);
  });
})();
