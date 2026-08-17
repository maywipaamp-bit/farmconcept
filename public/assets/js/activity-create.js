/* TheFarmConcept — หน้าเพิ่มกิจกรรม (admin/activities/create.html)
   แยกไฟล์เพราะสคริปต์ยาวเกินกว่าจะฝังในหน้า และหน้าแก้ไขกิจกรรมจะเรียกใช้ซ้ำได้ในภายหลัง

   หลักการเรนเดอร์: โครงฟอร์มทั้งหมดอยู่ใน HTML แล้ว สคริปต์นี้ไม่สร้าง input ใหม่ทับของเดิม
   จะเรนเดอร์ใหม่เฉพาะส่วนที่เป็นตัวเลือก (chip / การ์ดเลือก / combobox / รอบกิจกรรม)
   เพื่อไม่ให้ช่องที่กำลังพิมพ์อยู่เสีย focus ตอน sync */
(function () {
  var esc = window.TFC.escapeHtml;
  var mock = window.TFC_MOCK || {};

  /* ---------- ข้อมูลตั้งต้น ----------
     โปรแกรม/หลักสูตร/วิทยากร มาจากข้อมูลพื้นฐานในฐานข้อมูล
     ค่าสำรองด้านล่างใช้เฉพาะตอนเปิดหน้า static เดิมที่ยังไม่มีข้อมูลจริงส่งมาให้
     (ชุดสำรองเป็นคนละชุดกับฐานข้อมูล จงใจไม่แก้ให้ตรง เพราะเป็นแค่ตัวอย่างของต้นแบบ) */
  var CATALOG = mock.programCatalog || [
    { program: 'โปรแกรมเกษตรในเมือง', courses: [
      { name: 'ปลูกผักในพื้นที่จำกัด', teachers: ['ดร.กิตติพงศ์ วัฒนสุข', 'อรุณี ทองสุข'] },
      { name: 'ทำปุ๋ยหมักจากเศษอาหาร', teachers: ['อรุณี ทองสุข'] }
    ] },
    { program: 'โปรแกรมสุขภาพชุมชน', courses: [
      { name: 'อาหารปลอดภัยในครัวเรือน', teachers: ['พยาบาลวิชาชีพ ศิริพร'] },
      { name: 'ดูแลสุขภาพผู้สูงอายุ', teachers: ['พยาบาลวิชาชีพ ศิริพร', 'ปิยะดา รุ่งเรือง'] }
    ] },
    { program: 'โปรแกรมพัฒนาแกนนำ', courses: [
      { name: 'แกนนำสุขภาพชุมชน', teachers: ['วีระ ศรีสมบัติ', 'ดร.กิตติพงศ์ วัฒนสุข'] },
      { name: 'การสื่อสารและจัดกิจกรรม', teachers: ['คุณปกรณ์ชัย ใจดี'] }
    ] }
  ];

  var PLACE_EMPTY = 'เลือกพื้นที่ดำเนินงาน';
  /* อ่านจากข้อมูลกลางก่อน ถ้าไม่มีค่อยใช้ค่าสำรอง — เพิ่มพื้นที่ในระบบแล้วหน้านี้ต้องเห็นเอง
     ของเดิมเขียนรายชื่อไว้ตรง ๆ ทำให้พื้นที่ใหม่ไม่โผล่มาเป็นตัวเลือก */
  var PLACES = [PLACE_EMPTY].concat(
    (mock.areas || []).map(function (a) { return a.name; })
  );
  if (PLACES.length === 1) PLACES = [PLACE_EMPTY, 'The Farm Concept', 'ชุมชนพูนทรัพย์', 'ชุมชนตึกร้าง'];

  var KINDS = mock.activityTypes || ['กิจกรรม', 'อีเว้นท์'];

  /* หมวดหมู่ + ไอคอน มาจากข้อมูลกลาง (หน้า "หมวดหมู่กิจกรรม" เป็นคนดูแล)
     ไม่ hardcode รายชื่อไว้ที่นี่ ไม่งั้นเพิ่มหมวดหมู่ในระบบแล้วหน้านี้ไม่รู้เรื่อง */
  var CAT_ICONS = mock.activityCategoryIcons || [];
  var CATS = (mock.activityFormats || [])
    .filter(function (f) { return f.active; })
    .map(function (f) { return { name: f.name, icon: f.icon }; });
  if (!CATS.length) CATS = ['CRAFT', 'MIND', 'FOOD', 'WORKSHOP', 'COMMUNITY'].map(function (n) { return { name: n, icon: '' }; });

  function catIconSvg(value) {
    var found = CAT_ICONS.filter(function (ic) { return ic.value === value; })[0];
    if (!found) return '';
    return '<svg class="ac-chip-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + found.path + '</svg>';
  }

  var MODES = mock.activityVenueModes || ['จัดในพื้นที่ (Onsite)', 'ออนไลน์', 'ผสม (Hybrid)'];

  /* เหมือน PLACES — อ่านจากข้อมูลกลาง เพิ่มกลุ่มเป้าหมายในระบบแล้วหน้านี้ต้องเห็นเอง */
  var TARGETS = (mock.targetGroups || []).map(function (t) { return t.name; });
  if (!TARGETS.length) TARGETS = ['เด็กและเยาวชน', 'วัยทำงาน', 'ผู้สูงอายุ', 'กลุ่มเปราะบาง'];
  /* อีเวนท์ที่เลือกเป็นแม่ได้ — หน้าที่ต่อฐานข้อมูลจริงส่งมาให้ หน้า static เดิมไม่มี */
  var EVENTS = (mock.selectableEvents || []).map(function (e) { return e.name; });

  /* วิทยากรเลือกได้อิสระ ไม่ผูกกับหลักสูตรที่เลือกไว้อีกต่อไป — ใช้รายชื่อทั้งหมดในระบบ
     lookup.instructors เป็นตาราง ชื่อ->id ของวิทยากรทุกคน (ตั้งค่าไว้ก่อนไฟล์นี้โหลด ดู form.blade.php) */
  var INSTRUCTORS = Object.keys((window.TFC_ACTIVITY_LOOKUP || {}).instructors || {});
  if (!INSTRUCTORS.length) {
    var seenInstructor = {};
    CATALOG.forEach(function (p) {
      p.courses.forEach(function (c) {
        (c.teachers || []).forEach(function (teacher) {
          var name = teacher && typeof teacher === 'object' ? teacher.name : teacher;
          if (name && !seenInstructor[name]) { seenInstructor[name] = 1; INSTRUCTORS.push(name); }
        });
      });
    });
  }

  var FEES = ['ไม่มีค่าใช้จ่าย', 'มีค่าเข้าร่วม'];
  /* สามขั้นตอนที่เป็นอิสระต่อกัน ติ๊กแยกกันได้ทั้งหมด
     ที่ติ๊กไว้ที่นี่คือแหล่งความจริงเดียว — ฟิลด์เงื่อนไขและแถวช่วงเวลาข้างล่างอ่านจากชุดนี้
     ไม่มีช่องติ๊กซ้ำที่อื่นอีก ผู้ใช้จะได้ไม่ต้องตอบคำถามเดียวกันสองรอบแล้วขัดกันเอง */
  /* ป้ายสั้น ไม่ต้องมีคำว่า "ต้อง" นำหน้า เพราะชื่อฟิลด์บอกบริบทอยู่แล้ว
     และสามช่องต้องอยู่บรรทัดเดียวในคอลัมน์กว้าง ~344px */
  var JOIN_FLAGS = [
    { key: 'reg',    label: 'ลงทะเบียน',    hint: 'จองที่นั่งล่วงหน้าผ่านเว็บ' },
    { key: 'chk',    label: 'Check-in',     hint: 'สแกน QR ยืนยันตัวตนหน้างาน' },
    { key: 'survey', label: 'ทำแบบประเมิน', hint: 'ส่งแบบประเมินให้หลังจบกิจกรรม' }
  ];

  function needsReg() { return !!state.join.reg; }
  function needsCheckin() { return !!state.join.chk; }
  function hasSurvey() { return !!state.join.survey; }

  /* ---------- แบบประเมินที่เลือกได้ ----------
     ดึงจากระบบแบบประเมิน (mock.evaluationForms) ไม่ได้พิมพ์รายชื่อไว้ที่นี่
     เอาเฉพาะชุดที่เปิดใช้งานแล้ว — ฉบับร่างยังแก้อยู่ ผูกกับกิจกรรมไปก่อนไม่ได้
     แยกสองช่องด้วย type มาตรฐานของระบบ ไม่ใช้ข้อความที่แสดงบนหน้าจอมาเทียบ */
  var FORM_ACTIVE_STATUS = 'active';

  function activeForms() {
    return (mock.evaluationForms || []).filter(function (f) { return f.status === FORM_ACTIVE_STATUS; });
  }

  function isRegForm(f) { return f && f.type === 'registration'; }
  function isPostForm(f) { return f && f.type === 'post_activity'; }

  function formsReg() { return activeForms().filter(isRegForm); }
  function formsPost() { return activeForms().filter(isPostForm); }

  /* คำอธิบายใต้ชื่อชุด สร้างจากข้อมูลจริงของชุดนั้น ไม่ได้พิมพ์ตัวเลขไว้เอง */
  function formHint(o) {
    var n = o.questions ? o.questions.length : (o.questionCount || 0);
    var type = isRegForm(o) ? 'แบบลงทะเบียน' : 'แบบประเมินหลังกิจกรรม';
    return type + ' · ' + (n ? n + ' คำถามเพิ่มเติม' : 'ไม่มีคำถามเพิ่มเติม');
  }

  function formById(id) {
    return activeForms().filter(function (o) { return o.id === id; })[0];
  }

  /* รายการจริงจาก act_qr_codes — หน้า Create ยังไม่มี activity_id จึงเป็นรายการว่าง
     และจะสร้างอัตโนมัติทันทีหลังบันทึกกิจกรรม */
  var QR_LINKS = window.TFC_ACTIVITY_QR || [];

  var MAX_SLOTS = 5;
  var AUTOSAVE_MS = 60000;

  /* ---------- สถานะ ---------- */
  var state = {
    title: '', detail: '',
    kind: KINDS[0], cats: ['FOOD'], mode: MODES[0],
    parentEvent: '',
    place: PLACE_EMPTY,
    cover: false,
    coverLabel: '', coverUrl: '',
    courses: [], hosts: [],
    slots: [{ id: 1, date: '', start: '', end: '', cap: '' }],
    nextSlotId: 2,
    fee: FEES[0], feeAmount: '',
    /* ตั้งต้นไม่ติ๊กอะไรเลย = เข้าร่วมได้ทันที
       ผู้ใช้ติ๊กเพิ่มเองเมื่อกิจกรรมนั้นต้องมีขั้นตอน ไม่ใช่ต้องมาไล่ติ๊กออก */
    join: { reg: false, chk: false, survey: false },
    targets: [],
    /* เก็บเป็น id ของแบบประเมิน ไม่ใช่ชื่อ — ชื่อแก้ได้ในระบบแบบประเมิน แต่ id ไม่เปลี่ยน */
    formReg: (formsReg()[0] || {}).id || '',
    formsPost: formsPost().slice(0, 1).map(function (f) { return f.id; }),
    windows: { reg: {}, chk: {}, srv: {} },
    /* true = ช่วงเวลานั้นยังเป็นค่าอัตโนมัติที่คำนวณจากรอบกิจกรรม
       ผู้ใช้แก้เองเมื่อไหร่จะพลิกเป็น false แล้วระบบเลิกเขียนทับช่วงนั้น */
    winAuto: { reg: true, chk: true, srv: true },
    publish: false, pin: false,
    /* ลำดับการแสดงบนหน้าเว็บสาธารณะ — ค่าว่าง = ให้ระบบต่อท้ายรายการเองตอนเผยแพร่ */
    sortOrder: '',
    combo: null,
    dirty: false,
    savedAt: null
  };

  var $ = function (id) { return document.getElementById(id); };

  /* ---------- ตัวช่วยสร้าง markup ---------- */
  function chipHtml(label, on, attr, icon) {
    return '<button type="button" class="ac-chip' + (on ? ' is-on' : '') + '" ' +
      attr + '="' + esc(label) + '" aria-pressed="' + on + '">' + (icon || '') + esc(label) + '</button>';
  }

  function markHtml(on, round) {
    return '<span class="ac-mark' + (round ? ' is-round' : '') + (on ? ' is-on' : '') + '">' +
      (round ? '' : '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>') +
      '</span>';
  }

  /* เครื่องหมายถูกท้ายรายการที่เลือกอยู่ ใช้กับ combobox แบบเลือกค่าเดียว
     แบบเลือกหลายค่ายังใช้ markHtml เพราะต้องเห็นช่องว่างของตัวที่ยังไม่เลือก */
  function tickHtml() {
    return '<svg class="ac-combo-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" ' +
      'stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
  }

  function toggleIn(key, value) {
    var arr = state[key];
    var i = arr.indexOf(value);
    if (i > -1) arr.splice(i, 1); else arr.push(value);
  }

  /* วิทยากรเลือกได้อิสระจากรายชื่อทั้งหมด ไม่ต้องดึงตามหลักสูตรที่เลือกไว้อีกต่อไป */
  function teachersForCourses() {
    return INSTRUCTORS.slice();
  }

  /* ---------- เช็กลิสต์ที่คุมปุ่มเผยแพร่และแถบความคืบหน้า ---------- */
  function checklist() {
    var first = state.slots[0] || {};
    var list = [
      { label: 'ชื่อกิจกรรม', ok: state.title.trim().length > 0 },
      { label: 'ประเภท', ok: !!state.kind },
      { label: 'หมวดหมู่', ok: state.cats.length > 0 },
      { label: 'รายละเอียด', ok: state.detail.trim().length > 0 },
      { label: 'รูปภาพปก', ok: state.cover },
      { label: 'สถานที่จัด', ok: state.place !== PLACE_EMPTY },
      { label: 'วันและเวลาอย่างน้อย 1 รอบ', ok: !!(first.date && first.start) },
      { label: 'กลุ่มเป้าหมาย', ok: state.targets.length > 0 }
      /* วิทยากรกับรูปแบบไม่อยู่ในเช็กลิสต์แล้ว — ไม่บังคับกรอกทั้งฝั่งหน้าจอและ ActivityRequest
         กิจกรรมบางประเภท (ข่าวสาร งานอีเวนท์) ไม่มีวิทยากรตั้งแต่แรก */
    ];

    if (needsReg()) list.push({ label: 'เลือกแบบลงทะเบียน', ok: !!state.formReg });
    if (hasSurvey()) list.push({ label: 'เลือกแบบประเมินหลังกิจกรรม', ok: state.formsPost.length === 1 });

    return list;
  }

  /* ================= เรนเดอร์แต่ละส่วน ================= */

  /* ตัวเลือกที่ไม่เปลี่ยนตามการกรอก (dropdown / radio) สร้างครั้งเดียวตอนเปิดหน้า
     ถ้าสร้างใหม่ทุกรอบ sync ช่องที่กำลังโฟกัสอยู่จะหลุดโฟกัสและ dropdown ที่เปิดค้างจะปิดเอง */
  function initOptions() {
    /* ประเภทเป็น dropdown ไม่ใช่ชิป เพราะเลือกได้อย่างเดียวและอยู่ติดกับ "หมวดหมู่"
       ที่เลือกได้หลายอัน — ใช้คนละรูปแบบกันช่วยให้แยกออกทันทีว่าอันไหนเลือกได้กี่ค่า */

    /* คลาส .ac-cond ไม่ใช่ .ac-check — .ac-check เป็นของเช็กลิสต์ในแผงขวาอยู่แล้ว ชนกันไม่ได้ */
    $('ac-reg').innerHTML = JOIN_FLAGS.map(function (f) {
      return '<label class="ac-cond" title="' + esc(f.hint) + '">' +
        '<input type="checkbox" data-join="' + f.key + '"' + (state.join[f.key] ? ' checked' : '') + '>' +
        '<span>' + esc(f.label) + '</span></label>';
    }).join('');

    /* ค่าเข้าร่วมมีสองทางที่ตัดกันชัด ใช้ radio ให้เห็นทั้งสองตัวเลือกพร้อมกัน
       ไม่ใช่ dropdown ที่ต้องกดก่อนถึงจะรู้ว่ามีอะไรให้เลือกบ้าง */
    $('ac-fee').innerHTML = FEES.map(function (f) {
      return '<label class="radio-item">' +
        '<input type="radio" name="ac-fee-opt" value="' + esc(f) + '" data-fee="' + esc(f) + '"' +
        (state.fee === f ? ' checked' : '') + '>' + esc(f) + '</label>';
    }).join('');
  }


  function renderChips() {

    Array.prototype.forEach.call($('ac-reg').querySelectorAll('[data-join]'), function (b) {
      b.checked = !!state.join[b.getAttribute('data-join')];
    });

    Array.prototype.forEach.call($('ac-fee').querySelectorAll('[data-fee]'), function (r) {
      r.checked = r.getAttribute('data-fee') === state.fee;
    });
  }

  /* เหลือแค่ชื่อชุดกับไอคอนตา — รายละเอียด (จำนวนคำถาม ฯลฯ) อยู่ในตัวอย่างที่กดดูได้อยู่แล้ว
     พิมพ์ซ้ำไว้ทุกแถวกินพื้นที่ฟรี ทั้งที่อ่านครั้งเดียวก็พอ */
  function pickRow(o, on, attr, round) {
    return '<div class="ac-pick-row">' +
      '<button type="button" class="ac-pick' + (on ? ' is-on' : '') + '" ' + attr + '="' + esc(o.id) + '"' +
      ' title="' + esc(formHint(o)) + '">' +
      markHtml(on, round) +
      '<span class="ac-pick-title">' + esc(o.name) + '</span>' +
      '</button>' +
      previewBtn(o.id) +
      '</div>';
  }

  /* ไม่มีชุดที่เปิดใช้งานให้เลือก ต้องบอกว่าไปสร้างที่ไหน ไม่ใช่ปล่อยว่างเฉย ๆ
     จนผู้ใช้คิดว่าหน้าจอเสีย */
  function pickEmpty(what) {
    return '<p class="ac-field-note is-warning">ยังไม่มี' + esc(what) + 'ที่เปิดใช้งาน — ' +
      'ไปสร้างและเปิดใช้งานที่เมนู ประเมินสุขภาพ › แบบประเมิน ก่อน</p>';
  }

  function renderPicks() {
    var reg = formsReg(), post = formsPost();

    $('ac-form-reg').innerHTML = reg.length
      ? reg.map(function (o) { return pickRow(o, state.formReg === o.id, 'data-form-reg', true); }).join('')
      : pickEmpty('แบบลงทะเบียน');

    $('ac-form-post').innerHTML = post.length
      ? post.map(function (o) { return pickRow(o, state.formsPost.indexOf(o.id) > -1, 'data-form-post', true); }).join('')
      : pickEmpty('แบบประเมิน');
  }

  /* ---------- ดูตัวอย่างแบบประเมิน ----------
     ชื่อชุดอย่างเดียวบอกไม่ได้ว่าข้างในถามอะไร ผู้ใช้จึงเลือกผิดชุดได้ง่าย
     ปุ่มนี้เปิดรายการคำถามจริงให้ดูก่อนตัดสินใจ โดยไม่ต้องออกจากหน้านี้ */
  function previewBtn(id) {
    return '<button type="button" class="ac-preview-btn" data-form-preview="' + esc(id) + '"' +
      ' title="ดูคำถามในชุดนี้" aria-label="ดูตัวอย่างคำถามของแบบประเมิน ' + esc(id) + '">' +
      '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>' +
      '</button>';
  }

  function openFormPreview(id) {
    var o = formById(id);
    if (!o) return;

    /* ระบบแบบประเมินเก็บแค่จำนวนคำถามในรายการ ยังไม่ส่งตัวคำถามมาด้วย
       จึงแสดงเท่าที่มีจริง แล้วชี้ทางไปดูตัวเต็มที่หน้าแบบประเมิน
       ดีกว่าแต่งคำถามปลอมขึ้นมาให้ดูเหมือนมีข้อมูล */
    var qs = o.questions || [];
    var need = qs.filter(function (q) { return q.required; }).length;
    var count = qs.length || o.questionCount || 0;

    var body = qs.length
      ? '<ol class="ac-preview-list">' +
          qs.map(function (q) {
            return '<li class="ac-preview-q">' +
              '<span class="ac-preview-q-text">' + esc(q.text) +
                (q.required ? '<span class="req-mark">*</span>' : '') + '</span>' +
              '<span class="ac-preview-q-type">' + esc(q.type) + '</span>' +
              '</li>';
          }).join('') +
        '</ol>'
      : '<p class="ac-field-note">ชุดนี้ไม่มีคำถามเพิ่มเติม · ดูและแก้ไขได้ที่ ' +
        '<a href="/admin/evaluations">เมนู แบบประเมิน</a></p>';

    var root = document.createElement('div');
    root.className = 'modal-overlay is-open ac-preview-overlay';
    root.innerHTML =
      '<div class="modal ac-preview-modal" role="dialog" aria-modal="true" aria-label="ตัวอย่างแบบประเมิน">' +
        '<div class="modal-header">' +
          '<div class="ac-preview-head">' +
            '<span class="ac-preview-name">' + esc(o.name) + '</span>' +
            '<span class="ac-preview-meta">' + count + ' คำถาม' +
              (qs.length ? ' · จำเป็นต้องตอบ ' + need : '') + '</span>' +
          '</div>' +
          '<button type="button" class="modal-close" data-preview-close aria-label="ปิด">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
          '</button>' +
        '</div>' +
        '<div class="modal-body">' + body + '</div>' +
        '<div class="modal-footer">' +
          '<button type="button" class="btn btn-outline" data-preview-close>ปิด</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(root);
    document.body.style.overflow = 'hidden';
    root.querySelector('[data-preview-close]').focus();
  }

  function closeFormPreview() {
    var el = document.querySelector('.ac-preview-overlay');
    if (!el) return;
    el.remove();
    if (!document.querySelector('.modal-overlay.is-open')) document.body.style.overflow = '';
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-preview-close]') || e.target.classList.contains('ac-preview-overlay')) return closeFormPreview();
    var btn = e.target.closest('[data-form-preview]');
    if (btn) { e.preventDefault(); e.stopPropagation(); openFormPreview(btn.getAttribute('data-form-preview')); }
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeFormPreview();
  });

  /* ตัวสร้าง combobox แบบเลือกได้หลายค่า ใช้กับกลุ่มเป้าหมาย
     (หมวดหมู่เปลี่ยนเป็นเลือกค่าเดียวแล้ว ดู renderCatCombo — หลักสูตรกับวิทยากรมีเงื่อนไขเฉพาะจึงยังแยกฟังก์ชันไว้)

     cfg = { stateKey, valuesId, panelId, removeAttr, itemAttr, placeholder, options }
     options เป็นรายการ { value, icon } — icon เว้นว่างได้ */
  function renderTagCombo(cfg) {
    var selected = state[cfg.stateKey];

    $(cfg.valuesId).innerHTML = selected.length
      ? selected.map(function (v) {
          return '<span class="ac-tag">' + esc(v) +
            '<button type="button" class="ac-tag-x" ' + cfg.removeAttr + '="' + esc(v) + '" aria-label="เอา ' + esc(v) + ' ออก">' +
            '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></span>';
        }).join('')
      : '<span class="ac-combo-placeholder">' + esc(cfg.placeholder) + '</span>';

    $(cfg.panelId).innerHTML = cfg.options.map(function (o) {
      var on = selected.indexOf(o.value) > -1;
      return '<button type="button" class="ac-combo-item' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + on + '" ' +
        cfg.itemAttr + '="' + esc(o.value) + '">' +
        markHtml(on, false) + (o.icon ? catIconSvg(o.icon) : '') + esc(o.value) + '</button>';
    }).join('');
  }

  /* combobox แบบเลือกค่าเดียว ใช้แทน <select> ของเบราว์เซอร์
     เพราะรายการที่เบราว์เซอร์วาดเองแต่งด้วย CSS ไม่ได้เลย จึงมุมเหลี่ยมและหน้าตาต่างกันทุกเครื่อง
     เลือกแล้วปิดแผงทันที ต่างจากแบบเลือกหลายค่าที่เปิดค้างไว้ให้เลือกต่อ

     cfg = { stateKey, valuesId, panelId, itemAttr, options, placeholder } */
  function renderPickCombo(cfg) {
    var current = state[cfg.stateKey];
    var isEmpty = !current || current === cfg.placeholder;

    $(cfg.valuesId).innerHTML = isEmpty
      ? '<span class="ac-combo-placeholder">' + esc(cfg.placeholder || 'เลือก') + '</span>'
      : '<span class="ac-combo-single">' + esc(current) + '</span>';

    $(cfg.panelId).innerHTML = cfg.options.map(function (v) {
      var on = v === current;
      return '<button type="button" class="ac-combo-item is-plain' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + on + '" ' +
        cfg.itemAttr + '="' + esc(v) + '">' + esc(v) + (on ? tickHtml() : '') + '</button>';
    }).join('');
  }

  function renderEventCombo() {
    /* แสดงเฉพาะตอนกรอกกิจกรรม — อีเวนท์ซ้อนอีเวนท์ไม่ได้ */
    var field = $('ac-field-parent-event');
    if (!field) return;
    field.hidden = state.kind !== 'กิจกรรม';

    renderPickCombo({
      stateKey: 'parentEvent', valuesId: 'ac-event-values', panelId: 'ac-event-panel',
      itemAttr: 'data-parent-event', options: EVENTS, placeholder: 'ไม่อยู่ในอีเวนท์'
    });

    /* ยังไม่มีอีเวนท์ให้เลือก ต้องบอกเหตุผล ไม่ใช่เปิดแผงเปล่า */
    if (!EVENTS.length) {
      $('ac-event-panel').innerHTML = '<div class="ac-combo-empty">ยังไม่มีอีเวนท์ในระบบ — สร้างรายการประเภท "อีเว้นท์" ก่อน</div>';
    }
  }

  function renderSingleCombos() {
    renderPickCombo({ stateKey: 'kind', valuesId: 'ac-kind-values', panelId: 'ac-kind-panel', itemAttr: 'data-kind', options: KINDS });
    renderPickCombo({ stateKey: 'mode', valuesId: 'ac-mode-values', panelId: 'ac-mode-panel', itemAttr: 'data-mode', options: MODES });
    renderPickCombo({
      stateKey: 'place', valuesId: 'ac-place-values', panelId: 'ac-place-panel', itemAttr: 'data-place',
      /* ตัวแรกของ PLACES เป็นข้อความ placeholder ไม่ใช่พื้นที่จริง จึงไม่ใส่ลงรายการให้เลือก */
      options: PLACES.slice(1), placeholder: PLACE_EMPTY
    });
  }

  /* หมวดหมู่เลือกได้ค่าเดียวเท่านั้น — เหมือน renderPickCombo แต่มีไอคอนนำหน้าตัวเลือก */
  function renderCatCombo() {
    var current = (state.cats || [])[0] || '';
    var currentOpt = CATS.filter(function (c) { return c.name === current; })[0];

    $('ac-cat-values').innerHTML = current
      ? '<span class="ac-combo-single">' + (currentOpt && currentOpt.icon ? catIconSvg(currentOpt.icon) : '') + esc(current) + '</span>'
      : '<span class="ac-combo-placeholder">คลิกเพื่อเลือกหมวดหมู่</span>';

    $('ac-cat-panel').innerHTML = CATS.map(function (c) {
      var on = c.name === current;
      return '<button type="button" class="ac-combo-item is-plain' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + on + '" data-cat="' + esc(c.name) + '">' +
        (c.icon ? catIconSvg(c.icon) : '') + esc(c.name) + (on ? tickHtml() : '') + '</button>';
    }).join('');
  }

  function renderTargetCombo() {
    renderTagCombo({
      stateKey: 'targets',
      valuesId: 'ac-target-values',
      panelId: 'ac-target-panel',
      removeAttr: 'data-remove-target',
      itemAttr: 'data-target',
      placeholder: 'คลิกเพื่อเลือกกลุ่มเป้าหมาย',
      options: TARGETS.map(function (t) { return { value: t, icon: '' }; })
    });
  }

  /* combobox 2 เลเวล: ชื่อโปรแกรมเป็นหัวข้อกดไม่ได้ หลักสูตรย่อยกดเลือกได้ */
  function renderCourseCombo() {
    var values = $('ac-course-values');
    values.innerHTML = state.courses.length
      ? state.courses.map(function (c) {
          return '<span class="ac-tag">' + esc(c) +
            '<button type="button" class="ac-tag-x" data-remove-course="' + esc(c) + '" aria-label="เอา ' + esc(c) + ' ออก">' +
            '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></span>';
        }).join('')
      : '<span class="ac-combo-placeholder">คลิกเพื่อเลือกหลักสูตร</span>';

    $('ac-course-panel').innerHTML = CATALOG.map(function (p) {
      return '<span class="ac-combo-group">' + esc(p.program) + '</span>' +
        p.courses.map(function (c) {
          var on = state.courses.indexOf(c.name) > -1;
          return '<button type="button" class="ac-combo-item' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + on + '" data-course="' + esc(c.name) + '">' +
            markHtml(on, false) + esc(c.name) + '</button>';
        }).join('');
    }).join('');
  }

  function renderHostCombo() {
    var opts = teachersForCourses();
    var values = $('ac-host-values');

    values.innerHTML = state.hosts.length
      ? state.hosts.map(function (h) {
          return '<span class="ac-tag">' + esc(h) +
            '<button type="button" class="ac-tag-x" data-remove-host="' + esc(h) + '" aria-label="เอา ' + esc(h) + ' ออก">' +
            '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></span>';
        }).join('')
      : '<span class="ac-combo-placeholder">คลิกเพื่อเลือกวิทยากร</span>';

    $('ac-host-panel').innerHTML = opts.length
      ? opts.map(function (h) {
          var on = state.hosts.indexOf(h) > -1;
          return '<button type="button" class="ac-combo-item' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + on + '" data-host="' + esc(h) + '">' +
            markHtml(on, false) + esc(h) + '</button>';
        }).join('')
      : '<span class="ac-combo-empty">ยังไม่มีวิทยากรในระบบ</span>';
  }

  /* หนึ่งรอบ = หนึ่งแถว ป้ายกำกับอยู่บนหัวตารางแถวเดียว ไม่ซ้ำทุกรอบ
     เดิมแต่ละรอบเป็นการ์ดที่มีหัวการ์ด + ป้าย 4 ช่อง กินความสูงเกือบสามเท่า
     ทั้งที่ข้อมูลมีแค่ 4 ค่า และผู้ใช้เทียบรอบกันได้ง่ายกว่าเมื่ออยู่เป็นคอลัมน์ */
  function renderSlots() {
    var single = state.slots.length <= 1;
    $('ac-slots').innerHTML =
      '<div class="ac-slot-row is-head" aria-hidden="true">' +
        '<span></span><span>วันที่จัด</span><span>เริ่ม</span><span>สิ้นสุด</span><span>รับได้ (คน)</span><span></span>' +
      '</div>' +
      state.slots.map(function (s, i) {
        return '<div class="ac-slot-row" data-slot="' + s.id + '">' +
          '<span class="ac-slot-no">' + (i + 1) + '</span>' +
          slotField(s, 'date', 'วันที่จัด', 'date', i + 1) +
          slotField(s, 'start', 'เริ่ม', 'time', i + 1) +
          slotField(s, 'end', 'สิ้นสุด', 'time', i + 1) +
          slotField(s, 'cap', 'รับได้ (คน)', 'number', i + 1) +
          '<button type="button" class="ac-slot-remove" data-remove-slot="' + s.id + '"' + (single ? ' disabled' : '') +
            ' aria-label="ลบรอบที่ ' + (i + 1) + '">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
          '</button>' +
          '</div>';
      }).join('');

    $('ac-add-slot').disabled = state.slots.length >= MAX_SLOTS;
  }

  /* วันที่กับเวลาใช้ช่องของระบบ (datetime-picker.js) ไม่ใช้ของเบราว์เซอร์
     วันที่เลือกจากปฏิทิน · เวลาพิมพ์เอง · ค่าจริงเก็บที่ data-iso ส่วน value เป็นข้อความไทย
     ป้ายกำกับอยู่บนหัวตาราง ในแถวจึงเหลือแต่ช่องกรอก และผูกชื่อด้วย aria-label แทน */
  function slotField(slot, key, label, type, no) {
    var v = esc(slot[key] || '');
    var aria = ' aria-label="' + esc(label + ' ของรอบที่ ' + no) + '"';
    var attrs = ' data-slot-id="' + slot.id + '" data-slot-key="' + key + '"' + aria;

    if (type === 'date' || type === 'time') {
      return '<input type="text" class="input" data-picker="' + type + '" data-iso="' + v + '"' +
        ' placeholder="' + (type === 'date' ? 'เลือกวันที่' : '09:30') + '"' + attrs + '>';
    }
    return '<input type="' + type + '" class="input" value="' + v + '" min="0" inputmode="numeric" placeholder="0"' +
      attrs + '>';
  }

  /* แถวช่วงเวลามาจากสิ่งที่ติ๊กไว้ใน "รูปแบบการเข้าร่วม" ที่เดียว ไม่มีช่องติ๊กซ้ำที่นี่อีก
     ไม่งั้นผู้ใช้ต้องตอบคำถามเดียวกันสองรอบ แล้วสองที่ขัดกันเองได้
     (เช่น ไม่ติ๊ก "ต้องลงทะเบียน" แต่ยังตั้งช่วงเวลาเปิดลงทะเบียนไว้) */
  var WINDOWS = [
    { key: 'reg', flag: 'reg',    label: 'ลงทะเบียน' },
    { key: 'chk', flag: 'chk',    label: 'Check-in' },
    { key: 'srv', flag: 'survey', label: 'แบบประเมินหลังกิจกรรม' }
  ];

  function activeWindows() {
    return WINDOWS.filter(function (w) { return !!state.join[w.flag]; });
  }

  /* ---------- ค่าเริ่มต้นของช่วงเวลาเปิด–ปิดระบบ ----------
     กรอกวันจัดกิจกรรมแล้วระบบเติมช่วงเวลาที่เหลือให้ตามแนวปฏิบัติมาตรฐานของระบบอีเวนท์:
       ลงทะเบียน  เปิดวันนี้ · ปิดเมื่อกิจกรรมเริ่ม
       Check-in   เปิดก่อนรอบแรกเริ่ม 1 ชั่วโมง · ปิดเมื่อรอบสุดท้ายจบ
       แบบประเมิน เปิดเมื่อรอบสุดท้ายจบ · ปิดหลังจากนั้น 7 วัน
     ทุกช่องยังแก้เองได้ — แก้เมื่อไหร่ช่วงนั้นหยุดเป็นอัตโนมัติทันที (winAuto) */
  function pad2(n) { return String(n).padStart(2, '0'); }

  function todayIso() {
    var d = new Date();
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }

  function addDaysIso(iso, days) {
    var p = iso.split('-');
    var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]) + days);
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }

  function minusMinutes(time, mins) {
    var p = time.split(':');
    var total = Math.max(0, Number(p[0]) * 60 + Number(p[1]) - mins);
    return pad2(Math.floor(total / 60)) + ':' + pad2(total % 60);
  }

  function windowDefaults() {
    var slots = state.slots
      .filter(function (s) { return s.date; })
      .sort(function (a, b) { return a.date.localeCompare(b.date); });
    if (!slots.length) return null;

    var first = slots[0];
    var last = slots[slots.length - 1];
    var startT = first.start || '09:00';
    var endT = last.end || '16:00';
    var today = todayIso();

    return {
      /* กิจกรรมย้อนหลัง (บันทึกข้อมูลเก่า) ไม่ควรได้ช่วงลงทะเบียนที่เริ่มหลังจบ */
      reg: { from: today < first.date ? today : first.date, fromT: '09:00', to: first.date, toT: startT },
      chk: { from: first.date, fromT: minusMinutes(startT, 60), to: last.date, toT: endT },
      srv: { from: last.date, fromT: endT, to: addDaysIso(last.date, 7), toT: '23:59' }
    };
  }

  function applyWindowDefaults() {
    var d = windowDefaults();
    if (!d) return;
    WINDOWS.forEach(function (w) {
      if (!state.join[w.flag] || !state.winAuto[w.key]) return;
      state.windows[w.key] = d[w.key];
    });
  }

  function renderWindows() {
    applyWindowDefaults();
    var list = activeWindows();
    $('ac-windows').innerHTML = list.length
      ? list.map(function (w) {
          var v = state.windows[w.key] || {};
          return '<div class="ac-window">' +
            '<span class="ac-window-name">' + esc(w.label) + '</span>' +
            /* ตัดคำว่า "ตั้งแต่/ถึง" เหลือขีดคั่น เพื่อให้ทั้งช่วงอยู่ในบรรทัดเดียวได้จริง */
            '<div class="ac-window-range">' +
              winInput(w.key, 'from', 'date', v.from) +
              winInput(w.key, 'fromT', 'time', v.fromT) +
              '<span class="ac-window-sep">–</span>' +
              winInput(w.key, 'to', 'date', v.to) +
              winInput(w.key, 'toT', 'time', v.toT) +
            '</div>' +
            '</div>';
        }).join('') + windowAutoNote(list)
      : '<p class="ac-field-note">ยังไม่ได้ติ๊กขั้นตอนใดในรูปแบบการเข้าร่วม จึงไม่มีช่วงเวลาให้กำหนด</p>';
  }

  /* บอกให้รู้ว่าค่าที่เห็นมาจากไหน — ไม่งั้นช่องที่จู่ ๆ มีค่าเองจะดูเหมือนกรอกค้างไว้ */
  function windowAutoNote(list) {
    var hasAuto = list.some(function (w) { return state.winAuto[w.key]; });
    if (!hasAuto || !windowDefaults()) return '';
    return '<p class="ac-field-note">ระบบเติมช่วงเวลาให้อัตโนมัติจากรอบกิจกรรม — แก้ไขได้ทุกช่อง</p>';
  }

  function winInput(group, key, type, value) {
    return '<input type="text" class="input ac-window-input" data-picker="' + type + '"' +
      ' data-iso="' + esc(value || '') + '" placeholder="' + (type === 'date' ? 'เลือกวันที่' : '09:30') + '"' +
      ' data-win-group="' + group + '" data-win-key="' + key + '">';
  }

  function renderToggles() {
    var items = [
      { key: 'publish', label: 'เผยแพร่บนหน้าเว็บผู้เข้าร่วม',
        hint: state.publish ? 'ผู้เข้าร่วมเห็นและลงทะเบียนได้ทันทีหลังบันทึก' : 'ยังไม่เผยแพร่ — บันทึกไว้เป็นฉบับร่างได้' },
      { key: 'pin', label: 'ปักหมุดเป็นกิจกรรมแนะนำ', hint: 'แสดงในแถบแนะนำด้านบนสุดของหน้าเว็บ' }
    ];
    $('ac-toggles').innerHTML = items.map(function (t) {
      var on = state[t.key];
      return '<div class="ac-toggle">' +
        '<div class="ac-toggle-text">' +
          '<span class="ac-toggle-label">' + esc(t.label) + '</span>' +
          '<span class="ac-toggle-hint">' + esc(t.hint) + '</span>' +
        '</div>' +
        '<button type="button" class="ac-switch' + (on ? ' is-on' : '') + '" role="switch" aria-checked="' + on + '"' +
          ' data-toggle="' + t.key + '" aria-label="' + esc(t.label) + '"><span></span></button>' +
        '</div>';
    }).join('') + sortOrderFieldHtml();
  }

  /* ลำดับการแสดงบนหน้าเว็บสาธารณะ — โผล่เฉพาะตอนเปิดเผยแพร่ เพราะกิจกรรมที่ไม่เผยแพร่
     ไม่มีที่ให้แสดงอยู่แล้ว · เว้นว่างได้ ระบบจะต่อท้ายรายการให้เองตอนบันทึก */
  function sortOrderFieldHtml() {
    if (!state.publish) return '';
    return '<div class="ac-toggle">' +
      '<div class="ac-toggle-text">' +
        '<label class="ac-toggle-label" for="ac-sort-order">ลำดับการแสดงบนหน้าเว็บ</label>' +
        '<span class="ac-toggle-hint">เลขน้อยขึ้นก่อน · เว้นว่าง = ระบบต่อท้ายรายการให้อัตโนมัติ</span>' +
      '</div>' +
      '<input type="number" class="input ac-sort-input" id="ac-sort-order" min="1" step="1"' +
        ' inputmode="numeric" placeholder="อัตโนมัติ" value="' + esc(state.sortOrder) + '">' +
      '</div>';
  }

  /* ---------- แผงขวา ---------- */
  function renderPreview() {
    var first = state.slots[0] || {};
    var totalCap = state.slots.reduce(function (n, s) { return n + (parseInt(s.cap, 10) || 0); }, 0);
    var hasFee = state.fee === FEES[1];

    var previewCover = $('ac-preview-cover');
    var previewImage = $('ac-preview-cover-image');
    var previewText = previewCover.querySelector('.ac-preview-cover-text');
    previewCover.classList.toggle('is-filled', state.cover);
    previewImage.hidden = !state.coverUrl;
    previewImage.src = state.coverUrl || '';
    previewText.hidden = !!state.coverUrl;
    previewText.textContent = state.cover
      ? (state.coverLabel || 'มีรูปภาพปกแล้ว')
      : 'ยังไม่ได้อัปโหลดรูปปก';

    /* ตัวอย่างแสดงเฉพาะหมวดหมู่ — "กิจกรรม/อีเว้นท์" เป็นข้อมูลสำหรับจัดการภายใน
       ผู้เข้าร่วมไม่ได้ใช้แยกแยะอะไร ใส่ไปก็เบียดพื้นที่ป้ายที่มีความหมายจริง */
    $('ac-preview-tags').innerHTML = state.cats.slice(0, 3).map(function (t) {
      return '<span class="ac-preview-tag">' + esc(t) + '</span>';
    }).join('');

    var titleEl = $('ac-preview-title');
    titleEl.textContent = state.title.trim() || 'ชื่อกิจกรรมจะแสดงตรงนี้';
    titleEl.classList.toggle('is-empty', !state.title.trim());

    /* ค่าที่ยังไม่กรอกแสดงเป็นข้อความเทา ไม่ปล่อยเป็นช่องว่าง ผู้ใช้จะได้รู้ว่ายังขาดอะไร */
    var when = first.date
      ? window.TFC.formatThaiDate(first.date) + (first.start ? ' · ' + first.start + (first.end ? '–' + first.end : '') + ' น.' : '')
      : 'ยังไม่ระบุวันที่';
    var place = state.place === PLACE_EMPTY ? 'ยังไม่ระบุสถานที่' : state.place;
    var fee = hasFee ? (state.feeAmount ? Number(state.feeAmount).toLocaleString('th-TH') + ' บาท' : 'มีค่าเข้าร่วม') : 'เข้าร่วมฟรี';
    var seats = totalCap > 0 ? 'รับ ' + totalCap + ' คน' : 'ยังไม่ระบุจำนวนรับ';

    var rows = [
      { icon: '<rect x="4" y="6" width="16" height="14" rx="2"/><path d="M8 3v4M16 3v4M4 11h16"/>', text: when, empty: !first.date },
      { icon: '<path d="M12 21s6.5-6 6.5-10.5a6.5 6.5 0 1 0-13 0C5.5 15 12 21 12 21Z"/><circle cx="12" cy="10.4" r="2.2"/>', text: place, empty: state.place === PLACE_EMPTY },
      { icon: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.8v8.4M9.6 10.2h4.2a1.8 1.8 0 0 1 0 3.6H9.6"/>', text: fee, empty: false },
      { icon: '<path d="M16 20v-1.6a3.4 3.4 0 0 0-3.4-3.4H6.4A3.4 3.4 0 0 0 3 18.4V20"/><circle cx="9.5" cy="8" r="3.4"/><path d="M17 11.5h4"/>', text: seats, empty: totalCap === 0 }
    ];

    $('ac-preview-meta').innerHTML = rows.map(function (r) {
      return '<span class="ac-preview-row' + (r.empty ? ' is-empty' : '') + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' + r.icon + '</svg>' +
        esc(r.text) + '</span>';
    }).join('');
  }

  function renderQr() {
    if (!QR_LINKS.length) {
      $('ac-qr-hint').textContent = window.TFC_ACTIVITY_IS_NEW
        ? 'QR จะถูกสร้างอัตโนมัติหลังบันทึกกิจกรรม'
        : 'บันทึกกิจกรรมเพื่อสร้าง QR ตามขั้นตอนที่เปิดไว้';
      $('ac-qr-list').innerHTML = '<p class="ac-field-note">ยังไม่มี QR สำหรับกิจกรรมนี้</p>';
      $('ac-qr-all').disabled = true;
      return;
    }

    var activeCount = QR_LINKS.filter(function (q) { return q.active; }).length;
    $('ac-qr-hint').textContent = activeCount === QR_LINKS.length
      ? 'QR พร้อมใช้งานและสแกนได้จริง'
      : 'ดาวน์โหลดเตรียมไว้ได้ · QR จะเปิดใช้งานเมื่อเผยแพร่กิจกรรม';

    $('ac-qr-list').innerHTML = QR_LINKS.map(function (q) {
      return '<div class="ac-qr-item' + (q.active ? '' : ' is-off') + '">' +
        '<span class="ac-qr-thumb"><img src="' + esc(q.imageUrl) + '" alt="QR ' + esc(q.label) + '"></span>' +
        '<span class="ac-qr-text">' +
          '<span class="ac-qr-label">' + esc(q.label) + '</span>' +
          '<span class="ac-qr-url">' + esc(q.url) + '</span>' +
          '<span class="ac-field-note">' + (q.active ? 'เปิดใช้งาน' : 'ยังไม่เปิดใช้งาน') + '</span>' +
        '</span>' +
        '<span class="ac-qr-actions">' +
          '<button type="button" class="ac-qr-btn" data-qr-download="' + esc(q.downloadUrl) + '" aria-label="ดาวน์โหลด QR ' + esc(q.label) + '">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg></button>' +
          '<button type="button" class="ac-qr-btn" data-copy="' + esc(q.url) + '" aria-label="คัดลอกลิงก์ ' + esc(q.label) + '">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/></svg></button>' +
        '</span>' +
        '</div>';
    }).join('');

    $('ac-qr-all').disabled = false;
  }

  function renderChecklist() {
    var list = checklist();
    var okCount = list.filter(function (d) { return d.ok; }).length;
    var ready = okCount === list.length;

    $('ac-checklist').innerHTML = list.map(function (d) {
      return '<span class="ac-check' + (d.ok ? ' is-ok' : '') + '">' +
        '<span class="ac-check-dot">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(d.label) + '</span>';
    }).join('');

    var badge = $('ac-done-badge');
    badge.textContent = okCount + '/' + list.length;
    badge.classList.toggle('is-ready', ready);

    $('ac-progress-fill').style.width = Math.round((okCount / list.length) * 100) + '%';
    $('ac-progress-fill').classList.toggle('is-ready', ready);
    $('ac-progress-text').textContent = ready
      ? 'กรอกครบแล้ว พร้อมเผยแพร่'
      : 'กรอกครบ ' + okCount + ' จาก ' + list.length + ' ข้อที่จำเป็น';
    $('ac-publish').disabled = !ready;
  }

  function renderCover() {
    var zone = $('ac-cover');
    var image = $('ac-cover-preview-image');
    zone.classList.toggle('is-filled', state.cover);
    zone.classList.toggle('has-image', !!state.coverUrl);
    image.hidden = !state.coverUrl;
    image.src = state.coverUrl || '';
    /* ชื่อไฟล์จริงมาจาก state.coverLabel ที่หน้าจอตั้งให้หลังอัปโหลดสำเร็จ
       ข้อความตัวอย่างใช้เฉพาะหน้า static เดิมที่ยังอัปโหลดจริงไม่ได้ */
    $('ac-cover-headline').textContent = state.cover
      ? (state.coverLabel || 'cover-ปลูกผักปลอดสาร.jpg · 1.8MB')
      : 'ลากไฟล์มาวาง หรือเลือกจากเครื่อง';
    $('ac-cover-pick').textContent = state.cover ? 'เปลี่ยนรูป' : 'เลือกไฟล์';
    $('ac-cover-remove').hidden = !state.cover;
  }

  /* เรนเดอร์ทุกส่วนที่ขึ้นกับ state — ไม่แตะ input ที่ผู้ใช้กำลังพิมพ์ */
  function sync() {
    renderChips();
    renderPicks();
    renderSingleCombos();
    renderEventCombo();
    renderCatCombo();
    renderTargetCombo();
    renderCourseCombo();
    renderHostCombo();
    renderWindows();
    renderToggles();
    renderCover();
    renderPreview();
    renderQr();
    renderChecklist();

    /* ไม่มีค่าใช้จ่าย = ไม่ต้องมีช่องกรอกจำนวนเงินให้สับสน */
    $('ac-fee-amount').hidden = state.fee !== FEES[1];

    /* ฟิลด์ที่มีความหมายเฉพาะตอนเปิดให้ลงทะเบียนล่วงหน้าเท่านั้น
       ถ้าเข้าร่วมได้เลย การถามว่าใครลงทะเบียนได้/สำรองที่นั่งกี่คน/ใช้แบบฟอร์มไหน ไม่มีความหมาย */
    $('ac-field-form-reg').hidden = !needsReg();
    $('ac-field-form-post').hidden = !hasSurvey();
    $('ac-title-count').textContent = state.title.length + '/120';
    $('ac-detail-count').textContent = state.detail.length + '/500';
  }

  function touch() {
    state.dirty = true;
    sync();
  }

  /* ================= เหตุการณ์ ================= */

  $('ac-title').addEventListener('input', function () { state.title = this.value; touch(); });
  $('ac-detail').addEventListener('input', function () { state.detail = this.value; touch(); });
  $('ac-fee-input').addEventListener('input', function () { state.feeAmount = this.value; touch(); });


  /* ติ๊กรูปแบบการเข้าร่วม — คุมทั้งแบบฟอร์มที่ต้องเลือกและแถวช่วงเวลาข้างล่าง */
  $('ac-reg').addEventListener('change', function (e) {
    var box = e.target.closest('[data-join]');
    if (!box) return;
    state.join[box.getAttribute('data-join')] = box.checked;
    touch();
  });

  /* radio ค่าเข้าร่วม — ผูกที่ container เพราะ input ถูกสร้างจาก JS */
  $('ac-fee').addEventListener('change', function (e) {
    var r = e.target.closest('[data-fee]');
    if (!r) return;
    state.fee = r.getAttribute('data-fee');
    if (state.fee !== FEES[1]) { state.feeAmount = ''; $('ac-fee-input').value = ''; }
    touch();
  });


  /* ช่องที่ใช้ตัวเลือกวงล้อเก็บค่าจริง (ISO) ไว้ที่ data-iso ส่วน .value เป็นข้อความไทย */
  function fieldValue(el) {
    return el.hasAttribute('data-picker') ? (el.getAttribute('data-iso') || '') : el.value;
  }

  /* input ของรอบกิจกรรมและช่วงเวลาถูกเรนเดอร์ใหม่ได้ จึงผูก event แบบ delegate */
  document.addEventListener('input', function (e) {
    if (e.target.id === 'ac-sort-order') {
      state.sortOrder = e.target.value;
      state.dirty = true;
      return;
    }

    var slot = e.target.closest('[data-slot-id]');
    if (slot) {
      var row = state.slots.filter(function (s) { return String(s.id) === slot.getAttribute('data-slot-id'); })[0];
      if (!row) return;
      var key = slot.getAttribute('data-slot-key');
      row[key] = fieldValue(slot);
      state.dirty = true;

      /* เพิ่งเลือกวันที่และยังไม่ได้กรอกเวลา — เติมเวลาทำการมาตรฐานให้ก่อน (แก้ได้)
         เขียนลงช่องใน DOM ตรง ๆ ไม่ re-render ทั้งแถว เพื่อไม่ให้โฟกัสหลุดจากช่องที่เพิ่งใช้ */
      if (key === 'date' && row.date && !row.start && !row.end) {
        row.start = '09:00';
        row.end = '16:00';
        var rowEl = slot.closest('.ac-slot-row');
        ['start', 'end'].forEach(function (k) {
          var el = rowEl && rowEl.querySelector('[data-slot-key="' + k + '"]');
          if (el) { el.setAttribute('data-iso', row[k]); el.value = row[k] + ' น.'; }
        });
      }

      /* วันเวลาของรอบเปลี่ยน = ช่วงเวลาเปิด–ปิดระบบที่เป็นค่าอัตโนมัติต้องขยับตาม */
      if (key !== 'cap') renderWindows();
      renderPreview();
      renderChecklist();
      return;
    }
    var win = e.target.closest('[data-win-group]');
    if (win) {
      var g = win.getAttribute('data-win-group');
      state.windows[g] = state.windows[g] || {};
      state.windows[g][win.getAttribute('data-win-key')] = fieldValue(win);
      /* ผู้ใช้ลงมือแก้ช่วงนี้เองแล้ว — ระบบเลิกคำนวณทับให้ ไม่งั้นค่าที่แก้จะเด้งกลับ */
      state.winAuto[g] = false;
      state.dirty = true;
    }
  });


  document.addEventListener('click', function (e) {
    var t = e.target;

    /* ---- combobox เลือกค่าเดียว (ประเภท · สถานที่ · รูปแบบ) ----
       เลือกแล้วปิดแผงทันที เพราะเลือกได้ค่าเดียวอยู่แล้ว ไม่มีเหตุให้เปิดค้าง */
    if (t.closest('#ac-event-control')) { state.combo = state.combo === 'event' ? null : 'event'; return syncCombo(); }

    var pickEvent = t.closest('[data-parent-event]');
    if (pickEvent) {
      var value = pickEvent.getAttribute('data-parent-event');
      /* กดค่าเดิมซ้ำ = เอาออก เพราะไม่มีปุ่มล้างค่าแยก */
      state.parentEvent = state.parentEvent === value ? '' : value;
      state.combo = null; touch(); return syncCombo();
    }

    if (t.closest('#ac-kind-control')) { state.combo = state.combo === 'kind' ? null : 'kind'; return syncCombo(); }
    if (t.closest('#ac-place-control')) { state.combo = state.combo === 'place' ? null : 'place'; return syncCombo(); }
    if (t.closest('#ac-mode-control')) { state.combo = state.combo === 'mode' ? null : 'mode'; return syncCombo(); }

    var pickKind = t.closest('[data-kind]');
    if (pickKind) { state.kind = pickKind.getAttribute('data-kind'); state.combo = null; touch(); return syncCombo(); }

    var pickPlace = t.closest('[data-place]');
    if (pickPlace) { state.place = pickPlace.getAttribute('data-place'); state.combo = null; touch(); return syncCombo(); }

    var pickMode = t.closest('[data-mode]');
    if (pickMode) { state.mode = pickMode.getAttribute('data-mode'); state.combo = null; touch(); return syncCombo(); }
    /* ---- กลุ่มเป้าหมาย (combobox หลายค่า) ----
       ปุ่มเอาแท็กออกต้องมาก่อนตัวควบคุม ไม่งั้นคลิกกากบาทจะไปโดนการเปิด/ปิดแผงแทน */
    var rmTarget = t.closest('[data-remove-target]');
    if (rmTarget) {
      e.stopPropagation();
      toggleIn('targets', rmTarget.getAttribute('data-remove-target'));
      return touch();
    }

    if (t.closest('#ac-cat-control')) { state.combo = state.combo === 'cat' ? null : 'cat'; return syncCombo(); }
    if (t.closest('#ac-target-control')) { state.combo = state.combo === 'target' ? null : 'target'; return syncCombo(); }

    /* หมวดหมู่เลือกได้ค่าเดียว — เลือกแล้วปิดแผงทันทีเหมือนประเภท/สถานที่/รูปแบบ */
    var cat = t.closest('[data-cat]');
    if (cat) { state.cats = [cat.getAttribute('data-cat')]; state.combo = null; touch(); return syncCombo(); }

    var target = t.closest('[data-target]');
    if (target) { toggleIn('targets', target.getAttribute('data-target')); touch(); return syncCombo(); }

    /* ---- การ์ดเลือก ---- */
    var fr = t.closest('[data-form-reg]');
    if (fr) { state.formReg = fr.getAttribute('data-form-reg'); return touch(); }

    var fp = t.closest('[data-form-post]');
    if (fp) { state.formsPost = [fp.getAttribute('data-form-post')]; return touch(); }

    /* ---- combobox ---- */
    var rmCourse = t.closest('[data-remove-course]');
    if (rmCourse) {
      e.stopPropagation();
      toggleIn('courses', rmCourse.getAttribute('data-remove-course'));
      return touch();
    }

    var rmHost = t.closest('[data-remove-host]');
    if (rmHost) {
      e.stopPropagation();
      toggleIn('hosts', rmHost.getAttribute('data-remove-host'));
      return touch();
    }

    if (t.closest('#ac-course-control')) { state.combo = state.combo === 'course' ? null : 'course'; return syncCombo(); }
    if (t.closest('#ac-host-control')) { state.combo = state.combo === 'host' ? null : 'host'; return syncCombo(); }

    var course = t.closest('[data-course]');
    if (course) {
      toggleIn('courses', course.getAttribute('data-course'));
      touch();
      return syncCombo();
    }

    var host = t.closest('[data-host]');
    if (host) { toggleIn('hosts', host.getAttribute('data-host')); touch(); return syncCombo(); }

    /* ---- รอบกิจกรรม ---- */
    var rmSlot = t.closest('[data-remove-slot]');
    if (rmSlot && !rmSlot.disabled) {
      if (state.slots.length <= 1) return;
      var id = rmSlot.getAttribute('data-remove-slot');
      state.slots = state.slots.filter(function (s) { return String(s.id) !== id; });
      state.dirty = true;
      renderSlots();
      return sync();
    }

    if (t.closest('#ac-add-slot')) {
      if (state.slots.length >= MAX_SLOTS) return;
      /* รอบใหม่เริ่มจากรอบก่อนหน้า + 1 วัน — รอบส่วนใหญ่จัดต่อเนื่องกันด้วยเวลาเดิม
         (วันซ้ำกันไม่ได้อยู่แล้ว เพราะฐานข้อมูลใช้วันที่เป็นคีย์ของรอบ) กรอกทับได้ทุกช่อง */
      var prev = state.slots[state.slots.length - 1] || {};
      state.slots.push({
        id: state.nextSlotId++,
        date: prev.date ? addDaysIso(prev.date, 1) : '',
        start: prev.start || '',
        end: prev.end || '',
        cap: prev.cap || ''
      });
      state.dirty = true;
      renderSlots();
      return sync();
    }

    /* ---- รูปปก ---- */
    /* หน้าที่ต่อฐานข้อมูลจริงลงทะเบียนตัวจัดการไว้ (อัปโหลดไฟล์จริง)
       ไม่ได้ลงทะเบียน = หน้า static เดิม แค่สลับสถานะให้เห็นหน้าตา */
    if (t.closest('#ac-cover-pick')) {
      if (api.onPickCover) return api.onPickCover();
      state.cover = true;
      return touch();
    }

    if (t.closest('#ac-cover-remove')) {
      if (api.onRemoveCover) return api.onRemoveCover();
      state.cover = false;
      return touch();
    }

    /* ---- toggle ---- */
    var tg = t.closest('[data-toggle]');
    if (tg) { var k = tg.getAttribute('data-toggle'); state[k] = !state[k]; return touch(); }

    /* ---- QR ---- */
    var copy = t.closest('[data-copy]');
    if (copy && !copy.disabled) {
      var url = copy.getAttribute('data-copy');
      if (navigator.clipboard) navigator.clipboard.writeText(url);
      if (window.TFC.showToast) window.TFC.showToast('คัดลอกลิงก์ ' + url + ' แล้ว', 'success');
      return;
    }
    var dl = t.closest('[data-qr-download]');
    if (dl && !dl.disabled) {
      window.location.assign(dl.getAttribute('data-qr-download'));
      if (window.TFC.showToast) window.TFC.showToast('ดาวน์โหลด QR เรียบร้อย', 'success');
      return;
    }
    if (t.closest('#ac-qr-all') && !$('ac-qr-all').disabled) {
      QR_LINKS.forEach(function (q, index) {
        window.setTimeout(function () {
          var link = document.createElement('a');
          link.href = q.downloadUrl;
          link.click();
        }, index * 250);
      });
      if (window.TFC.showToast) window.TFC.showToast('กำลังดาวน์โหลด QR ทั้งหมด', 'info');
      return;
    }

    /* ---- ปุ่มล่าง ----
       ถ้าหน้าจอลงทะเบียน onSubmit ไว้ (หน้า Blade ที่ต่อฐานข้อมูลจริง) ให้ส่งงานต่อไปที่นั่น
       ไม่ได้ลงทะเบียนไว้ = หน้า static เดิม ทำงานแบบเดิมคือแค่ขึ้น toast
       แยกแบบนี้เพื่อให้ไฟล์นี้ใช้ได้ทั้งสองหน้าโดยไม่ต้องมีสองเวอร์ชัน */
    if (t.closest('#ac-save-draft')) {
      return api.onSubmit ? api.onSubmit(state, { publish: false }) : saveDraft(true);
    }

    if (t.closest('#ac-publish') && !$('ac-publish').disabled) {
      if (api.onSubmit) return api.onSubmit(state, { publish: true });
      state.dirty = false;
      if (window.TFC.showToast) window.TFC.showToast('บันทึกและเผยแพร่กิจกรรมเรียบร้อย', 'success');
      return;
    }

    /* คลิกนอก combobox ให้ปิด */
    if (!t.closest('.ac-combo')) {
      if (state.combo) { state.combo = null; syncCombo(); }
    }
  });

  function syncCombo() {
    [['kind', 'ac-kind-panel', 'ac-kind-control'],
     ['event', 'ac-event-panel', 'ac-event-control'],
     ['place', 'ac-place-panel', 'ac-place-control'],
     ['mode', 'ac-mode-panel', 'ac-mode-control'],
     ['cat', 'ac-cat-panel', 'ac-cat-control'],
     ['target', 'ac-target-panel', 'ac-target-control'],
     ['course', 'ac-course-panel', 'ac-course-control'],
     ['host', 'ac-host-panel', 'ac-host-control']]
      .forEach(function (c) {
        var open = state.combo === c[0];
        $(c[1]).hidden = !open;
        $(c[2]).setAttribute('aria-expanded', String(open));
        $(c[2]).classList.toggle('is-open', open);
      });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && state.combo) { state.combo = null; syncCombo(); }
    if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('ac-combo-control')) {
      e.preventDefault();
      e.target.click();
    }
  });

  /* ---------- บันทึกร่าง ---------- */
  function saveDraft(manual) {
    state.dirty = false;
    state.savedAt = new Date();
    var el = $('ac-autosave');
    var text = $('ac-autosave-text');
    if (el) el.hidden = false;
    if (text) {
      text.textContent = 'บันทึกร่างล่าสุด ' +
        String(state.savedAt.getHours()).padStart(2, '0') + ':' +
        String(state.savedAt.getMinutes()).padStart(2, '0') + ' น.';
    }
    if (manual && window.TFC.showToast) window.TFC.showToast('บันทึกฉบับร่างเรียบร้อย', 'success');
  }

  /* หน้าที่ต่อฐานข้อมูลจริง (มี api.onSubmit) ต้องบันทึกร่างขึ้นเซิร์ฟเวอร์จริง ไม่ใช่แค่เคลียร์ state.dirty
     เฉย ๆ ในเครื่อง — ของเดิมทำแบบนั้น ทำให้ปิดแท็บ/รีเฟรชแล้วข้อมูลที่เพิ่งแก้ (เช่น รอบที่เพิ่งเพิ่ม) หายเงียบ ๆ
     เพราะสัญญาณเตือน beforeunload ถูกปิดไปแล้วทั้งที่ไม่ได้บันทึกจริง */
  setInterval(function () {
    if (!state.dirty) return;
    if (api.onSubmit) { api.onSubmit(state, { publish: false }); return; }
    saveDraft(false);
  }, AUTOSAVE_MS);

  /* ออกจากหน้าโดยยังไม่บันทึก ต้องถามยืนยันก่อน */
  window.addEventListener('beforeunload', function (e) {
    if (!state.dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  $('ac-cancel').addEventListener('click', function (e) {
    if (!state.dirty) return;
    if (!window.confirm('ยังไม่ได้บันทึกการเปลี่ยนแปลง ต้องการออกจากหน้านี้หรือไม่')) e.preventDefault();
  });

  /* ---------- โหมดแก้ไข ----------
     หน้านี้ทำหน้าที่เป็นทั้งฟอร์มสร้างและฟอร์มแก้ไข จะได้ไม่ต้องดูแลสองฟอร์ม
     ที่ต้องคอยแก้ให้ตรงกันตลอด (ของเดิมคือ edit.html + activity-form.js ซึ่งถูกลบไปแล้ว)
     ถ้ามี ?id= ให้ดึงกิจกรรมนั้นมาเติมลงฟอร์ม แล้วเปลี่ยนชื่อหน้ากับปุ่มให้ตรงกับงาน */
  /* หน้า Blade ระบุรหัสกิจกรรมมาให้ตรง ๆ เพราะ URL เป็น /admin/activities/{code}/edit
     ไม่มี ?id= แบบหน้า static เดิม — รองรับทั้งสองแบบไว้ จะได้ไม่ต้องมีสองเวอร์ชัน */
  function editingId() {
    return window.TFC_ACTIVITY_EDIT_ID || new URLSearchParams(location.search).get('id') || '';
  }

  function fillFromActivity(a) {
    state.title = a.name || '';
    state.detail = a.description || '';
    state.kind = KINDS.indexOf(a.type) > -1 ? a.type : KINDS[0];
    /* CATS เป็นรายการ object {name, icon} ไม่ใช่รายการข้อความ
       ของเดิมใช้ CATS.indexOf(ข้อความ) ซึ่งไม่มีทางเจอ หมวดหมู่จึงว่างเสมอตอนเปิดแก้ไข */
    function isKnownCat(value) {
      return CATS.some(function (c) { return c.name === value; });
    }

    state.cats = (a.tags || []).filter(isKnownCat).slice(0, 1);
    if (!state.cats.length && isKnownCat(a.format)) state.cats = [a.format];
    state.place = PLACES.indexOf(a.area) > -1 ? a.area : PLACE_EMPTY;
    state.cover = !!a.coverImage;
    state.coverUrl = a.coverImageUrl || '';
    state.coverLabel = a.coverImage ? String(a.coverImage).split('/').pop() : '';
    state.targets = (a.targetGroups || []).filter(function (t) { return TARGETS.indexOf(t) > -1; });
    state.hosts = (a.instructorList || []).slice();
    state.courses = a.course ? [a.course] : [];
    state.fee = a.hasFee ? FEES[1] : FEES[0];
    state.feeAmount = a.hasFee ? String(a.fee || '') : '';
    state.parentEvent = a.parentEventName || '';
    state.publish = !!a.isPublished;
    state.pin = !!a.isFeatured;

    /* ช่วงเวลาเก็บเป็นค่าเดียว ("2026-08-10 09:00") แต่ฟอร์มแยกช่องวันกับเวลา จึงต้องผ่าครึ่ง
       ของเดิมไม่ได้เติมส่วนนี้เลย ช่วงเวลาจึงว่างทุกครั้งที่เปิดแก้ไข */
    function splitAt(value) {
      if (!value) return {};
      var parts = String(value).replace('T', ' ').split(' ');
      return { date: parts[0] || '', time: (parts[1] || '').slice(0, 5) };
    }

    function windowFrom(startValue, endValue) {
      var s = splitAt(startValue);
      var e = splitAt(endValue);
      return { from: s.date || '', fromT: s.time || '', to: e.date || '', toT: e.time || '' };
    }

    state.windows.reg = windowFrom(a.registrationStart, a.registrationEnd);
    state.windows.chk = windowFrom(a.checkinStart, a.checkinEnd);
    state.windows.srv = windowFrom(a.surveyStart, a.surveyEnd);

    /* ช่วงเวลาที่มีค่าอยู่แล้วเป็นของที่คนตั้งไว้ ระบบห้ามคำนวณทับ
       ช่วงที่ยังว่างเท่านั้นที่ปล่อยให้เติมอัตโนมัติจากรอบกิจกรรมต่อไป */
    function hasWin(w) { return !!(w && (w.from || w.fromT || w.to || w.toT)); }
    state.winAuto = {
      reg: !hasWin(state.windows.reg),
      chk: !hasWin(state.windows.chk),
      srv: !hasWin(state.windows.srv)
    };

    /* 0 = ยังไม่กำหนด แสดงเป็นช่องว่างให้อ่านว่า "อัตโนมัติ" ไม่ใช่เลข 0 ที่ดูเหมือนตั้งใจตั้ง */
    state.sortOrder = a.publicSortOrder > 0 ? String(a.publicSortOrder) : '';

    /* รอบกิจกรรมมาจาก activitySessions ถ้ามี ไม่งั้นใช้วันเริ่มกับเวลาของตัวกิจกรรมเอง */
    var sessions = (mock.activitySessions || {})[a.id] || [];
    var times = (a.time || '').split('-');
    state.slots = sessions.length
      ? sessions.map(function (s, i) {
          var t = (s.time || '').split('-');
          return { id: i + 1, date: s.date || '', start: (t[0] || '').trim(), end: (t[1] || '').trim(), cap: String(s.capacity || '') };
        })
      : [{ id: 1, date: a.startDate || '', start: (times[0] || '').trim(), end: (times[1] || '').trim(), cap: String(a.capacity || '') }];
    state.nextSlotId = state.slots.length + 1;

    /* ชุดข้อมูลกลางยังไม่มีฟิลด์ "ต้องลงทะเบียนไหม / มีแบบประเมินหลังจบไหม" ตรง ๆ
       จึงอนุมานจากสิ่งที่มี: กำหนดจำนวนรับหรือมีผู้ลงทะเบียนแล้ว = เปิดให้ลงทะเบียน
       ผูกชุดแบบประเมินไว้ = มีแบบประเมินหลังจบ */
    /* ถ้าแหล่งข้อมูลบอกสวิตช์มาตรง ๆ ให้ใช้ค่านั้น (ฐานข้อมูลจริงมีคอลัมน์แล้ว)
       ถ้าไม่มีค่อยอนุมานจากสิ่งที่พอมีแบบเดิม — ข้อมูลจำลองยังไม่มีสามฟิลด์นี้ */
    state.join = a.joinFlags || {
      reg: !!(a.capacity || a.registered),
      survey: !!(a.evaluationFormIds && a.evaluationFormIds.length)
    };

    /* กิจกรรมเก็บ id ของแบบประเมินอยู่แล้ว ตอนนี้หน้านี้ก็ใช้ id เหมือนกัน จึงผูกกลับได้ตรง ๆ
       กรองเฉพาะชุดที่ยังเปิดใช้งาน — ชุดที่ถูกปิดไปแล้วไม่ควรโผล่มาเป็นตัวเลือกที่ติ๊กค้างไว้ */
    var linked = (a.evaluationFormIds || []).filter(function (id) { return !!formById(id); });
    var linkedPost = linked.filter(function (id) { return isPostForm(formById(id)); });
    var linkedReg = linked.filter(function (id) { return isRegForm(formById(id)); });
    if (linkedPost.length) state.formsPost = linkedPost;
    if (linkedReg.length) state.formReg = linkedReg[0];

    state.dirty = false;
  }

  /* ช่องกรอกข้อความกับ dropdown ผูกทางเดียว (DOM -> state) เพราะปกติผู้ใช้เป็นคนพิมพ์
     โหมดแก้ไขต้องดันค่าย้อนกลับลง DOM เอง ไม่งั้นฟอร์มจะว่างทั้งที่ state มีค่าแล้ว
     (chip, รอบกิจกรรม และการ์ดแบบประเมิน วาดจาก state อยู่แล้ว จึงไม่ต้องทำตรงนี้) */
  function pushStateToDom() {
    /* ac-reg ไม่อยู่ในนี้ เพราะเป็นช่องติ๊กที่ renderChips() วาดจาก state ให้อยู่แล้ว */
    var map = { 'ac-title': state.title, 'ac-detail': state.detail, 'ac-fee-input': state.feeAmount,
                'ac-kind': state.kind, 'ac-place': state.place, 'ac-mode': state.mode };
    Object.keys(map).forEach(function (id) {
      var el = $(id);
      if (el) el.value = map[id];
    });
  }

  function applyEditChrome(a) {
    document.title = 'แก้ไขกิจกรรม | TheFarmConcept';
    var h1 = document.querySelector('.page-header-text h1');
    if (h1) h1.textContent = 'แก้ไขกิจกรรม';
    var desc = document.querySelector('.page-header-text .page-description');
    if (desc) desc.innerHTML = 'กำลังแก้ไข <strong>' + esc(a.name) + '</strong> · ช่องที่มี <span class="form-required">*</span> จำเป็นต้องกรอก';
    var crumb = document.querySelector('.breadcrumb .is-current');
    if (crumb) crumb.textContent = 'แก้ไขกิจกรรม';
    var pub = $('ac-publish');
    if (pub) pub.textContent = 'บันทึกการแก้ไข';
  }

  function initEditMode() {
    var id = editingId();
    if (!id) return;
    var a = (mock.activities || []).filter(function (x) { return x.id === id; })[0];
    if (!a) {
      if (window.TFC.showToast) window.TFC.showToast('ไม่พบกิจกรรม ' + id + ' — เปิดเป็นฟอร์มสร้างใหม่แทน', 'danger');
      return;
    }
    fillFromActivity(a);
    pushStateToDom();

    /* โหมดสร้างใหม่ใช้เส้นทางนี้ด้วยเพื่อเติมค่าตั้งต้นจากแถวเปล่า
       แต่หัวหน้ากับป้ายปุ่มต้องคงเป็นของหน้าสร้าง ไม่ใช่ "บันทึกการแก้ไข" */
    if (!window.TFC_ACTIVITY_IS_NEW) applyEditChrome(a);
  }

  /* ---------- จุดต่อสำหรับหน้าที่ทำงานบนฐานข้อมูลจริง ----------
     state ถูกปิดอยู่ใน IIFE นี้ หน้าอื่นจึงอ่านไม่ได้ ต้องเปิดช่องให้ผ่าน object เดียวนี้
     เปิดเท่าที่จำเป็นจริง ไม่เปิดทั้ง scope:
       onSubmit   หน้าจอกำหนดเองว่าจะบันทึกยังไง (ไม่กำหนด = พฤติกรรมเดิม)
       state      อ่านค่าที่ผู้ใช้กรอกไปประกอบ payload
       markSaved  ให้หน้าจอบอกกลับได้ว่าบันทึกสำเร็จแล้ว จะได้ไม่เตือนตอนออกจากหน้า */
  var api = window.TFC.activityForm = {
    onSubmit: null,
    onPickCover: null,
    onRemoveCover: null,
    state: state,
    markSaved: function () { state.dirty = false; },

    /* ตั้งรูปปกจากภายนอกหลังอัปโหลดสำเร็จ — label เป็นข้อความที่แสดงในกล่อง
       ส่งค่าว่างหรือ null = ไม่มีรูปปก */
    setCover: function (label, url) {
      state.cover = !!label;
      state.coverLabel = label || '';
      state.coverUrl = url || '';
      renderCover();
      sync();
    },

    /* จำสถานะเดิมของปุ่มเผยแพร่ไว้ เพราะ sync() เป็นคนคุมว่ากดได้หรือยังตามความครบของฟอร์ม
       ถ้าเปิดกลับมาเฉย ๆ จะกลายเป็นกดได้ทั้งที่ยังกรอกไม่ครบ */
    setBusy: function (on) {
      var publish = $('ac-publish');
      var draft = $('ac-save-draft');

      if (on) {
        api.wasDisabled = publish.disabled;
        publish.disabled = true;
        draft.disabled = true;
        return;
      }

      publish.disabled = !!api.wasDisabled;
      draft.disabled = false;
    }
  };

  /* ---------- เริ่มต้น ---------- */
  initEditMode();
  initOptions();
  renderSlots();
  sync();
  syncCombo();
})();
