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
      '<span class="thai-date-hint"></span>';

    input.parentNode.insertBefore(wrap, input.nextSibling);
    var text = wrap.querySelector('.thai-date-text');
    var hint = wrap.querySelector('.thai-date-hint');

    function syncFromIso() {
      text.value = isoToThai(input.value);
      showHint();
    }

    /* บอกวันที่แบบเต็มไว้ข้าง ๆ กันกรอกสลับวัน/เดือน */
    function showHint() {
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

    wrap.syncFromIso = syncFromIso;
    syncFromIso();
  }

  window.TFC.setDateValue = function (inputId, iso) {
    var input = typeof inputId === 'string' ? document.getElementById(inputId) : inputId;
    if (!input) return;
    input.value = iso || '';
    var wrap = input.nextElementSibling;
    if (wrap && wrap.classList.contains('thai-date')) wrap.syncFromIso();
  };

  /* ---------- 2) Tags Input ---------- */
  function buildTagsInput(input) {
    input.classList.add('hidden');

    var wrap = document.createElement('div');
    wrap.className = 'multiselect tags-input';
    wrap.innerHTML = '<input type="text" class="tags-input-field"' +
      ' placeholder="' + window.TFC.escapeHtml(input.getAttribute('data-tags-placeholder') || 'พิมพ์แล้วกด Enter') + '"' +
      ' aria-label="เพิ่มรายการ">';

    input.parentNode.insertBefore(wrap, input.nextSibling);
    var field = wrap.querySelector('.tags-input-field');

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
  document.querySelectorAll('input[data-thai-date]').forEach(buildThaiDate);
  document.querySelectorAll('input[data-tags-input]').forEach(buildTagsInput);
})();
