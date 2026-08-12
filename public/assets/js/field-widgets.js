/* TheFarmConcept — Field Widgets: ช่องกรอกเฉพาะทางที่ใช้ซ้ำได้ทุกฟอร์ม
   จับคู่กับ CSS .thai-date / .tags-input ใน components.css
   โหลดหลัง mock-data.js (ต้องใช้ TFC.escapeHtml)

   1) วันที่แบบไทย — <input data-thai-date>
      แสดงและกรอกเป็น วว/ดด/ปปปป (พ.ศ.) แต่เก็บค่าจริงเป็น ISO (YYYY-MM-DD ค.ศ.) ไว้ใน
      input ที่ซ่อนอยู่ ชื่อ/ไอดีเดิม เพื่อให้โค้ดที่อ่าน element.value เดิมทำงานต่อได้ทันที
        TFC.setDateValue(inputId, '2025-01-15')  -> เติมค่าเข้าไป (ใช้ตอนเปิดฟอร์มแก้ไข)
        document.getElementById(inputId).value   -> ยังได้ ISO เหมือนเดิม

   2) Tags Input — <input data-tags-input>
      พิมพ์แล้วกด Enter หรือ , เพื่อเพิ่มเป็นแท็ก, กด × หรือ Backspace เพื่อลบ
      ค่าที่เก็บคือข้อความคั่นด้วย ", " ใน input เดิม (ที่ถูกซ่อนไว้)
        TFC.setTagsValue(inputId, 'สสส. พลเมืองอาสา, สำนักงานเขต') */
window.TFC = window.TFC || {};

