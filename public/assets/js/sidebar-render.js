/* TheFarmConcept — เมนูด้านข้างสองชั้น (icon rail + submenu panel)
   สร้างจาก window.TFC_MENU ซึ่งเป็นโครงเมนูที่ผ่านการกรองสิทธิ์จากเซิร์ฟเวอร์มาแล้ว

   ตำแหน่งการโหลด: ไฟล์นี้ถูกใส่ทันทีหลัง #sidebar-shell ไม่ใช่ท้าย <body>
   ตอนนั้น mount มีแล้วแต่เบราว์เซอร์ยังไม่วาด เมนูจึงขึ้นครบตั้งแต่เฟรมแรก
   ถ้าย้ายไปท้าย body เมนูจะกระพริบว่างแล้วค่อยเต็มทุกครั้งที่เปลี่ยนหน้า

   พฤติกรรมที่ตัดสินไว้: คลิกหมวดในแถบไอคอน = "สลับแผงให้ดู" เท่านั้น ยังไม่เปลี่ยนหน้า
   ต้นแบบเลือกรายการแรกของหมวดให้อัตโนมัติ ซึ่งเมื่อผูกกับ router จริงแปลว่าเด้งหน้าทันทีที่คลิก
   ผู้ใช้ที่แค่อยากดูว่าหมวดนั้นมีอะไรจะโดนพาออกจากงานที่ทำค้างอยู่ จึงให้กดรายการในแผงเองอีกที */
