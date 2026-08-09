/* TheFarmConcept — Standard Index Page Layout components (Page Header / Stat Card Group / Filter Row / Search Input).
   Shared across every CRUD List / Index screen so each module renders the same structure from a config object
   instead of hand-copied HTML. Load after mock-data.js (needs window.TFC.escapeHtml) and before each page's inline script.
   Data Table itself is intentionally NOT covered here — pages keep using the existing table markup/pattern as-is.

   API
   ---
   TFC.searchInputHTML({ id, name, placeholder, value, wrapperClass })
     -> HTML string for a search input with the magnifier icon (now the system-wide default for every search box).

   TFC.renderPageHeader(mountId, { title, description, actions: [{ label, icon, href, variant, attrs }] })
     -> Fills a `<div class="page-header" id="mountId"></div>` with title/description + action buttons.
        variant: 'primary' (default) | 'outline' | 'secondary' | 'danger'. actions is optional (header-only pages).

   TFC.renderStatCards(mountId, [{ icon, tone, label, value, target, trend: { text, direction, icon } }])
     -> Fills a `<div class="summary-cards" id="mountId"></div>`. Optional section — only call it when a screen needs it.
        tone: 'primary' (default) | 'info' | 'warning' | 'success' — all muted/soft tones from the existing design tokens.
        Pass `target` for a "value/target" progress card; pass `trend` for the existing dashboard-style trend caption
        (direction: 'up' (default) | 'down', icon is an optional leading svg string).

   TFC.renderFilterRow(mountId, { resultCountId, resultCount, resultLabelPrefix, resultLabelSuffix, filters: [...], search: {...}, extraButton })
     -> Fills a `<div class="page-toolbar" id="mountId"></div>` with result count + filter dropdowns + search input.
        filters: [{ id, placeholder, value, options: [{ value, label }] }] — any number, page owns the change handling.
        search: same shape as TFC.searchInputHTML (omit to hide the search box). extraButton: { label, icon, attrs }. */
window.TFC = window.TFC || {};

