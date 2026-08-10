/* TheFarmConcept — ตัวเลือกวันที่/เวลาแบบวงล้อ (แนวเดียวกับ LINE)
   ใช้แทน <input type="date"> และ <input type="time"> ของเบราว์เซอร์ ซึ่งหน้าตาต่างกันทุกเครื่อง
   คุมสไตล์ไม่ได้ และบนเดสก์ท็อปเป็นปฏิทิน/สปินเนอร์ที่ไม่เข้ากับส่วนอื่นของระบบ

   วิธีใช้ — ใส่ data-picker ให้ input แล้วจบ ไม่ต้องเรียกอะไรเพิ่ม
     <input type="text" class="input" data-picker="date" data-iso="2026-08-11">
     <input type="text" class="input" data-picker="time" data-iso="09:00">

   สัญญาที่หน้าจออื่นต้องรู้
   - ค่าจริงอยู่ที่ data-iso เสมอ (date = YYYY-MM-DD, time = HH:MM)
     ส่วน .value เป็นข้อความไทยไว้ให้คนอ่าน เช่น "11 ส.ค. 2569"
   - เลือกเสร็จแล้วยิง event 'input' และ 'change' ให้ ทั้งคู่ bubble
     โค้ดเดิมที่ดักสองอีเวนต์นี้จึงทำงานต่อได้ แค่เปลี่ยนไปอ่าน data-iso แทน .value
*/
window.TFC = window.TFC || {};

