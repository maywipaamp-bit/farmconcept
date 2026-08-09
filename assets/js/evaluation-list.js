/* TheFarmConcept — หน้าจัดการแบบประเมิน (admin/evaluations/list.html)

   หมายเหตุสำคัญเรื่องการลบ:
   เงื่อนไข "ลบได้เฉพาะชุดที่ยังไม่มีคำตอบ" ที่บังคับไว้ในไฟล์นี้เป็นแค่ชั้น UI
   ตอนต่อ backend ต้องตรวจ answers === 0 ที่ฝั่ง server อีกชั้นก่อนลบจริงเสมอ
   เพราะปุ่มที่ disable ไว้ในหน้าเว็บถูกข้ามได้ด้วยการยิง request ตรง */
(function () {
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var TABS = ['ทั้งหมด', 'ตอนลงทะเบียน', 'หลังกิจกรรม', 'ติดตามสุขภาพ', 'ฉบับร่าง'];
  var STAGES = ['ตอนลงทะเบียน', 'หลังกิจกรรม', 'ติดตามสุขภาพ'];
  var PAGE_SIZES = [10, 20, 50];

  var FORMS = [
    { id: 'f1', name: 'แบบประเมินความพึงพอใจ', stage: 'หลังกิจกรรม', status: 'ใช้งานอยู่', q: 8, answers: 486, updated: '2 ส.ค. 69' },
    { id: 'f2', name: 'แบบลงทะเบียนมาตรฐาน', stage: 'ตอนลงทะเบียน', status: 'ใช้งานอยู่', q: 6, answers: 742, updated: '28 ก.ค. 69' },
    { id: 'f3', name: 'แบบประเมินความรู้หลังอบรม', stage: 'หลังกิจกรรม', status: 'ใช้งานอยู่', q: 10, answers: 96, updated: '15 ก.ค. 69' },
    { id: 'f4', name: 'แบบประเมินสุขภาพก่อนเข้าร่วม', stage: 'ตอนลงทะเบียน', status: 'ฉบับร่าง', q: 14, answers: 0, updated: '8 ส.ค. 69' },
    { id: 'f5', name: 'แบบประเมินวิทยากร', stage: 'หลังกิจกรรม', status: 'ปิดใช้งาน', q: 5, answers: 38, updated: '20 มิ.ย. 69' },
    { id: 'f6', name: 'แบบติดตามการเปลี่ยนแปลงสุขภาพ', stage: 'ติดตามสุขภาพ', status: 'ใช้งานอยู่', q: 12, answers: 264, updated: '6 ส.ค. 69' },
    { id: 'f7', name: 'แบบคัดกรองสุขภาพเบื้องต้น', stage: 'ติดตามสุขภาพ', status: 'ฉบับร่าง', q: 16, answers: 0, updated: '8 ส.ค. 69' }
  ];

  /* ชุดคำถามของแต่ละแบบประเมิน — Preview ต้องแสดงคำถามของชุดที่กดจริง ไม่ใช่ชุดตัวอย่างชุดเดียว */
  var QUESTIONS = {
    f2: [
      { title: 'ชื่อ–นามสกุล', kind: 'text', placeholder: 'ชื่อ นามสกุล' },
      { title: 'เพศ', kind: 'choice', multi: false, choices: ['หญิง', 'ชาย', 'ไม่ระบุ'] },
      { title: 'ช่วงอายุ', kind: 'dropdown', choices: ['18–25 ปี', '26–35 ปี', '36–45 ปี', '46 ปีขึ้นไป'] },
      { title: 'รู้จักกิจกรรมจากช่องทางใด', kind: 'choice', multi: false, choices: ['Instagram', 'Facebook', 'เพื่อนแนะนำ', 'ป้ายในชุมชน'] },
      { title: 'กิจกรรมที่สนใจเพิ่มเติม', kind: 'choice', multi: true, choices: ['เวิร์กช็อปทำอาหาร', 'ปลูกต้นไม้ / ทำสวน', 'โยคะ / สมาธิ'] }
    ],
    f3: [
      { title: 'ผักปลอดสารต้องงดใช้สารเคมีทุกชนิด', kind: 'choice', multi: false, choices: ['ถูก', 'ผิด'] },
      { title: 'ระยะเวลาหมักปุ๋ยจากเศษอาหารที่เหมาะสม', kind: 'dropdown', choices: ['7 วัน', '14 วัน', '30 วัน'] },
      { title: 'สิ่งที่ได้เรียนรู้และจะนำไปใช้ต่อ', kind: 'text', placeholder: 'พิมพ์คำตอบ…' }
    ],
    f4: [
      { title: 'โรคประจำตัว', kind: 'choice', multi: true, choices: ['ไม่มี', 'ความดันโลหิตสูง', 'เบาหวาน', 'โรคหัวใจ'] },
      { title: 'น้ำหนัก (กก.)', kind: 'text', placeholder: 'เช่น 62.5' },
      { title: 'ส่วนสูง (ซม.)', kind: 'text', placeholder: 'เช่น 165' }
    ],
    f5: [
      { title: 'วิทยากรอธิบายเข้าใจง่าย', kind: 'rating' },
      { title: 'ตอบคำถามได้ชัดเจน', kind: 'rating' },
      { title: 'ความเห็นเพิ่มเติมต่อวิทยากร', kind: 'text', placeholder: 'พิมพ์คำตอบ…' }
    ],
    health: [
      { title: 'น้ำหนักปัจจุบัน (กก.)', kind: 'text', placeholder: 'เช่น 62.5' },
      { title: 'รอบเอว (ซม.)', kind: 'text', placeholder: 'เช่น 78' },
      { title: 'ใน 1 สัปดาห์ที่ผ่านมา กินผักผลไม้กี่วัน', kind: 'dropdown', choices: ['ไม่ได้กินเลย', '1–2 วัน', '3–5 วัน', 'เกือบทุกวัน'] },
      { title: 'ออกกำลังกาย 30 นาที กี่วันต่อสัปดาห์', kind: 'choice', multi: false, choices: ['ไม่ได้ออกเลย', '1–2 วัน', '3–4 วัน', '5 วันขึ้นไป'] },
      { title: 'ประเมินสุขภาพโดยรวมของตนเอง', kind: 'rating' },
      { title: 'สิ่งที่เปลี่ยนแปลงหลังเข้าร่วมโครงการ', kind: 'text', placeholder: 'พิมพ์คำตอบ…' }
    ],
    _default: [
      { title: 'ความพึงพอใจโดยรวมต่อกิจกรรมนี้', kind: 'rating' },
      { title: 'เนื้อหาตรงกับที่คาดหวัง', kind: 'rating' },
      { title: 'วิทยากรอธิบายเข้าใจง่าย', kind: 'rating' },
      { title: 'จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่', kind: 'choice', multi: false, choices: ['แนะนำ', 'ไม่แน่ใจ', 'ไม่แนะนำ'] },
      { title: 'สิ่งที่อยากให้ปรับปรุง', kind: 'text', placeholder: 'พิมพ์ความเห็นของคุณ…' }
    ]
  };

  function questionsFor(id) {
    if (id === 'f6' || id === 'f7') return QUESTIONS.health;
    return QUESTIONS[id] || QUESTIONS._default;
  }

  var state = { tab: 'ทั้งหมด', menu: null, page: 1, pageSize: PAGE_SIZES[0] };

  var THAI_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

  /* รูปแบบเดียวกับที่เก็บไว้ในข้อมูล เช่น "8 ส.ค. 69" (พ.ศ. 2 หลัก) */
  function todayThai() {
    var d = new Date();
    return d.getDate() + ' ' + THAI_MONTHS[d.getMonth()] + ' ' + String((d.getFullYear() + 543) % 100);
  }

  function filtered() {
    if (STAGES.indexOf(state.tab) > -1) {
      return FORMS.filter(function (f) { return f.stage === state.tab; });
    }
    if (state.tab === 'ฉบับร่าง') {
      return FORMS.filter(function (f) { return f.status === 'ฉบับร่าง'; });
    }
    return FORMS;
  }

  function formById(id) {
    return FORMS.filter(function (f) { return f.id === id; })[0];
  }

  /* ---------- หัวหน้า ---------- */
  window.TFC.renderPageHeader('el-page-header', {
    title: 'แบบประเมิน',
    actions: [
      { label: 'สร้างแบบประเมิน', variant: 'primary', href: 'create.html',
        icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>' }
    ]
  });

  /* ---------- แท็บกรอง ---------- */
  function renderTabs() {
    $('el-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="el-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + '</button>';
    }).join('');
  }

  /* ---------- ตาราง ---------- */
  var STATUS_CLASS = { 'ใช้งานอยู่': 'is-live', 'ฉบับร่าง': 'is-draft', 'ปิดใช้งาน': 'is-off' };

  /* รหัสสั้นสำหรับอ้างอิงเวลาคุยกัน — ยึดลำดับที่สร้าง ไม่ใช่ลำดับที่แสดงในตาราง
     สลับแท็บกรองแล้วรหัสจึงไม่เปลี่ยน */
  function codeOf(f) {
    return 'EVL-' + String(FORMS.indexOf(f) + 1).padStart(2, '0');
  }

  function rowHtml(f) {
    var open = state.menu === f.id;
    return '<div class="el-tr" data-row="' + f.id + '">' +
      '<div class="el-cell el-num el-code">' + esc(codeOf(f)) + '</div>' +
      '<div class="el-name-cell">' +
        '<span class="el-dot ' + STATUS_CLASS[f.status] + '"></span>' +
        '<span class="el-name-text">' +
          '<a class="el-name" href="create.html?id=' + esc(f.id) + '">' + esc(f.name) + '</a>' +
          '<span class="el-status ' + STATUS_CLASS[f.status] + '">' + esc(f.status) + '</span>' +
        '</span>' +
      '</div>' +
      '<div class="el-cell">' + esc(f.stage) + '</div>' +
      '<div class="el-cell el-num text-right">' + f.q + ' ข้อ</div>' +
      '<div class="el-cell el-num text-right">' + f.answers.toLocaleString('th-TH') + '</div>' +
      '<div class="el-cell el-num">' + esc(f.updated) + '</div>' +
      '<div class="el-actions">' +
        '<button type="button" class="el-menu-btn' + (open ? ' is-on' : '') + '" data-menu="' + f.id +
          '" aria-haspopup="menu" aria-expanded="' + open + '" aria-label="เมนูจัดการ ' + esc(f.name) + '">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>' +
        '</button>' +
        (open ? menuHtml(f) : '') +
      '</div>' +
      '</div>';
  }

  /* ชุดติดตามสุขภาพส่งตามรอบเวลา ไม่ผูกกับกิจกรรม จึงต้องมีลิงก์ตรงให้ส่งหากลุ่มตัวอย่างเอง
     ส่วนชุดที่ผูกกับกิจกรรม ผู้ตอบเข้าผ่านหน้ากิจกรรมอยู่แล้ว ไม่ต้องมีลิงก์แยก */
  function answerLink(f) {
    return 'farmconcept.th/s/' + codeOf(f).toLowerCase();
  }

  /* สถานะที่เปลี่ยนไปได้จากสถานะปัจจุบัน — แสดงเฉพาะปลายทางที่เปลี่ยนได้จริง
     ไม่ต้องโชว์ทั้ง 3 สถานะแล้วให้กดอันที่เป็นอยู่แล้ว
     ชุดที่มีคำตอบแล้วห้ามย้อนกลับเป็นฉบับร่าง เพราะคำตอบที่เก็บไว้จะไม่มีแบบฟอร์มที่เผยแพร่รองรับ */
  function statusMoves(f) {
    var moves = [];
    if (f.status !== 'ใช้งานอยู่') moves.push({ to: 'ใช้งานอยู่', label: 'เปิดใช้งาน', icon: 'play' });
    if (f.status !== 'ปิดใช้งาน' && f.status !== 'ฉบับร่าง') moves.push({ to: 'ปิดใช้งาน', label: 'ปิดใช้งาน', icon: 'pause' });
    if (f.status !== 'ฉบับร่าง' && f.answers === 0) moves.push({ to: 'ฉบับร่าง', label: 'เก็บเป็นฉบับร่าง', icon: 'draft' });
    return moves;
  }

  var STATUS_ICONS = {
    play: '<circle cx="12" cy="12" r="8.5"/><path d="M10.4 9.2l4.4 2.8-4.4 2.8z"/>',
    pause: '<circle cx="12" cy="12" r="8.5"/><path d="M10.2 9.5v5M13.8 9.5v5"/>',
    draft: '<path d="M5 4.5h9l4.5 4.5v10.5H5z"/><path d="M14 4.5V9h4.5"/>'
  };

  /* ลบได้เฉพาะชุดที่ยังไม่มีคำตอบ — ชุดที่มีคำตอบแล้วต้องเก็บไว้เพื่อไม่ให้ข้อมูลที่เก็บมากำพร้า */
  function menuHtml(f) {
    var canDelete = f.answers === 0;
    var moves = statusMoves(f);

    return '<div class="el-menu" role="menu">' +
      moves.map(function (m) {
        return '<button type="button" class="el-menu-item" role="menuitem" data-status="' + f.id + ':' + esc(m.to) + '">' +
          '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' +
          STATUS_ICONS[m.icon] + '</svg>' + esc(m.label) + '</button>';
      }).join('') +
      '<span class="el-menu-sep"></span>' +
      (f.stage === 'ติดตามสุขภาพ'
        ? '<button type="button" class="el-menu-item" role="menuitem" data-copy="' + f.id + '">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/></svg>' +
            'คัดลอกลิงก์สำหรับตอบ</button>'
        : '') +
      '<button type="button" class="el-menu-item" role="menuitem" data-preview="' + f.id + '">' +
        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M10.8 5.4h2.4"/></svg>' +
        'Preview มือถือ</button>' +
      '<a class="el-menu-item" role="menuitem" href="create.html?id=' + esc(f.id) + '">' +
        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M13.5 6.5l4 4"/></svg>' +
        'แก้ไขแบบประเมิน</a>' +
      /* เหตุผลที่ลบไม่ได้ย้ายไปอยู่ใน title แทนบรรทัดอธิบาย เพื่อให้เมนูสะอาด
         แต่ยังชี้เมาส์อ่านได้ว่าทำไมปุ่มถึงกดไม่ลง */
      '<button type="button" class="el-menu-item is-danger" role="menuitem" data-delete="' + f.id + '"' +
        (canDelete ? '' : ' disabled title="ลบไม่ได้ — ชุดนี้มีคำตอบที่เก็บไว้แล้ว"') + '>' +
        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M9.5 7V5.5h5V7M7 7l.8 12h8.4L17 7"/></svg>' +
        'ลบแบบประเมิน</button>' +
      '</div>';
  }

  var EMPTY_HTML = '<div class="el-empty">' +
    '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>' +
    '<span class="el-empty-title">ไม่มีแบบประเมินในกลุ่มนี้</span>' +
    '</div>';

  function render() {
    var list = filtered();
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;
    var pageRows = list.slice(start, start + state.pageSize);

    $('el-rows').innerHTML = pageRows.length ? pageRows.map(rowHtml).join('') : EMPTY_HTML;
    $('el-count').textContent = 'แสดง ' + list.length + ' จาก ' + FORMS.length + ' ชุด';

    window.TFC.renderPagination('el-pagination', {
      page: state.page,
      pageSize: state.pageSize,
      total: list.length,
      pageSizeOptions: PAGE_SIZES,
      onChange: function (p) { state.page = p; render(); },
      onPageSizeChange: function (size) { state.pageSize = size; state.page = 1; render(); }
    });

    flipMenuIfClipped();
  }

  /* กรอบตารางมี overflow-x: auto ซึ่งบังคับให้ overflow-y เป็น auto ตามไปด้วย
     เมนูของแถวท้ายๆ จึงถูกตัดที่ขอบล่างของกรอบ ถ้าไม่พอที่ให้เปิดลงล่างก็พลิกขึ้นบนแทน */
  function flipMenuIfClipped() {
    var menu = document.querySelector('.el-menu');
    if (!menu) return;
    var scroller = document.querySelector('.el-table-scroll');
    if (!scroller) return;

    menu.classList.remove('is-up');
    if (menu.getBoundingClientRect().bottom > scroller.getBoundingClientRect().bottom) {
      menu.classList.add('is-up');
    }
  }

  /* ---------- Preview มือถือ ---------- */
  function questionHtml(q, i) {
    var body = '';
    if (q.kind === 'rating') {
      body = '<div class="el-q-scale">' + [1, 2, 3, 4, 5].map(function (s) {
        return '<span class="el-q-scale-box">' + s + '</span>';
      }).join('') + '</div>';
    } else if (q.kind === 'choice') {
      body = '<div class="el-q-choices">' + (q.choices || []).map(function (c) {
        return '<span class="el-q-choice">' +
          '<span class="el-q-mark' + (q.multi ? ' is-box' : '') + '"></span>' + esc(c) + '</span>';
      }).join('') + '</div>';
    } else if (q.kind === 'dropdown') {
      body = '<div class="el-q-select"><span>' + esc((q.choices || [])[0] || 'เลือกตัวเลือก') + '</span>' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></div>';
    } else {
      body = '<div class="el-q-text">' + esc(q.placeholder || 'พิมพ์คำตอบ…') + '</div>';
    }

    return '<div class="el-q">' +
      '<span class="el-q-title">' + (i + 1) + '. ' + esc(q.title) + '</span>' +
      body + '</div>';
  }

  function openPreview(id) {
    var f = formById(id);
    if (!f) return;

    /* ชุดที่ทำหลังกิจกรรมเก็บแบบไม่ระบุตัวตน ที่เหลือผูกกับผู้ตอบรายคน */
    var anon = f.stage === 'หลังกิจกรรม' ? 'ไม่ระบุตัวตน' : 'ระบุตัวตน';
    $('el-preview-chip').textContent = f.stage + ' · ' + anon;
    $('el-phone-title').textContent = f.name;
    $('el-phone-sub').textContent = f.stage === 'ติดตามสุขภาพ'
      ? 'โครงการติดตามสุขภาพชุมชน'
      : 'ปลูกผักปลอดสารสำหรับครอบครัว';
    $('el-phone-body').innerHTML = questionsFor(id).map(questionHtml).join('');
    $('el-phone-body').scrollTop = 0;

    $('el-preview').hidden = false;
    /* ล็อกสกรอลล์แบบเดียวกับ modal.js กลางของระบบ */
    document.body.style.overflow = 'hidden';
    $('el-preview-close').focus();
  }

  function closePreview() {
    $('el-preview').hidden = true;
    document.body.style.overflow = '';
  }

  /* ---------- เหตุการณ์ ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var tab = t.closest('[data-tab]');
    if (tab) {
      state.tab = tab.getAttribute('data-tab');
      state.menu = null;
      state.page = 1;
      renderTabs();
      return render();
    }

    /* เปิดเมนูได้ครั้งละแถวเดียว กดซ้ำที่ปุ่มเดิมคือปิด */
    var menuBtn = t.closest('[data-menu]');
    if (menuBtn) {
      var id = menuBtn.getAttribute('data-menu');
      state.menu = state.menu === id ? null : id;
      return render();
    }

    var st = t.closest('[data-status]');
    if (st) {
      var sp = st.getAttribute('data-status').split(':');
      var sf = formById(sp[0]);
      state.menu = null;
      if (sf) {
        sf.status = sp[1];
        /* เปลี่ยนสถานะถือเป็นการแก้ไข วันที่แก้ไขล่าสุดจึงต้องขยับตาม */
        sf.updated = todayThai();
      }
      render();
      if (sf && window.TFC.showToast) window.TFC.showToast(sf.name + ' → ' + sf.status, 'success');
      return;
    }

    var copy = t.closest('[data-copy]');
    if (copy) {
      var cf = formById(copy.getAttribute('data-copy'));
      state.menu = null;
      render();
      if (!cf) return;
      var url = 'https://' + answerLink(cf);
      if (navigator.clipboard) navigator.clipboard.writeText(url);
      if (window.TFC.showToast) window.TFC.showToast('คัดลอกลิงก์ ' + answerLink(cf) + ' แล้ว', 'success');
      return;
    }

    var pv = t.closest('[data-preview]');
    if (pv) {
      var pid = pv.getAttribute('data-preview');
      state.menu = null;
      render();
      return openPreview(pid);
    }

    var del = t.closest('[data-delete]');
    if (del) {
      if (del.disabled) return;
      var df = formById(del.getAttribute('data-delete'));
      state.menu = null;
      render();
      if (df && window.TFC.showToast) window.TFC.showToast('ลบ ' + df.name + ' เรียบร้อย', 'success');
      return;
    }

    if (t.closest('#el-preview-close') || t === $('el-preview')) return closePreview();

    if (state.menu && !t.closest('.el-actions')) {
      state.menu = null;
      render();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (!$('el-preview').hidden) return closePreview();
    if (state.menu) { state.menu = null; render(); }
  });

  /* ---------- เริ่มต้น ---------- */
  renderTabs();
  render();
})();
