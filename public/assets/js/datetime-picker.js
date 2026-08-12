/* TheFarmConcept — ช่องวันที่และเวลา
   ใช้แทน <input type="date"> และ <input type="time"> ของเบราว์เซอร์ ซึ่งหน้าตาต่างกันทุกเครื่อง
   คุมสไตล์ไม่ได้ และแสดงเป็น ค.ศ. กับรูปแบบสากลที่ไม่ตรงกับส่วนอื่นของระบบ

   สองแบบ ใช้คนละวิธีตามธรรมชาติของข้อมูล
   - วันที่ = เลือกจากปฏิทิน (ตารางเดือน เดือน/ปี พ.ศ.) เพราะพิมพ์วันที่ไทยมีหลายรูปแบบ
     และคนมักไม่รู้ว่าเดือนนั้นมีกี่วันหรือวันนั้นตรงกับวันอะไร ปฏิทินตอบได้ในตัว
   - เวลา = พิมพ์เอง เพราะเวลาที่ต้องการมักเจาะจงเป็นนาที (09:45, 13:20)
     วงล้อที่ขยับทีละ 5 นาทีทำให้กรอกค่าที่ต้องการจริงไม่ได้

   วิธีใช้ — ใส่ data-picker ให้ input แล้วจบ ไม่ต้องเรียกอะไรเพิ่ม
     <input type="text" class="input" data-picker="date" data-iso="2026-08-11">
     <input type="text" class="input" data-picker="time" data-iso="09:00">

   สัญญาที่หน้าจออื่นต้องรู้
   - ค่าจริงอยู่ที่ data-iso เสมอ (date = YYYY-MM-DD, time = HH:MM)
     ส่วน .value เป็นข้อความไทยไว้ให้คนอ่าน เช่น "11 ส.ค. 2569" · "09:30 น."
   - ค่าเปลี่ยนเมื่อไร ยิง event 'input' และ 'change' ให้ ทั้งคู่ bubble
     โค้ดเดิมที่ดักสองอีเวนต์นี้จึงทำงานต่อได้ แค่เปลี่ยนไปอ่าน data-iso แทน .value
   - ช่องเวลาที่พิมพ์ค้างไว้ยังไม่ครบ data-iso จะเป็นค่าว่าง จนกว่าจะอ่านออกเป็นเวลาจริง
*/
window.TFC = window.TFC || {};

