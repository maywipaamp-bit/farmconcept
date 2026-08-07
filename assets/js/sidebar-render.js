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

  function leafLinkHtml(item) {
    var active = isCurrentHref(item.href) ? ' is-active' : '';
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
    var childrenActive = item.children.some(function (c) { return isCurrentHref(c.href); });
    var selfActive = isCurrentHref(item.href);
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
})();
