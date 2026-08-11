/* TheFarmConcept — หน้าสร้างแบบประเมิน (admin/evaluations/create.html)

   วิธีเรนเดอร์: รายการคำถามถูกสร้างใหม่เฉพาะตอนที่โครงสร้างเปลี่ยน (เพิ่ม/ลบ/สลับลำดับ/เปลี่ยนชนิด)
   ส่วนการพิมพ์ในช่องข้อความจะอัปเดตแค่ state + แผงขวา ไม่แตะ DOM ของรายการ
   ไม่งั้นช่องที่กำลังพิมพ์อยู่จะเสีย focus ทุกตัวอักษร */
(function () {
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var KIND_LABELS = {
    rating: 'ให้คะแนน 1–5',
    single: 'เลือก 1 ข้อ',
    multi: 'เลือกหลายข้อ',
    dropdown: 'เลือกจากรายการ',
    text: 'ข้อความ'
  };
  var KIND_ORDER = ['rating', 'single', 'multi', 'dropdown', 'text'];

  var STAGES = [
    { label: 'ตอนลงทะเบียน', hint: 'ผู้เข้าร่วมกรอกตอนจองที่นั่ง' },
    { label: 'หลังกิจกรรม', hint: 'ส่งให้ผู้เข้าร่วมหลังจบงาน' },
    { label: 'ติดตามสุขภาพ', hint: 'ไม่ผูกกับกิจกรรม ส่งตามรอบเวลา' }
  ];
  var STANDALONE = 'ติดตามสุขภาพ';

  var AUTOSAVE_MS = 60000;

  var state = {
    name: '',
    desc: '',
    stage: 'หลังกิจกรรม',
    nextId: 4,
    /* คำถามที่กำลังแก้อยู่ — แบบเดียวกับ Google Form คือขยายทีละใบ ที่เหลือยุบเป็นตัวอย่าง
       ทำให้เห็นทั้งชุดในจอเดียวโดยไม่ต้องเลื่อนผ่านช่องกรอกของทุกข้อ */
    activeId: null,
    dirty: false,
    /* ตั้งต้นด้วยหัวข้อส่วน 1 ส่วนพร้อมคำถาม 2 ข้อในนั้น
       ให้เห็นตั้งแต่แรกว่าจัดคำถามเป็นตอนได้ และเลขข้อจะเป็น 1.1 / 1.2
       ตั้งชื่อส่วนไว้ให้ด้วย ไม่งั้นเช็กลิสต์ข้อ "ทุกคำถามและหัวข้อส่วนมีข้อความ" จะไม่ผ่านตั้งแต่เปิดหน้า */
    items: [
      { id: 1, title: 'ความคิดเห็นต่อกิจกรรม', kind: 'section', required: false, choices: [] },
      { id: 2, title: 'ความพึงพอใจโดยรวมต่อกิจกรรมนี้', kind: 'rating', required: true, choices: [] },
      { id: 3, title: 'จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่', kind: 'single', required: true, choices: ['แนะนำ', 'ไม่แน่ใจ', 'ไม่แนะนำ'] }
    ]
  };

  function isStandalone() { return state.stage === STANDALONE; }
  function itemById(id) { return state.items.filter(function (i) { return String(i.id) === String(id); })[0]; }
  /* เลือกจากรายการใช้ชุดตัวเลือกเหมือนแบบเลือก 1 ข้อ ต่างแค่วิธีแสดงให้ผู้ตอบ */
  function hasChoices(kind) { return kind === 'single' || kind === 'multi' || kind === 'dropdown'; }

  /* วงกลม = เลือก 1 ข้อ · สี่เหลี่ยม = เลือกหลายข้อ · สี่เหลี่ยมเทา = เลือกจากรายการ */
  function markHtml(kind) {
    var cls = kind === 'multi' ? ' is-box' : (kind === 'dropdown' ? ' is-box is-muted' : '');
    return '<span class="ec-mark' + cls + '"></span>';
  }

  /* เลขข้อ: ส่วนได้ "ตอนที่ N" · คำถามในส่วนได้ "N.M" · คำถามนอกส่วนได้ "M" */
  function numbering() {
    var qn = 0, sn = 0;
    return state.items.map(function (q) {
      if (q.kind === 'section') {
        sn++; qn = 0;
        return { no: '', sectionNo: 'ตอนที่ ' + sn, inSection: true };
      }
      qn++;
      return { no: sn > 0 ? sn + '.' + qn : String(qn), sectionNo: '', inSection: sn > 0 };
    });
  }

  function questionCount() {
    return state.items.filter(function (i) { return i.kind !== 'section'; }).length;
  }

  function checklist() {
    var filled = state.items.filter(function (i) { return i.title.trim().length > 0; }).length;
    return [
      { label: 'ตั้งชื่อชุดแบบประเมิน', ok: state.name.trim().length > 0 },
      { label: 'เลือกว่าใช้ตอนไหน', ok: !!state.stage },
      { label: 'มีคำถามอย่างน้อย 1 ข้อ', ok: questionCount() > 0 },
      { label: 'ทุกคำถามและหัวข้อส่วนมีข้อความ', ok: state.items.length > 0 && filled === state.items.length },
      { label: 'คำถามแบบเลือกมีตัวเลือกครบ', ok: state.items.every(function (i) {
          return !hasChoices(i.kind) || i.choices.filter(function (c) { return c.trim(); }).length >= 2;
        }) }
    ];
  }

  /* ================= ส่วนที่ 1 ================= */
  function renderStages() {
    $('ec-stages').innerHTML = STAGES.map(function (s) {
      var on = state.stage === s.label;
      return '<button type="button" class="ec-pick' + (on ? ' is-on' : '') + '" data-stage="' + esc(s.label) + '">' +
        '<span class="ec-pick-title">' + esc(s.label) + '</span>' +
        '<span class="ec-pick-hint">' + esc(s.hint) + '</span>' +
        '</button>';
    }).join('');
  }


  /* ================= ส่วนที่ 2 — รายการคำถาม ================= */
  function renderItems() {
    var nums = numbering();

    $('ec-items').innerHTML = state.items.map(function (q, i) {
      var n = nums[i];
      return q.kind === 'section' ? sectionHtml(q, n) : questionHtml(q, n);
    }).join('');

    $('ec-summary').textContent = questionCount() + ' ข้อ · ' +
      state.items.filter(function (i2) { return i2.kind === 'section'; }).length + ' หัวข้อส่วน · บังคับตอบ ' +
      state.items.filter(function (i2) { return i2.required && i2.kind !== 'section'; }).length + ' ข้อ';
  }

  /* ที่จับลาก — แทนปุ่มลูกศรสองปุ่มเดิม ทำให้แถวเตี้ยลงและใช้ลากสลับลำดับได้
     ยังกดลูกศรขึ้น/ลงบนคีย์บอร์ดได้เมื่อโฟกัสอยู่ที่ตัวจับ สำหรับคนที่ลากไม่ได้ */
  function gripHtml(id) {
    return '<button type="button" class="ec-grip" data-grip="' + id + '"' +
      ' aria-label="ลากเพื่อสลับลำดับ หรือกดลูกศรขึ้น/ลง">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
      '<circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>' +
      '<circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>' +
      '<circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></button>';
  }

  /* แถบเครื่องมือลอยข้างการ์ดที่กำลังแก้ — แบบเดียวกับ Google Form
     ใส่เฉพาะปุ่มที่ระบบนี้ทำได้จริง (เพิ่มคำถาม · ทำสำเนา · เพิ่มหัวข้อส่วน)
     ปุ่มรูปภาพ/วิดีโอ/นำเข้าคำถามของ Google Form ยังไม่มีที่เก็บข้อมูลรองรับ จึงไม่ใส่ปุ่มหลอก */
  function toolbarHtml(id) {
    var btn = function (act, label, path) {
      return '<button type="button" class="ec-tool" data-tool="' + act + ':' + id + '" title="' + esc(label) + '" aria-label="' + esc(label) + '">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' + path + '</svg>' +
        '</button>';
    };
    return '<div class="ec-tools">' +
      btn('add', 'เพิ่มคำถาม', '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>') +
      btn('dup', 'ทำสำเนาคำถาม', '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/>') +
      btn('section', 'เพิ่มหัวข้อส่วน', '<rect x="3" y="4" width="18" height="6" rx="1.5"/><path d="M3 14h18M3 19h12"/>') +
      '</div>';
  }

  function sectionHtml(q, n) {
    var on = String(state.activeId) === String(q.id);
    return '<div class="ec-card ec-card-section' + (on ? ' is-active' : '') + '" data-row="' + q.id + '" data-activate="' + q.id + '">' +
      gripHtml(q.id) +
      '<div class="ec-card-body">' +
        (on
          ? '<div class="ec-section-line">' +
              '<span class="ec-section-badge">' + esc(n.sectionNo) + '</span>' +
              '<input type="text" class="input ec-title-input" value="' + esc(q.title) + '"' +
                ' placeholder="ชื่อหัวข้อส่วน เช่น ข้อมูลสุขภาพ" data-title="' + q.id + '">' +
            '</div>' +
            footHtml(q, true)
          : '<div class="ec-section-line">' +
              '<span class="ec-section-badge">' + esc(n.sectionNo) + '</span>' +
              '<span class="ec-view-title">' + esc(q.title.trim() || 'หัวข้อส่วน') + '</span>' +
            '</div>') +
      '</div>' +
      (on ? toolbarHtml(q.id) : '') +
      '</div>';
  }

  /* แถบล่างของการ์ดที่เปิดอยู่ — ทำสำเนา / ลบ / สวิตช์บังคับตอบ */
  function footHtml(q, isSection) {
    return '<div class="ec-card-foot">' +
      '<button type="button" class="ec-foot-btn" data-tool="dup:' + q.id + '" title="ทำสำเนา" aria-label="ทำสำเนา">' +
        '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/></svg></button>' +
      '<button type="button" class="ec-foot-btn" data-remove="' + q.id + '" title="ลบ" aria-label="ลบ">' +
        '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M9.5 7V5.5h5V7M7 7l.8 12h8.4L17 7"/></svg></button>' +
      (isSection ? '' :
        '<span class="ec-foot-sep"></span>' +
        '<label class="ec-req-toggle">' +
          '<span>จำเป็น</span>' +
          '<span class="switch switch-sm">' +
            '<input type="checkbox" data-required="' + q.id + '"' + (q.required ? ' checked' : '') + '>' +
            '<span class="switch-track"></span>' +
          '</span>' +
        '</label>') +
      '</div>';
  }

  /* การ์ดคำถาม 2 โหมด — เปิดอยู่ = แก้ได้ · ยุบ = แสดงอย่างเดียวเหมือนที่ผู้ตอบเห็น
     เหตุผลเดียวกับ Google Form: เห็นทั้งชุดได้ในจอเดียว ไม่ต้องเลื่อนผ่านช่องกรอกของทุกข้อ */
  function questionHtml(q, n) {
    var on = String(state.activeId) === String(q.id);
    return '<div class="ec-card' + (n.inSection ? ' is-nested' : '') + (on ? ' is-active' : '') +
      '" data-row="' + q.id + '" data-activate="' + q.id + '">' +
      gripHtml(q.id) +
      '<div class="ec-card-body">' + (on ? editBody(q, n) : viewBody(q, n)) + '</div>' +
      (on ? toolbarHtml(q.id) : '') +
      '</div>';
  }

  function editBody(q, n) {
    return '<div class="ec-q-head">' +
        '<span class="ec-card-no">' + esc(n.no) + '.</span>' +
        '<input type="text" class="input ec-title-input" value="' + esc(q.title) + '"' +
          ' placeholder="พิมพ์คำถาม" data-title="' + q.id + '">' +
        '<select class="select ec-type" data-kind="' + q.id + '" aria-label="ชนิดคำถาม">' +
          KIND_ORDER.map(function (k) {
            return '<option value="' + k + '"' + (q.kind === k ? ' selected' : '') + '>' + esc(KIND_LABELS[k]) + '</option>';
          }).join('') +
        '</select>' +
      '</div>' +
      (hasChoices(q.kind) ? choicesHtml(q) : '') +
      (q.kind === 'rating' ? ratingHtml() : '') +
      (q.kind === 'text' ? '<div class="ec-text-hint">ผู้ตอบจะพิมพ์คำตอบเองในช่องนี้</div>' : '') +
      footHtml(q, false);
  }

  function viewBody(q, n) {
    return '<div class="ec-view-head">' +
        '<span class="ec-card-no">' + esc(n.no) + '.</span>' +
        '<span class="ec-view-title">' + esc(q.title.trim() || 'คำถามที่ยังไม่ได้พิมพ์') + '</span>' +
        (q.required ? '<span class="ec-view-req">*</span>' : '') +
      '</div>' +
      (hasChoices(q.kind)
        ? '<div class="ec-view-choices">' + q.choices.map(function (c) {
            return '<span class="ec-view-choice">' + markHtml(q.kind) + esc(c) + '</span>';
          }).join('') + '</div>'
        : '') +
      (q.kind === 'rating' ? ratingHtml() : '') +
      (q.kind === 'text' ? '<div class="ec-view-text">พิมพ์คำตอบ…</div>' : '');
  }

  function choicesHtml(q) {
    return '<div class="ec-choices">' +
      q.choices.map(function (c, ci) {
        return '<div class="ec-choice">' +
          markHtml(q.kind) +
          '<input type="text" class="input ec-choice-input" value="' + esc(c) + '" placeholder="ตัวเลือก"' +
            ' data-choice="' + q.id + ':' + ci + '">' +
          '<button type="button" class="ec-choice-remove" data-remove-choice="' + q.id + ':' + ci + '" aria-label="ลบตัวเลือก">' +
            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>' +
          '</div>';
      }).join('') +
      /* แถวสุดท้ายเลียนแบบ Google Form: ช่องเปล่าที่กดแล้วกลายเป็นตัวเลือกใหม่
         คู่กับทางลัดเพิ่ม "อื่น ๆ" ซึ่งเป็นตัวเลือกที่ผู้ตอบพิมพ์เองได้ */
      '<div class="ec-choice ec-choice-add">' +
        markHtml(q.kind) +
        '<button type="button" class="ec-add-choice" data-add-choice="' + q.id + '">เพิ่มตัวเลือก</button>' +
        '<span class="ec-choice-or">หรือ</span>' +
        '<button type="button" class="ec-add-other" data-add-other="' + q.id + '">เพิ่ม "อื่น ๆ"</button>' +
      '</div>' +
      '</div>';
  }

  function ratingHtml() {
    return '<div class="ec-scale">' +
      [1, 2, 3, 4, 5].map(function (s) { return '<span class="ec-scale-box">' + s + '</span>'; }).join('') +
      '<span class="ec-scale-hint">1 = น้อยที่สุด · 5 = มากที่สุด</span>' +
      '</div>';
  }

  /* ================= แผงขวา ================= */
  function renderPreview() {
    var nameEl = $('ec-preview-name');
    nameEl.textContent = state.name.trim() || 'ชื่อชุดแบบประเมิน';
    nameEl.classList.toggle('is-empty', !state.name.trim());

    $('ec-preview-desc').textContent = state.desc.trim() ||
      (isStandalone() ? 'ส่งตามรอบเวลา ไม่ผูกกับกิจกรรม' : 'คำอธิบายจะแสดงตรงนี้');

    var nums = numbering();
    $('ec-preview-list').innerHTML = state.items.map(function (q, i) {
      var n = nums[i];
      if (q.kind === 'section') {
        return '<div class="ec-pv-section">' +
          '<span class="ec-pv-section-no">' + esc(n.sectionNo) + '</span>' +
          '<span class="ec-pv-section-title">' + esc(q.title.trim() || 'หัวข้อส่วน') + '</span>' +
          '</div>';
      }
      return '<div class="ec-pv-q' + (n.inSection ? ' is-nested' : '') + '">' +
        '<span class="ec-pv-title">' + esc(n.no + '. ' + (q.title.trim() || 'คำถามที่ยังไม่ได้พิมพ์') + (q.required ? ' *' : '')) + '</span>' +
        (q.kind === 'rating'
          ? '<div class="ec-scale">' + [1, 2, 3, 4, 5].map(function (s) { return '<span class="ec-scale-box">' + s + '</span>'; }).join('') + '</div>'
          : '') +
        (q.kind === 'dropdown'
          ? '<div class="ec-pv-select">' +
              '<span>' + esc((q.choices[0] || '').trim() || 'เลือกคำตอบ') + '</span>' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>' +
            '</div>'
          : '') +
        (hasChoices(q.kind) && q.kind !== 'dropdown'
          ? '<div class="ec-pv-choices">' + q.choices.map(function (c) {
              return '<span class="ec-pv-choice">' + markHtml(q.kind) + esc(c) + '</span>';
            }).join('') + '</div>'
          : '') +
        (q.kind === 'text' ? '<div class="ec-pv-text">พิมพ์คำตอบ…</div>' : '') +
        '</div>';
    }).join('');
  }

  function renderChecklist() {
    var list = checklist();
    var okCount = list.filter(function (d) { return d.ok; }).length;
    var ready = okCount === list.length;

    $('ec-checklist').innerHTML = list.map(function (d) {
      return '<span class="ec-check' + (d.ok ? ' is-ok' : '') + '">' +
        '<span class="ec-check-dot">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(d.label) + '</span>';
    }).join('');

    var badge = $('ec-done-badge');
    badge.textContent = okCount + '/' + list.length;
    badge.classList.toggle('is-ready', ready);

    $('ec-progress-text').textContent = ready
      ? 'พร้อมบันทึกและเปิดใช้งาน'
      : 'ยังเหลือ ' + (list.length - okCount) + ' ข้อที่ต้องแก้ก่อนเปิดใช้งาน';
    $('ec-save').disabled = !ready;
  }

  /* อัปเดตทุกอย่างที่คำนวณจาก state ยกเว้นรายการคำถาม (ซึ่งมี input ที่อาจกำลังพิมพ์อยู่) */
  function syncDerived() {
    renderPreview();
    renderChecklist();
    $('ec-summary').textContent = questionCount() + ' ข้อ · ' +
      state.items.filter(function (i) { return i.kind === 'section'; }).length + ' หัวข้อส่วน · บังคับตอบ ' +
      state.items.filter(function (i) { return i.required && i.kind !== 'section'; }).length + ' ข้อ';
  }

  function syncAll() {
    renderStages();
    renderItems();
    syncDerived();
  }

  function touch(structural) {
    state.dirty = true;
    if (structural) renderItems();
    syncDerived();
  }

  /* ================= เพิ่ม / ทำสำเนา =================
     แทรกต่อจากใบที่กำลังแก้อยู่ ไม่ใช่ต่อท้ายสุด — คนมักเพิ่มคำถามตรงจุดที่กำลังคิดอยู่
     แล้วเปิดใบใหม่ให้ทันที ผู้ใช้พิมพ์ต่อได้เลยโดยไม่ต้องกดอีกครั้ง */
  function insertAfter(id, item) {
    var at = indexOfId(id);
    if (at < 0) state.items.push(item); else state.items.splice(at + 1, 0, item);
    state.activeId = item.id;
    touch(true);
    focusActiveTitle();
  }

  function addItem(afterId, kind) {
    insertAfter(afterId, {
      id: state.nextId++,
      title: '',
      kind: kind,
      required: false,
      choices: hasChoices(kind) ? ['ตัวเลือกที่ 1', 'ตัวเลือกที่ 2'] : []
    });
  }

  function duplicateItem(id) {
    var src = itemById(id);
    if (!src) return;
    insertAfter(id, {
      id: state.nextId++,
      title: src.title,
      kind: src.kind,
      required: src.required,
      choices: src.choices.slice()
    });
  }

  /* วางเคอร์เซอร์ในช่องคำถามของใบที่เพิ่งเปิด — ต้องรอ DOM ใหม่วาดเสร็จก่อน */
  function focusActiveTitle() {
    var el = document.querySelector('.ec-card.is-active .ec-title-input');
    if (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); }
  }

  function indexOfId(id) {
    return state.items.map(function (i) { return String(i.id); }).indexOf(String(id));
  }

  function move(id, dir) {
    var idx = indexOfId(id);
    var to = idx + dir;
    if (idx < 0 || to < 0 || to >= state.items.length) return;
    var tmp = state.items[idx];
    state.items[idx] = state.items[to];
    state.items[to] = tmp;
    touch(true);
    /* คืนโฟกัสให้ตัวจับของแถวเดิม จะได้กดลูกศรรัวๆ เลื่อนต่อได้ */
    var grip = document.querySelector('[data-grip="' + id + '"]');
    if (grip) grip.focus();
  }

  /* ---------- ลากสลับลำดับ ----------
     ตั้ง draggable ตอนกดที่ตัวจับเท่านั้น ไม่งั้นการลากเลือกข้อความในช่องกรอก
     จะกลายเป็นการลากทั้งแถวไปด้วย */
  var dragId = null;

  document.addEventListener('mousedown', function (e) {
    var grip = e.target.closest('[data-grip]');
    var row = grip && grip.closest('[data-row]');
    if (row) row.setAttribute('draggable', 'true');
  });

  document.addEventListener('mouseup', clearDraggable);

  function clearDraggable() {
    var rows = document.querySelectorAll('[data-row][draggable]');
    Array.prototype.forEach.call(rows, function (r) { r.removeAttribute('draggable'); });
  }

  document.addEventListener('dragstart', function (e) {
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row || !row.getAttribute('draggable')) return;
    dragId = row.getAttribute('data-row');
    row.classList.add('is-dragging');
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      /* Firefox ไม่เริ่มลากถ้าไม่ได้ตั้งข้อมูลไว้ */
      e.dataTransfer.setData('text/plain', dragId);
    }
  });

  document.addEventListener('dragover', function (e) {
    if (dragId === null) return;
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row) return;
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    clearDropMarks();
    if (row.getAttribute('data-row') === dragId) return;
    var box = row.getBoundingClientRect();
    row.classList.add(e.clientY < box.top + box.height / 2 ? 'is-drop-before' : 'is-drop-after');
  });

  document.addEventListener('drop', function (e) {
    if (dragId === null) return;
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row) return;
    e.preventDefault();

    var targetId = row.getAttribute('data-row');
    var before = row.classList.contains('is-drop-before');
    endDrag();
    if (targetId === dragId) return;

    var from = indexOfId(dragId);
    var moved = state.items.splice(from, 1)[0];
    var to = indexOfId(targetId);
    state.items.splice(before ? to : to + 1, 0, moved);
    touch(true);
  });

  document.addEventListener('dragend', endDrag);

  function endDrag() {
    var dragging = document.querySelector('.is-dragging');
    if (dragging) dragging.classList.remove('is-dragging');
    clearDropMarks();
    clearDraggable();
    dragId = null;
  }

  function clearDropMarks() {
    var marked = document.querySelectorAll('.is-drop-before, .is-drop-after');
    Array.prototype.forEach.call(marked, function (r) {
      r.classList.remove('is-drop-before', 'is-drop-after');
    });
  }

  /* ================= เหตุการณ์ ================= */
  $('ec-name').addEventListener('input', function () { state.name = this.value; touch(false); });
  $('ec-desc').addEventListener('input', function () { state.desc = this.value; touch(false); });

  /* พิมพ์ในช่องคำถาม/ตัวเลือก -> อัปเดตแค่ state กับแผงขวา ไม่เรนเดอร์รายการใหม่ */
  document.addEventListener('input', function (e) {
    var title = e.target.closest('[data-title]');
    if (title) {
      var q = itemById(title.getAttribute('data-title'));
      if (q) { q.title = title.value; touch(false); }
      return;
    }
    var choice = e.target.closest('[data-choice]');
    if (choice) {
      var parts = choice.getAttribute('data-choice').split(':');
      var item = itemById(parts[0]);
      if (item) { item.choices[Number(parts[1])] = choice.value; touch(false); }
    }
  });

  document.addEventListener('change', function (e) {
    var req = e.target.closest('[data-required]');
    if (req) {
      var q = itemById(req.getAttribute('data-required'));
      if (q) { q.required = req.checked; touch(false); }
      return;
    }

    /* ชนิดคำถามเป็น dropdown แบบ Google Form ไม่ใช่ชิปเรียงกัน — ประหยัดที่และเลือกได้อันเดียวชัดเจน */
    var kind = e.target.closest('[data-kind]');
    if (kind) {
      var kq = itemById(kind.getAttribute('data-kind'));
      if (!kq) return;
      kq.kind = kind.value;
      if (hasChoices(kq.kind)) {
        if (!kq.choices.length) kq.choices = ['ตัวเลือกที่ 1', 'ตัวเลือกที่ 2'];
      } else {
        kq.choices = [];
      }
      touch(true);
    }
  });

  document.addEventListener('click', function (e) {
    var t = e.target;

    var stage = t.closest('[data-stage]');
    if (stage) {
      state.stage = stage.getAttribute('data-stage');
      renderStages();
      return touch(true);
    }

    var rm = t.closest('[data-remove]');
    if (rm) {
      var id = rm.getAttribute('data-remove');
      state.items = state.items.filter(function (x) { return String(x.id) !== String(id); });
      return touch(true);
    }

    var addChoice = t.closest('[data-add-choice]');
    if (addChoice) {
      var aq = itemById(addChoice.getAttribute('data-add-choice'));
      if (aq) { aq.choices.push('ตัวเลือกใหม่'); touch(true); }
      return;
    }

    /* "อื่น ๆ" = ตัวเลือกที่ผู้ตอบพิมพ์คำตอบเองได้ มีได้ชุดละหนึ่งอันเท่านั้น */
    var addOther = t.closest('[data-add-other]');
    if (addOther) {
      var oq = itemById(addOther.getAttribute('data-add-other'));
      if (oq && oq.choices.indexOf('อื่น ๆ') < 0) { oq.choices.push('อื่น ๆ'); touch(true); }
      return;
    }

    var rmChoice = t.closest('[data-remove-choice]');
    if (rmChoice) {
      var cp = rmChoice.getAttribute('data-remove-choice').split(':');
      var cq = itemById(cp[0]);
      if (cq) { cq.choices.splice(Number(cp[1]), 1); touch(true); }
      return;
    }

    /* แถบเครื่องมือข้างการ์ด */
    var tool = t.closest('[data-tool]');
    if (tool) {
      var tp = tool.getAttribute('data-tool').split(':');
      if (tp[0] === 'add') return addItem(tp[1], 'single');
      if (tp[0] === 'dup') return duplicateItem(tp[1]);
      if (tp[0] === 'section') {
        return insertAfter(tp[1], { id: state.nextId++, title: '', kind: 'section', required: false, choices: [] });
      }
      return;
    }

    /* คลิกที่ใบไหนก็เปิดใบนั้นให้แก้ — ใบอื่นยุบกลับเป็นตัวอย่างอัตโนมัติ */
    var card = t.closest('[data-activate]');
    if (card) {
      var cid = card.getAttribute('data-activate');
      if (String(state.activeId) !== String(cid)) {
        state.activeId = cid;
        renderItems();
        focusActiveTitle();
      }
      return;
    }

    if (t.closest('#ec-save-draft')) return saveDraft(true);

    if (t.closest('#ec-save') && !$('ec-save').disabled) {
      state.dirty = false;
      if (window.TFC.showToast) window.TFC.showToast('บันทึกและเปิดใช้งานแบบประเมินเรียบร้อย', 'success');
      return;
    }

    /* คลิกนอกรายการคำถาม = เลิกแก้ใบที่เปิดอยู่ ทุกใบยุบกลับเป็นตัวอย่าง
       ยกเว้นคลิกในแผงขวา/แถบล่าง ซึ่งไม่ควรทำให้ใบที่กำลังพิมพ์ปิดไปเฉย ๆ */
    if (state.activeId !== null && !t.closest('#ec-items') && !t.closest('.ec-side') && !t.closest('.ec-bottombar')) {
      state.activeId = null;
      renderItems();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (dragId !== null) return endDrag();
      if (state.activeId !== null) { state.activeId = null; renderItems(); }
      return;
    }
    /* คนที่ใช้คีย์บอร์ดหรือ screen reader ลากไม่ได้ จึงต้องมีทางเลื่อนลำดับด้วยลูกศร */
    var grip = e.target.closest && e.target.closest('[data-grip]');
    if (grip && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
      e.preventDefault();
      move(grip.getAttribute('data-grip'), e.key === 'ArrowUp' ? -1 : 1);
    }
  });

  /* ---------- บันทึกร่าง ---------- */
  function saveDraft(manual) {
    state.dirty = false;
    var now = new Date();
    $('ec-autosave').hidden = false;
    $('ec-autosave-text').textContent = 'บันทึกร่างล่าสุด ' +
      String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ' น.';
    if (manual && window.TFC.showToast) window.TFC.showToast('บันทึกฉบับร่างเรียบร้อย', 'success');
  }

  setInterval(function () { if (state.dirty) saveDraft(false); }, AUTOSAVE_MS);

  window.addEventListener('beforeunload', function (e) {
    if (!state.dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  $('ec-cancel').addEventListener('click', function (e) {
    if (!state.dirty) return;
    if (!window.confirm('ยังไม่ได้บันทึกการเปลี่ยนแปลง ต้องการออกจากหน้านี้หรือไม่')) e.preventDefault();
  });

  /* ---------- เริ่มต้น ---------- */
  syncAll();
})();
