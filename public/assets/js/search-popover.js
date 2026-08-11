/* TheFarmConcept — Search Panel Popover
   แผงค้นหา/ตัวกรองที่ซ่อนอยู่ในไอคอนแว่นขยาย เปิดเป็น Popover เมื่อคลิก
   ใช้แทนแถบค้นหา/ตัวกรองแบบแสดงตลอดเวลา เพื่อคืนพื้นที่หน้าจอให้ตาราง

   Component กลาง — ทุกหน้ารายการกำหนดฟิลด์ค้นหา/ตัวกรองของตัวเองผ่าน config
   โดยไม่ต้องแก้ไฟล์นี้ จับคู่กับ CSS .search-popover* ใน components.css
   โหลดหลัง mock-data.js (ต้องใช้ TFC.escapeHtml)

   การใช้งาน
   ---------
   var panel = TFC.searchPopover('mount-id', {
     note: 'แสดงผลไม่เกิน 250 รายการ',              // optional — ข้อความหมายเหตุมุมขวาบน
     searchLabel: 'ค้นหา:',                          // optional
     filterLabel: 'รายการค้นหา:',                    // optional
     search: { placeholder: 'ค้นหาจากชื่อ, รหัส...' },
     filters: [
       { id: 'status', label: 'สถานะเอกสาร', placeholder: 'ทั้งหมด',
         options: [{ value: 'open', label: 'เปิดรับสมัคร' }] }   // value ไม่ใส่ = ใช้ label
     ],
     onSearch: function (values, done) { ...กรองตาราง...; done(); },
     onClear: function () { ...แสดงทั้งหมด... }
   });

   values = { keyword: '', filters: { status: 'open' }, activeCount: 1 }
   onSearch รับ done() ไว้ปิด Loading ของปุ่ม (ถ้าไม่เรียก จะปิดเองภายใน 5 วินาที)

   เมธอดที่คืนกลับ: open() / close() / toggle() / getValues() / setValues(obj) / reset() / element */
window.TFC = window.TFC || {};