(function () {
  function mountEl(target) {
    return typeof target === 'string' ? document.getElementById(target) : target;
  }

  function attrString(attrs) {
    if (!attrs) return '';
    return Object.keys(attrs).map(function (key) {
      return ' ' + key + '="' + window.TFC.escapeHtml(attrs[key]) + '"';
    }).join('');
  }

  var SEARCH_ICON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>';

  window.TFC.searchInputHTML = function (opts) {
    opts = opts || {};
    var idAttr = opts.id ? ' id="' + window.TFC.escapeHtml(opts.id) + '"' : '';
    var nameAttr = opts.name ? ' name="' + window.TFC.escapeHtml(opts.name) + '"' : '';
    var valueAttr = opts.value ? ' value="' + window.TFC.escapeHtml(opts.value) + '"' : '';
    return '<div class="search-input' + (opts.wrapperClass ? ' ' + opts.wrapperClass : '') + '">' +
      '<span class="search-input-icon">' + SEARCH_ICON + '</span>' +
      '<input type="search" class="input"' + idAttr + nameAttr +
      ' placeholder="' + window.TFC.escapeHtml(opts.placeholder || 'ค้นหา...') + '"' + valueAttr + '>' +
      '</div>';
  };

  /* A. Page Header — optional per screen */
  window.TFC.renderPageHeader = function (target, opts) {
    var el = mountEl(target);
    if (!el) return;
    opts = opts || {};

    var actionsHtml = (opts.actions || []).map(function (action) {
      var tag = action.href ? 'a' : 'button';
      var open = '<' + tag + (action.href ? ' href="' + window.TFC.escapeHtml(action.href) + '"' : ' type="button"');
      open += ' class="btn btn-' + (action.variant || 'primary') + '"' + attrString(action.attrs) + '>';
      return open + (action.icon || '') + window.TFC.escapeHtml(action.label) + '</' + tag + '>';
    }).join('');

    el.innerHTML =
      '<div class="page-header-text">' +
      '<h1>' + window.TFC.escapeHtml(opts.title || '') + '</h1>' +
      (opts.description ? '<p class="page-description">' + window.TFC.escapeHtml(opts.description) + '</p>' : '') +
      '</div>' +
      (actionsHtml ? '<div class="page-header-actions">' + actionsHtml + '</div>' : '');

    if (!standardLayoutOff()) {
      /* .is-tight บังคับชื่อหน้าให้เป็นขนาดหัวข้อรอง (20px) — มาตรฐานกำหนด 28px */
      el.classList.remove('is-tight');
      mergeToolbarIntoHeader(el);
    }
  };

  /* หน้าที่ไม่ต้องการเลย์เอาต์มาตรฐาน ใส่ data-standard-layout="off" ที่แท็ก <html> */
  function standardLayoutOff() {
    return document.documentElement.getAttribute('data-standard-layout') === 'off';
  }

  /* ยุบแถบเครื่องมือ (.list-toolbar) เข้าไปอยู่แถวเดียวกับชื่อหน้า ตามแบบมาตรฐาน
     ย้ายตัว DOM เดิมทั้งก้อน ไม่ได้สร้างใหม่ id และ event handler จึงยังทำงานเหมือนเดิม
     ปุ่มหลัก (สีเขียว) ถูกดันไปอยู่ขวาสุดเสมอ */
  function mergeToolbarIntoHeader(headerEl) {
    var toolbar = document.querySelector('.list-toolbar');
    var actions = headerEl.querySelector('.page-header-actions');
    if (!toolbar || !actions) return;

    var primary = actions.querySelector('.btn-primary');
    Array.prototype.slice.call(toolbar.children).forEach(function (child) {
      /* ตามแบบมาตรฐาน แถวชื่อหน้าเหลือแค่ ค้นหา + ปุ่มหลัก
         - เส้นคั่น: ไม่มีความหมายเมื่อย้ายมาอยู่แถวชื่อหน้าแล้ว
         - ชิป "แสดงทั้งหมด": สถานะตัวกรองไปแสดงในแผงค้นหาแทน
         - ปุ่มดาวน์โหลด: ตัดออกทั้งระบบ (ฟังก์ชัน exportTableCsv ยังอยู่ เรียกกลับมาได้) */
      if (child.classList.contains('toolbar-divider') ||
          child.classList.contains('filter-chip') ||
          /export/i.test(child.id || '')) { child.remove(); return; }
      child.classList.remove('ml-auto');
      if (primary) actions.insertBefore(child, primary);
      else actions.appendChild(child);
    });
    toolbar.remove();
  }

  /* มาตรฐานฟอร์มข้อ 2: ทุกฟอร์มที่มีช่องบังคับกรอก ต้องมีบรรทัดอธิบายดอกจันที่หัวฟอร์มหนึ่งครั้ง
     ทำที่นี่ครั้งเดียวแทนการไล่เพิ่ม markup ทีละหน้า */
  function addRequiredHintToModals() {
    if (standardLayoutOff()) return;
    document.querySelectorAll('.modal-overlay .modal').forEach(function (modal) {
      if (!modal.querySelector('.form-required')) return;
      var head = modal.querySelector('.modal-header');
      var title = head && head.querySelector('.modal-title');
      if (!head || !title || head.querySelector('.modal-subtitle')) return;

      var wrap = document.createElement('div');
      wrap.className = 'modal-heading';
      title.parentNode.insertBefore(wrap, title);
      wrap.appendChild(title);

      var hint = document.createElement('p');
      hint.className = 'modal-subtitle';
      hint.innerHTML = 'ช่องที่มี <span class="form-required">*</span> จำเป็นต้องกรอก';
      wrap.appendChild(hint);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addRequiredHintToModals);
  } else {
    addRequiredHintToModals();
  }

  /* B. Summary Stat Card Group — optional per screen, any number of cards */
  window.TFC.renderStatCards = function (target, cards) {
    var el = mountEl(target);
    if (!el) return;

    el.innerHTML = (cards || []).map(function (card) {
      var tone = card.tone && card.tone !== 'primary' ? ' is-' + card.tone : '';
      var valueHtml = card.target != null
        ? '<div class="summary-card-value">' + window.TFC.escapeHtml(String(card.value)) +
          '<span class="summary-card-target">/' + window.TFC.escapeHtml(String(card.target)) + '</span></div>'
        : '<div class="summary-card-value">' + window.TFC.escapeHtml(String(card.value)) + '</div>';
      var trendHtml = card.trend
        ? '<div class="summary-card-trend is-' + (card.trend.direction || 'up') + '">' +
          (card.trend.icon || '') + window.TFC.escapeHtml(card.trend.text) + '</div>'
        : '';
      return '<div class="summary-card">' +
        '<span class="summary-card-icon' + tone + '">' + (card.icon || '') + '</span>' +
        '<div><div class="summary-card-label">' + window.TFC.escapeHtml(card.label) + '</div>' + valueHtml + trendHtml + '</div>' +
        '</div>';
    }).join('');
  };

  /* C. Filter Row (result count + filters + search) — optional per screen, Data Table itself unaffected */
  window.TFC.renderFilterRow = function (target, opts) {
    var el = mountEl(target);
    if (!el) return;
    opts = opts || {};

    var countHtml = opts.resultCountId
      ? '<div class="page-toolbar-count">' + window.TFC.escapeHtml(opts.resultLabelPrefix || 'พบทั้งหมด') +
        ' <strong id="' + window.TFC.escapeHtml(opts.resultCountId) + '">' + (opts.resultCount != null ? opts.resultCount : 0) + '</strong>' +
        (opts.resultLabelSuffix ? ' ' + window.TFC.escapeHtml(opts.resultLabelSuffix) : ' รายการ') +
        '</div>'
      : '';

    var filtersHtml = (opts.filters || []).map(function (filter) {
      var optionsHtml = (filter.options || []).map(function (option) {
        var val = option.value != null ? option.value : option.label;
        var selected = filter.value != null && String(filter.value) === String(val) ? ' selected' : '';
        return '<option value="' + window.TFC.escapeHtml(val) + '"' + selected + '>' + window.TFC.escapeHtml(option.label) + '</option>';
      }).join('');
      return '<select class="select filter-select" id="' + window.TFC.escapeHtml(filter.id) + '">' +
        (filter.placeholder ? '<option value="">' + window.TFC.escapeHtml(filter.placeholder) + '</option>' : '') +
        optionsHtml + '</select>';
    }).join('');

    var searchHtml = opts.search
      ? '<div class="page-toolbar-search">' + window.TFC.searchInputHTML(opts.search) + '</div>'
      : '';

    var extraHtml = opts.extraButton
      ? '<button type="button" class="btn btn-outline"' + attrString(opts.extraButton.attrs) + '>' +
        (opts.extraButton.icon || '') + window.TFC.escapeHtml(opts.extraButton.label) + '</button>'
      : '';

    el.innerHTML = countHtml + filtersHtml + searchHtml + extraHtml;
  };

  /* D. Pagination — optional per screen. เดิมทุกหน้าเขียน markup แบบ static ไว้; ฟังก์ชันนี้ทำให้หน้าที่ต้องการ
     paginate จริงเรียกใช้ได้โดยไม่ต้องเขียน logic ซ้ำ (หน้าที่ยังใช้ markup เดิมไม่ได้รับผลกระทบ)
     TFC.renderPagination(mountId, { page, pageSize, total, onChange, pageSizeOptions, onPageSizeChange, footer })
     - pageSizeOptions (เช่น [10, 20, 50]) -> แสดงตัวเลือก "รายการต่อหน้า" (ไม่ใส่ = ไม่แสดง, พฤติกรรมเดิม)
     - footer: true -> วางกลุ่มปุ่มหน้าไว้ซ้าย และข้อความสรุปไว้ขวา ตามโครงหน้ารายการมาตรฐาน */
  window.TFC.renderPagination = function (target, opts) {
    var el = mountEl(target);
    if (!el) return;
    opts = opts || {};

    var total = opts.total || 0;
    var pageSize = opts.pageSize || 10;
    var pageCount = Math.max(1, Math.ceil(total / pageSize));
    var page = Math.min(Math.max(1, opts.page || 1), pageCount);
    var from = total === 0 ? 0 : (page - 1) * pageSize + 1;
    var to = Math.min(page * pageSize, total);

    var numbersHtml = '';
    for (var i = 1; i <= pageCount; i++) {
      numbersHtml += '<button type="button" class="pagination-btn' + (i === page ? ' is-active' : '') +
        '" data-page="' + i + '">' + i + '</button>';
    }

    var sizeHtml = '';
    if (opts.pageSizeOptions && opts.pageSizeOptions.length) {
      sizeHtml = '<label class="pagination-size">รายการต่อหน้า' +
        '<select class="select" data-page-size>' +
        opts.pageSizeOptions.map(function (size) {
          return '<option value="' + size + '"' + (size === pageSize ? ' selected' : '') + '>' + size + '</option>';
        }).join('') +
        '</select></label>';
    }

    el.innerHTML = '<div class="pagination' + (opts.footer ? ' is-footer' : '') + '">' +
      '<div class="pagination-info">แสดง ' + from + '-' + to + ' จาก ' + total + ' รายการ</div>' +
      '<div class="pagination-controls">' +
      '<button type="button" class="pagination-btn" data-page="1"' + (page === 1 ? ' disabled' : '') + ' aria-label="หน้าแรก">«</button>' +
      '<button type="button" class="pagination-btn" data-page="' + (page - 1) + '"' + (page === 1 ? ' disabled' : '') + ' aria-label="ก่อนหน้า">‹</button>' +
      numbersHtml +
      '<button type="button" class="pagination-btn" data-page="' + (page + 1) + '"' + (page === pageCount ? ' disabled' : '') + ' aria-label="ถัดไป">›</button>' +
      '<button type="button" class="pagination-btn" data-page="' + pageCount + '"' + (page === pageCount ? ' disabled' : '') + ' aria-label="หน้าสุดท้าย">»</button>' +
      sizeHtml +
      '</div></div>';

    if (typeof opts.onChange === 'function') {
      el.querySelectorAll('[data-page]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var next = Number(btn.getAttribute('data-page'));
          if (next >= 1 && next <= pageCount && next !== page) opts.onChange(next);
        });
      });
    }

    var sizeSelect = el.querySelector('[data-page-size]');
    if (sizeSelect && typeof opts.onPageSizeChange === 'function') {
      sizeSelect.addEventListener('change', function () {
        opts.onPageSizeChange(Number(sizeSelect.value));
      });
    }
  };
})();
