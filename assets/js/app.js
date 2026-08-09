/* TheFarmConcept — App bootstrap & shared helpers (load last, after other assets/js files) */
window.TFC = window.TFC || {};

(function () {
  /* Re-enable transitions once the page has settled (the .is-preload guard is set in the <html> tag).
     Uses both rAF and a timeout: rAF gives the earliest correct moment on a visible page, but it never
     fires while the tab is hidden/not compositing, which would otherwise leave transitions dead for a
     page opened in a background tab. */
  var enableTransitions = function () {
    document.documentElement.classList.remove('is-preload');
  };
  requestAnimationFrame(function () { requestAnimationFrame(enableTransitions); });
  setTimeout(enableTransitions, 120);

  /* Generic row-action navigation: <button data-nav-link="target.html"> */
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-nav-link]');
    if (trigger) window.location.href = trigger.getAttribute('data-nav-link');

    if (e.target.closest('[data-go-back]')) history.back();
  });

  /* Sidebar active-item highlight is now computed in navigation.js's TFC.renderSidebarNav()
     (needs pathname + query string, e.g. to tell apart multiple placeholder.html?title=... links). */

  /* Thai date formatter: 2026-08-10 -> 10 ส.ค. 2569 */
  var thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  window.TFC.formatThaiDate = function (isoDate) {
    var d = new Date(isoDate);
    return d.getDate() + ' ' + thaiMonths[d.getMonth()] + ' ' + (d.getFullYear() + 543);
  };

  /* window.TFC.escapeHtml now lives in mock-data.js (loads first on every page, before this file) */

  /* อ่านไฟล์รูปเป็น data URL พร้อมตรวจชนิดไฟล์และขนาด — ใช้ร่วมกันทุกหน้าที่ให้อัปโหลดรูป
     (โปรไฟล์ / วิทยากร / ผู้ใช้งาน) จะได้ไม่ต้องเขียน FileReader + validation ซ้ำ */
  window.TFC.readImageFile = function (file, opts, onLoad) {
    opts = opts || {};
    var maxMB = opts.maxMB || 2;
    if (!file) return;

    function fail(message) {
      if (window.TFC.showToast) window.TFC.showToast(message, 'danger');
    }
    if (file.type && file.type.indexOf('image/') !== 0) return fail('รองรับเฉพาะไฟล์รูปภาพเท่านั้น');
    if (file.size > maxMB * 1024 * 1024) return fail('ไฟล์ใหญ่เกิน ' + maxMB + ' MB กรุณาเลือกไฟล์ใหม่');

    var reader = new FileReader();
    reader.onload = function (e) { onLoad(e.target.result); };
    reader.readAsDataURL(file);
  };

  /* ผูกช่องเลือกไฟล์เข้ากับกรอบพรีวิวรูปวงกลม (.profile-avatar / .cell-avatar ใช้โครงเดียวกัน:
     ตัวย่อชื่อเป็นข้อความ + <img> ทับด้านบนเมื่อมีรูป) คืน { set, get } ให้ฟอร์มสั่งงานได้
     TFC.attachAvatarPicker(previewEl, inputEl, { maxMB }) */
  window.TFC.attachAvatarPicker = function (preview, input, opts) {
    function set(src) {
      var img = preview.querySelector('img');
      if (!src) {
        if (img) img.remove();
        return;
      }
      if (!img) { img = document.createElement('img'); img.alt = ''; preview.appendChild(img); }
      img.src = src;
    }

    if (input) {
      input.addEventListener('change', function () {
        window.TFC.readImageFile(input.files && input.files[0], opts, set);
      });
    }

    return { set: set, get: function () { var img = preview.querySelector('img'); return img ? img.getAttribute('src') : ''; } };
  };

  /* Simulate a short async load, then swap loading -> content (used by [data-loading-target]) */
  window.TFC.simulateLoad = function (loadingEl, contentEl, delay) {
    setTimeout(function () {
      if (loadingEl) loadingEl.classList.add('hidden');
      if (contentEl) contentEl.classList.remove('hidden');
    }, delay || 600);
  };

  /* Auto-render shared notification dropdown from mock data, if present on the page */
  var notifBadge = document.querySelector('[data-notification-badge]');
  var notifList = document.querySelector('[data-notification-list]');
  var notifications = (window.TFC_MOCK && window.TFC_MOCK.notifications) || [];

  if (notifBadge) {
    notifBadge.classList.toggle('hidden', notifications.length === 0);
  }

  if (notifList) {
    if (notifications.length) {
      notifList.innerHTML = notifications.map(function (n) {
        return '<div class="dropdown-item notification-item">' +
          '<strong>' + window.TFC.escapeHtml(n.title) + '</strong>' +
          '<span class="text-secondary small">' + window.TFC.escapeHtml(n.detail) + '</span>' +
          '<span class="caption">' + window.TFC.escapeHtml(n.time) + '</span>' +
          '</div>';
      }).join('');
    } else {
      notifList.innerHTML = '<div class="notification-empty">ไม่มีการแจ้งเตือนใหม่</div>';
    }
  }

  /* Auto-render notification panel (card list, not dropdown) from mock data, if present on the page */
  var notifPanel = document.querySelector('[data-notification-panel]');
  if (notifPanel) {
    var iconByType = { info: 'info', warning: 'warning', danger: 'danger' };
    notifPanel.innerHTML = notifications.map(function (n) {
      var iconClass = n.type === 'warning' ? 'is-warning' : (n.type === 'danger' ? 'is-danger' : '');
      return '<div class="list-row">' +
        '<span class="list-row-icon ' + iconClass + '">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>' +
        '</span>' +
        '<span class="list-row-body">' +
        '<div class="small font-medium">' + window.TFC.escapeHtml(n.title) + '</div>' +
        '<div class="caption text-secondary">' + window.TFC.escapeHtml(n.detail) + '</div>' +
        '</span>' +
        '</div>';
    }).join('') || '<div class="notification-empty">ไม่มีการแจ้งเตือนใหม่</div>';
  }

  /* Auto-render recent activity timeline from mock data, if present on the page */
  var timelineEl = document.querySelector('[data-recent-activity]');
  var recentLog = (window.TFC_MOCK && window.TFC_MOCK.recentActivityLog) || [];
  if (timelineEl) {
    timelineEl.innerHTML = recentLog.map(function (item) {
      return '<div class="timeline-item">' +
        '<div class="timeline-time">' + window.TFC.escapeHtml(item.time) + '</div>' +
        '<div class="timeline-title">' + window.TFC.escapeHtml(item.title) + '</div>' +
        '<div class="small text-secondary">' + window.TFC.escapeHtml(item.detail) + '</div>' +
        '</div>';
    }).join('');
  }
})();