(function () {
  var TH_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  var TH_MONTHS_FULL = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

  /* ---------- 1) วันที่แบบไทย ---------- */
  function isoToThai(iso) {
    if (!iso) return '';
    var parts = String(iso).split('-');
    if (parts.length !== 3) return '';
    return parts[2] + '/' + parts[1] + '/' + (Number(parts[0]) + 543);
  }

  function thaiToIso(text) {
    var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec((text || '').trim());
    if (!m) return '';
    var day = Number(m[1]), month = Number(m[2]), year = Number(m[3]) - 543;
    if (month < 1 || month > 12 || day < 1 || day > 31 || year < 1900) return '';
    return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
  }

  function buildThaiDate(input) {
    input.classList.add('hidden');

    var wrap = document.createElement('div');
    wrap.className = 'thai-date';
    wrap.innerHTML =
      '<input type="text" class="input thai-date-text" inputmode="numeric" maxlength="10"' +
      ' placeholder="วว/ดด/ปปปป" aria-label="วันที่ (พ.ศ.)">' +
      '<button type="button" class="thai-date-toggle" aria-label="เลือกจากปฏิทิน">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
      '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></button>' +
      '<div class="thai-date-panel" role="dialog" aria-label="ปฏิทิน">' +
      '<div class="thai-date-head">' +
      '<button type="button" class="thai-date-nav" data-nav="-1" aria-label="เดือนก่อนหน้า">‹</button>' +
      '<span class="thai-date-title"></span>' +
      '<button type="button" class="thai-date-nav" data-nav="1" aria-label="เดือนถัดไป">›</button>' +
      '</div>' +
      '<div class="thai-date-grid"></div>' +
      '<div class="thai-date-foot">' +
      '<button type="button" data-clear>ล้างวันที่</button>' +
      '<button type="button" data-today>วันนี้</button>' +
      '</div></div>';

    input.parentNode.insertBefore(wrap, input.nextSibling);
    var text = wrap.querySelector('.thai-date-text');
    var hint = wrap.querySelector('.thai-date-hint');

    function syncFromIso() {
      text.value = isoToThai(input.value);
      showHint();
    }

    /* บอกวันที่แบบเต็มไว้ข้าง ๆ กันกรอกสลับวัน/เดือน */
    function showHint() {
      if (!hint) return;          /* เวอร์ชันที่มีปฏิทินแล้ว ไม่ต้องมีข้อความช่วยด้านขวา */
      var iso = input.value;
      if (!iso) { hint.textContent = ''; return; }
      var d = new Date(iso);
      hint.textContent = d.getDate() + ' ' + TH_MONTHS[d.getMonth()] + ' ' + (d.getFullYear() + 543);
    }

    text.addEventListener('input', function () {
      /* ใส่ / ให้อัตโนมัติระหว่างพิมพ์ */
      var digits = text.value.replace(/\D/g, '').slice(0, 8);
      var out = digits.slice(0, 2);
      if (digits.length > 2) out += '/' + digits.slice(2, 4);
      if (digits.length > 4) out += '/' + digits.slice(4, 8);
      text.value = out;

      var iso = thaiToIso(out);
      input.value = iso;
      text.classList.toggle('is-invalid', out.length === 10 && !iso);
      showHint();
      if (iso) input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    /* ---------- ปฏิทินให้เลือก (แสดงปี พ.ศ.) ---------- */
    var panel = wrap.querySelector('.thai-date-panel');
    var view = { year: null, month: null };   /* ค.ศ. ภายใน, แสดงผลเป็น พ.ศ. */

    function openCalendar() {
      closeAllCalendars();
      var base = input.value ? new Date(input.value) : new Date();
      view.year = base.getFullYear();
      view.month = base.getMonth();
      renderCalendar();
      panel.classList.add('is-open');

      /* ที่ว่างด้านล่างไม่พอ -> กางขึ้นด้านบน จะได้ไม่ดันกรอบให้เลื่อนยาว */
      panel.classList.remove('is-above');
      var rect = text.getBoundingClientRect();
      if (window.innerHeight - rect.bottom < panel.offsetHeight + 16 &&
        rect.top > window.innerHeight - rect.bottom) {
        panel.classList.add('is-above');
      }
    }

    function renderCalendar() {
      var first = new Date(view.year, view.month, 1);
      var daysInMonth = new Date(view.year, view.month + 1, 0).getDate();
      var offset = first.getDay();
      var today = new Date();
      var selected = input.value ? new Date(input.value) : null;

      var cells = '';
      for (var i = 0; i < offset; i++) cells += '<button type="button" class="thai-date-day is-empty" tabindex="-1"></button>';
      for (var day = 1; day <= daysInMonth; day++) {
        var isToday = today.getFullYear() === view.year && today.getMonth() === view.month && today.getDate() === day;
        var isSelected = selected && selected.getFullYear() === view.year &&
          selected.getMonth() === view.month && selected.getDate() === day;
        cells += '<button type="button" class="thai-date-day' + (isToday ? ' is-today' : '') +
          (isSelected ? ' is-selected' : '') + '" data-day="' + day + '">' + day + '</button>';
      }

      panel.querySelector('.thai-date-title').textContent = TH_MONTHS_FULL[view.month] + ' ' + (view.year + 543);
      panel.querySelector('.thai-date-grid').innerHTML =
        ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'].map(function (d) {
          return '<div class="thai-date-dow">' + d + '</div>';
        }).join('') + cells;
    }

    function pick(day) {
      var iso = view.year + '-' + String(view.month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
      input.value = iso;
      syncFromIso();
      panel.classList.remove('is-open');
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    wrap.querySelector('.thai-date-toggle').addEventListener('click', function (e) {
      e.stopPropagation();
      if (panel.classList.contains('is-open')) panel.classList.remove('is-open');
      else openCalendar();
    });

    text.addEventListener('focus', openCalendar);

    panel.addEventListener('click', function (e) {
      e.stopPropagation();
      var nav = e.target.closest('[data-nav]');
      if (nav) {
        view.month += Number(nav.getAttribute('data-nav'));
        if (view.month < 0) { view.month = 11; view.year--; }
        if (view.month > 11) { view.month = 0; view.year++; }
        renderCalendar();
        return;
      }
      if (e.target.closest('[data-today]')) {
        var now = new Date();
        view.year = now.getFullYear();
        view.month = now.getMonth();
        pick(now.getDate());
        return;
      }
      if (e.target.closest('[data-clear]')) {
        input.value = '';
        syncFromIso();
        panel.classList.remove('is-open');
        return;
      }
      var dayBtn = e.target.closest('[data-day]');
      if (dayBtn) pick(Number(dayBtn.getAttribute('data-day')));
    });

    wrap.closeCalendar = function () { panel.classList.remove('is-open'); };
    wrap.syncFromIso = syncFromIso;
    syncFromIso();
  }

  function closeAllCalendars() {
    document.querySelectorAll('.thai-date-panel.is-open').forEach(function (p) { p.classList.remove('is-open'); });
  }

  document.addEventListener('click', closeAllCalendars);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAllCalendars(); });

  window.TFC.setDateValue = function (inputId, iso) {
    var input = typeof inputId === 'string' ? document.getElementById(inputId) : inputId;
    if (!input) return;
    input.value = iso || '';
    var wrap = input.nextElementSibling;
    if (wrap && wrap.classList.contains('thai-date')) wrap.syncFromIso();
  };

  /* ---------- 2) Tags Input ---------- */
  var suggestSeq = 0;

  /* data-tags-options เป็น JSON array ของข้อความ — ใช้ JSON ไม่ใช่ข้อความคั่นจุลภาค
     เพราะชื่อหน่วยงานมีจุลภาคอยู่ในตัวได้ */
  function attachSuggestions(input, field) {
    var raw = input.getAttribute('data-tags-options');
    if (!raw) return;

    var list;
    try { list = JSON.parse(raw); } catch (e) { return; }
    if (!Array.isArray(list) || !list.length) return;

    var dl = document.createElement('datalist');
    dl.id = 'tags-suggest-' + (++suggestSeq);
    list.forEach(function (v) {
      var opt = document.createElement('option');
      opt.value = v;
      dl.appendChild(opt);
    });

    field.parentNode.appendChild(dl);
    field.setAttribute('list', dl.id);
  }

  function buildTagsInput(input) {
    input.classList.add('hidden');

    var wrap = document.createElement('div');
    wrap.className = 'multiselect tags-input';
    wrap.innerHTML = '<input type="text" class="tags-input-field"' +
      ' placeholder="' + window.TFC.escapeHtml(input.getAttribute('data-tags-placeholder') || 'พิมพ์แล้วกด Enter') + '"' +
      ' aria-label="เพิ่มรายการ">';

    input.parentNode.insertBefore(wrap, input.nextSibling);
    var field = wrap.querySelector('.tags-input-field');

    /* รายการที่มีอยู่แล้วในระบบ — ให้เลือกซ้ำได้แทนที่จะพิมพ์เอง
       ถ้าพิมพ์เองทุกครั้งจะได้ชื่อที่ต่างกันนิดเดียวแต่กลายเป็นคนละรายการ */
    attachSuggestions(input, field);

    function tags() {
      return input.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
    }

    function render() {
      wrap.querySelectorAll('.multiselect-tag').forEach(function (el) { el.remove(); });
      tags().forEach(function (tag) {
        var chip = document.createElement('span');
        chip.className = 'multiselect-tag';
        chip.innerHTML = '<span></span><button type="button" aria-label="ลบ ' + window.TFC.escapeHtml(tag) + '">' +
          '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">' +
          '<path d="M18 6L6 18M6 6l12 12"/></svg></button>';
        chip.querySelector('span').textContent = tag;
        chip.querySelector('button').addEventListener('click', function () { remove(tag); });
        wrap.insertBefore(chip, field);
      });
    }

    function setTags(list) {
      input.value = list.join(', ');
      render();
    }

    function add(value) {
      var tag = (value || '').trim();
      if (!tag) return;
      var list = tags();
      if (list.indexOf(tag) === -1) list.push(tag);
      setTags(list);
      field.value = '';
    }

    function remove(tag) {
      setTags(tags().filter(function (t) { return t !== tag; }));
    }

    field.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        add(field.value);
      } else if (e.key === 'Backspace' && !field.value) {
        var list = tags();
        if (list.length) remove(list[list.length - 1]);
      }
    });

    field.addEventListener('blur', function () { add(field.value); });
    wrap.addEventListener('click', function (e) { if (e.target === wrap) field.focus(); });

    wrap.setTags = setTags;
    render();
  }

  window.TFC.setTagsValue = function (inputId, value) {
    var input = typeof inputId === 'string' ? document.getElementById(inputId) : inputId;
    if (!input) return;
    var wrap = input.nextElementSibling;
    var list = (value || '').split(',').map(function (t) { return t.trim(); }).filter(Boolean);
    if (wrap && wrap.classList.contains('tags-input')) wrap.setTags(list);
    else input.value = list.join(', ');
  };

  /* ---------- init ---------- */
  /* เรียกซ้ำได้ เมื่อหน้าจอสร้างฟิลด์ใหม่ภายหลัง (เช่น ฟอร์มที่ render ด้วย JS) */
  window.TFC.initFieldWidgets = function (root) {
    (root || document).querySelectorAll('input[data-thai-date]:not(.hidden)').forEach(buildThaiDate);
    (root || document).querySelectorAll('input[data-tags-input]:not(.hidden)').forEach(buildTagsInput);
  };

  window.TFC.initFieldWidgets(document);
})();
