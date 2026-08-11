/* TheFarmConcept — Sidebar renderer: builds the 2-level nav from window.TFC_MENU (assets/js/menu-config.js).

   IMPORTANT — load position: this file is included immediately after the closing </aside> of the
   sidebar, NOT at the end of <body> with the other scripts. At that point the <nav id="sidebar-nav">
   mount already exists but the browser has not painted yet, so the sidebar appears fully built on the
   first frame. Loading it at the end of <body> made the sidebar flash in empty-then-filled on every
   navigation. Its only dependencies are window.TFC_MENU and window.TFC.escapeHtml, so mock-data.js
   and menu-config.js are loaded alongside it at the same early position.

   Interaction handlers (expand/collapse, tooltips, drawer) stay in navigation.js at the end of <body>,
   because those also bind to topbar/content elements that do not exist this early. */
(function () {
  var mount = document.getElementById('sidebar-nav');
  if (!mount || !window.TFC_MENU) return;

  var base = mount.getAttribute('data-nav-base') || '';
  var currentPath = window.location.pathname.replace(/\/+$/, '');
  var currentHref = currentPath + window.location.search;

  function iconSvg(pathMarkup) {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + pathMarkup + '</svg>';
  }

  function resolvedHref(rootRelativeHref) {
    return base + rootRelativeHref;
  }

  function isCurrentHref(rootRelativeHref) {
    if (!rootRelativeHref) return false;
    var a = document.createElement('a');
    a.href = resolvedHref(rootRelativeHref);
    var linkPath = a.pathname.replace(/\/+$/, '');
    return (linkPath + a.search) === currentHref;
  }

  /* alsoMatch เทียบเฉพาะ path ไม่รวม query string เพราะหน้ารายละเอียดมักมี ?id=
     ต่างจาก href ของเมนูเองที่ต้องเทียบทั้ง query ด้วย (placeholder.html?title=… ใช้แยกเมนูกัน) */
  function isCurrentPath(rootRelativeHref) {
    if (!rootRelativeHref) return false;
    var a = document.createElement('a');
    a.href = resolvedHref(rootRelativeHref);
    return a.pathname.replace(/\/+$/, '') === currentPath;
  }

  /* เมนูจะไฮไลต์เมื่ออยู่ที่ href ของตัวเอง หรืออยู่ที่หน้าใน alsoMatch
     ใช้กับโมดูลที่มีเมนูเดียวแต่มีหลายหน้า เช่น หน้าสร้าง/แก้ไข ที่ไม่มีเมนูของตัวเอง */
  function isActiveItem(item) {
    if (isCurrentHref(item.href)) return true;
    return (item.alsoMatch || []).some(isCurrentPath);
  }

  function leafLinkHtml(item) {
    var active = isActiveItem(item) ? ' is-active' : '';
    return '<a href="' + resolvedHref(item.href) + '" class="nav-item' + active + '" data-nav-key="' + item.key + '">' +
      '<span class="nav-item-icon">' + iconSvg(item.icon) + '</span>' +
      '<span class="nav-label">' + window.TFC.escapeHtml(item.label) + '</span>' +
      '</a>';
  }

  var chevron = '<svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>';

  mount.innerHTML = window.TFC_MENU.map(function (item) {
    if (!item.children || !item.children.length) {
      return leafLinkHtml(item);
    }

    var submenuId = 'nav-submenu-' + item.key;
    var childrenActive = item.children.some(isActiveItem);
    var selfActive = isActiveItem(item);
    var expanded = childrenActive; // auto-expand only the group containing the current page

    var headerHtml = item.href
      ? '<div class="nav-item-row">' +
        '<a href="' + resolvedHref(item.href) + '" class="nav-item nav-item-parent' + (selfActive ? ' is-active' : '') + '" data-nav-key="' + item.key + '">' +
        '<span class="nav-item-icon">' + iconSvg(item.icon) + '</span><span class="nav-label">' + window.TFC.escapeHtml(item.label) + '</span>' +
        '</a>' +
        '<button type="button" class="nav-submenu-toggle" data-nav-submenu-toggle="' + submenuId + '" aria-expanded="' + expanded + '" aria-label="ขยาย/ยุบเมนู ' + window.TFC.escapeHtml(item.label) + '">' + chevron + '</button>' +
        '</div>'
      : '<button type="button" class="nav-item nav-item-parent nav-item-parent-toggle' + (childrenActive ? ' is-active' : '') + '" data-nav-submenu-toggle="' + submenuId + '" aria-expanded="' + expanded + '">' +
        '<span class="nav-item-icon">' + iconSvg(item.icon) + '</span><span class="nav-label">' + window.TFC.escapeHtml(item.label) + '</span>' +
        chevron +
        '</button>';

    return '<div class="nav-group' + (selfActive || childrenActive ? ' is-active-group' : '') + '">' +
      headerHtml +
      '<div class="nav-submenu' + (expanded ? '' : ' hidden') + '" id="' + submenuId + '">' +
      item.children.map(leafLinkHtml).join('') +
      '</div></div>';
  }).join('');

  /* ---------- โปรไฟล์ผู้ใช้ท้ายแถบซ้าย ----------
     ย้ายลงมาจากมุมขวาบน เพื่อคืนความกว้างทั้งแถวให้แถบบนไว้ใช้กับ breadcrumb และค้นหา
     สร้างจาก JS ที่เดียวเหมือนเมนู แทนที่จะเขียนซ้ำในทุกหน้า (ของเดิมซ้ำอยู่ 25 หน้า
     และชื่อที่เขียนไว้ก็ไม่ตรงกับ currentUser จริง ต้องอาศัย JS มาเขียนทับทีหลัง)
     ชื่อคลาสตรงกับที่ profile-modal.js ใช้อัปเดตหลังแก้โปรไฟล์ */
  var sidebar = mount.closest('.sidebar');
  var user = (window.TFC_MOCK && window.TFC_MOCK.currentUser) || {};
  if (sidebar && !sidebar.querySelector('.sidebar-profile')) {
    var esc = window.TFC.escapeHtml;
    var avatarImg = user.avatar
      ? '<img src="' + esc(user.avatar) + '" alt="" onerror="this.remove()">'
      : '<img src="' + base + 'assets/images/avatar-default.png" alt="" onerror="this.remove()">';

    var box = document.createElement('div');
    box.className = 'sidebar-foot dropdown';
    box.innerHTML =
      '<button type="button" class="sidebar-profile" data-dropdown-toggle aria-label="เมนูผู้ใช้งาน">' +
        '<span class="avatar avatar-sm">' + esc(user.initials || '') + avatarImg + '</span>' +
        '<span class="sidebar-profile-info">' +
          '<span class="sidebar-profile-name">' + esc(user.name || '') + '</span>' +
          '<span class="sidebar-profile-role">' + esc(user.role || '') + '</span>' +
        '</span>' +
        '<svg class="sidebar-profile-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>' +
      '</button>' +
      '<div class="dropdown-menu">' +
        '<a class="dropdown-item" href="' + base + 'admin/profile.html">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>' +
          'โปรไฟล์ของฉัน' +
        '</a>' +
        '<div class="dropdown-divider"></div>' +
        '<a class="dropdown-item is-danger" href="' + base + 'login.html">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>' +
          'ออกจากระบบ' +
        '</a>' +
      '</div>';
    sidebar.appendChild(box);
  }
})();