(function () {
  var mount = document.getElementById('sidebar-shell');
  if (!mount || !window.TFC_MENU) return;

  var esc = window.TFC.escapeHtml;
  var base = mount.getAttribute('data-nav-base') || '';
  var shell = mount.closest('.app-shell');
  var user = (window.TFC_MOCK && window.TFC_MOCK.currentUser) || {};

  var currentPath = window.location.pathname.replace(/\/+$/, '');
  var currentHref = currentPath + window.location.search;

  var SUBNAV_KEY = 'tfc-subnav-collapsed';

  /* ---------- ตัวช่วยเรื่อง URL ---------- */

  function resolvedHref(href) { return base + href; }

  function pathOf(href) {
    var a = document.createElement('a');
    a.href = resolvedHref(href);
    return { path: a.pathname.replace(/\/+$/, ''), search: a.search };
  }

  function isCurrentHref(href) {
    if (!href) return false;
    var p = pathOf(href);
    return (p.path + p.search) === currentHref;
  }

  /* alsoMatch เทียบเฉพาะ path ไม่รวม query string เพราะหน้ารายละเอียดมักมี ?id= */
  function isCurrentPath(href) {
    if (!href) return false;
    return pathOf(href).path === currentPath;
  }

  function isCurrentPattern(pattern) {
    if (!pattern) return false;
    try { return new RegExp(pattern).test(currentPath); }
    catch (e) { return false; }
  }

  /* เมนูไฮไลต์เมื่ออยู่ที่ href ของตัวเอง หรืออยู่ที่หน้าใน alsoMatch
     ใช้กับโมดูลที่มีเมนูเดียวแต่หลายหน้า เช่นหน้าสร้าง/แก้ไขที่ไม่มีเมนูของตัวเอง */
  function isActiveItem(item) {
    return isCurrentHref(item.href)
      || (item.alsoMatch || []).some(isCurrentPath)
      || (item.alsoMatchPatterns || []).some(isCurrentPattern);
  }

  /* ---------- โครงข้อมูล ----------
     หมวดที่ไม่มีเมนูย่อย (แดชบอร์ด) ถือเป็นหมวดที่มีรายการเดียวคือตัวมันเอง
     แผงจึงมีเนื้อหาเสมอ ไม่มีหมวดไหนที่กดแล้วแผงว่าง */
  var categories = window.TFC_MENU.map(function (item) {
    var hasChildren = !!(item.children && item.children.length);

    return {
      key: item.key,
      label: item.label,
      icon: item.icon,
      /* หมวดจริงที่มีเมนูย่อย ต่างจากหมวดที่เป็นหน้าเดี่ยว (แดชบอร์ด) ซึ่งตัวมันเองคือรายการเดียว
         breadcrumb ใช้ค่านี้ตัดสินว่าจะใส่ชื่อหมวดนำหน้าไหม */
      hasChildren: hasChildren,
      items: hasChildren ? item.children : [item]
    };
  });

  function findActiveCategory() {
    for (var i = 0; i < categories.length; i++) {
      if (categories[i].items.some(isActiveItem)) return i;
    }
    return 0;
  }

  var activeIndex = findActiveCategory();
  var shownIndex = activeIndex;

  /* ---------- เส้นทางของหน้าปัจจุบัน — navigation.js เอาไปสร้าง breadcrumb ต่อ ----------
     เก็บจากที่นี่เพราะที่นี่คือที่เดียวที่รู้ว่าเมนูไหน "ตรงกับหน้านี้" อยู่แล้ว
     ของเดิม breadcrumb เขียนมือไว้ในทุกหน้า พอโครงเมนูเปลี่ยนก็ค้างเป็นของเก่าทั้งหมด */
  window.TFC.navTrail = null;

  (function recordTrail() {
    var cat = categories[activeIndex];
    if (!cat) return;

    var item = cat.items.filter(isActiveItem)[0];
    if (!item) return;

    /* ใส่ชื่อหมวดนำหน้าเมื่อเป็นหมวดที่มีเมนูย่อยจริง — หมวดที่เป็นหน้าเดี่ยว (แดชบอร์ด)
       ชื่อหมวดกับชื่อรายการเป็นตัวเดียวกัน ใส่แล้วจะได้ "แดชบอร์ด / แดชบอร์ด"
       เช็คด้วย hasChildren ไม่ใช่จำนวนรายการ ไม่งั้นหมวดที่มีเมนูย่อยแค่ตัวเดียวจะหายไปจาก breadcrumb */
    var trail = [];
    if (cat.hasChildren) trail.push({ label: cat.label });

    trail.push({
      label: item.label,
      href: resolvedHref(item.href),
      /* ตรงกับ href เป๊ะ = อยู่ที่หน้าของเมนูนั้นเอง · ตรงผ่าน alsoMatch = อยู่หน้าย่อยที่ไม่มีเมนูของตัวเอง
         ตัวนี้บอกว่า breadcrumb ต้องมีชั้นสุดท้ายที่หน้าเขียนเองต่อท้ายไหม */
      isCurrent: isCurrentHref(item.href)
    });

    window.TFC.navTrail = trail;
  })();

  /* ---------- ไอคอน ---------- */

  function railIcon(pathMarkup) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + pathMarkup + '</svg>';
  }

  function itemIcon(pathMarkup) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + pathMarkup + '</svg>';
  }

  var ICON = {
    cog: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.2.63.79 1.05 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1"/>',
    doc: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
    user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    exit: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
    help: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>'
  };

  /* ---------- แถบไอคอน ---------- */

  /* หมวดพื้นฐานอยู่ในลำดับเมนูหลักเหนือผู้ใช้งาน แต่ยังใช้ไอคอนเฟืองเดิม */
  var SETTINGS_KEY = 'master-data';

  function railBtnHtml(cat, i, extraClass, iconOverride) {
    return '<button type="button" class="rail-btn' + (extraClass || '') + (i === shownIndex ? ' is-active' : '') + '"' +
      ' data-rail="' + i + '" title="' + esc(cat.label) + '" aria-label="' + esc(cat.label) + '"' +
      ' aria-current="' + (i === activeIndex ? 'true' : 'false') + '">' +
      railIcon(iconOverride || cat.icon) + '</button>';
  }

  function railHtml() {
    var main = categories.map(function (cat, i) {
      return railBtnHtml(cat, i, '', cat.key === SETTINGS_KEY ? ICON.cog : null);
    }).join('');

    var avatar = user.avatar
      ? '<img src="' + esc(user.avatar) + '" alt="" onerror="this.remove()">'
      : esc(user.initials || (user.name || '?').charAt(0));

    return '<nav class="rail" aria-label="หมวดเมนูหลัก">' +
      main +
      '<span class="rail-spacer"></span>' +
      '<div class="rail-user">' +
        '<button type="button" class="rail-avatar" id="rail-avatar" aria-haspopup="menu" aria-expanded="false"' +
        ' aria-label="เมนูผู้ใช้งาน" title="บัญชีผู้ใช้งาน">' + avatar + '</button>' +
        userMenuHtml() +
      '</div>' +
      '</nav>';
  }

  function userMenuHtml() {
    /* ออกจากระบบต้องเป็น POST พร้อม CSRF token ไม่ใช่ลิงก์ธรรมดา
       ไม่งั้นเว็บอื่นฝัง <img src="/logout"> แล้วเตะผู้ใช้ออกจากระบบได้ */
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';

    return '<div class="rail-menu" id="rail-user-menu" role="menu" hidden>' +
      '<div class="rail-menu-head">' +
        '<span class="rail-menu-name">' + esc(user.name || '') + '</span>' +
        '<span class="rail-menu-mail">' + esc(user.role || '') + '</span>' +
      '</div>' +
      '<div class="rail-menu-divider"></div>' +
      /* เปิด popup โปรไฟล์ตรง ๆ ไม่พาไปหน้าอื่น
         profile-modal.js มีตัวไล่เปลี่ยนลิงก์ .../profile.html ให้อยู่แล้ว แต่แผงนี้ถูกวาดใหม่
         ทุกครั้งที่สลับหมวด แอตทริบิวต์ที่มันเขียนไว้จึงหายไป — ประกาศที่นี่เลยจะไม่มีทางหลุด */
      '<a class="rail-menu-item" role="menuitem" href="#" data-open-modal="profile-modal">' +
        itemIcon(ICON.user) + '<span>โปรไฟล์</span>' +
      '</a>' +
      '<form method="POST" action="' + base + 'logout">' +
        '<input type="hidden" name="_token" value="' + esc(token) + '">' +
        '<button type="submit" class="rail-menu-item is-danger" role="menuitem">' +
          itemIcon(ICON.exit) + '<span>ออกจากระบบ</span>' +
        '</button>' +
      '</form>' +
      '</div>';
  }

  /* ---------- แผงเมนูย่อย ---------- */

  function subnavHtml() {
    var cat = categories[shownIndex] || categories[0];

    var items = cat.items.map(function (item) {
      var active = isActiveItem(item);

      return '<a class="subnav-item' + (active ? ' is-active' : '') + '" href="' + resolvedHref(item.href) + '"' +
        ' data-nav-key="' + item.key + '"' + (active ? ' aria-current="page"' : '') + '>' +
        itemIcon(item.icon || ICON.doc) +
        '<span class="subnav-item-label">' + esc(item.label) + '</span>' +
        '</a>';
    }).join('');

    return '<div class="subnav" id="app-subnav">' +
      '<div class="subnav-head">' +
        '<a href="' + base + 'home.html" aria-label="หน้าแรก">' +
          '<img class="subnav-logo" src="' + base + 'assets/images/logo-farm.png" alt="The Farm Concept">' +
        '</a>' +
        '<button type="button" class="subnav-toggle" data-subnav-toggle title="ย่อเมนู" aria-label="ย่อเมนู" aria-expanded="true">‹</button>' +
      '</div>' +
      '<div class="subnav-title">' + esc(cat.label) + '</div>' +
      '<nav class="subnav-list" aria-label="' + esc(cat.label) + '">' +
        '<div class="subnav-group">' + items + '</div>' +
      '</nav>' +
      '<div class="subnav-foot">' +
        /* คู่มืออยู่ที่ docs/user-manual/ เสิร์ฟผ่าน /manual (หลัง middleware auth)
           เปิดแท็บใหม่เพราะคนมักเปิดคู่มือค้างไว้แล้วสลับกลับมาทำงานต่อ */
        '<a class="subnav-foot-link" href="' + base + 'manual" target="_blank" rel="noopener">' + itemIcon(ICON.help) + '<span>คู่มือการใช้งาน</span></a>' +
        '<div class="subnav-foot-meta">' +
          '<span>เวอร์ชัน ' + esc((window.TFC_APP && window.TFC_APP.version) || '1.0.0') + '</span>' +
          '<span>TheFarmConcept © ' + esc((window.TFC_APP && window.TFC_APP.year) || '') + '</span>' +
        '</div>' +
      '</div>' +
      '</div>' +
      '<div class="subnav-reopen">' +
        '<button type="button" class="subnav-toggle" data-subnav-toggle title="ขยายเมนู" aria-label="ขยายเมนู" aria-expanded="false">›</button>' +
      '</div>';
  }

  function render() {
    mount.innerHTML = railHtml() + subnavHtml() + '<div class="drawer-scrim" data-drawer-close></div>';
  }

  render();

  /* ---------- สลับหมวด ----------
     วาดใหม่ทั้งก้อนเพราะแผงเปลี่ยนทั้งชื่อหมวดและรายการ การแก้ทีละ node
     ต้องไล่ลบ/เพิ่มเองทุกจุด ซึ่งพลาดง่ายกว่าและไม่ได้เร็วขึ้นจริงที่ขนาดนี้ */
  mount.addEventListener('click', function (e) {
    var railBtn = e.target.closest('[data-rail]');

    if (railBtn) {
      shownIndex = Number(railBtn.getAttribute('data-rail'));
      render();

      /* บนจอแคบแผงเป็น drawer ทับเนื้อหา — เลือกหมวดแล้วต้องเปิดให้เห็นรายการทันที */
      if (shell && window.matchMedia('(max-width: 1023px)').matches) {
        shell.classList.add('is-drawer-open');
      }
      return;
    }

    if (e.target.closest('[data-subnav-toggle]')) {
      toggleSubnav();
      return;
    }

    if (e.target.closest('[data-drawer-close]') && shell) {
      shell.classList.remove('is-drawer-open');
      return;
    }

    var avatar = e.target.closest('#rail-avatar');
    if (avatar) {
      toggleUserMenu(avatar.getAttribute('aria-expanded') !== 'true');
      return;
    }
  });

  /* ---------- ย่อ/ขยายแผง ---------- */

  function toggleSubnav() {
    if (!shell) return;

    if (window.matchMedia('(max-width: 1023px)').matches) {
      shell.classList.toggle('is-drawer-open');
      return;
    }

    var collapsed = shell.classList.toggle('is-subnav-collapsed');
    try { localStorage.setItem(SUBNAV_KEY, collapsed ? '1' : '0'); } catch (err) { /* โหมดส่วนตัวเขียนไม่ได้ */ }
  }

  /* ---------- เมนูผู้ใช้ ---------- */

  function toggleUserMenu(open) {
    var avatar = document.getElementById('rail-avatar');
    var menu = document.getElementById('rail-user-menu');
    if (!avatar || !menu) return;

    avatar.setAttribute('aria-expanded', String(open));
    menu.hidden = !open;

    if (open) {
      var first = menu.querySelector('.rail-menu-item');
      if (first) first.focus();
    }
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('.rail-user')) return;
    toggleUserMenu(false);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;

    toggleUserMenu(false);
    if (shell) shell.classList.remove('is-drawer-open');
  });
})();
