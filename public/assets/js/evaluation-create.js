/* TheFarmConcept — หน้าสร้าง/แก้ไขแบบประเมิน (/admin/evaluations/create และ /{code}/edit)

   วิธีเรนเดอร์: รายการคำถามถูกสร้างใหม่เฉพาะตอนที่โครงสร้างเปลี่ยน (เพิ่ม/ลบ/สลับลำดับ/เปลี่ยนชนิด)
   ส่วนการพิมพ์ในช่องข้อความจะอัปเดตแค่ state + แผงขวา ไม่แตะ DOM ของรายการ
   ไม่งั้นช่องที่กำลังพิมพ์อยู่จะเสีย focus ทุกตัวอักษร */
(function () {
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  /* chips เลือกได้หลายข้อเหมือน multi แต่คนละชนิดกันโดยตั้งใจ
     multi = รายการติ๊กแนวตั้ง เหมาะกับตัวเลือกที่ข้อความยาวหรือมีเยอะ อ่านทีละบรรทัด
     chips = ป้ายเรียงต่อกัน เหมาะกับคำสั้น ๆ ที่กดเล่นได้เร็ว เห็นครบในสายตาเดียว
     ถ้ารวมเป็นชนิดเดียวแล้วให้เลือกหน้าตาทีหลัง จะกลายเป็นสองคำถามในที่เดียว */
  var KIND_LABELS = {
    rating: 'ให้คะแนน 1–5',
    single: 'เลือก 1 ข้อ',
    multi: 'เลือกหลายข้อ',
    chips: 'เลือกแบบป้าย (หลายข้อ)',
    dropdown: 'เลือกจากรายการ',
    text: 'ข้อความ'
  };
  var KIND_ORDER = ['rating', 'single', 'multi', 'chips', 'dropdown', 'text'];

  var STAGES = [
    { label: 'ตอนลงทะเบียน', hint: 'ผู้เข้าร่วมกรอกตอนจองที่นั่ง' },
    { label: 'หลังกิจกรรม', hint: 'ส่งให้ผู้เข้าร่วมหลังจบงาน' },
    { label: 'ติดตามสุขภาพ', hint: 'ไม่ผูกกับกิจกรรม ส่งตามรอบเวลา' }
  ];
  var REGISTRATION = 'ตอนลงทะเบียน';
  var STANDALONE = 'ติดตามสุขภาพ';
  var TYPE_BY_STAGE = {
    'ตอนลงทะเบียน': 'registration',
    'หลังกิจกรรม': 'post_activity',
    'ติดตามสุขภาพ': 'health_follow_up'
  };
  var STAGE_BY_TYPE = {
    registration: 'ตอนลงทะเบียน',
    post_activity: 'หลังกิจกรรม',
    health_follow_up: 'ติดตามสุขภาพ'
  };

  /* ฟิลด์มาตรฐานของแบบลงทะเบียน — ชื่อ/ชนิดมาจากระบบ ผู้ดูแลเลือกได้แค่เปิดหรือปิด
     ชื่อ เบอร์โทร และ PDPA เป็นแกนของการลงทะเบียน จึงแสดงตลอดและไม่มีสวิตช์ให้ปิด */
  var REGISTRATION_FIELDS = [
    { key: 'name', label: 'ชื่อ–นามสกุล', hint: 'ใช้ระบุตัวผู้ลงทะเบียน', required: true, preview: 'text' },
    { key: 'phone', label: 'เบอร์โทรศัพท์', hint: 'ใช้ตรวจสอบการลงทะเบียน / Check-in', required: true, preview: 'phone' },
    { key: 'gender', label: 'เพศ', hint: 'ตัวเลือกมาตรฐานจากระบบ', enabled: true, preview: 'select' },
    { key: 'age_range', label: 'ช่วงอายุ', hint: 'ตัวเลือกมาตรฐานจากระบบ', enabled: true, preview: 'select' },
    { key: 'email', label: 'อีเมล', hint: 'สำหรับรับข้อมูลและการติดต่อกลับ', enabled: true, preview: 'email' },
    { key: 'occupation', label: 'อาชีพ', hint: 'ตัวเลือกอาชีพจากข้อมูลพื้นฐาน', enabled: true, preview: 'select' },
    { key: 'source_channel', label: 'รับรู้ข่าวสารกิจกรรมจากช่องทางใด', hint: 'ตัวเลือกมาตรฐานจากระบบ', enabled: true, preview: 'select' },
    { key: 'interests', label: 'กิจกรรมที่สนใจนอกเหนือจากที่เราจัด', hint: 'เลือกได้มากกว่า 1 ข้อ', enabled: true, preview: 'multi' },
    { key: 'pdpa', label: 'การยอมรับ PDPA', hint: 'ต้องยอมรับก่อนยืนยันการลงทะเบียน', required: true, preview: 'consent' }
  ];

  var AUTOSAVE_MS = 60000;

  var state = {
    formCode: null,
    status: 'draft',
    name: '',
    desc: '',
    stage: 'หลังกิจกรรม',
    nextId: 4,
    /* คำถามที่กำลังแก้อยู่ — แบบเดียวกับ Google Form คือขยายทีละใบ ที่เหลือยุบเป็นตัวอย่าง
       ทำให้เห็นทั้งชุดในจอเดียวโดยไม่ต้องเลื่อนผ่านช่องกรอกของทุกข้อ */
    activeId: null,
    dirty: false,
    saving: false,
    hasResponses: false,
    bookingMode: 'single',
    maxSeats: 5,
    registrationItems: [],
    /* ตั้งต้นด้วยหัวข้อส่วน 1 ส่วนพร้อมคำถาม 2 ข้อในนั้น
       ให้เห็นตั้งแต่แรกว่าจัดคำถามเป็นตอนได้ และเลขข้อจะเป็น 1.1 / 1.2
       ตั้งชื่อส่วนไว้ให้ด้วย ไม่งั้นเช็กลิสต์ข้อ "ทุกคำถามและหัวข้อส่วนมีข้อความ" จะไม่ผ่านตั้งแต่เปิดหน้า */
    items: [
      { id: 1, title: 'ความคิดเห็นต่อกิจกรรม', kind: 'section', required: false, choices: [] },
      { id: 2, title: 'ความพึงพอใจโดยรวมต่อกิจกรรมนี้', kind: 'rating', required: true, choices: [] },
      { id: 3, title: 'จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่', kind: 'single', required: true, choices: ['แนะนำ', 'ไม่แน่ใจ', 'ไม่แนะนำ'] }
    ]
  };
  var evaluationItems = state.items;

  function isRegistration() { return state.stage === REGISTRATION; }
  function isStandalone() { return state.stage === STANDALONE; }
  function itemById(id) { return state.items.filter(function (i) { return String(i.id) === String(id); })[0]; }
  /* เลือกจากรายการใช้ชุดตัวเลือกเหมือนแบบเลือก 1 ข้อ ต่างแค่วิธีแสดงให้ผู้ตอบ */
  function hasChoices(kind) {
    return kind === 'single' || kind === 'multi' || kind === 'chips' || kind === 'dropdown';
  }

  /* เลือกได้หลายข้อไหม — ใช้ตัดสินทั้งเครื่องหมายในตัวแก้ไขและคำกำกับที่ผู้ตอบเห็น */
  function isMulti(kind) { return kind === 'multi' || kind === 'chips'; }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function requestJson(url, options) {
    options = options || {};
    options.headers = Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken()
    }, options.headers || {});

    return fetch(url, options).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) {
          var errors = data.errors || {};
          var firstKey = Object.keys(errors)[0];
          var message = firstKey && errors[firstKey] && errors[firstKey][0]
            ? errors[firstKey][0]
            : (data.message || 'ไม่สามารถบันทึกข้อมูลได้');
          throw new Error(message);
        }
        return data;
      });
    });
  }

  /* วงกลม = เลือก 1 ข้อ · สี่เหลี่ยม = เลือกหลายข้อ · แคปซูล = แบบป้าย · สี่เหลี่ยมเทา = เลือกจากรายการ */
  function markHtml(kind) {
    var cls = kind === 'multi' ? ' is-box'
      : (kind === 'chips' ? ' is-pill'
      : (kind === 'dropdown' ? ' is-box is-muted' : ''));
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
    if (isRegistration()) {
      return [
        { label: 'ตั้งชื่อชุดแบบประเมิน', ok: state.name.trim().length > 0 },
        { label: 'เลือกประเภทตอนลงทะเบียน', ok: true },
        { label: 'ฟิลด์บังคับเปิดใช้งานครบ 3 รายการ', ok: true },
        { label: 'คำถามเพิ่มเติมมีข้อความครบ', ok: state.items.length === 0 || filled === state.items.length },
        { label: 'คำถามแบบเลือกมีตัวเลือกครบ', ok: state.items.every(function (i) {
            return !hasChoices(i.kind) || i.choices.filter(function (c) { return c.trim(); }).length >= 2;
          }) }
      ];
    }
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

  /* ================= ส่วนที่ 2 — ฟิลด์ลงทะเบียน ================= */
  function registrationFieldByKey(key) {
    return REGISTRATION_FIELDS.filter(function (field) { return field.key === key; })[0];
  }

  function renderBookingConfig() {
    $('ec-booking-mode').value = state.bookingMode;
    $('ec-booking-max').hidden = state.bookingMode !== 'group';
    $('ec-booking-max-seats').value = String(state.maxSeats);
  }

  function registrationFieldRow(field) {
    var control = field.required
      ? '<span class="ec-registration-required-badge">บังคับ</span>'
      : '<label class="switch" aria-label="เปิดหรือปิดฟิลด์ ' + esc(field.label) + '">' +
          '<input type="checkbox" data-registration-field="' + esc(field.key) + '"' + (field.enabled ? ' checked' : '') + '>' +
          '<span class="switch-track"></span>' +
        '</label>';

    var leading = field.required
      ? '<span class="ec-registration-field-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>'
      : '<button type="button" class="ec-registration-grip" data-registration-grip="' + esc(field.key) + '" aria-label="ลากเพื่อสลับลำดับฟิลด์ ' + esc(field.label) + ' หรือกดลูกศรขึ้นลง"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="8" cy="7" r="1.5"/><circle cx="16" cy="7" r="1.5"/><circle cx="8" cy="12" r="1.5"/><circle cx="16" cy="12" r="1.5"/><circle cx="8" cy="17" r="1.5"/><circle cx="16" cy="17" r="1.5"/></svg></button>';

    return '<div class="ec-registration-field' + (field.required ? ' is-required' : '') + '"' + (field.required ? '' : ' data-registration-row="' + esc(field.key) + '"') + '>' +
      leading +
      '<span class="ec-registration-field-text"><strong>' + esc(field.label) + '</strong><span>' + esc(field.hint) + '</span></span>' +
      control +
    '</div>';
  }

  function renderRegistrationFields() {
    var section = $('ec-registration-section');
    var registration = isRegistration();
    section.hidden = !registration;

    $('ec-questions-section-no').textContent = registration ? '3' : '2';
    $('ec-questions-title').textContent = registration ? 'คำถามเพิ่มเติม' : 'คำถาม';
    $('ec-questions-note').hidden = !registration;
    if (!registration) return;

    renderBookingConfig();
    var optional = REGISTRATION_FIELDS.filter(function (field) { return !field.required; });
    var enabledCount = optional.filter(function (field) { return field.enabled; }).length;

    $('ec-registration-fields').innerHTML = REGISTRATION_FIELDS.map(registrationFieldRow).join('');
    $('ec-registration-summary').textContent = 'เปิดใช้งาน ' + (enabledCount + 3) + '/' + REGISTRATION_FIELDS.length;
  }


  /* ================= ส่วนที่ 2 — รายการคำถาม ================= */
  function renderItems() {
    var nums = numbering();

    $('ec-items').innerHTML = state.items.length
      ? state.items.map(function (q, i) {
          var n = nums[i];
          return q.kind === 'section' ? sectionHtml(q, n) : questionHtml(q, n);
        }).join('')
      : '<div class="ec-questions-empty">' +
          '<span class="ec-questions-empty-title">' + (isRegistration() ? 'ยังไม่มีคำถามเพิ่มเติม' : 'ยังไม่มีคำถามในแบบประเมิน') + '</span>' +
          '<span class="ec-questions-empty-text">' + (isRegistration()
            ? 'ฟิลด์ระบบด้านบนเพียงพอสำหรับการลงทะเบียน หรือเพิ่มคำถามเฉพาะกิจกรรมได้ภายหลัง'
            : 'เพิ่มคำถามอย่างน้อย 1 ข้อ เพื่อให้ผู้ตอบเริ่มทำแบบประเมินได้') + '</span>' +
          '<button type="button" class="btn btn-outline btn-sm" data-add-first>' + (isRegistration() ? 'เพิ่มคำถามเพิ่มเติม' : 'เพิ่มคำถาม') + '</button>' +
        '</div>';

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
      multiHint(q.kind) +
      answerHtml(q, 'ec-view') +
      (q.kind === 'text' ? '<div class="ec-view-text">พิมพ์คำตอบ…</div>' : '');
  }

  /* ชนิดที่ตอบได้หลายข้อต้องบอกให้ชัด โดยเฉพาะแบบป้ายที่ไม่มีช่องติ๊กให้เดาจากรูป */
  function multiHint(kind) {
    return isMulti(kind) ? '<span class="ec-multi-hint">เลือกได้มากกว่า 1 ข้อ</span>' : '';
  }

  /* หน้าตาคำตอบที่ผู้ตอบเห็น — ใช้ทั้งการ์ดที่ยุบและแผงตัวอย่างขวา
     prefix คุมแค่ขนาด/ระยะของแต่ละที่ ส่วนโครงและตรรกะเป็นชุดเดียวกัน จะได้ไม่เพี้ยนจากกัน */
  function answerHtml(q, prefix) {
    if (q.kind === 'rating') return ratingHtml();
    if (!hasChoices(q.kind)) return '';

    if (q.kind === 'dropdown') {
      return '<div class="ec-pv-select">' +
        '<span>' + esc((q.choices[0] || '').trim() || 'เลือกคำตอบ') + '</span>' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>' +
        '</div>';
    }

    /* แบบป้าย: ตัวเลือกเป็นแคปซูลเรียงต่อกัน กดได้ทั้งใบ ไม่มีช่องติ๊กแยก */
    if (q.kind === 'chips') {
      return '<div class="ec-chip-answers">' + q.choices.map(function (c) {
        return '<span class="ec-chip-answer">' + esc(c) + '</span>';
      }).join('') + '</div>';
    }

    return '<div class="' + prefix + '-choices">' + q.choices.map(function (c) {
      return '<span class="' + prefix + '-choice">' + markHtml(q.kind) + esc(c) + '</span>';
    }).join('') + '</div>';
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

  /* สเกล 1–5 แสดงเป็นหน้ายิ้ม — ผู้ตอบเลือกจากความรู้สึกได้ทันที ไม่ต้องแปลตัวเลขเป็นความหมายเอง
     ค่าที่ระบบเก็บยังเป็น 1–5 เหมือนเดิม รายงานและคะแนนเฉลี่ยจึงไม่กระทบ
     เก็บชุดนี้ไว้ที่เดียว ทั้งฝั่งแก้ไขและฝั่งตัวอย่างใช้ตัวเดียวกัน จะได้ไม่มีทางเพี้ยนจากกัน */
  var RATING_FACES = [
    { score: 1, emoji: '😞', label: 'น้อยที่สุด' },
    { score: 2, emoji: '🙁', label: 'น้อย' },
    { score: 3, emoji: '😐', label: 'ปานกลาง' },
    { score: 4, emoji: '🙂', label: 'มาก' },
    { score: 5, emoji: '😍', label: 'มากที่สุด' }
  ];

  function ratingHtml() {
    return '<div class="ec-scale">' +
      RATING_FACES.map(function (f) {
        return '<span class="ec-face" title="' + esc(f.score + ' = ' + f.label) + '">' +
          '<span class="ec-face-emoji" role="img" aria-label="' + esc(f.label) + '">' + f.emoji + '</span>' +
          '<span class="ec-face-label">' + esc(f.label) + '</span>' +
          '</span>';
      }).join('') +
      '</div>';
  }

  /* ================= แผงขวา ================= */
  function previewInput(label, placeholder, kind) {
    if (kind === 'select') {
      return '<div class="ec-pv-select"><span>' + esc(placeholder) + '</span>' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></div>';
    }
    return '<div class="ec-registration-preview-input">' + esc(placeholder) + '</div>';
  }

  function registrationPreviewField(key) {
    var field = registrationFieldByKey(key);
    if (!field || (!field.required && !field.enabled)) return '';

    if (field.preview === 'consent') {
      return '<div class="ec-registration-preview-consent"><span class="ec-mark is-box"></span><span>ยอมรับเงื่อนไขการเข้าร่วมกิจกรรมและนโยบาย PDPA <b>*</b></span></div>';
    }
    if (field.preview === 'multi') {
      return '<div class="ec-registration-preview-field"><span class="ec-registration-preview-label">' + esc(field.label) + '</span>' +
        '<div class="ec-pv-choices"><span class="ec-pv-choice"><span class="ec-mark is-box"></span>เวิร์กช็อปอาหาร / ขนม</span><span class="ec-pv-choice"><span class="ec-mark is-box"></span>กิจกรรมปลูกต้นไม้ / ทำสวน</span><span class="ec-pv-choice"><span class="ec-mark is-box"></span>โยคะ / สมาธิ</span></div></div>';
    }

    var placeholder = field.key === 'name' ? 'ชื่อ นามสกุล'
      : (field.key === 'phone' ? '08X-XXX-XXXX'
      : (field.key === 'email' ? 'name@email.com'
      : (field.key === 'gender' ? 'เลือกเพศ'
      : (field.key === 'age_range' ? 'เลือกช่วงอายุ'
      : (field.key === 'occupation' ? 'เลือกอาชีพ' : 'เลือกช่องทาง')))));

    return '<div class="ec-registration-preview-field"><span class="ec-registration-preview-label">' + esc(field.label) + (field.required ? ' <b>*</b>' : '') + '</span>' +
      previewInput(field.label, placeholder, field.preview) + '</div>';
  }

  function participantNamesPreviewHtml() {
    var seats = state.bookingMode === 'group' ? state.maxSeats : 1;
    var quantity = state.bookingMode === 'group'
      ? '<div class="ec-registration-preview-field"><span class="ec-registration-preview-label">จำนวนที่นั่ง <b>*</b></span>' +
          previewInput('จำนวนที่นั่ง', seats + ' ที่นั่ง', 'select') +
          '<span class="ec-registration-preview-note">ตัวอย่างเมื่อผู้ลงทะเบียนเลือก ' + seats + ' ที่นั่ง</span></div>'
      : '';
    var names = [];
    for (var i = 1; i <= seats; i++) {
      var label = seats > 1 ? 'ชื่อ–นามสกุล คนที่ ' + i : 'ชื่อ–นามสกุล';
      names.push('<div class="ec-registration-preview-field"><span class="ec-registration-preview-label">' + esc(label) + ' <b>*</b></span>' +
        previewInput(label, 'ชื่อ นามสกุล', 'text') + '</div>');
    }
    return quantity + names.join('');
  }

  function customQuestionsPreviewHtml() {
    if (!state.items.length) return '';
    return state.items.map(function (q) {
        if (q.kind === 'section') {
          return '<div class="ec-registration-preview-heading">' + esc(q.title.trim() || 'หัวข้อส่วน') + '</div>';
        }
        return '<div class="ec-pv-q"><span class="ec-pv-title">' + esc((q.title.trim() || 'คำถามที่ยังไม่ได้พิมพ์') + (q.required ? ' *' : '')) + '</span>' +
          multiHint(q.kind) + answerHtml(q, 'ec-pv') + (q.kind === 'text' ? '<div class="ec-pv-text">พิมพ์คำตอบ…</div>' : '') + '</div>';
      }).join('');
  }

  function registrationPreviewHtml() {
    var optional = REGISTRATION_FIELDS.filter(function (field) { return !field.required; });
    var order = optional.slice(0, 2).map(function (field) { return field.key; })
      .concat(['phone'])
      .concat(optional.slice(2).map(function (field) { return field.key; }));
    return participantNamesPreviewHtml() + order.map(registrationPreviewField).join('') + customQuestionsPreviewHtml() + registrationPreviewField('pdpa');
  }

  function renderPreview() {
    var nameEl = $('ec-preview-name');
    nameEl.textContent = state.name.trim() || (isRegistration() ? 'แบบฟอร์มลงทะเบียนกิจกรรม' : 'ชื่อชุดแบบประเมิน');
    nameEl.classList.toggle('is-empty', !state.name.trim());

    $('ec-preview-desc').textContent = state.desc.trim() ||
      (isRegistration() ? 'กรอกข้อมูลเพื่อสำรองที่นั่งเข้าร่วมกิจกรรม'
      : (isStandalone() ? 'ส่งตามรอบเวลา ไม่ผูกกับกิจกรรม' : 'คำอธิบายจะแสดงตรงนี้'));

    $('ec-preview-submit').textContent = isRegistration() ? 'ยืนยันการลงทะเบียน' : 'ส่งแบบประเมิน';
    $('ec-preview-intro').hidden = isRegistration();

    if (isRegistration()) {
      $('ec-preview-list').innerHTML = registrationPreviewHtml();
      return;
    }

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
        multiHint(q.kind) +
        answerHtml(q, 'ec-pv') +
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
    renderRegistrationFields();
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
  var registrationDragKey = null;

  document.addEventListener('mousedown', function (e) {
    var registrationGrip = e.target.closest('[data-registration-grip]');
    var registrationRow = registrationGrip && registrationGrip.closest('[data-registration-row]');
    if (registrationRow) {
      registrationRow.setAttribute('draggable', 'true');
      return;
    }
    var grip = e.target.closest('[data-grip]');
    var row = grip && grip.closest('[data-row]');
    if (row) row.setAttribute('draggable', 'true');
  });

  document.addEventListener('mouseup', clearDraggable);

  function clearDraggable() {
    var rows = document.querySelectorAll('[data-row][draggable], [data-registration-row][draggable]');
    Array.prototype.forEach.call(rows, function (r) { r.removeAttribute('draggable'); });
  }

  document.addEventListener('dragstart', function (e) {
    var registrationRow = e.target.closest && e.target.closest('[data-registration-row]');
    if (registrationRow && registrationRow.getAttribute('draggable')) {
      registrationDragKey = registrationRow.getAttribute('data-registration-row');
      registrationRow.classList.add('is-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', registrationDragKey);
      }
      return;
    }
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
    if (registrationDragKey !== null) {
      var registrationRow = e.target.closest && e.target.closest('[data-registration-row]');
      if (!registrationRow) return;
      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
      clearDropMarks();
      if (registrationRow.getAttribute('data-registration-row') === registrationDragKey) return;
      var registrationBox = registrationRow.getBoundingClientRect();
      registrationRow.classList.add(e.clientY < registrationBox.top + registrationBox.height / 2 ? 'is-drop-before' : 'is-drop-after');
      return;
    }
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
    if (registrationDragKey !== null) {
      var registrationRow = e.target.closest && e.target.closest('[data-registration-row]');
      if (!registrationRow) return;
      e.preventDefault();
      var sourceKey = registrationDragKey;
      var registrationTargetKey = registrationRow.getAttribute('data-registration-row');
      var registrationBefore = registrationRow.classList.contains('is-drop-before');
      endRegistrationDrag();
      if (registrationTargetKey !== sourceKey) reorderRegistrationField(sourceKey, registrationTargetKey, registrationBefore);
      return;
    }
    if (dragId === null) return;
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row) return;
    e.preventDefault();

    var sourceId = dragId;
    var targetId = row.getAttribute('data-row');
    var before = row.classList.contains('is-drop-before');
    endDrag();
    if (targetId === sourceId) return;

    var from = indexOfId(sourceId);
    var moved = state.items.splice(from, 1)[0];
    var to = indexOfId(targetId);
    state.items.splice(before ? to : to + 1, 0, moved);
    touch(true);
  });

  document.addEventListener('dragend', function () {
    if (registrationDragKey !== null) endRegistrationDrag();
    else endDrag();
  });

  function optionalRegistrationFields() {
    return REGISTRATION_FIELDS.filter(function (field) { return !field.required; });
  }

  function applyRegistrationFieldOrder(optional) {
    var required = REGISTRATION_FIELDS.filter(function (field) { return field.required && field.key !== 'pdpa'; });
    var pdpa = registrationFieldByKey('pdpa');
    REGISTRATION_FIELDS = required.concat(optional).concat(pdpa ? [pdpa] : []);
  }

  function finishRegistrationFieldMove(key) {
    state.dirty = true;
    renderRegistrationFields();
    syncDerived();
    var grip = document.querySelector('[data-registration-grip="' + key + '"]');
    if (grip) grip.focus();
  }

  function moveRegistrationField(key, dir) {
    var optional = optionalRegistrationFields();
    var from = optional.map(function (field) { return field.key; }).indexOf(key);
    var to = from + dir;
    if (from < 0 || to < 0 || to >= optional.length) return;
    var moved = optional.splice(from, 1)[0];
    optional.splice(to, 0, moved);
    applyRegistrationFieldOrder(optional);
    finishRegistrationFieldMove(key);
  }

  function reorderRegistrationField(sourceKey, targetKey, before) {
    var optional = optionalRegistrationFields();
    var from = optional.map(function (field) { return field.key; }).indexOf(sourceKey);
    if (from < 0) return;
    var moved = optional.splice(from, 1)[0];
    var to = optional.map(function (field) { return field.key; }).indexOf(targetKey);
    if (to < 0) return;
    optional.splice(before ? to : to + 1, 0, moved);
    applyRegistrationFieldOrder(optional);
    finishRegistrationFieldMove(sourceKey);
  }

  function endRegistrationDrag() {
    var dragging = document.querySelector('[data-registration-row].is-dragging');
    if (dragging) dragging.classList.remove('is-dragging');
    clearDropMarks();
    clearDraggable();
    registrationDragKey = null;
  }

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
    if (e.target.id === 'ec-booking-mode') {
      state.bookingMode = e.target.value;
      state.dirty = true;
      renderBookingConfig();
      syncDerived();
      return;
    }

    if (e.target.id === 'ec-booking-max-seats') {
      state.maxSeats = Math.max(2, Math.min(5, Number(e.target.value) || 5));
      state.dirty = true;
      syncDerived();
      return;
    }

    var registrationField = e.target.closest('[data-registration-field]');
    if (registrationField) {
      var field = registrationFieldByKey(registrationField.getAttribute('data-registration-field'));
      if (field && !field.required) {
        field.enabled = registrationField.checked;
        state.dirty = true;
        renderRegistrationFields();
        syncDerived();
      }
      return;
    }

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
      var nextStage = stage.getAttribute('data-stage');
      if (nextStage === state.stage) return;
      if (isRegistration()) state.registrationItems = state.items;
      else evaluationItems = state.items;
      state.stage = nextStage;
      state.items = isRegistration() ? state.registrationItems : evaluationItems;
      state.activeId = null;
      renderStages();
      renderRegistrationFields();
      return touch(true);
    }

    var addFirst = t.closest('[data-add-first]');
    if (addFirst) {
      var first = { id: state.nextId++, title: '', kind: 'single', required: false, choices: ['ตัวเลือกที่ 1', 'ตัวเลือกที่ 2'] };
      state.items.push(first);
      state.activeId = first.id;
      touch(true);
      focusActiveTitle();
      return;
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
      return persist('active', true);
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
      if (registrationDragKey !== null) return endRegistrationDrag();
      if (dragId !== null) return endDrag();
      if (state.activeId !== null) { state.activeId = null; renderItems(); }
      return;
    }
    /* คนที่ใช้คีย์บอร์ดหรือ screen reader ลากไม่ได้ จึงต้องมีทางเลื่อนลำดับด้วยลูกศร */
    var registrationGrip = e.target.closest && e.target.closest('[data-registration-grip]');
    if (registrationGrip && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
      e.preventDefault();
      moveRegistrationField(registrationGrip.getAttribute('data-registration-grip'), e.key === 'ArrowUp' ? -1 : 1);
      return;
    }
    var grip = e.target.closest && e.target.closest('[data-grip]');
    if (grip && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
      e.preventDefault();
      move(grip.getAttribute('data-grip'), e.key === 'ArrowUp' ? -1 : 1);
    }
  });

  /* ---------- บันทึกร่าง ---------- */
  function payload(status) {
    return {
      name: state.name.trim(),
      description: state.desc.trim() || null,
      type: TYPE_BY_STAGE[state.stage],
      status: status,
      registration_mode: isRegistration() ? state.bookingMode : null,
      max_participants: isRegistration() ? (state.bookingMode === 'group' ? state.maxSeats : 1) : null,
      fields: isRegistration() ? REGISTRATION_FIELDS.map(function (field, index) {
        return {
          key: field.key,
          is_enabled: field.required || !!field.enabled,
          is_required: !!field.required,
          sort_order: index + 1
        };
      }) : [],
      questions: state.items.map(function (item, index) {
        return {
          type: item.kind,
          text: item.title.trim(),
          dimension: item.dimension || null,
          is_required: item.kind === 'section' ? false : !!item.required,
          sort_order: index + 1,
          options: (item.choices || []).map(function (choice) {
            return { label: choice.trim(), is_other: choice.trim() === 'อื่น ๆ' };
          })
        };
      })
    };
  }

  function setSaving(saving) {
    state.saving = saving;
    $('ec-save-draft').disabled = saving;
    $('ec-save').disabled = saving || checklist().some(function (item) { return !item.ok; });
  }

  function updateHasResponsesUI() {
    var notice = $('ec-has-responses-notice');
    var dupBtn = $('ec-duplicate-btn');
    var saveBtn = $('ec-save');

    if (notice) notice.hidden = !state.hasResponses;
    if (dupBtn) dupBtn.hidden = !state.hasResponses;
    if (state.hasResponses && saveBtn) {
      saveBtn.textContent = 'บันทึกทำสำเนาเป็นชุดใหม่';
    }
  }

  function persistAsCopy(status, redirectAfter) {
    if (state.saving) return Promise.resolve();
    if (!state.name.trim()) {
      if (window.TFC.showToast) window.TFC.showToast('กรุณาระบุชื่อชุดแบบประเมิน', 'error');
      $('ec-name').focus();
      return Promise.resolve();
    }

    setSaving(true);
    var copyName = state.name.trim();
    if (copyName.indexOf('(สำเนา)') === -1) {
      copyName += ' (สำเนา)';
      state.name = copyName;
      if ($('ec-name')) $('ec-name').value = copyName;
    }

    var bodyPayload = payload(status);
    bodyPayload.name = copyName;

    return requestJson('/admin/evaluations', { method: 'POST', body: JSON.stringify(bodyPayload) })
      .then(function (data) {
        state.formCode = data.form.code;
        state.status = data.form.status;
        state.hasResponses = false;
        state.dirty = false;
        updateHasResponsesUI();
        window.history.replaceState({}, '', '/admin/evaluations/' + encodeURIComponent(state.formCode) + '/edit');
        if (window.TFC.showToast) window.TFC.showToast('ทำสำเนาและบันทึกเป็นชุดใหม่เรียบร้อย', 'success');
        if (redirectAfter) window.setTimeout(function () { window.location.href = data.redirect; }, 450);
        return data;
      })
      .catch(function (error) {
        if (window.TFC.showToast) window.TFC.showToast(error.message, 'error');
      })
      .finally(function () { setSaving(false); });
  }

  function persist(status, redirectAfter) {
    if (state.saving) return Promise.resolve();
    if (!state.name.trim()) {
      if (window.TFC.showToast) window.TFC.showToast('กรุณาระบุชื่อชุดแบบประเมิน', 'error');
      $('ec-name').focus();
      return Promise.resolve();
    }

    if (state.hasResponses) {
      return persistAsCopy(status, redirectAfter);
    }

    setSaving(true);
    var url = state.formCode ? '/admin/evaluations/' + encodeURIComponent(state.formCode) : '/admin/evaluations';
    var method = state.formCode ? 'PUT' : 'POST';

    return requestJson(url, { method: method, body: JSON.stringify(payload(status)) })
      .then(function (data) {
        state.formCode = data.form.code;
        state.status = data.form.status;
        state.dirty = false;
        window.history.replaceState({}, '', '/admin/evaluations/' + encodeURIComponent(state.formCode) + '/edit');
        if (window.TFC.showToast) window.TFC.showToast(data.message, 'success');
        if (redirectAfter) window.setTimeout(function () { window.location.href = data.redirect; }, 450);
        return data;
      })
      .catch(function (error) {
        if (error.message && (error.message.indexOf('ทำสำเนาเป็นชุดใหม่') !== -1 || error.message.indexOf('แบบประเมินนี้มีคำตอบแล้ว') !== -1)) {
          state.hasResponses = true;
          updateHasResponsesUI();
          if (window.confirm(error.message + '\n\nคุณต้องการทำสำเนาเป็นชุดใหม่เพื่อบันทึกการเปลี่ยนแปลงนี้หรือไม่?')) {
            return persistAsCopy(status, redirectAfter);
          }
        } else {
          if (window.TFC.showToast) window.TFC.showToast(error.message, 'error');
        }
      })
      .finally(function () { setSaving(false); });
  }

  function saveDraft(manual) {
    if (!state.name.trim()) {
      if (manual && window.TFC.showToast) window.TFC.showToast('กรุณาระบุชื่อชุดแบบประเมินก่อนบันทึกร่าง', 'error');
      return Promise.resolve();
    }

    var now = new Date();
    /* ป้ายบอกเวลาบันทึกร่างอยู่บน topbar ซึ่งบางหน้าไม่ได้ใส่ไว้
       เช็กก่อนใช้ ไม่งั้นทั้งฟังก์ชันพังและ toast แจ้งผลก็ไม่ทำงานตามไปด้วย */
    var badge = $('ec-autosave'), badgeText = $('ec-autosave-text');
    if (badge) badge.hidden = false;
    if (badgeText) {
      badgeText.textContent = 'บันทึกร่างล่าสุด ' +
        String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ' น.';
    }
    return persist(manual ? 'draft' : state.status, false);
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

  var dupBtn = $('ec-duplicate-btn');
  if (dupBtn) {
    dupBtn.addEventListener('click', function () {
      persistAsCopy('draft', false);
    });
  }

  function applyLoadedForm(form) {
    state.formCode = form.code;
    state.status = form.status || 'draft';
    state.name = form.name || '';
    state.desc = form.description || '';
    state.stage = STAGE_BY_TYPE[form.type] || 'หลังกิจกรรม';
    state.bookingMode = form.registration_mode || 'single';
    state.maxSeats = form.max_participants || 5;
    state.hasResponses = !!form.has_responses;
    updateHasResponsesUI();

    var fields = (form.fields || []).slice().sort(function (a, b) { return a.sort_order - b.sort_order; });
    if (fields.length) {
      REGISTRATION_FIELDS = fields.map(function (saved) {
        var base = registrationFieldByKey(saved.key);
        if (!base) return null;
        return Object.assign({}, base, {
          enabled: !!saved.is_enabled,
          required: !!saved.is_required || base.required
        });
      }).filter(Boolean);
    }

    var items = (form.questions || []).slice().sort(function (a, b) { return a.sort_order - b.sort_order; }).map(function (question) {
      return {
        id: question.id,
        title: question.text,
        kind: question.type,
        dimension: question.dimension,
        required: !!question.is_required,
        choices: (question.options || []).map(function (option) { return option.label; })
      };
    });
    state.nextId = items.reduce(function (max, item) { return Math.max(max, Number(item.id) || 0); }, 0) + 1;
    state.items = items;
    if (isRegistration()) {
      state.registrationItems = items;
      evaluationItems = [];
    } else {
      evaluationItems = items;
      state.registrationItems = [];
    }

    $('ec-name').value = state.name;
    $('ec-desc').value = state.desc;
    document.title = 'แก้ไขแบบประเมิน | TheFarmConcept';
    var pageTitle = document.querySelector('.ec-title');
    var currentBreadcrumb = document.querySelector('.breadcrumb .is-current');
    if (pageTitle) pageTitle.textContent = 'แก้ไขแบบประเมิน';
    if (currentBreadcrumb) currentBreadcrumb.textContent = 'แก้ไขแบบประเมิน';
    state.dirty = false;
  }

  function boot() {
    if (window.TFC_EVALUATION_FORM) applyLoadedForm(window.TFC_EVALUATION_FORM);
    syncAll();
  }

  /* ---------- เริ่มต้น ---------- */
  boot();
})();
