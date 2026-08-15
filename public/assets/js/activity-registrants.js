/* TheFarmConcept — หน้าผู้ลงทะเบียน (admin/activities/registrants)

   ทุกอย่างในหน้านี้แยกตามกิจกรรม — รายชื่อ ตัวเลข ยอดเงิน และที่นั่งที่รับได้
   ข้อมูลมาจากฐานข้อมูลผ่าน window.TFC_REGISTRANTS ที่ Blade ฉีดไว้ ไม่มีตัวเลขที่พิมพ์ทับไว้
   เปลี่ยนกิจกรรมคือโหลดหน้าใหม่พร้อม ?id= เพื่อให้ตัวเลขทุกใบมาจากคำถามเดียวกันที่เซิร์ฟเวอร์

   การยืนยัน/ปฏิเสธสลิป และการเช็คอิน/ยกเลิก เขียนลงฐานจริงที่ RegistrantController
   ซึ่งบันทึก audit log (ใคร ทำอะไร กับใคร เมื่อไหร่) ให้ในคำขอเดียวกัน */
(function () {
  var data = window.TFC_REGISTRANTS;
  if (!data) return;

  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var TABS = ['ทั้งหมด', 'ชำระแล้ว', 'รอตรวจสอบ', 'ยังไม่ชำระ', 'เช็คอินแล้ว'];
  var PAGE_SIZES = [10, 20, 50];
  var FREE = 'ไม่มีค่าใช้จ่าย';

  /* นิยามฟิลด์ของแบบฟอร์มลงทะเบียน — โมดัลรายละเอียดเรนเดอร์จากรายการนี้
     ไม่ได้ไล่เขียนทีละช่องในหน้าจอ เพิ่ม/ลดฟิลด์ในแบบฟอร์มแล้วโมดัลตามเอง

     group = หัวข้อกลุ่มที่ฟิลด์นี้สังกัด · full = ค่ายาว ให้กินเต็มแถวจะได้ไม่ตัดบรรทัดกลางคัน */
  var REG_FORM_FIELDS = [
    { key: 'name', label: 'ชื่อ–นามสกุล', group: 'ข้อมูลติดต่อ' },
    { key: 'phone', label: 'เบอร์โทร', group: 'ข้อมูลติดต่อ' },
    { key: 'email', label: 'อีเมล', group: 'ข้อมูลติดต่อ', full: true },

    { key: 'gender', label: 'เพศ', group: 'ข้อมูลผู้เข้าร่วม' },
    { key: 'ageRange', label: 'ช่วงอายุ', group: 'ข้อมูลผู้เข้าร่วม' },
    { key: 'occupation', label: 'อาชีพ', group: 'ข้อมูลผู้เข้าร่วม' },
    { key: 'area', label: 'พื้นที่', group: 'ข้อมูลผู้เข้าร่วม' },
    { key: 'interests', label: 'กิจกรรมที่สนใจเพิ่มเติม', group: 'ข้อมูลผู้เข้าร่วม', full: true },
    { key: 'dietaryNote', label: 'ข้อจำกัดด้านอาหาร', group: 'ข้อมูลผู้เข้าร่วม', full: true },

    { key: 'round', label: 'รอบที่สมัคร', group: 'การลงทะเบียน' },
    { key: 'sourceChannel', label: 'รู้จักกิจกรรมจาก', group: 'การลงทะเบียน' },
    { key: 'registeredAt', label: 'ลงทะเบียนเมื่อ', group: 'การลงทะเบียน', full: true }
  ];

  var activities = data.activities || [];
  var activity = data.activity;
  var rows = data.rows || [];

  var state = { tab: 'ทั้งหมด', q: '', searchOpen: false, pickerOpen: false, page: 1, pageSize: PAGE_SIZES[0] };
  var slipTarget = null;
  var undoTarget = null;

  var csrf = document.querySelector('meta[name="csrf-token"]');

  /* ทุกคำขอที่เปลี่ยนข้อมูลผ่านทางนี้ทางเดียว — โยน Error พร้อมข้อความจากเซิร์ฟเวอร์ให้ผู้เรียกจัดการ */
  function send(url, method, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-HTTP-Method-Override': method
      },
      body: JSON.stringify(body || {})
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (payload) {
        if (!res.ok) throw new Error(payload.message || 'ทำรายการไม่สำเร็จ กรุณาลองใหม่');
        return payload;
      });
    });
  }

  function endpoint(name, code) {
    return data.endpoints[name].replace(':code', encodeURIComponent(code));
  }

  function toast(message, tone) {
    if (window.TFC.showToast) window.TFC.showToast(message, tone);
  }

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return rows.filter(function (r) {
      if (state.tab === 'ชำระแล้ว' && r.pay !== 'ชำระแล้ว') return false;
      if (state.tab === 'รอตรวจสอบ' && r.pay !== 'รอตรวจสอบ') return false;
      if (state.tab === 'ยังไม่ชำระ' && r.pay !== 'ยังไม่ชำระ') return false;
      if (state.tab === 'เช็คอินแล้ว' && !r.checkedIn) return false;
      if (!q) return true;
      return (r.name + ' ' + r.code + ' ' + r.phone).toLowerCase().indexOf(q) > -1;
    });
  }

  function baht(n) { return Number(n || 0).toLocaleString('th-TH'); }
  function pct(a, b) { return b ? Math.round((a / b) * 100) : 0; }

  /* ---------- หัวหน้า: ชื่อกิจกรรม + ปุ่มเปลี่ยนกิจกรรม ---------- */
  function renderActivityLine() {
    $('rg-activity-line').innerHTML =
      '<span class="rg-activity-name">' + esc(activity.name) + '</span>' +
      '<span class="rg-activity-code">' + esc(activity.id) + '</span>' +
      '<div class="rg-picker">' +
        '<button type="button" class="rg-picker-btn" id="rg-picker-btn" aria-expanded="' + state.pickerOpen + '" aria-haspopup="listbox">' +
          '<span>เปลี่ยนกิจกรรม</span>' +
          '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M8 10l4 4 4-4"/></svg>' +
        '</button>' +
        '<div class="rg-picker-panel" id="rg-picker-panel" role="listbox"' + (state.pickerOpen ? '' : ' hidden') + '>' +
          '<span class="rg-picker-arrow"></span>' +
          '<span class="rg-picker-title">เลือกกิจกรรม</span>' +
          activities.map(function (a) {
            var on = a.id === activity.id;
            return '<button type="button" class="rg-picker-item' + (on ? ' is-on' : '') +
              '" role="option" aria-selected="' + on + '" data-activity="' + esc(a.id) + '">' +
              '<span class="rg-picker-item-name">' + esc(a.name) + '</span>' +
              '<span class="rg-picker-item-meta">' + esc(a.startDateLabel || '-') + ' · ' + a.registered + ' คน</span>' +
              '</button>';
          }).join('') +
        '</div>' +
      '</div>';

    clampPicker();
  }

  /* ปุ่มอยู่ท้ายบรรทัดชื่อกิจกรรม ตำแหน่งจึงขยับตามความยาวชื่อ
     ถ้าแผงเปิดไปทางขวาแล้วเลยขอบจอ ให้สลับไปยึดขวาแทน */
  function clampPicker() {
    var panel = $('rg-picker-panel');
    if (!panel || panel.hidden) return;
    panel.classList.remove('is-right');
    if (panel.getBoundingClientRect().right > document.documentElement.clientWidth - 16) {
      panel.classList.add('is-right');
    }
  }

  window.addEventListener('resize', clampPicker);

  /* ---------- การ์ดสรุป ---------- */
  function renderStats() {
    var paid = rows.filter(function (r) { return r.pay === 'ชำระแล้ว'; });
    var waiting = rows.filter(function (r) { return r.pay === 'รอตรวจสอบ'; });
    var cap = activity.capacity || 0;
    /* ยอดที่รับแล้วนับเฉพาะรายการที่สถานะ "ชำระแล้ว" เท่านั้น */
    var received = paid.reduce(function (n, r) { return n + r.amount; }, 0);
    var outstanding = rows.filter(function (r) { return r.pay !== 'ชำระแล้ว' && r.pay !== FREE; })
      .reduce(function (n, r) { return n + r.amount; }, 0);

    var cards = [
      { label: 'ลงทะเบียนทั้งหมด', value: rows.length, unit: 'คน', hint: 'จากที่รับได้ ' + cap + ' ที่นั่ง', tone: '' },
      { label: 'ชำระเงินแล้ว', value: paid.length, unit: 'คน', hint: pct(paid.length, rows.length) + '% ของผู้ลงทะเบียน', tone: 'is-good' },
      { label: 'ยอดเงินที่รับแล้ว', value: baht(received), unit: 'บาท',
        hint: outstanding > 0 ? 'ค้างชำระอีก ' + baht(outstanding) + ' บาท' : 'ไม่มียอดค้างชำระ', tone: 'is-good' },
      { label: 'รอตรวจสอบสลิป', value: waiting.length, unit: 'คน',
        hint: waiting.length > 0 ? 'ต้องยืนยันก่อนวันงาน' : 'ไม่มีรายการค้าง', tone: waiting.length > 0 ? 'is-warn' : '' }
    ];

    $('rg-stats').innerHTML = cards.map(function (c) {
      return '<div class="card rg-stat">' +
        '<span class="rg-stat-label">' + esc(c.label) + '</span>' +
        '<span class="rg-stat-value ' + c.tone + '">' + esc(String(c.value)) +
          '<span class="rg-stat-unit">' + esc(c.unit) + '</span></span>' +
        '<span class="rg-stat-hint">' + esc(c.hint) + '</span>' +
        '</div>';
    }).join('');
  }

  /* ---------- แท็บ + ค้นหา ---------- */
  function renderTabs() {
    $('rg-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="rg-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + '</button>';
    }).join('');
  }

  function syncSearch() {
    /* ปุ่มเป็นสีเขียวทั้งตอนเปิดแผงและตอนมีคำค้างไว้ จะได้รู้ว่าตารางถูกกรองอยู่ */
    $('rg-search-btn').classList.toggle('is-on', state.searchOpen || !!state.q.trim());
    $('rg-search-btn').setAttribute('aria-expanded', String(state.searchOpen));
    $('rg-search-panel').hidden = !state.searchOpen;
    $('rg-search-count').textContent = state.q.trim()
      ? 'พบ ' + filtered().length + ' จาก ' + rows.length + ' คน'
      : 'พิมพ์เพื่อค้นหาในรายชื่อ ' + rows.length + ' คน';
  }

  /* ---------- ตาราง ---------- */
  var PAY_CLASS = {
    'ชำระแล้ว': 'is-paid', 'รอตรวจสอบ': 'is-waiting',
    'ยังไม่ชำระ': 'is-unpaid', 'ปฏิเสธ': 'is-unpaid'
  };
  PAY_CLASS[FREE] = 'is-free';

  function rowHtml(r) {
    var amountText = r.amount > 0 ? baht(r.amount) + ' ฿' : '—';
    var amountClass = r.amount > 0 ? (r.pay === 'ชำระแล้ว' ? 'is-paid' : 'is-due') : 'is-none';

    return '<div class="rg-tr" data-row="' + esc(r.id) + '">' +
      '<div class="rg-person">' +
        '<span class="rg-avatar">' + esc(r.name.charAt(0)) + '</span>' +
        '<span class="rg-person-text">' +
          '<span class="rg-person-name">' + esc(r.name) + '</span>' +
          '<span class="rg-person-code">' + esc(r.code) + '</span>' +
        '</span>' +
      '</div>' +
      '<div class="rg-cell rg-nums">' + esc(r.phone) + '</div>' +
      '<div class="rg-cell">' + esc(r.round) + '</div>' +
      '<div class="rg-amount ' + amountClass + '">' + esc(amountText) + '</div>' +
      '<div>' +
        '<button type="button" class="rg-pay ' + (PAY_CLASS[r.pay] || '') + '" data-slip="' + esc(r.id) + '">' +
          '<span class="rg-pay-dot"></span>' + esc(r.pay) +
        '</button>' +
      '</div>' +
      '<div class="rg-check">' +
        (r.checkedIn
          ? '<span class="rg-check-time">' + esc(r.checkedInAt) + ' น.</span>' +
            '<button type="button" class="rg-undo-btn" data-undo="' + esc(r.id) + '" aria-label="ยกเลิกเช็คอิน ' + esc(r.name) + '">' +
              '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 1 0 2.6-5.9"/><path d="M4 4.5V9h4.5"/></svg>' +
            '</button>'
          : '<button type="button" class="rg-checkin-btn" data-checkin="' + esc(r.id) + '">เช็คอิน</button>') +
      '</div>' +
      '<div class="rg-actions">' +
        '<button type="button" class="rg-more" data-detail="' + esc(r.id) + '" aria-label="รายละเอียด ' + esc(r.name) + '">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>' +
        '</button>' +
      '</div>' +
      '</div>';
  }

  var EMPTY_HTML = '<div class="rg-empty">' +
    '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>' +
    '<span class="rg-empty-title">ไม่พบผู้ลงทะเบียนที่ตรงกับเงื่อนไข</span></div>';

  function render() {
    var list = filtered();
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;
    var pageRows = list.slice(start, start + state.pageSize);

    $('rg-rows').innerHTML = pageRows.length ? pageRows.map(rowHtml).join('') : EMPTY_HTML;

    $('rg-foot').innerHTML = '<span class="rg-foot-text">แสดง ' + (list.length ? start + 1 : 0) + '–' +
      (start + pageRows.length) + ' จาก ' + list.length + ' คน</span><div id="rg-pagination"></div>';

    window.TFC.renderPagination('rg-pagination', {
      page: state.page,
      pageSize: state.pageSize,
      total: list.length,
      pageSizeOptions: PAGE_SIZES,
      onChange: function (p) { state.page = p; render(); },
      onPageSizeChange: function (size) { state.pageSize = size; state.page = 1; render(); }
    });

    syncSearch();
  }

  function rowById(id) {
    return rows.filter(function (r) { return r.id === id; })[0];
  }

  /* ---------- โมดัลสลิป ---------- */
  function openSlip(id) {
    var r = rowById(id);
    if (!r) return;
    slipTarget = r;

    $('rg-slip-title').textContent = 'สลิปการชำระเงิน · ' + r.name;
    $('rg-slip-image').innerHTML = slipImageHtml(r);

    /* สองค่าแรกสั้น วางคู่กันได้ · อีกสองค่ายาว ให้กินเต็มแถว */
    var facts = [
      { label: 'ยอดที่ต้องชำระ', value: r.amount > 0 ? baht(r.amount) + ' บาท' : FREE },
      { label: 'สถานะปัจจุบัน', value: r.pay },
      { label: 'โอนเมื่อ', value: r.pay === FREE ? '—' : ((r.slip && r.slip.transferredAt) || 'ยังไม่มีการแจ้งโอน'), full: true },
      { label: 'บัญชีปลายทาง', value: data.paymentAccount || 'ยังไม่ได้ตั้งค่าบัญชีรับชำระ', full: true }
    ];
    $('rg-slip-facts').innerHTML = facts.map(function (f) {
      return '<div class="field-view' + (f.full ? ' is-full' : '') + '">' +
        '<dt>' + esc(f.label) + '</dt><dd>' + esc(f.value) + '</dd></div>';
    }).join('');

    /* กิจกรรมฟรีไม่มีอะไรให้ยืนยัน เหลือแค่ปุ่มปิด */
    $('rg-slip-actions').innerHTML = r.pay === FREE
      ? '<button type="button" class="btn btn-outline" data-close-modal>ปิด</button>'
      : '<button type="button" class="btn btn-outline rg-reject" id="rg-slip-reject">สลิปไม่ถูกต้อง</button>' +
        '<button type="button" class="btn btn-primary" id="rg-slip-confirm">ยืนยันการชำระเงิน</button>';

    window.TFC.openModal('rg-slip-modal');
  }

  /* ไฟล์สลิปเก็บนอก public/ ลิงก์ที่ได้จึงเป็น route ที่ตรวจสิทธิ์ ไม่ใช่ path ของไฟล์ */
  function slipImageHtml(r) {
    if (r.pay === FREE) return '<span class="rg-slip-none">กิจกรรมนี้ไม่มีค่าใช้จ่าย จึงไม่มีสลิป</span>';
    if (!r.slip) return '<span class="rg-slip-none">ยังไม่มีสลิปแนบเข้ามาสำหรับรายการนี้</span>';

    return '<a class="rg-slip-thumb" href="' + esc(r.slip.url) + '" target="_blank" rel="noopener">' +
      '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>' +
      '<span>' + esc(r.slip.name) + '</span></a>';
  }

  /* ---------- โมดัลรายละเอียด ---------- */
  function openDetail(id) {
    var r = rowById(id);
    if (!r) return;

    $('rg-detail-avatar').textContent = r.name.charAt(0);
    $('rg-detail-name').textContent = r.name;
    $('rg-detail-code').textContent = r.code;

    /* เรนเดอร์จากนิยามฟิลด์ของแบบฟอร์ม ไม่ได้ไล่เขียนทีละช่อง
       ขึ้นหัวข้อกลุ่มใหม่เมื่อ group เปลี่ยน จึงไม่ต้องเขียนโครงกลุ่มซ้ำในหน้าจอ */
    var lastGroup = null;
    $('rg-detail-fields').innerHTML = REG_FORM_FIELDS.map(function (f) {
      var v = r[f.key];
      var head = '';
      if (f.group !== lastGroup) {
        lastGroup = f.group;
        head = '<span class="field-view-group">' + esc(f.group) + '</span>';
      }
      return head +
        '<div class="field-view' + (f.full ? ' is-full' : '') + '">' +
        '<dt>' + esc(f.label) + '</dt>' +
        '<dd' + (v ? '' : ' class="is-empty"') + '>' + esc(v || 'ไม่ได้กรอก') + '</dd></div>';
    }).join('');

    window.TFC.openModal('rg-detail-modal');
  }

  /* ---------- เหตุการณ์ ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    if (t.closest('#rg-picker-btn')) {
      state.pickerOpen = !state.pickerOpen;
      return renderActivityLine();
    }

    /* เปลี่ยนกิจกรรมคือโหลดหน้าใหม่ ตัวเลขสรุปและรายชื่อจึงมาจากฐานข้อมูลชุดเดียวกันเสมอ */
    var pick = t.closest('[data-activity]');
    if (pick) {
      var nextId = pick.getAttribute('data-activity');
      state.pickerOpen = false;
      if (nextId === activity.id) return renderActivityLine();
      location.search = '?id=' + encodeURIComponent(nextId);
      return;
    }

    var tab = t.closest('[data-tab]');
    if (tab) {
      state.tab = tab.getAttribute('data-tab');
      state.page = 1;
      renderTabs();
      return render();
    }

    if (t.closest('#rg-search-btn')) {
      state.searchOpen = !state.searchOpen;
      syncSearch();
      if (state.searchOpen) $('rg-query').focus();
      return;
    }

    if (t.closest('#rg-search-clear')) {
      state.q = '';
      $('rg-query').value = '';
      state.page = 1;
      return render();
    }

    var slip = t.closest('[data-slip]');
    if (slip) return openSlip(slip.getAttribute('data-slip'));

    var detail = t.closest('[data-detail]');
    if (detail) return openDetail(detail.getAttribute('data-detail'));

    var checkin = t.closest('[data-checkin]');
    if (checkin) return doCheckin(checkin, rowById(checkin.getAttribute('data-checkin')));

    var undo = t.closest('[data-undo]');
    if (undo) {
      undoTarget = rowById(undo.getAttribute('data-undo'));
      if (!undoTarget) return;
      $('rg-undo-message').textContent = undoTarget.name + ' เช็คอินเมื่อ ' + undoTarget.checkedInAt +
        ' น. — ยืนยันเปลี่ยนสถานะกลับเป็นยังไม่มา';
      return window.TFC.openModal('rg-undo-modal');
    }

    if (t.closest('#rg-slip-confirm')) return settleSlip('ชำระแล้ว');
    if (t.closest('#rg-slip-reject')) return settleSlip('ยังไม่ชำระ');

    /* คลิกนอกตัวเลือกกิจกรรม/แผงค้นหา ให้ปิด */
    if (state.pickerOpen && !t.closest('.rg-picker')) {
      state.pickerOpen = false;
      renderActivityLine();
    }
    if (state.searchOpen && !t.closest('.rg-search')) {
      state.searchOpen = false;
      syncSearch();
    }
  });

  /* ปุ่มเข้าสถานะรอทันทีที่กด กันกดซ้ำระหว่างรอเซิร์ฟเวอร์ แล้วค่อยอัปเดตแถวเมื่อบันทึกสำเร็จ
     ไม่ใช้ optimistic update เพราะเซิร์ฟเวอร์เป็นคนกำหนดเวลาเช็คอิน ไม่ใช่นาฬิกาของเครื่องหน้างาน */
  function doCheckin(button, row) {
    if (!row || button.disabled) return;
    button.disabled = true;
    button.textContent = 'กำลังบันทึก…';

    send(endpoint('checkin', row.code), 'POST')
      .then(function (payload) {
        row.checkedIn = true;
        row.checkedInAt = payload.checkedInAt;
        renderStats();
        render();
        toast(payload.message, 'success');
      })
      .catch(function (err) {
        button.disabled = false;
        button.textContent = 'เช็คอิน';
        toast(err.message, 'danger');
      });
  }

  function settleSlip(status) {
    if (!slipTarget) return;
    var target = slipTarget;
    var buttons = ['rg-slip-confirm', 'rg-slip-reject'].map($).filter(Boolean);
    buttons.forEach(function (b) { b.disabled = true; });
    if ($('rg-slip-confirm')) $('rg-slip-confirm').textContent = 'กำลังบันทึก…';

    send(endpoint('payment', target.code), 'PATCH', { status: status })
      .then(function (payload) {
        target.pay = payload.pay;
        window.TFC.closeModal('rg-slip-modal');
        renderStats();
        render();
        toast(payload.message, status === 'ชำระแล้ว' ? 'success' : 'warning');
        slipTarget = null;
      })
      .catch(function (err) {
        buttons.forEach(function (b) { b.disabled = false; });
        if ($('rg-slip-confirm')) $('rg-slip-confirm').textContent = 'ยืนยันการชำระเงิน';
        toast(err.message, 'danger');
      });
  }

  var undoBtn = $('rg-undo-ok');

  undoBtn.addEventListener('click', function () {
    if (!undoTarget || undoBtn.disabled) return;
    var target = undoTarget;
    undoBtn.disabled = true;
    undoBtn.textContent = 'กำลังยกเลิก…';

    send(endpoint('checkin', target.code), 'DELETE')
      .then(function (payload) {
        target.checkedIn = false;
        target.checkedInAt = '';
        window.TFC.closeModal('rg-undo-modal');
        renderStats();
        render();
        toast(payload.message, 'info');
        undoTarget = null;
      })
      .catch(function (err) { toast(err.message, 'danger'); })
      .finally(function () {
        undoBtn.disabled = false;
        undoBtn.textContent = 'ยืนยันยกเลิก';
      });
  });

  $('rg-query').addEventListener('input', function () {
    state.q = this.value;
    state.page = 1;
    render();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (state.pickerOpen) { state.pickerOpen = false; renderActivityLine(); }
    if (state.searchOpen) { state.searchOpen = false; syncSearch(); }
  });

  $('rg-export').addEventListener('click', function () {
    var list = filtered().map(function (r, i) {
      return [i + 1, r.code, r.name, r.phone, r.email, r.round, r.pay,
        r.amount || '', r.checkedIn ? r.checkedInAt + ' น.' : 'ยังไม่มา'];
    });
    window.TFC.exportCsv('ผู้ลงทะเบียน-' + activity.id + '.csv',
      ['#', 'รหัสลงทะเบียน', 'ชื่อ-นามสกุล', 'เบอร์โทร', 'อีเมล', 'รอบ', 'การชำระเงิน', 'ยอดเงิน', 'Check-in'], list);
  });

  $('rg-add').addEventListener('click', function () {
    toast('เพิ่มผู้ลงทะเบียนด้วยตนเอง — เชื่อมกับฟอร์มลงทะเบียนในขั้นถัดไป', 'info');
  });

  /* ---------- เริ่มต้น ---------- */
  /* ยังไม่มีกิจกรรมที่เปิดรับลงทะเบียนเลย — บอกให้ไปสร้างก่อน ไม่ปล่อยหน้าว่างเปล่า */
  if (!activity) {
    $('rg-activity-line').innerHTML = '<span class="rg-activity-name">ยังไม่มีกิจกรรมที่เปิดรับลงทะเบียน</span>';
    $('rg-rows').innerHTML = EMPTY_HTML;
    ['rg-export', 'rg-add'].forEach(function (id) { $(id).disabled = true; });
    return;
  }

  renderActivityLine();
  renderStats();
  renderTabs();
  render();
})();