(function () {
  var SEARCH_ICON = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>';
  var PANEL_WIDTH = 340;
  var openPanels = [];

  function esc(value) { return window.TFC.escapeHtml(value); }

  window.TFC.searchPopover = function (target, config) {
    var mount = typeof target === 'string' ? document.getElementById(target) : target;
    if (!mount) return null;
    config = config || {};
    var filters = config.filters || [];

    /* ---------- Markup ---------- */
    var filtersHtml = filters.map(function (filter) {
      var options = (filter.options || []).map(function (option) {
        var value = option.value != null ? option.value : option.label;
        return '<option value="' + esc(value) + '">' + esc(option.label) + '</option>';
      }).join('');
      return '<div class="search-popover-field">' +
        '<label class="form-label" for="' + esc(filter.inputId || ('sp-filter-' + filter.id)) + '">' + esc(filter.label) + '</label>' +
        '<select class="select" id="' + esc(filter.inputId || ('sp-filter-' + filter.id)) + '" data-filter-id="' + esc(filter.id) + '">' +
        '<option value="">' + esc(filter.placeholder || 'ทั้งหมด') + '</option>' + options +
        '</select></div>';
    }).join('');

    mount.classList.add('search-popover');
    mount.innerHTML =
      '<button type="button" class="search-popover-trigger" data-sp-trigger' +
      ' aria-haspopup="dialog" aria-expanded="false" aria-label="' + esc(config.triggerLabel || 'ค้นหาและกรองข้อมูล') + '">' +
      SEARCH_ICON +
      '<span class="search-popover-badge hidden" data-sp-badge></span>' +
      '</button>' +

      '<div class="search-popover-panel" data-sp-panel role="dialog" aria-modal="false"' +
      ' aria-label="' + esc(config.triggerLabel || 'ค้นหาและกรองข้อมูล') + '">' +

      '<div class="search-popover-head">' +
      '<span class="search-popover-title">' + esc(config.searchLabel || 'ค้นหา:') + '</span>' +
      (config.note ? '<span class="search-popover-note">(' + esc(config.note) + ')</span>' : '') +
      '</div>' +

      '<div class="search-popover-field">' +
      '<div class="search-input">' +
      '<span class="search-input-icon">' + SEARCH_ICON + '</span>' +
      '<input type="search" class="input" data-sp-keyword' +
      ((config.search && config.search.id) ? ' id="' + esc(config.search.id) + '"' : '') +
      ' placeholder="' + esc((config.search && config.search.placeholder) || 'ค้นหา...') + '">' +
      '</div></div>' +

      (filters.length
        ? '<div class="search-popover-subtitle">' + esc(config.filterLabel || 'รายการค้นหา:') + '</div>' +
          '<div class="search-popover-filters">' + filtersHtml + '</div>'
        : '') +

      '<div class="search-popover-footer">' +
      '<button type="button" class="btn btn-text" data-sp-clear>' + esc(config.clearLabel || 'ล้างค่า') + '</button>' +
      '<button type="button" class="btn btn-primary" data-sp-submit>' + esc(config.submitLabel || 'ค้นหา') + '</button>' +
      '</div></div>';

    var trigger = mount.querySelector('[data-sp-trigger]');
    var panel = mount.querySelector('[data-sp-panel]');
    var badge = mount.querySelector('[data-sp-badge]');
    var keywordInput = mount.querySelector('[data-sp-keyword]');
    var submitBtn = mount.querySelector('[data-sp-submit]');
    var clearBtn = mount.querySelector('[data-sp-clear]');
    var selects = Array.prototype.slice.call(mount.querySelectorAll('[data-filter-id]'));

    /* ---------- ค่าปัจจุบันในแผง ---------- */
    function getValues() {
      var values = { keyword: keywordInput.value.trim(), filters: {}, activeCount: 0 };
      selects.forEach(function (select) {
        values.filters[select.getAttribute('data-filter-id')] = select.value;
        if (select.value) values.activeCount++;
      });
      if (values.keyword) values.activeCount++;
      return values;
    }

    function setValues(next) {
      next = next || {};
      if (next.keyword != null) keywordInput.value = next.keyword;
      if (next.filters) {
        selects.forEach(function (select) {
          var value = next.filters[select.getAttribute('data-filter-id')];
          if (value != null) select.value = value;
        });
      }
      syncBadge();
    }

    /* จุด/ตัวเลขบนไอคอน บอกว่ากำลังกรองอยู่กี่เงื่อนไข */
    function syncBadge(count) {
      var active = count != null ? count : getValues().activeCount;
      badge.textContent = active;
      badge.classList.toggle('hidden', active === 0);
      trigger.classList.toggle('is-active', active > 0);
    }

    /* ---------- ตำแหน่ง: ชิดขวาใต้ไอคอน พลิก/จำกัดความสูงอัตโนมัติเมื่อพื้นที่ไม่พอ ---------- */
    var GAP = 16;
    function position() {
      panel.classList.remove('is-align-left', 'is-above');
      panel.style.maxHeight = '';

      var rect = trigger.getBoundingClientRect();

      /* ขวาไม่พอ (ขอบซ้ายของแผงจะหลุดจอ) -> สลับไปชิดซ้ายของไอคอนแทน */
      if (rect.right - PANEL_WIDTH < 8) panel.classList.add('is-align-left');

      var spaceBelow = window.innerHeight - rect.bottom - GAP;
      var spaceAbove = rect.top - GAP;
      var needed = panel.scrollHeight;

      /* ล่างไม่พอ และบนกว้างกว่า -> เปิดขึ้นด้านบน */
      var openUpward = needed > spaceBelow && spaceAbove > spaceBelow;
      if (openUpward) panel.classList.add('is-above');

      /* ไม่ว่าจะเปิดขึ้นหรือลง ถ้าสูงเกินพื้นที่ที่เหลือ ให้จำกัดความสูงแล้วเลื่อนดูข้างในแทนการล้นจอ */
      var available = openUpward ? spaceAbove : spaceBelow;
      if (needed > available) panel.style.maxHeight = Math.max(available, 200) + 'px';
    }

    function open() {
      openPanels.forEach(function (other) { if (other !== api) other.close(); });
      mount.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');
      position();
      keywordInput.focus();
    }

    function close() {
      mount.classList.remove('is-open');
      trigger.setAttribute('aria-expanded', 'false');
    }

    function isOpen() { return mount.classList.contains('is-open'); }

    /* ---------- ค้นหา ---------- */
    var loadingTimer = null;
    function stopLoading() {
      clearTimeout(loadingTimer);
      submitBtn.classList.remove('btn-loading');
      submitBtn.disabled = false;
    }

    function submit() {
      if (submitBtn.disabled) return;                 /* กันกดซ้ำระหว่างโหลด */
      var values = getValues();
      syncBadge(values.activeCount);

      submitBtn.classList.add('btn-loading');
      submitBtn.disabled = true;
      loadingTimer = setTimeout(stopLoading, 5000);   /* กันค้างถ้าหน้าไม่เรียก done() */

      if (typeof config.onSearch === 'function') {
        config.onSearch(values, function () {
          stopLoading();
          close();
        });
      } else {
        stopLoading();
        close();
      }
    }

    function clear() {
      keywordInput.value = '';
      selects.forEach(function (select) { select.value = ''; });
      syncBadge(0);
      if (typeof config.onClear === 'function') config.onClear();
      else if (typeof config.onSearch === 'function') config.onSearch(getValues(), function () {});
    }

    /* ---------- Events ---------- */
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (isOpen()) close(); else open();
    });

    submitBtn.addEventListener('click', submit);
    clearBtn.addEventListener('click', clear);

    keywordInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submit();
      }
    });

    /* คลิกในแผงต้องไม่ปิดแผง */
    panel.addEventListener('click', function (e) { e.stopPropagation(); });

    document.addEventListener('click', function (e) {
      if (isOpen() && !mount.contains(e.target)) close();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen()) {
        close();
        trigger.focus();
      }
    });

    window.addEventListener('resize', function () { if (isOpen()) position(); });

    var api = {
      element: mount,
      open: open,
      close: close,
      toggle: function () { if (isOpen()) close(); else open(); },
      getValues: getValues,
      setValues: setValues,
      reset: clear,
      setActiveCount: syncBadge
    };

    openPanels.push(api);
    syncBadge(0);
    return api;
  };

  /* ---------------------------------------------------------------------------
     TFC.attachListToolbar — ผูก "ชิปสถานะตัวกรอง + แผงค้นหา" เข้าด้วยกันในคำสั่งเดียว
     ใช้กับทุกหน้ารายการที่ใช้โครงเดียวกับหน้าจัดการกิจกรรม จะได้ไม่ต้องเขียน logic ชิปซ้ำทุกหน้า

     TFC.attachListToolbar({
       chipId: 'program-filter-chip',          // ปุ่มชิป (ข้างในมี <span id="...-label">)
       chipLabelId: 'program-filter-chip-label',
       popoverId: 'program-search-popover',    // div ที่จะ mount แผงค้นหา
       search: { placeholder },
       filters: [...],                          // เหมือน TFC.searchPopover
       onApply: function (values) { ...กรอง+วาดตาราง...; }   // เรียกทั้งตอนค้นหาและตอนล้างค่า
     })
     คืน { panel, syncChip, getValues } — เรียก syncChip() หลังวาดตารางเพื่ออัปเดตข้อความบนชิป */
  window.TFC.attachListToolbar = function (config) {
    config = config || {};
    var chip = document.getElementById(config.chipId);
    var chipLabel = document.getElementById(config.chipLabelId);
    var current = { keyword: '', filters: {}, activeCount: 0 };

    function labelOf(values) {
      var parts = [];
      if (values.keyword) parts.push('"' + values.keyword + '"');
      Object.keys(values.filters || {}).forEach(function (key) {
        if (values.filters[key]) parts.push(values.filters[key]);
      });
      return parts.length ? 'กรองอยู่: ' + parts.join(' · ') : (config.allLabel || 'แสดงทั้งหมด');
    }

    function syncChip(values) {
      current = values || current;
      if (!chip || !chipLabel) return;
      chip.classList.toggle('is-active', current.activeCount > 0);
      chipLabel.textContent = labelOf(current);
    }

    var panel = window.TFC.searchPopover(config.popoverId, {
      search: config.search,
      filters: config.filters,
      onSearch: function (values, done) {
        syncChip(values);
        if (typeof config.onApply === 'function') config.onApply(values, done);
        else done();
      },
      onClear: function () {
        var empty = { keyword: '', filters: {}, activeCount: 0 };
        syncChip(empty);
        if (typeof config.onApply === 'function') config.onApply(empty, function () {});
      }
    });

    if (chip && panel) {
      chip.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.open();
      });
    }

    syncChip(current);
    return { panel: panel, syncChip: syncChip, getValues: function () { return current; } };
  };
})();