window.TFC.datetimePicker = (function () {
  var MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  var MONTHS_FULL = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                     'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
  var MINUTE_STEP = 5;
  var ROW_H = 36;          /* ต้องตรงกับ --dtp-row ใน components.css */
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

  /* วันที่อ้างอิงตอนยังไม่มีค่า — ใช้ของเซิร์ฟเวอร์ถ้ามี ไม่งั้นค่อยใช้นาฬิกาเครื่อง */
  function today() {
    var svc = window.TFC.followUpService;
    var iso = svc && svc.serverToday ? svc.serverToday() : new Date().toISOString().slice(0, 10);
    var p = iso.split('-');
    return { y: Number(p[0]), m: Number(p[1]), d: Number(p[2]) };
  }

  /* ---------- วงล้อหนึ่งคอลัมน์ ---------- */
  function wheel(name, items, selectedValue) {
    var rows = items.map(function (it) {
      return '<div class="dtp-item" data-value="' + it.value + '">' + it.label + '</div>';
    }).join('');
    return '<div class="dtp-wheel" data-wheel="' + name + '" data-selected="' + selectedValue + '">' +
      '<div class="dtp-wheel-pad"></div>' + rows + '<div class="dtp-wheel-pad"></div>' +
      '</div>';
  }

  function range(from, to, fmt, pick) {
    var out = [];
    for (var i = from; i <= to; i += (pick || 1)) out.push({ value: i, label: fmt(i) });
    return out;
  }

  /* ---------- ตัวแผง ---------- */
  var open = null;   /* { input, root, mode, sel } */

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
    var iso = o.mode === 'date'
      ? o.sel.y + '-' + pad(o.sel.m) + '-' + pad(o.sel.d)
      : pad(o.sel.h) + ':' + pad(o.sel.mi);
    o.input.setAttribute('data-iso', iso);
    o.input.value = o.mode === 'date' ? thaiDate(iso) : iso + ' น.';
    o.input.dispatchEvent(new Event('input', { bubbles: true }));
    o.input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function onKey(e) {
    if (e.key === 'Escape') { e.stopPropagation(); close(false); }
    else if (e.key === 'Enter') { e.preventDefault(); close(true); }
  }

  function buildDate(sel) {
    var t = today();
    var years = range(t.y - YEAR_BACK, t.y + YEAR_FWD, function (y) { return y + 543; });
    var months = range(1, 12, function (m) { return MONTHS_FULL[m - 1]; });
    var days = range(1, daysInMonth(sel.y, sel.m), function (d) { return d; });
    return '<div class="dtp-wheels">' +
      wheel('d', days, sel.d) + wheel('m', months, sel.m) + wheel('y', years, sel.y) +
      '<div class="dtp-marker" aria-hidden="true"></div></div>';
  }

  function buildTime(sel) {
    var hours = range(0, 23, pad);
    var mins = range(0, 59, pad, MINUTE_STEP);
    return '<div class="dtp-wheels">' +
      wheel('h', hours, sel.h) + wheel('mi', mins, sel.mi) +
      '<div class="dtp-marker" aria-hidden="true"></div></div>';
  }

  function openFor(input) {
    if (open && open.input === input) return;
    close(false);

    var mode = input.getAttribute('data-picker');
    var iso = input.getAttribute('data-iso') || '';
    var sel;

    if (mode === 'date') {
      var p = iso.split('-');
      var t = today();
      sel = p.length === 3
        ? { y: Number(p[0]), m: Number(p[1]), d: Number(p[2]) }
        : { y: t.y, m: t.m, d: t.d };
    } else {
      var q = iso.split(':');
      sel = q.length === 2
        ? { h: Number(q[0]), mi: Math.round(Number(q[1]) / MINUTE_STEP) * MINUTE_STEP }
        : { h: 9, mi: 0 };
      if (sel.mi > 59) sel.mi = 0;
    }

    var root = document.createElement('div');
    root.className = 'dtp';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', mode === 'date' ? 'เลือกวันที่' : 'เลือกเวลา');
    root.innerHTML =
      '<div class="dtp-backdrop" data-dtp-cancel></div>' +
      '<div class="dtp-sheet">' +
        '<div class="dtp-head">' +
          '<button type="button" class="dtp-btn" data-dtp-cancel>ยกเลิก</button>' +
          '<span class="dtp-title" data-dtp-title></span>' +
          '<button type="button" class="dtp-btn is-primary" data-dtp-ok>ตกลง</button>' +
        '</div>' +
        (mode === 'date' ? buildDate(sel) : buildTime(sel)) +
      '</div>';
    document.body.appendChild(root);

    open = { input: input, root: root, mode: mode, sel: sel };
    root.querySelectorAll('.dtp-wheel').forEach(initWheel);
    syncTitle();
    document.addEventListener('keydown', onKey, true);
    root.querySelector('[data-dtp-ok]').focus();
  }

  function syncTitle() {
    if (!open) return;
    var s = open.sel;
    open.root.querySelector('[data-dtp-title]').textContent = open.mode === 'date'
      ? thaiDate(s.y + '-' + pad(s.m) + '-' + pad(s.d))
      : pad(s.h) + ':' + pad(s.mi) + ' น.';
  }

  /* เลื่อนวงล้อไปที่ค่าที่เลือก แล้วผูก scroll ให้จับค่าที่หยุดตรงกลาง */
  function initWheel(el) {
    var items = [].slice.call(el.querySelectorAll('.dtp-item'));
    var idx = items.findIndex(function (n) { return Number(n.getAttribute('data-value')) === Number(el.getAttribute('data-selected')); });
    if (idx < 0) idx = 0;

    el.scrollTop = idx * ROW_H;
    mark(el, items, idx);

    var timer = null;
    el.addEventListener('scroll', function () {
      clearTimeout(timer);
      /* รอให้หยุดนิ่งก่อนค่อยสรุปค่า ไม่งั้นจะยิงอัปเดตรัวระหว่างสไลด์ */
      timer = setTimeout(function () {
        var i = Math.round(el.scrollTop / ROW_H);
        i = Math.max(0, Math.min(items.length - 1, i));
        mark(el, items, i);
        commitWheel(el, Number(items[i].getAttribute('data-value')));
      }, 90);
    });

    /* กดที่แถวไหนก็เลื่อนไปแถวนั้น เร็วกว่าสไลด์ทีละขั้น */
    el.addEventListener('click', function (e) {
      var row = e.target.closest('.dtp-item');
      if (!row) return;
      el.scrollTo({ top: items.indexOf(row) * ROW_H, behavior: 'smooth' });
    });
  }

  function mark(el, items, idx) {
    items.forEach(function (n, i) { n.classList.toggle('is-on', i === idx); });
  }

  function commitWheel(el, value) {
    if (!open) return;
    var name = el.getAttribute('data-wheel');
    open.sel[name] = value;

    /* เปลี่ยนเดือน/ปีแล้วจำนวนวันอาจไม่พอ (เช่น 31 ก.พ.) ต้องสร้างวงล้อวันใหม่ */
    if (open.mode === 'date' && (name === 'm' || name === 'y')) rebuildDays();
    syncTitle();
  }

  function rebuildDays() {
    var wrap = open.root.querySelector('.dtp-wheels');
    var dayEl = wrap.querySelector('[data-wheel="d"]');
    var max = daysInMonth(open.sel.y, open.sel.m);
    if (open.sel.d > max) open.sel.d = max;
    if (dayEl.querySelectorAll('.dtp-item').length === max) return;

    var fresh = document.createElement('div');
    fresh.innerHTML = wheel('d', range(1, max, function (d) { return d; }), open.sel.d);
    var next = fresh.firstChild;
    dayEl.replaceWith(next);
    initWheel(next);
  }

  /* ---------- ผูกกับหน้าเว็บ ---------- */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-dtp-cancel]')) return close(false);
    if (e.target.closest('[data-dtp-ok]')) return close(true);
    var input = e.target.closest('input[data-picker]');
    if (input && !input.disabled) { e.preventDefault(); openFor(input); }
  });

  /* เปิดด้วยคีย์บอร์ดได้ด้วย ไม่ใช่เฉพาะเมาส์ */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var input = e.target.closest && e.target.closest('input[data-picker]');
    if (input) { e.preventDefault(); openFor(input); }
  });

  /* input ที่ถูกเรนเดอร์ใหม่ต้องได้ข้อความอ่านง่ายทันที ไม่ต้องรอผู้ใช้กด */
  function decorate(scope) {
    (scope || document).querySelectorAll('input[data-picker]:not([data-dtp-ready])').forEach(function (el) {
      var iso = el.getAttribute('data-iso') || '';
      el.readOnly = true;
      el.autocomplete = 'off';
      el.value = !iso ? '' : (el.getAttribute('data-picker') === 'date' ? thaiDate(iso) : iso + ' น.');
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