window.TFC.datetimePicker = (function () {
  var MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  var MONTHS_FULL = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                     'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
  var WEEKDAYS = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
  var YEAR_BACK = 1;
  var YEAR_FWD = 3;

  function pad(n) { return String(n).padStart(2, '0'); }

  /* ปีในวงล้อเป็น พ.ศ. แต่ค่าที่เก็บเป็น ค.ศ. เสมอ */
  function thaiDate(iso) {
    var p = (iso || '').split('-');
    if (p.length !== 3) return '';
    return Number(p[2]) + ' ' + MONTHS[Number(p[1]) - 1] + ' ' + (Number(p[0]) + 543);
  }

  function daysInMonth(year, month) { return new Date(year, month, 0).getDate(); }

  /* ---------- ช่วงวันที่ที่เลือกได้ ----------
     ใส่ data-min-iso / data-max-iso ให้ input แล้วปฏิทินจะปิดวันที่นอกช่วงให้เอง
     เทียบด้วยข้อความได้ตรง ๆ เพราะ YYYY-MM-DD เรียงตามลำดับเวลาอยู่แล้ว */
  function limitsOf(input) {
    return {
      min: input.getAttribute('data-min-iso') || '',
      max: input.getAttribute('data-max-iso') || ''
    };
  }

  function outOfRange(iso, lim) {
    return (lim.min && iso < lim.min) || (lim.max && iso > lim.max);
  }

  /* วันที่อ้างอิงตอนยังไม่มีค่า — ใช้ของเซิร์ฟเวอร์ถ้ามี ไม่งั้นค่อยใช้นาฬิกาเครื่อง
     หน้าที่ไม่ได้โหลด service ไว้จะตกมาที่นาฬิกาเครื่องเอง จึงไม่ต้องบังคับให้ทุกหน้าโหลด */
  function today() {
    var svc = window.TFC.followUpTemplateService;
    var iso = svc && svc.serverToday ? svc.serverToday() : new Date().toISOString().slice(0, 10);
    var p = iso.split('-');
    return { y: Number(p[0]), m: Number(p[1]), d: Number(p[2]) };
  }

  function optionsHtml(items, selected) {
    return items.map(function (it) {
      return '<option value="' + it.value + '"' + (it.value === selected ? ' selected' : '') + '>' +
        it.label + '</option>';
    }).join('');
  }

  function range(from, to, fmt) {
    var out = [];
    for (var i = from; i <= to; i++) out.push({ value: i, label: fmt(i) });
    return out;
  }

  /* ---------- ตัวแผง ---------- */
  var open = null;   /* { input, root, sel } */

  function close(commit) {
    if (!open) return;
    var o = open;
    open = null;
    if (commit) writeBack(o);
    o.root.remove();
    document.removeEventListener('keydown', onKey, true);
    o.input.focus();
  }

  function writeBack(o) {
    var iso = o.sel.y + '-' + pad(o.sel.m) + '-' + pad(o.sel.d);
    o.input.setAttribute('data-iso', iso);
    o.input.value = thaiDate(iso);
    fire(o.input);
  }

  /* Esc ปิดโดยไม่บันทึก · Enter ปล่อยผ่านไปให้ปุ่มวันที่โฟกัสอยู่จัดการเอง
     ถ้าดัก Enter ตรงนี้ จะบันทึกวันเดิมแล้วปิด ทั้งที่ผู้ใช้ Tab ไปวันอื่นแล้ว */
  function onKey(e) {
    if (e.key === 'Escape') { e.stopPropagation(); close(false); }
  }

  /* ---------- ปฏิทิน ----------
     แถบบนเป็น dropdown เดือน/ปี คู่กับลูกศรเดือนก่อน–ถัดไป
     ถ้ามีแต่ลูกศร การไปเดือนที่ห่างหลายเดือนต้องกดสิบกว่าครั้ง */
  function buildNav(sel) {
    var t = today();
    /* ทั้ง value และป้ายเป็น พ.ศ. ให้ตรงกัน — ถ้า value เป็น ค.ศ. แต่ป้ายเป็น พ.ศ.
       ตัวที่ selected จะหาไม่เจอ แล้ว select เด้งไปที่ตัวเลือกแรกแทน */
    var years = range(t.y - YEAR_BACK + 543, t.y + YEAR_FWD + 543, function (y) { return y; });
    var months = range(1, 12, function (m) { return MONTHS_FULL[m - 1]; });
    return '<span class="dtp-nav">' +
      '<button type="button" class="dtp-arrow" data-dtp-step="-1" aria-label="เดือนก่อนหน้า">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>' +
      '<select class="select dtp-select" data-dtp-m aria-label="เดือน">' + optionsHtml(months, sel.m) + '</select>' +
      '<select class="select dtp-select is-year" data-dtp-y aria-label="ปี พ.ศ.">' + optionsHtml(years, sel.y + 543) + '</select>' +
      '<button type="button" class="dtp-arrow" data-dtp-step="1" aria-label="เดือนถัดไป">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>' +
      '</span>';
  }

  /* ตารางวัน — ช่องว่างต้นเดือนเป็น <span> ที่กดไม่ได้ ส่วนวันจริงเป็น <button>
     จะได้ Tab เข้าไปเลือกด้วยคีย์บอร์ดได้โดยไม่ต้องเขียน keydown เพิ่ม */
  function buildGrid(sel, lim) {
    var t = today();
    var first = new Date(sel.y, sel.m - 1, 1).getDay();
    var total = daysInMonth(sel.y, sel.m);
    lim = lim || { min: '', max: '' };

    var cells = '';
    for (var i = 0; i < first; i++) cells += '<span class="dtp-day is-blank" aria-hidden="true"></span>';
    for (var d = 1; d <= total; d++) {
      var isSel = d === sel.d;
      var isToday = sel.y === t.y && sel.m === t.m && d === t.d;
      var blocked = outOfRange(sel.y + '-' + pad(sel.m) + '-' + pad(d), lim);
      cells += '<button type="button" class="dtp-day' + (isSel ? ' is-on' : '') + (isToday ? ' is-today' : '') +
        (blocked ? ' is-disabled' : '') + '"' + (blocked ? ' disabled' : '') +
        ' data-dtp-day="' + d + '" aria-pressed="' + isSel + '"' +
        ' aria-label="' + d + ' ' + MONTHS_FULL[sel.m - 1] + ' ' + (sel.y + 543) + '">' + d + '</button>';
    }

    return '<div class="dtp-week" aria-hidden="true">' +
        WEEKDAYS.map(function (w) { return '<span>' + w + '</span>'; }).join('') +
      '</div>' +
      '<div class="dtp-grid" role="grid">' + cells + '</div>';
  }

  function buildDate(sel, lim) {
    return '<div class="dtp-cal" data-dtp-cal>' + buildGrid(sel, lim) + '</div>';
  }

  /* วาดเฉพาะตารางวันใหม่ ไม่แตะแถบ dropdown ที่ผู้ใช้เพิ่งเลือกอยู่ */
  function redrawGrid() {
    if (!open) return;
    open.root.querySelector('[data-dtp-cal]').innerHTML = buildGrid(open.sel, open.lim);
  }

  /* เปลี่ยนเดือน/ปีแล้ววันที่เลือกไว้อาจไม่มีอยู่จริง (เช่น 31 ก.พ.) ต้องหดลงมาที่วันสุดท้าย */
  function clampDay() {
    var max = daysInMonth(open.sel.y, open.sel.m);
    if (open.sel.d > max) open.sel.d = max;
  }

  function stepMonth(delta) {
    var m = open.sel.m + delta, y = open.sel.y;
    if (m < 1) { m = 12; y -= 1; }
    if (m > 12) { m = 1; y += 1; }
    open.sel.m = m; open.sel.y = y;
    clampDay();
    syncNav();
    redrawGrid();
  }

  function syncNav() {
    var root = open.root;
    root.querySelector('[data-dtp-m]').value = String(open.sel.m);
    var ySel = root.querySelector('[data-dtp-y]');
    /* ปีที่เลื่อนไปจนพ้นช่วงใน dropdown — เพิ่มตัวเลือกให้ ไม่งั้น select จะเด้งกลับค่าเดิม */
    var be = String(open.sel.y + 543);
    if (!ySel.querySelector('option[value="' + be + '"]')) {
      var opt = document.createElement('option');
      opt.value = be; opt.textContent = be;
      ySel.appendChild(opt);
      [].slice.call(ySel.options).sort(function (a, b) { return Number(a.value) - Number(b.value); })
        .forEach(function (o) { ySel.appendChild(o); });
    }
    ySel.value = be;
  }

  /* ---------- ช่องเวลาแบบพิมพ์ ----------
     รับได้หลายรูปแบบเพราะคนกรอกเวลาไม่เหมือนกัน และคีย์บอร์ดไทยพิมพ์ ":" ลำบาก
       9 · 930 · 9:5 · 09.30 · 0930 · ๐๙:๓๐ · "9:30 น."  ->  09:30
     อ่านไม่ออกคืนค่าว่าง เพื่อให้ปลายทางรู้ว่ายังไม่มีค่าจริง ไม่ใช่เดาให้ผิด ๆ */
  function parseTime(raw) {
    var s = String(raw || '')
      .replace(/[๐-๙]/g, function (d) { return String(d.charCodeAt(0) - 0x0E50); })
      .replace(/น\./g, '')
      .trim();

    var h, mi;
    var sep = s.match(/^(\d{1,2})\s*[:.：]\s*(\d{1,2})$/);
    if (sep) {
      h = Number(sep[1]); mi = Number(sep[2]);
    } else {
      var d = s.replace(/\D/g, '');
      if (!d) return '';
      if (d.length <= 2) { h = Number(d); mi = 0; }
      else if (d.length === 3) { h = Number(d.slice(0, 1)); mi = Number(d.slice(1)); }
      else if (d.length === 4) { h = Number(d.slice(0, 2)); mi = Number(d.slice(2)); }
      else return '';
    }
    if (!(h >= 0 && h <= 23) || !(mi >= 0 && mi <= 59)) return '';
    return pad(h) + ':' + pad(mi);
  }

  function isTimeInput(el) {
    return el && el.getAttribute && el.getAttribute('data-picker') === 'time';
  }

  function fire(el) {
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /* วางแผงปฏิทินให้ยึดกับช่องที่กด แทนการลอยกลางจอแบบ modal
     .dtp เป็น position:fixed พิกัดจาก getBoundingClientRect() จึงใช้ได้ตรง ๆ ไม่ต้องบวก scroll
     ถ้าที่ว่างด้านล่างไม่พอให้พลิกไปอยู่เหนือช่อง และหนีบไม่ให้ล้นขอบจอทั้งซ้ายและขวา */
  function anchorTo(sheet, input) {
    var GAP = 6;
    var EDGE = 12;
    var rect = input.getBoundingClientRect();

    sheet.classList.add('is-anchored');

    var width = sheet.offsetWidth;
    var height = sheet.offsetHeight;
    var spaceBelow = window.innerHeight - rect.bottom;
    var placeAbove = spaceBelow < height + GAP + EDGE && rect.top > spaceBelow;

    var left = Math.min(
      Math.max(EDGE, rect.left),
      Math.max(EDGE, window.innerWidth - width - EDGE)
    );

    sheet.style.left = left + 'px';
    sheet.style.top = (placeAbove ? Math.max(EDGE, rect.top - height - GAP) : rect.bottom + GAP) + 'px';
  }

  function openFor(input) {
    if (open && open.input === input) return;
    close(false);

    var iso = input.getAttribute('data-iso') || '';
    var lim = limitsOf(input);
    var p = iso.split('-');
    var t = today();
    var sel = p.length === 3
      ? { y: Number(p[0]), m: Number(p[1]), d: Number(p[2]) }
      : { y: t.y, m: t.m, d: t.d };

    /* ยังไม่มีค่า แล้ววันนี้อยู่นอกช่วงที่เลือกได้ — เปิดที่วันแรกที่เลือกได้แทน
       ไม่งั้นผู้ใช้จะเจอเดือนที่กดอะไรไม่ได้เลยแล้วต้องเดาว่าต้องเลื่อนไปทางไหน */
    if (p.length !== 3 && lim.min && outOfRange(sel.y + '-' + pad(sel.m) + '-' + pad(sel.d), lim)) {
      var mp = lim.min.split('-');
      sel = { y: Number(mp[0]), m: Number(mp[1]), d: Number(mp[2]) };
    }

    var root = document.createElement('div');
    root.className = 'dtp';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', 'เลือกวันที่');
    root.innerHTML =
      '<div class="dtp-backdrop" data-dtp-cancel></div>' +
      '<div class="dtp-sheet">' +
        '<div class="dtp-head">' +
          '<button type="button" class="dtp-btn" data-dtp-cancel>ยกเลิก</button>' +
          buildNav(sel) +
        '</div>' +
        buildDate(sel, lim) +
      '</div>';
    document.body.appendChild(root);
    anchorTo(root.querySelector('.dtp-sheet'), input);

    open = { input: input, root: root, sel: sel, lim: lim };
    document.addEventListener('keydown', onKey, true);
    /* โฟกัสวันที่เลือกอยู่ ผู้ใช้คีย์บอร์ดจะได้เริ่มจากตำแหน่งที่มีความหมาย */
    var cur = root.querySelector('.dtp-day.is-on') || root.querySelector('.dtp-day:not(.is-blank)');
    if (cur) cur.focus();
  }

  /* ---------- ผูกกับหน้าเว็บ ---------- */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-dtp-cancel]')) return close(false);

    /* กดวันในปฏิทิน = เลือกและปิดเลย ไม่ต้องกดยืนยันซ้ำ
       ปฏิทินเห็นทั้งเดือนอยู่แล้ว โอกาสกดผิดต่ำกว่าวงล้อที่ต้องเลื่อนทีละช่อง */
    var day = open && e.target.closest('[data-dtp-day]');
    if (day) {
      open.sel.d = Number(day.getAttribute('data-dtp-day'));
      return close(true);
    }

    var step = open && e.target.closest('[data-dtp-step]');
    if (step) return stepMonth(Number(step.getAttribute('data-dtp-step')));

    var input = e.target.closest('input[data-picker]');
    /* ช่องเวลาพิมพ์เอง ไม่เปิดวงล้อ — ปล่อยให้คลิกวางเคอร์เซอร์ได้ตามปกติ */
    if (input && !input.disabled && !isTimeInput(input)) { e.preventDefault(); openFor(input); }
  });

  /* เปิดด้วยคีย์บอร์ดได้ด้วย ไม่ใช่เฉพาะเมาส์ */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var input = e.target.closest && e.target.closest('input[data-picker]');
    if (input && !isTimeInput(input)) { e.preventDefault(); openFor(input); }
  });

  /* เปลี่ยนเดือน/ปีจาก dropdown */
  document.addEventListener('change', function (e) {
    if (!open) return;
    if (e.target.hasAttribute('data-dtp-m')) open.sel.m = Number(e.target.value);
    else if (e.target.hasAttribute('data-dtp-y')) open.sel.y = Number(e.target.value) - 543;
    else return;
    clampDay();
    redrawGrid();
  });

  /* พิมพ์ในช่องเวลา — อัปเดต data-iso ทุกตัวอักษร ปลายทางจะได้เห็นค่าล่าสุดทันที
     แต่ยังไม่จัดรูปแบบข้อความระหว่างพิมพ์ ไม่งั้นเคอร์เซอร์จะกระโดดกลางคัน */
  document.addEventListener('input', function (e) {
    if (!isTimeInput(e.target)) return;
    e.target.setAttribute('data-iso', parseTime(e.target.value));
  });

  /* ออกจากช่องแล้วค่อยจัดให้เป็น "09:30 น." — อ่านไม่ออกให้ล้างทิ้ง
     ปล่อยข้อความที่อ่านไม่ออกค้างไว้ อันตรายกว่า เพราะดูเหมือนกรอกแล้วทั้งที่ระบบไม่มีค่า */
  document.addEventListener('blur', function (e) {
    if (!isTimeInput(e.target)) return;
    var el = e.target;
    var iso = parseTime(el.value);
    var shown = iso ? iso + ' น.' : '';
    if (el.value === shown && el.getAttribute('data-iso') === iso) return;
    el.setAttribute('data-iso', iso);
    el.value = shown;
    fire(el);
  }, true);

  /* input ที่ถูกเรนเดอร์ใหม่ต้องได้ข้อความอ่านง่ายทันที ไม่ต้องรอผู้ใช้กด */
  function decorate(scope) {
    (scope || document).querySelectorAll('input[data-picker]:not([data-dtp-ready])').forEach(function (el) {
      var iso = el.getAttribute('data-iso') || '';
      var time = isTimeInput(el);
      /* วันที่เลือกอย่างเดียวจึงล็อกไม่ให้พิมพ์ ส่วนเวลาต้องพิมพ์ได้ */
      el.readOnly = !time;
      el.autocomplete = 'off';
      if (time) el.inputMode = 'numeric';
      el.value = !iso ? '' : (time ? iso + ' น.' : thaiDate(iso));
      /* ทำเครื่องหมายไว้ ไม่งั้น MutationObserver ด้านล่างจะไล่เขียนทับซ้ำทุกครั้งที่หน้าวาดใหม่
         ซึ่งหน้าฟอร์มวาดใหม่ทุกครั้งที่พิมพ์ */
      el.setAttribute('data-dtp-ready', '');
    });
  }

  /* หน้าไหนที่เรนเดอร์ input ใหม่หลังโหลด ให้เรียก decorate() ซ้ำได้ */
  document.addEventListener('DOMContentLoaded', function () { decorate(); });
  /* หน่วงรวมการเปลี่ยนแปลงหลาย ๆ ครั้งไว้รอบเดียว ไม่ให้วิ่งทุก mutation */
  var pending = null;
  new MutationObserver(function () {
    if (pending) return;
    pending = requestAnimationFrame(function () { pending = null; decorate(); });
  }).observe(document.documentElement, { childList: true, subtree: true });

  return { decorate: decorate, thaiDate: thaiDate, close: function () { close(false); } };
})();
