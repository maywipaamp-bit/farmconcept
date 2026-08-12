/* TheFarmConcept — Smart Dropdown: searchable select with an inline "+ เพิ่มรายการใหม่" modal.
   Usage: <select data-smart-select data-new-item-label="พื้นที่ดำเนินงาน" data-new-item-placeholder="เช่น ชุมชนตึกร้าง">...options...</select>
   Progressive enhancement — the original <select> stays in the DOM (hidden) and keeps receiving
   value updates + a "change" event, so existing form logic that reads select.value keeps working.

   หมายเหตุ: บางหน้า (เช่น login.html) ไม่ได้โหลด mock-data.js ซึ่งเป็นที่สร้าง window.TFC
   จึงต้องประกาศไว้ที่นี่ด้วย ไม่อย่างนั้นสคริปต์จะพังทั้งไฟล์ */
window.TFC = window.TFC || {};

(function () {
  var activePanel = null;
  var activeWidget = null;
  var highlightIndex = -1;

  var addModal = null;
  var addModalTarget = null; // { select, widget }

  /* ผูก observer กับ body — ถ้าไฟล์นี้ถูกโหลดใน <head> body ยังไม่มี แล้ว observe จะโยน error
     ทำให้สคริปต์ทั้งไฟล์ตายตั้งแต่บรรทัดนั้น dropdown ทั้งหน้าจะกลายเป็นของเบราว์เซอร์หมด */
  function observeBody(callback, options) {
    var start = function () { new MutationObserver(callback).observe(document.body, options); };

    if (document.body) start();
    else document.addEventListener('DOMContentLoaded', start);
  }

  function closePanel() {
    if (activePanel) {
      activePanel.classList.remove('is-open');
      /* คืนแผงกลับเข้า widget เดิม (ดูหมายเหตุ portal ที่ openPanel) */
      if (activeWidget && activePanel.parentNode === document.body) activeWidget.appendChild(activePanel);
      activePanel.style.left = activePanel.style.top = activePanel.style.width = '';
      if (activeWidget) activeWidget.querySelector('.smart-select-trigger').setAttribute('aria-expanded', 'false');
    }
    activePanel = null;
    activeWidget = null;
    highlightIndex = -1;
  }

  function visibleOptions(panel) {
    return Array.from(panel.querySelectorAll('.smart-select-option')).filter(function (opt) {
      return opt.style.display !== 'none';
    });
  }

  function setHighlight(panel, index) {
    var opts = visibleOptions(panel);
    opts.forEach(function (o) { o.classList.remove('is-highlighted'); });
    if (opts[index]) {
      opts[index].classList.add('is-highlighted');
      opts[index].scrollIntoView({ block: 'nearest' });
    }
    highlightIndex = index;
  }

  function selectOption(widget, select, optionEl) {
    var value = optionEl.getAttribute('data-value');
    select.value = value;
    widget.querySelector('.smart-select-value').textContent = optionEl.textContent;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    closePanel();
    widget.querySelector('.smart-select-trigger').focus();
  }

  function rebuildOptionsList(widget, select) {
    var panel = widget.querySelector('.smart-select-panel');
    var list = panel.querySelector('.smart-select-options');
    /* รองรับ <optgroup> — แสดงชื่อกลุ่มเป็นหัวข้อตัวหนา แล้วตัวเลือกใต้กลุ่มเป็นตัวบาง
       ทำให้เห็นลำดับชั้นว่าตัวเลือกนั้นอยู่ใต้กลุ่มใด (เช่น หลักสูตรอยู่ใต้โปรแกรมใด) */
    var lastGroup = null;
    list.innerHTML = Array.from(select.options).map(function (opt) {
      var html = '';
      var group = opt.parentElement && opt.parentElement.tagName === 'OPTGROUP'
        ? opt.parentElement.getAttribute('label') : null;
      if (group && group !== lastGroup) {
        html += '<div class="smart-select-group">' + window.TFC.escapeHtml(group) + '</div>';
      }
      lastGroup = group;
      return html + '<button type="button" class="dropdown-item smart-select-option" role="option" data-value="' +
        window.TFC.escapeHtml(opt.value) + '">' + window.TFC.escapeHtml(opt.textContent) + '</button>';
    }).join('');
  }

  /* รายการไม่เกิน 6 ตัวเลือก ซ่อนช่องค้นหา และซ่อนปุ่มเพิ่มรายการใหม่ถ้าไม่ได้ตั้งค่าไว้ */
  function syncCompact(widget, select) {
    var searchBox = widget.querySelector('.smart-select-search');
    var addBtn = widget.querySelector('.smart-select-add-btn');
    var divider = widget.querySelector('.smart-select-panel .dropdown-divider');

    /* ช่องค้นหาแสดงเสมอ ไม่ซ่อนตามจำนวนตัวเลือก
       ถ้าซ่อนบ้างไม่ซ่อนบ้าง ผู้ใช้จะไม่รู้ว่ากล่องไหนพิมพ์ค้นได้ แล้วเลิกพิมพ์ไปทั้งระบบ
       รายการที่สั้นก็ไม่เสียหาย เพราะพิมพ์แล้วเลือกยังเร็วกว่ากวาดตาหาอยู่ดี */
    searchBox.classList.remove('hidden');

    var allowAdd = select.hasAttribute('data-new-item-label');
    addBtn.classList.toggle('hidden', !allowAdd);
    if (divider) divider.classList.toggle('hidden', !allowAdd);
  }

  function trigger0(widget) { return widget.querySelector('.smart-select-trigger'); }

  function openPanel(widget, select) {
    closePanel();
    var panel = widget.querySelector('.smart-select-panel');
    rebuildOptionsList(widget, select);
    syncCompact(widget, select);
    var search = panel.querySelector('.smart-select-search-input');
    search.value = '';
    filterOptions(panel, '');
    panel.classList.add('is-open');

    /* ย้ายแผงไปแขวนไว้ที่ <body> ชั่วคราว (portal)
       เหตุผล: .modal มี transform จาก animation ตอนเปิด ซึ่งทำให้ตัวมันกลายเป็น containing block
       ของลูกที่เป็น position: fixed — แผงจึงยังติดอยู่ในกรอบ popup และยังดันพื้นที่เลื่อนเหมือนเดิม
       การย้ายออกมาที่ body ทำให้ fixed อิงกับ viewport จริง ๆ
       event listener ทั้งหมดผูกไว้กับตัว element โดยตรง จึงย้ายตามไปด้วยและยังทำงานปกติ */
    document.body.appendChild(panel);
    positionPanel(widget, panel);
    widget.querySelector('.smart-select-trigger').setAttribute('aria-expanded', 'true');
    activePanel = panel;
    activeWidget = widget;
    highlightIndex = -1;
    setTimeout(function () { search.focus(); }, 0);
  }

  /* วางแผงตัวเลือกแบบ fixed อิงพิกัดจริงของปุ่มบนหน้าจอ
     แผงจึงลอยอยู่เหนือทุกอย่าง ไม่ถูกนับเป็นพื้นที่เลื่อนของ .modal-body
     (เดิมเป็น absolute ทำให้ฟอร์มใน popup มีที่ว่างท้ายกรอบทุกครั้งที่กางเมนู) */
  function positionPanel(widget, panel) {
    var rect = trigger0(widget).getBoundingClientRect();
    var gap = 8;
    var height = panel.offsetHeight;
    var below = window.innerHeight - rect.bottom;

    panel.style.width = rect.width + 'px';
    panel.style.left = rect.left + 'px';

    /* ที่ว่างด้านล่างไม่พอ และด้านบนมีมากกว่า -> กางขึ้นด้านบนแทน */
    if (below < height + gap && rect.top > below) {
      panel.style.top = Math.max(gap, rect.top - height - gap) + 'px';
    } else {
      panel.style.top = (rect.bottom + gap) + 'px';
    }
  }

  /* ปุ่มเลื่อนไปตามการ scroll ของฟอร์ม แผงที่เป็น fixed จะไม่เลื่อนตามเอง
     จึงต้องคำนวณตำแหน่งใหม่ (true = ดักตอน capture เพื่อให้ได้ scroll ของ .modal-body ด้วย) */
  window.addEventListener('scroll', function () {
    if (activePanel && activeWidget) positionPanel(activeWidget, activePanel);
  }, true);

  window.addEventListener('resize', function () {
    if (activePanel && activeWidget) positionPanel(activeWidget, activePanel);
  });

  function filterOptions(panel, keyword) {
    var kw = keyword.trim().toLowerCase();
    var any = false;
    panel.querySelectorAll('.smart-select-option').forEach(function (opt) {
      var match = !kw || opt.textContent.toLowerCase().indexOf(kw) !== -1;
      opt.style.display = match ? '' : 'none';
      if (match) any = true;
    });
    var empty = panel.querySelector('.smart-select-empty');
    if (empty) empty.classList.toggle('hidden', any);
    highlightIndex = -1;
  }

  /* ---------- Add-new-item modal (one shared instance, reused by every Smart Dropdown on the page) ---------- */
  function ensureAddModal() {
    if (addModal) return addModal;
    var wrap = document.createElement('div');
    wrap.className = 'modal-overlay';
    wrap.id = 'smart-select-add-modal';
    wrap.innerHTML =
      '<div class="modal modal-sm">' +
      '<div class="modal-header">' +
      '<h3 class="modal-title" data-role="title">เพิ่มรายการใหม่</h3>' +
      '<button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">' +
      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
      '</button></div>' +
      '<div class="modal-body">' +
      '<div class="form-group mb-0">' +
      '<label class="form-label" for="smart-select-add-input" data-role="label">ชื่อรายการ<span class="form-required">*</span></label>' +
      '<input class="input" id="smart-select-add-input" data-role="input">' +
      '<div class="form-error-message hidden" data-role="error">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>' +
      '<span data-role="error-text"></span></div>' +
      '</div></div>' +
      '<div class="modal-footer">' +
      '<button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>' +
      '<button type="button" class="btn btn-primary" data-role="save">บันทึก</button>' +
      '</div></div>';
    document.body.appendChild(wrap);
    addModal = wrap;

    wrap.querySelector('[data-role="save"]').addEventListener('click', handleAddSave);
    wrap.querySelector('[data-role="input"]').addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); handleAddSave(); }
    });
    return addModal;
  }

  function showAddError(message) {
    var errorEl = addModal.querySelector('[data-role="error"]');
    addModal.querySelector('[data-role="error-text"]').textContent = message;
    addModal.querySelector('[data-role="input"]').classList.add('is-invalid');
    errorEl.classList.remove('hidden');
  }

  function clearAddError() {
    addModal.querySelector('[data-role="error"]').classList.add('hidden');
    addModal.querySelector('[data-role="input"]').classList.remove('is-invalid');
  }

  function handleAddSave() {
    if (!addModalTarget) return;
    var input = addModal.querySelector('[data-role="input"]');
    var value = input.value.trim();
    clearAddError();

    if (!value) {
      showAddError('กรุณากรอกชื่อรายการ');
      return;
    }

    var select = addModalTarget.select;
    var isDuplicate = Array.from(select.options).some(function (opt) {
      return opt.textContent.trim().toLowerCase() === value.toLowerCase();
    });
    if (isDuplicate) {
      showAddError('มีรายการนี้อยู่แล้ว กรุณาใช้ชื่ออื่น');
      return;
    }

    var saveBtn = addModal.querySelector('[data-role="save"]');
    if (saveBtn.classList.contains('btn-loading')) return;
    saveBtn.classList.add('btn-loading');
    saveBtn.disabled = true;

    /* Simulated AJAX save — no backend in this prototype phase */
    setTimeout(function () {
      saveBtn.classList.remove('btn-loading');
      saveBtn.disabled = false;

      var option = document.createElement('option');
      option.value = value;
      option.textContent = value;
      select.appendChild(option);
      select.value = value;
      select.dispatchEvent(new Event('change', { bubbles: true }));

      rebuildOptionsList(addModalTarget.widget, select);
      addModalTarget.widget.querySelector('.smart-select-value').textContent = value;

      if (window.TFC && window.TFC.closeModal) window.TFC.closeModal('smart-select-add-modal');
      if (window.TFC && window.TFC.showToast) {
        window.TFC.showToast('เพิ่ม "' + value + '" สำเร็จ และเลือกให้อัตโนมัติแล้ว', 'success');
      }
      input.value = '';
    }, 700);
  }

  function openAddModal(widget, select) {
    closePanel();
    var modal = ensureAddModal();
    var label = select.getAttribute('data-new-item-label') || 'รายการ';
    modal.querySelector('[data-role="title"]').textContent = 'เพิ่ม' + label + 'ใหม่';
    modal.querySelector('[data-role="label"]').innerHTML = 'ชื่อ' + window.TFC.escapeHtml(label) + '<span class="form-required">*</span>';
    var input = modal.querySelector('[data-role="input"]');
    input.value = '';
    input.placeholder = select.getAttribute('data-new-item-placeholder') || '';
    clearAddError();
    addModalTarget = { select: select, widget: widget };
    if (window.TFC && window.TFC.openModal) window.TFC.openModal('smart-select-add-modal');
    setTimeout(function () { input.focus(); }, 50);
  }

  /* ---------- Build one widget per [data-smart-select] element ---------- */
  function syncValueLabel(widget, select) {
    var opt = select.options[select.selectedIndex];
    var label = widget.querySelector('.smart-select-value');
    if (label) label.textContent = opt ? opt.textContent : 'เลือกรายการ';
  }

  /* หน้าจอที่เติม options / set value ให้ select เองภายหลัง (เช่น ฟอร์มใน popup) เรียกใช้เพื่อให้ป้ายตรงกับค่าจริง */
  window.TFC.refreshSmartSelects = function (root) {
    (root || document).querySelectorAll('select[data-smart-select]').forEach(function (select) {
      var widget = select.nextElementSibling;
      if (widget && widget.classList.contains('smart-select')) syncValueLabel(widget, select);
    });
  };

  function buildWidget(select) {
    select.classList.add('hidden');

    var widget = document.createElement('div');
    widget.className = 'smart-select';

    var selectedOption = select.options[select.selectedIndex];
    var initialText = selectedOption ? selectedOption.textContent : 'เลือกรายการ';

    widget.innerHTML =
      '<button type="button" class="input smart-select-trigger" aria-haspopup="listbox" aria-expanded="false">' +
      '<span class="smart-select-value"></span>' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>' +
      '</button>' +
      '<div class="dropdown-menu smart-select-panel" role="listbox">' +
      '<div class="smart-select-search"><input type="text" class="input smart-select-search-input" placeholder="ค้นหารายการ..." aria-label="ค้นหารายการ"></div>' +
      '<div class="smart-select-options"></div>' +
      '<div class="smart-select-empty notification-empty hidden">ไม่พบรายการที่ค้นหา</div>' +
      '<div class="dropdown-divider"></div>' +
      '<button type="button" class="dropdown-item smart-select-add-btn">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>' +
      '<span>เพิ่มรายการใหม่</span></button>' +
      '</div>';

    widget.querySelector('.smart-select-value').textContent = initialText;
    select.parentNode.insertBefore(widget, select.nextSibling);
    rebuildOptionsList(widget, select);

    var trigger = widget.querySelector('.smart-select-trigger');
    var panel = widget.querySelector('.smart-select-panel');
    var search = widget.querySelector('.smart-select-search-input');

    select.addEventListener('change', function () { syncValueLabel(widget, select); });

    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (activePanel === panel) closePanel();
      else openPanel(widget, select);
    });

    search.addEventListener('input', function () {
      filterOptions(panel, search.value);
    });

    search.addEventListener('keydown', function (e) {
      var opts = visibleOptions(panel);
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setHighlight(panel, Math.min(highlightIndex + 1, opts.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setHighlight(panel, Math.max(highlightIndex - 1, 0));
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (opts[highlightIndex]) selectOption(widget, select, opts[highlightIndex]);
      } else if (e.key === 'Escape') {
        closePanel();
        trigger.focus();
      }
    });

    panel.querySelector('.smart-select-options').addEventListener('click', function (e) {
      var opt = e.target.closest('.smart-select-option');
      if (opt) selectOption(widget, select, opt);
    });

    widget.querySelector('.smart-select-add-btn').addEventListener('click', function () {
      openAddModal(widget, select);
    });
  }

  /* ---------------------------------------------------------------------------
     มาตรฐานของระบบ: ทุก <select class="select"> เป็น combobox ที่พิมพ์ค้นหาได้
     ไม่ต้องใส่ data-smart-select รายจุดอีก — ของเดิมที่ใส่ไว้ยังใช้ได้ ไม่ต้องไล่ลบ

     ที่ยกเว้นไว้เป็น select ที่ไม่ใช่ "ช่องกรอกในฟอร์ม" แต่เป็นตัวควบคุมเล็ก ๆ ที่ฝังอยู่กับอย่างอื่น
     ถ้าแปลงจะเสียพฤติกรรมเดิมหรือกินที่เกินกว่าที่ควร
       [data-plain-select]  ทางออกสำหรับจุดที่ต้องการ select ของเบราว์เซอร์จริง ๆ
       [data-page-size]     ตัวเลือกจำนวนแถวในแถบแบ่งหน้า สูงแค่ 22px
       .status-select       ปุ่มเปลี่ยนสถานะในแถวตาราง มีสีและ handler ของตัวเอง
       .dtp-select          เดือน/ปี ในปฏิทิน อยู่ในแผงที่เปิดอยู่แล้ว ซ้อนแผงอีกชั้นไม่ได้
     --------------------------------------------------------------------------- */
  var SKIP = '[data-plain-select], [data-page-size], .status-select, .dtp-select';

  function shouldEnhance(select) {
    return select.classList.contains('select') &&
      !select.classList.contains('hidden') &&   /* แปลงไปแล้ว */
      !select.matches(SKIP) &&
      !select.closest('.dtp');
  }

  /* แถวที่สร้างทีหลัง (เช่น แถวหลักสูตรในฟอร์มวิทยากร) เรียกใช้เพื่อแปลง select ที่เพิ่งเพิ่มเข้ามา
     ข้าม select ที่แปลงไปแล้วเพื่อไม่ให้สร้าง widget ซ้ำ */
  window.TFC.initSmartSelects = function (root) {
    var scope = root || document;
    var list = Array.prototype.slice.call(scope.querySelectorAll('select'));

    /* querySelectorAll ไม่รวมตัว root เอง — หน้าที่ส่ง select มาตรง ๆ จึงต้องเติมเข้าไป */
    if (scope.tagName === 'SELECT') list.push(scope);

    list.filter(shouldEnhance).forEach(function (select) {
      try {
        buildWidget(select);
      } catch (err) {
        console.error('smart-select: failed to build widget for #' + select.id, err);
      }
    });
  };

  window.TFC.initSmartSelects(document);

  /* select ที่สคริปต์หน้าอื่นสร้างขึ้นภายหลังต้องได้ combobox เหมือนกันโดยไม่ต้องเรียก init เอง
     ไม่งั้นมาตรฐานจะขึ้นกับว่าคนเขียนหน้านั้นจำได้หรือเปล่า ซึ่งสุดท้ายจะไม่เหมือนกันทั้งระบบ

     อีกกรณีที่ต้องดัก: หน้าจอมัก render <select> เปล่าไว้ก่อนแล้วค่อยเติม <option> ทีหลัง
     ถ้าไม่ซิงก์ป้ายตอนนั้น ปุ่มจะค้างคำว่า "เลือกรายการ" ทั้งที่ค่าข้างในถูกตั้งไว้แล้ว */
  observeBody(function (records) {
    records.forEach(function (record) {
      if (record.target.tagName === 'SELECT') {
        var sibling = record.target.nextElementSibling;
        if (sibling && sibling.classList.contains('smart-select')) syncValueLabel(sibling, record.target);
        return;
      }

      Array.prototype.forEach.call(record.addedNodes, function (node) {
        if (node.nodeType !== 1) return;
        if (node.tagName === 'SELECT' || node.querySelector('select')) window.TFC.initSmartSelects(node);
      });
    });
  }, { subtree: true, childList: true });

  document.addEventListener('click', function (e) {
    /* ต้องเช็ค activePanel ด้วย เพราะตอนกางออกแผงถูกย้ายไปอยู่ที่ <body>
       activeWidget จึงไม่ได้ครอบมันแล้ว ถ้าเช็คแค่ widget การคลิกในแผงจะถูกนับเป็นคลิกข้างนอก */
    if (activePanel && !activeWidget.contains(e.target) && !activePanel.contains(e.target)) closePanel();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && activePanel) closePanel();
  });

  /* ทุกครั้งที่ popup เปิด ให้ซิงก์ป้ายของ Smart Dropdown ข้างในอีกครั้ง
     เพราะหน้าจอมักเติม options แล้ว set value หลังจากสร้าง widget ไปแล้ว (และ set value ตรง ๆ ไม่ยิง change) */
  observeBody(function (records) {
    records.forEach(function (record) {
      var el = record.target;
      if (!el.classList || !el.classList.contains('is-open')) return;
      if (!el.classList.contains('modal-overlay') && !el.classList.contains('drawer-overlay')) return;
      setTimeout(function () { window.TFC.refreshSmartSelects(el); }, 0);
    });
  }, { subtree: true, attributes: true, attributeFilter: ['class'] });
})();
