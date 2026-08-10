/* TheFarmConcept — หน้ารายละเอียดกลุ่มตัวอย่าง (admin/cohort/detail.html)

   ผลสุขภาพทั้งหมดมาจาก survey_responses เท่านั้น หน้านี้จึงไม่มีช่องให้กรอกคะแนน
   แท็บ "แบบประเมินสุขภาพ" แสดงแค่สถานะรอบและลิงก์ ไม่มีคอลัมน์ค่าตรวจ */
(function () {
  var C = window.TFC.cohort;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var params = new URLSearchParams(location.search);
  var m = C.byId(params.get('id')) || C.members()[0];

  var TABS = ['ประวัติกิจกรรม', 'แบบประเมินสุขภาพ', 'การซื้อสินค้า', 'ประวัติการติดตาม'];
  var NOTE_VIEWS = ['ทั้งหมด', 'ระบบแจ้งเตือน', 'แอดมินติดตาม'];

  var state = {
    tab: TABS[0],
    noteView: NOTE_VIEWS[0],
    noteKind: C.NOTE_KINDS[0],
    noteText: '',
    noteEditId: null,
    orderEditId: null,
    lineMsg: null,
    lineCopied: false,
    bindCopied: false,
    copiedRound: null
  };

  /* ---------- หัวหน้า ---------- */
  var STATUS_CLASS = {
    'กำลังติดตาม': 'is-running', 'ติดตามครบ': 'is-done',
    'หลุดการติดตาม': 'is-lost', 'ยุติการติดตาม': 'is-stopped'
  };

  function renderHeader() {
    var status = C.personStatus(m);
    var next = C.nextRound(m);
    var due = C.dueText(next);

    $('cd-crumb').textContent = m.name;
    document.title = m.name + ' | TheFarmConcept';

    $('cd-header').innerHTML =
      '<div class="cd-header-main">' +
        '<a class="cd-back" href="list.html" aria-label="ย้อนกลับไปรายการกลุ่มตัวอย่าง">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg>' +
        '</a>' +
        '<div class="cd-header-text">' +
          '<div class="cd-title-line">' +
            '<h1 class="cd-title">' + esc(m.name) + '</h1>' +
            '<span class="co-status ' + STATUS_CLASS[status] + '">' + esc(status) + '</span>' +
          '</div>' +
          '<div class="cd-meta">' +
            '<span>' + esc(m.pid) + '</span>' +
            '<span>' + esc(m.phone) + '</span>' +
            '<span>' + esc(m.area) + '</span>' +
            '<span>เข้ากลุ่ม ' + esc(C.fmt(m.base)) + '</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="cd-header-actions">' +
        (next
          ? '<span class="cd-next-badge ' + due.tone + '">' + esc(next.name) + ' · ' + esc(due.text) + '</span>'
          : '<span class="cd-next-badge">ติดตามครบทุกรอบ</span>') +
        '<button type="button" class="btn btn-primary" id="cd-line-open">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11.5c0 4-3.6 7.2-8 7.2-.9 0-1.7-.1-2.5-.3L5 20l1-3.2A7 7 0 0 1 4 11.5c0-4 3.6-7.2 8-7.2s8 3.2 8 7.2Z"/></svg>' +
          'แจ้งเตือน LINE</button>' +
      '</div>';
  }

  /* ---------- ข้อมูลทั่วไป ---------- */
  function renderInfo() {
    var sc = C.schedule(m);
    var answered = sc.filter(function (r) { return r.state === 'ตอบแล้ว'; }).length;

    var fields = [
      { label: 'รหัสบุคคล', value: m.pid },
      { label: 'ชื่อ–นามสกุล', value: m.name },
      { label: 'เบอร์โทร', value: m.phone },
      { label: 'เพศ', value: m.gender },
      { label: 'ช่วงอายุ', value: m.age },
      { label: 'อาชีพ', value: m.job },
      { label: 'พื้นที่', value: m.area },
      { label: 'กลุ่มเป้าหมาย', value: m.target },
      { label: 'แหล่งที่มา', value: m.source, full: true },
      { label: 'วันที่เข้ากลุ่มตัวอย่าง', value: C.fmt(m.base) },
      { label: 'ความยินยอม', value: m.consent ? 'ได้รับแล้ว' : 'ยังไม่ได้รับ' },
      { label: 'LINE', value: m.line ? 'ผูกแล้ว' : 'ยังไม่ผูก', tone: m.line ? '' : 'is-warn' },
      { label: 'ตอบครบ', value: answered + ' จาก ' + sc.length + ' รอบ' }
    ];

    /* ใช้โครงมาตรฐาน .field-view — ห้ามเขียนโครง label/value เองในหน้านี้ */
    $('cd-info').innerHTML = fields.map(function (f) {
      return '<div class="field-view' + (f.full ? ' is-full' : '') + '">' +
        '<dt>' + esc(f.label) + '</dt>' +
        '<dd' + (f.tone ? ' class="' + f.tone + '"' : '') + '>' + esc(f.value) + '</dd></div>';
    }).join('');
  }

  /* ---------- ไทม์ไลน์ ---------- */
  var ROUND_CLASS = {
    'ตอบแล้ว': 'is-done', 'รอติดตาม': 'is-due',
    'เกินกำหนด': 'is-over', 'ยังไม่ถึงกำหนด': 'is-idle', 'ยุติการติดตาม': 'is-idle'
  };

  function renderTimeline() {
    $('cd-timeline').innerHTML = C.schedule(m).map(function (r) {
      /* รอบ "ก่อนเข้าร่วม" ทำในวันเดียว จึงไม่มีช่วงติดตามให้แสดง */
      var range = r.from === r.to ? '' : 'ช่วงติดตาม ' + C.fmt(r.from) + ' – ' + C.fmt(r.to);
      return '<div class="cd-step ' + ROUND_CLASS[r.state] + '">' +
        '<span class="cd-step-dot"></span>' +
        '<div class="cd-step-body">' +
          '<div class="cd-step-head">' +
            '<span class="cd-step-name">' + esc(r.name) + '</span>' +
            '<span class="cd-step-state">' + esc(r.state) + '</span>' +
          '</div>' +
          '<span class="cd-step-meta">ครบกำหนด ' + esc(C.fmt(r.due)) + (range ? ' · ' + esc(range) : '') + '</span>' +
          '<span class="cd-step-meta">' + (r.at ? 'ตอบเมื่อ ' + esc(C.fmt(r.at)) : 'ยังไม่มีคำตอบ') + '</span>' +
        '</div></div>';
    }).join('');
  }

  /* ---------- แท็บ ---------- */
  function renderTabs() {
    $('cd-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="cd-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + '</button>';
    }).join('');
  }

  function tableCard(cls, headers, rowsHtml, footHtml) {
    return '<div class="card cd-table-card">' +
      '<div class="cd-table-scroll"><div class="cd-table ' + cls + '">' +
        '<div class="cd-tr cd-th">' + headers.map(function (h) {
          return '<div' + (h.align ? ' class="text-' + h.align + '"' : '') + '>' + esc(h.label || h) + '</div>';
        }).join('') + '</div>' +
        rowsHtml +
      '</div></div>' + (footHtml || '') + '</div>';
  }

  /* ---------- 1) ประวัติกิจกรรม ---------- */
  function renderActivities() {
    var rows = C.activitiesOf(m).map(function (a) {
      return '<div class="cd-tr">' +
        '<div class="cd-cell cd-nums">' + esc(C.fmt(a.at)) + '</div>' +
        '<div class="cd-cell">' + esc(a.name) + '</div>' +
        '<div class="cd-cell">' + esc(a.program) + '</div>' +
        '<div class="cd-cell">' + esc(a.place) + '</div>' +
        '<div><span class="cd-badge ' + (a.status === 'เข้าร่วม' ? 'is-good' : 'is-warn') + '">' + esc(a.status) + '</span></div>' +
        '</div>';
    }).join('');
    return tableCard('cd-act-table', ['วันที่', 'กิจกรรม', 'โปรแกรม / หลักสูตร', 'สถานที่', 'สถานะ'], rows);
  }

  /* ---------- 2) แบบประเมินสุขภาพ ---------- */
  function renderSurvey() {
    var rows = C.schedule(m).map(function (r) {
      var range = r.from === r.to ? '—' : C.fmt(r.from) + ' – ' + C.fmt(r.to);
      var copied = state.copiedRound === r.id;
      return '<div class="cd-tr">' +
        '<div class="cd-cell">' + esc(r.name) + '</div>' +
        '<div class="cd-cell cd-nums">' + esc(range) + '</div>' +
        '<div><span class="cd-badge ' + ROUND_CLASS[r.state] + '">' + esc(r.state) + '</span></div>' +
        '<div class="cd-cell cd-nums">' + esc(r.at ? C.fmt(r.at) : '—') + '</div>' +
        '<div class="cd-actions-cell">' +
          '<button type="button" class="cd-mini-btn' + (copied ? ' is-done' : '') + '" data-copy-round="' + esc(r.id) + '">' +
            (copied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์') + '</button>' +
        '</div></div>';
    }).join('');

    return tableCard('cd-survey-table',
      ['รอบติดตาม', 'ช่วงติดตาม', 'สถานะ', 'ตอบเมื่อ', ''], rows,
      '<div class="cd-note-line">ผลตรวจและคะแนนทั้งหมดดึงจากคำตอบในแบบประเมิน ไม่ได้กรอกในโปรไฟล์นี้</div>');
  }

  /* ---------- 3) การซื้อสินค้า ---------- */
  function renderOrders() {
    var list = C.ordersOf(m);
    var total = list.reduce(function (n, o) { return n + o.amount; }, 0);

    var rows = list.map(function (o) {
      return '<div class="cd-tr">' +
        '<div class="cd-cell cd-nums">' + esc(C.fmt(o.at)) + '</div>' +
        '<div class="cd-cell">' + esc(o.item) + '</div>' +
        '<div class="cd-cell">' + esc(o.shop) + '</div>' +
        '<div><span class="cd-badge is-plain">' + esc(o.channel) + '</span></div>' +
        '<div><span class="cd-badge ' + (o.status === 'ชำระแล้ว' ? 'is-good' : 'is-warn') + '">' + esc(o.status) + '</span></div>' +
        '<div class="cd-cell cd-nums text-right">' + o.amount.toLocaleString('th-TH') + ' ฿</div>' +
        '<div class="cd-cell">' + esc(o.by) + '</div>' +
        '<div class="cd-actions-cell">' +
          '<button type="button" class="cd-icon-btn" data-edit-order="' + esc(o.id) + '" aria-label="แก้ไขรายการ">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M13.5 6.5l4 4"/></svg>' +
          '</button></div>' +
        '</div>';
    }).join('');

    return '<div class="cd-tab-bar">' +
        '<span class="cd-tab-title">การซื้อสินค้า ' + list.length + ' รายการ</span>' +
        '<button type="button" class="btn btn-primary btn-sm" id="cd-order-add">' +
          '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5.5v13M5.5 12h13"/></svg>' +
          'บันทึกการซื้อสินค้า</button>' +
      '</div>' +
      tableCard('cd-order-table',
        ['วันที่ซื้อ', 'รายการสินค้า', 'ร้านที่ซื้อ', 'ช่องทางซื้อ', 'สถานะ', { label: 'ยอดเงิน', align: 'right' }, 'บันทึกโดย', ''],
        rows,
        '<div class="cd-total-row"><span>ยอดรวมทั้งหมด</span>' +
        '<span class="cd-total-value">' + total.toLocaleString('th-TH') + ' ฿</span></div>');
  }

  /* ---------- 4) ประวัติการติดตาม ---------- */
  function renderNotes() {
    var list = C.notesOf(m).filter(function (n) {
      return state.noteView === 'ทั้งหมด' || n.src === state.noteView;
    });

    var rows = list.map(function (n) {
      /* log จากระบบแก้/ลบไม่ได้ เพราะเป็นหลักฐานการส่งจริง */
      var isSystem = n.src === 'ระบบแจ้งเตือน';
      return '<div class="cd-tr">' +
        '<div class="cd-cell cd-nums">' + esc(C.fmt(n.at)) + ' · ' + esc(n.time) + '</div>' +
        '<div><span class="cd-badge ' + (isSystem ? 'is-plain' : 'is-good') + '">' + esc(n.src) + '</span></div>' +
        '<div class="cd-cell">' + esc(n.kind) + '</div>' +
        '<div class="cd-cell cd-wrap">' + esc(n.text) + '</div>' +
        '<div class="cd-cell">' + esc(n.by) + '</div>' +
        '<div class="cd-actions-cell">' +
          (isSystem ? '' :
            '<button type="button" class="cd-icon-btn" data-edit-note="' + esc(n.id) + '" aria-label="แก้ไขบันทึก">' +
              '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M13.5 6.5l4 4"/></svg></button>' +
            '<button type="button" class="cd-icon-btn is-danger" data-del-note="' + esc(n.id) + '" aria-label="ลบบันทึก">' +
              '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M9.5 7V5.5h5V7M7 7l.8 12h8.4L17 7"/></svg></button>') +
        '</div></div>';
    }).join('');

    var editing = !!state.noteEditId;

    return '<div class="cd-tab-bar">' +
        '<div class="cd-segmented">' + NOTE_VIEWS.map(function (v) {
          return '<button type="button" class="cd-seg' + (state.noteView === v ? ' is-on' : '') +
            '" data-note-view="' + esc(v) + '">' + esc(v) + '</button>';
        }).join('') + '</div>' +
      '</div>' +

      /* ฟอร์มเพิ่มบันทึกอยู่บรรทัดเดียว ไม่ต้องเปิด popup */
      '<div class="card cd-note-form">' +
        '<select class="select cd-note-kind" id="cd-note-kind">' + C.NOTE_KINDS.map(function (k) {
          return '<option value="' + esc(k) + '"' + (state.noteKind === k ? ' selected' : '') + '>' + esc(k) + '</option>';
        }).join('') + '</select>' +
        '<input type="text" class="input cd-note-text" id="cd-note-text" value="' + esc(state.noteText) +
          '" placeholder="รายละเอียดการติดตาม">' +
        '<button type="button" class="btn btn-primary btn-sm" id="cd-note-save">' +
          (editing ? 'บันทึกการแก้ไข' : 'เพิ่มบันทึก') + '</button>' +
        (editing ? '<button type="button" class="btn btn-outline btn-sm" id="cd-note-cancel">ยกเลิก</button>' : '') +
      '</div>' +

      tableCard('cd-note-table',
        ['วันที่ / เวลา', 'ที่มา', 'ประเภท', 'รายละเอียด', 'ดำเนินการโดย', ''], rows);
  }

  function renderPanel() {
    var html = state.tab === 'ประวัติกิจกรรม' ? renderActivities()
      : state.tab === 'แบบประเมินสุขภาพ' ? renderSurvey()
      : state.tab === 'การซื้อสินค้า' ? renderOrders()
      : renderNotes();
    $('cd-panel').innerHTML = html;
  }

  function render() {
    renderHeader();
    renderInfo();
    renderTimeline();
    renderTabs();
    renderPanel();
  }

  /* ================= แจ้งเตือน LINE ================= */
  function defaultMsg() {
    var next = C.nextRound(m);
    return 'สวัสดีคุณ ' + m.name + ' 🌱\n' +
      'ถึงเวลาทำแบบประเมิน' + (next ? next.name : 'รอบติดตาม') + 'แล้ว\n' +
      'กรุณาตอบภายในวันที่ ' + (next ? C.fmt(next.to) : '-');
  }

  function openLine() {
    if (state.lineMsg === null) state.lineMsg = defaultMsg();
    syncLine();
    window.TFC.openModal('cd-line-modal');
  }

  function syncLine() {
    $('cd-line-msg').value = state.lineMsg;
    $('cd-bubble-text').textContent = state.lineMsg;
    $('cd-line-warn').hidden = m.line;
    $('cd-bind-url').textContent = C.LINE_LINK_URL;
    $('cd-bind-text').textContent = state.bindCopied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์ผูก LINE';
    /* ยังไม่ผูก LINE ส่งไม่ได้ แต่ยังคัดลอกข้อความไปส่งช่องทางอื่นได้ */
    $('cd-line-send').disabled = !m.line;
    $('cd-line-send').textContent = m.line ? 'ส่งแจ้งเตือน' : 'ส่งไม่ได้ — ยังไม่ผูก LINE';
    $('cd-line-copy').textContent = state.lineCopied ? 'คัดลอกแล้ว' : 'คัดลอกข้อความ';
  }

  /* ================= การซื้อสินค้า ================= */
  var orderDraft = {};

  function openOrder(id) {
    var o = id ? C.ordersOf(m).filter(function (x) { return x.id === id; })[0] : null;
    state.orderEditId = id || null;

    orderDraft = o
      ? { at: o.at, item: o.item, shop: o.shop, channel: o.channel, amount: String(o.amount), status: o.status }
      : { at: C.TODAY, item: '', shop: C.SHOPS[0], channel: C.CHANNELS[0], amount: '', status: 'ชำระแล้ว' };

    $('cd-order-title').textContent = o ? 'แก้ไขการซื้อสินค้า' : 'บันทึกการซื้อสินค้า';
    $('cd-order-delete').hidden = !o;

    function sel(id2, opts, val) {
      return '<select class="select" id="' + id2 + '">' + opts.map(function (v) {
        return '<option value="' + esc(v) + '"' + (v === val ? ' selected' : '') + '>' + esc(v) + '</option>';
      }).join('') + '</select>';
    }
    function field(label, control) {
      return '<label class="co-field"><span class="co-field-label">' + esc(label) + '</span>' + control + '</label>';
    }

    $('cd-order-grid').innerHTML =
      field('วันที่ซื้อ', '<input type="date" class="input" id="cd-o-date" value="' + esc(orderDraft.at) + '" lang="th-TH">') +
      field('ยอดเงิน (บาท)', '<input type="number" class="input" id="cd-o-amount" min="0" inputmode="numeric" value="' + esc(orderDraft.amount) + '">') +
      '<label class="co-field is-full"><span class="co-field-label">รายการสินค้า</span>' +
        '<input type="text" class="input" id="cd-o-item" value="' + esc(orderDraft.item) + '" placeholder="เช่น ชุดผักสลัดปลอดสาร 1 กก."></label>' +
      field('ร้านที่ซื้อ', sel('cd-o-shop', C.SHOPS, orderDraft.shop)) +
      field('ช่องทางซื้อ', sel('cd-o-channel', C.CHANNELS, orderDraft.channel)) +
      field('สถานะ', sel('cd-o-status', ['ชำระแล้ว', 'รอชำระ'], orderDraft.status));

    window.TFC.openModal('cd-order-modal');
  }

  /* ================= เหตุการณ์ ================= */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var tab = t.closest('[data-tab]');
    if (tab) { state.tab = tab.getAttribute('data-tab'); renderTabs(); return renderPanel(); }

    var nv = t.closest('[data-note-view]');
    if (nv) { state.noteView = nv.getAttribute('data-note-view'); return renderPanel(); }

    /* ---- LINE ---- */
    if (t.closest('#cd-line-open')) return openLine();
    if (t.closest('#cd-line-reset')) { state.lineMsg = defaultMsg(); state.lineCopied = false; return syncLine(); }
    if (t.closest('#cd-line-copy')) {
      if (navigator.clipboard) navigator.clipboard.writeText(state.lineMsg);
      state.lineCopied = true;
      return syncLine();
    }
    if (t.closest('#cd-bind-copy')) {
      /* clipboard ล้มเหลวได้จริง (ไม่ใช่ https หรือผู้ใช้ไม่อนุญาต) จึงรอผลก่อนค่อยบอกว่าสำเร็จ */
      var bindOk = function () { state.bindCopied = true; syncLine(); };
      var bindNo = function () {
        if (window.TFC.showToast) window.TFC.showToast('คัดลอกไม่สำเร็จ — ลิงก์คือ ' + C.LINE_LINK_URL, 'warning');
      };
      if (!navigator.clipboard) return bindNo();
      navigator.clipboard.writeText('https://' + C.LINE_LINK_URL).then(bindOk, bindNo);
      return;
    }
    if (t.closest('#cd-line-send')) {
      if ($('cd-line-send').disabled) return;
      C.logLine(m, C.nextRound(m), true);
      window.TFC.closeModal('cd-line-modal');
      if (window.TFC.showToast) window.TFC.showToast('ส่งแจ้งเตือนให้ ' + m.name + ' แล้ว', 'success');
      return;
    }

    /* ---- ลิงก์รายรอบ ---- */
    var cr = t.closest('[data-copy-round]');
    if (cr) {
      var rid = cr.getAttribute('data-copy-round');
      if (navigator.clipboard) navigator.clipboard.writeText('https://' + C.surveyLink(m, rid));
      state.copiedRound = rid;
      return renderPanel();
    }

    /* ---- การซื้อสินค้า ---- */
    if (t.closest('#cd-order-add')) return openOrder(null);
    var eo = t.closest('[data-edit-order]');
    if (eo) return openOrder(eo.getAttribute('data-edit-order'));
    if (t.closest('#cd-order-delete')) {
      if (!state.orderEditId) return;
      C.removeOrder(m, state.orderEditId);
      window.TFC.closeModal('cd-order-modal');
      renderPanel();
      if (window.TFC.showToast) window.TFC.showToast('ลบรายการซื้อแล้ว', 'info');
      return;
    }

    /* ---- บันทึกติดตาม ---- */
    if (t.closest('#cd-note-save')) {
      var text = $('cd-note-text').value.trim();
      if (!text) return;
      var kind = $('cd-note-kind').value;
      var now = new Date();
      var time = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

      if (state.noteEditId) {
        C.editNote(m, state.noteEditId, { src: 'แอดมินติดตาม', at: C.TODAY, time: time, kind: kind, text: text,
          by: (window.TFC_MOCK.currentUser && window.TFC_MOCK.currentUser.name) || 'ผู้ดูแลระบบ' });
        state.noteEditId = null;
      } else {
        C.addNote(m, { at: C.TODAY, time: time, kind: kind, text: text,
          by: (window.TFC_MOCK.currentUser && window.TFC_MOCK.currentUser.name) || 'ผู้ดูแลระบบ' });
      }
      state.noteText = '';
      renderPanel();
      if (window.TFC.showToast) window.TFC.showToast('บันทึกการติดตามแล้ว', 'success');
      return;
    }

    if (t.closest('#cd-note-cancel')) {
      state.noteEditId = null;
      state.noteText = '';
      return renderPanel();
    }

    var en = t.closest('[data-edit-note]');
    if (en) {
      var note = C.notesOf(m).filter(function (x) { return x.id === en.getAttribute('data-edit-note'); })[0];
      if (!note) return;
      state.noteEditId = note.id;
      state.noteKind = note.kind;
      state.noteText = note.text;
      return renderPanel();
    }

    var dn = t.closest('[data-del-note]');
    if (dn) {
      C.removeNote(m, dn.getAttribute('data-del-note'));
      if (state.noteEditId === dn.getAttribute('data-del-note')) { state.noteEditId = null; state.noteText = ''; }
      renderPanel();
      if (window.TFC.showToast) window.TFC.showToast('ลบบันทึกแล้ว', 'info');
      return;
    }
  });

  document.addEventListener('input', function (e) {
    if (e.target.id === 'cd-line-msg') {
      state.lineMsg = e.target.value;
      state.lineCopied = false;
      $('cd-bubble-text').textContent = state.lineMsg;
      $('cd-line-copy').textContent = 'คัดลอกข้อความ';
    } else if (e.target.id === 'cd-note-text') {
      state.noteText = e.target.value;
    }
  });

  document.addEventListener('change', function (e) {
    if (e.target.id === 'cd-note-kind') state.noteKind = e.target.value;
  });

  $('cd-order-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var data = {
      at: $('cd-o-date').value || C.TODAY,
      item: $('cd-o-item').value.trim() || 'ไม่ระบุรายการ',
      shop: $('cd-o-shop').value,
      channel: $('cd-o-channel').value,
      amount: Number($('cd-o-amount').value) || 0,
      status: $('cd-o-status').value,
      by: (window.TFC_MOCK.currentUser && window.TFC_MOCK.currentUser.name) || 'ผู้ดูแลระบบ'
    };

    /* แก้ไขใช้ id เดิม ไม่ลบแล้วเพิ่มใหม่ ประวัติจึงยังชี้รายการเดิมได้ */
    if (state.orderEditId) C.editOrder(m, state.orderEditId, data);
    else C.addOrder(m, data);

    window.TFC.closeModal('cd-order-modal');
    renderPanel();
    if (window.TFC.showToast) window.TFC.showToast(state.orderEditId ? 'แก้ไขรายการซื้อแล้ว' : 'บันทึกการซื้อสินค้าแล้ว', 'success');
    state.orderEditId = null;
  });

  /* ---------- เริ่มต้น ---------- */
  render();
  if (params.get('notify') === '1') openLine();
})();
