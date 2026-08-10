/* TheFarmConcept — หน้าเพิ่มกิจกรรม (admin/activities/create.html)
   แยกไฟล์เพราะสคริปต์ยาวเกินกว่าจะฝังในหน้า และหน้าแก้ไขกิจกรรมจะเรียกใช้ซ้ำได้ในภายหลัง

   หลักการเรนเดอร์: โครงฟอร์มทั้งหมดอยู่ใน HTML แล้ว สคริปต์นี้ไม่สร้าง input ใหม่ทับของเดิม
   จะเรนเดอร์ใหม่เฉพาะส่วนที่เป็นตัวเลือก (chip / การ์ดเลือก / combobox / รอบกิจกรรม)
   เพื่อไม่ให้ช่องที่กำลังพิมพ์อยู่เสีย focus ตอน sync */
(function () {
  var esc = window.TFC.escapeHtml;
  var mock = window.TFC_MOCK || {};

  /* ---------- ข้อมูลตั้งต้น ---------- */
  var CATALOG = [
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
  var PLACES = [PLACE_EMPTY, 'The Farm Concept', 'ชุมชนพูนทรัพย์', 'ชุมชนตึกร้าง'];
  var KINDS = ['กิจกรรม', 'อีเว้นท์'];

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

  var MODES = ['จัดในพื้นที่ (Onsite)', 'ออนไลน์', 'ผสม (Hybrid)'];
  var TARGETS = ['เด็กและเยาวชน', 'วัยทำงาน', 'ผู้สูงอายุ', 'กลุ่มเปราะบาง'];
  var FEES = ['ไม่มีค่าใช้จ่าย', 'มีค่าเข้าร่วม'];
  /* ฟิลด์เดียวที่ตอบสองคำถามพร้อมกัน: ต้องลงทะเบียนไหม และมีแบบประเมินหลังจบไหม
     ค่าที่เลือกที่นี่เป็นตัวกำหนดว่าจะแสดงฟิลด์ถัดไปชุดไหน จึงไม่ต้องให้ผู้ใช้
     ตอบซ้ำอีกหลายจุดแล้วมาขัดกันเอง (เช่น เลือก Walk-in แต่ยังตั้งที่นั่งสำรองได้)
     เรียงจากเงื่อนไขน้อยไปมาก ตัวเลือกในลิสต์บอกผลลัพธ์ต่อท้ายชื่อ ผู้ใช้จะได้ไม่ต้องเดา */
  var JOIN_MODES = [
    { key: 'walkin-only',   label: 'เข้าร่วมได้ทันที',          effect: 'ไม่ต้องลงทะเบียน / ไม่ทำแบบประเมิน', reg: false, survey: false },
    { key: 'reg-only',      label: 'ลงทะเบียนอย่างเดียว',       effect: 'ต้องลงทะเบียน / ไม่ทำแบบประเมิน',    reg: true,  survey: false },
    { key: 'survey-only',   label: 'ทำแบบประเมินอย่างเดียว',    effect: 'ไม่ต้องลงทะเบียน / ต้องทำแบบประเมิน', reg: false, survey: true },
    { key: 'reg-survey',    label: 'ลงทะเบียนและทำแบบประเมิน',  effect: 'ต้องลงทะเบียน / ต้องทำแบบประเมิน',   reg: true,  survey: true }
  ];

  function joinMode() {
    return JOIN_MODES.filter(function (m) { return m.key === state.join; })[0] || JOIN_MODES[0];
  }
  function needsReg() { return joinMode().reg; }
  function hasSurvey() { return joinMode().survey; }

  /* คำถามจริงของแต่ละชุด ใช้ทั้งแสดงจำนวนในคำอธิบายและแสดงตัวอย่างตอนกด "ดูตัวอย่าง"
     เก็บไว้ที่เดียวกัน จำนวนที่โชว์จึงตรงกับคำถามที่มีจริงเสมอ ไม่มีทางคลาดกัน */
  var FORM_REG = [
    { label: 'แบบลงทะเบียนมาตรฐาน (ข้อมูลพื้นฐาน)', note: 'ชื่อ เบอร์โทร พื้นที่', questions: [
      { type: 'ข้อความสั้น', text: 'ชื่อ–นามสกุล', required: true },
      { type: 'เบอร์โทร', text: 'เบอร์โทรศัพท์', required: true },
      { type: 'ตัวเลือกเดียว', text: 'ช่วงอายุ', required: true },
      { type: 'ตัวเลือกเดียว', text: 'เพศ', required: false },
      { type: 'ตัวเลือกเดียว', text: 'พื้นที่ที่อาศัยอยู่', required: true },
      { type: 'ตัวเลือกเดียว', text: 'ทราบข่าวกิจกรรมจากช่องทางใด', required: false }
    ] },
    { label: 'แบบลงทะเบียน + ประเมินสุขภาพก่อนเข้าร่วม', note: 'เพิ่มข้อมูลสุขภาพตั้งต้น', questions: [
      { type: 'ข้อความสั้น', text: 'ชื่อ–นามสกุล', required: true },
      { type: 'เบอร์โทร', text: 'เบอร์โทรศัพท์', required: true },
      { type: 'ตัวเลือกเดียว', text: 'ช่วงอายุ', required: true },
      { type: 'ตัวเลือกเดียว', text: 'เพศ', required: false },
      { type: 'ตัวเลือกเดียว', text: 'พื้นที่ที่อาศัยอยู่', required: true },
      { type: 'ตัวเลข', text: 'น้ำหนัก (กก.)', required: true },
      { type: 'ตัวเลข', text: 'ส่วนสูง (ซม.)', required: true },
      { type: 'ตัวเลข', text: 'รอบเอว (ซม.)', required: false },
      { type: 'ตัวเลือกหลายข้อ', text: 'โรคประจำตัว', required: false },
      { type: 'ข้อความสั้น', text: 'ยาที่ใช้ประจำ', required: false },
      { type: 'ตัวเลือกเดียว', text: 'ความถี่ในการออกกำลังกาย', required: true },
      { type: 'ตัวเลือกเดียว', text: 'การสูบบุหรี่', required: false },
      { type: 'ตัวเลือกเดียว', text: 'การดื่มแอลกอฮอล์', required: false },
      { type: 'ข้อความยาว', text: 'เป้าหมายด้านสุขภาพที่อยากเห็น', required: false }
    ] },
    { label: 'แบบลงทะเบียนสำหรับแกนนำชุมชน', note: 'เพิ่มบทบาทและพื้นที่รับผิดชอบ', questions: [
      { type: 'ข้อความสั้น', text: 'ชื่อ–นามสกุล', required: true },
      { type: 'เบอร์โทร', text: 'เบอร์โทรศัพท์', required: true },
      { type: 'ข้อความสั้น', text: 'ชุมชน/หน่วยงานที่สังกัด', required: true },
      { type: 'ตัวเลือกเดียว', text: 'บทบาทในชุมชน', required: true },
      { type: 'ตัวเลข', text: 'จำนวนปีที่ทำงานในพื้นที่', required: false },
      { type: 'ตัวเลือกหลายข้อ', text: 'พื้นที่ที่รับผิดชอบ', required: true },
      { type: 'ตัวเลือกหลายข้อ', text: 'ประสบการณ์จัดกิจกรรมที่ผ่านมา', required: false },
      { type: 'ตัวเลือกเดียว', text: 'ช่วงเวลาที่สะดวกเข้าร่วม', required: false },
      { type: 'ข้อความยาว', text: 'สิ่งที่คาดหวังจากการอบรม', required: false },
      { type: 'ข้อความยาว', text: 'ข้อจำกัดหรือสิ่งที่ต้องการให้ช่วยเหลือ', required: false }
    ] }
  ];

  var FORM_POST = [
    { label: 'แบบประเมินความพึงพอใจ', note: 'ให้คะแนน + ความเห็นปลายเปิด', questions: [
      { type: 'คะแนน 1-5', text: 'ความพึงพอใจโดยรวมต่อกิจกรรม', required: true },
      { type: 'คะแนน 1-5', text: 'ความรู้ที่ได้รับนำไปใช้ได้จริง', required: true },
      { type: 'คะแนน 1-5', text: 'ความเหมาะสมของสถานที่และเวลา', required: true },
      { type: 'คะแนน 1-5', text: 'ความชัดเจนของการสื่อสารก่อนกิจกรรม', required: false },
      { type: 'คะแนน 1-5', text: 'ความคุ้มค่าเมื่อเทียบกับเวลาที่ใช้', required: false },
      { type: 'ข้อความยาว', text: 'สิ่งที่ประทับใจที่สุด', required: false },
      { type: 'ข้อความยาว', text: 'ข้อเสนอแนะเพิ่มเติม', required: false }
    ] },
    { label: 'แบบประเมินความรู้หลังอบรม', note: 'คำถามถูก/ผิด วัดความเข้าใจ', questions: [
      { type: 'ถูก/ผิด', text: 'ผักปลอดสารต้องงดใช้ปุ๋ยทุกชนิด', required: true },
      { type: 'ถูก/ผิด', text: 'ปุ๋ยหมักใช้ได้ทันทีหลังผสมเสร็จ', required: true },
      { type: 'ถูก/ผิด', text: 'การปลูกพืชหมุนเวียนช่วยลดโรคในดิน', required: true },
      { type: 'ถูก/ผิด', text: 'น้ำหมักชีวภาพใช้แทนน้ำเปล่าได้ทุกวัน', required: true },
      { type: 'ถูก/ผิด', text: 'ควรรดน้ำผักตอนแดดจัดที่สุดของวัน', required: true },
      { type: 'ตัวเลือกเดียว', text: 'ระยะเวลาที่เหมาะสมในการหมักปุ๋ย', required: true },
      { type: 'ตัวเลือกเดียว', text: 'วิธีสังเกตว่าดินขาดธาตุอาหาร', required: true },
      { type: 'ตัวเลือกหลายข้อ', text: 'วิธีกำจัดศัตรูพืชแบบไม่ใช้สารเคมี', required: true },
      { type: 'ตัวเลือกเดียว', text: 'ช่วงเวลาที่เหมาะกับการเก็บเกี่ยว', required: false },
      { type: 'ข้อความยาว', text: 'สิ่งที่จะนำกลับไปทำต่อที่บ้าน', required: false }
    ] },
    { label: 'แบบประเมินวิทยากร', note: 'ให้คะแนนรายวิทยากร', questions: [
      { type: 'คะแนน 1-5', text: 'ความรู้ความเชี่ยวชาญในเนื้อหา', required: true },
      { type: 'คะแนน 1-5', text: 'การอธิบายเข้าใจง่าย', required: true },
      { type: 'คะแนน 1-5', text: 'การเปิดโอกาสให้ซักถาม', required: true },
      { type: 'คะแนน 1-5', text: 'การบริหารเวลา', required: false },
      { type: 'ข้อความยาว', text: 'ข้อเสนอแนะต่อวิทยากร', required: false }
    ] }
  ];

  /* คำอธิบายใต้ชื่อชุด สร้างจากคำถามจริง ไม่ได้พิมพ์ตัวเลขไว้เอง */
  function formHint(o) {
    var need = o.questions.filter(function (q) { return q.required; }).length;
    return o.note + ' · ' + o.questions.length + ' คำถาม (จำเป็น ' + need + ')';
  }

  function formByLabel(label) {
    return FORM_REG.concat(FORM_POST).filter(function (o) { return o.label === label; })[0];
  }

  var QR_LINKS = [
    { label: 'ลงทะเบียนเข้าร่วม', url: 'farmconcept.th/r/0142' },
    { label: 'เช็คอินหน้างาน', url: 'farmconcept.th/c/0142' },
    { label: 'แบบประเมินหลังกิจกรรม', url: 'farmconcept.th/s/0142' }
  ];

  var MAX_SLOTS = 5;
  var AUTOSAVE_MS = 60000;

  /* ---------- สถานะ ---------- */
  var state = {
    title: '', detail: '',
    kind: KINDS[0], cats: ['FOOD'], mode: MODES[0],
    place: PLACE_EMPTY,
    cover: false,
    courses: [], hosts: [],
    slots: [{ id: 1, date: '', start: '', end: '', cap: '' }],
    nextSlotId: 2,
    fee: FEES[0], feeAmount: '',
    /* ตั้งต้นเป็นรูปแบบที่มีเงื่อนไขครบ ผู้ใช้จะเห็นฟิลด์ทั้งหมดก่อนแล้วค่อยตัดออก
       ถ้าตั้งต้นเป็น "เข้าร่วมได้ทันที" หน้าจอตอนเปิดจะว่างจนดูเหมือนโหลดไม่ครบ */
    join: 'reg-survey',
    targets: [],
    formReg: FORM_REG[0].label,
    formsPost: [FORM_POST[0].label],
    windows: { reg: {}, chk: {}, srv: {} },
    publish: false, pin: false,
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

  function toggleIn(key, value) {
    var arr = state[key];
    var i = arr.indexOf(value);
    if (i > -1) arr.splice(i, 1); else arr.push(value);
  }

  /* วิทยากรที่เลือกได้ = รวมผู้สอนของหลักสูตรที่เลือกไว้ แบบไม่ซ้ำ */
  function teachersForCourses() {
    var out = [];
    CATALOG.forEach(function (p) {
      p.courses.forEach(function (c) {
        if (state.courses.indexOf(c.name) < 0) return;
        c.teachers.forEach(function (t) { if (out.indexOf(t) < 0) out.push(t); });
      });
    });
    return out;
  }

  /* ---------- เช็กลิสต์ 9 ข้อ ที่คุมปุ่มเผยแพร่และแถบความคืบหน้า ---------- */
  function checklist() {
    var first = state.slots[0] || {};
    return [
      { label: 'ชื่อกิจกรรม', ok: state.title.trim().length > 0 },
      { label: 'ประเภท', ok: !!state.kind },
      { label: 'หมวดหมู่', ok: state.cats.length > 0 },
      { label: 'รายละเอียด', ok: state.detail.trim().length > 0 },
      { label: 'รูปภาพปก', ok: state.cover },
      { label: 'สถานที่จัด', ok: state.place !== PLACE_EMPTY },
      { label: 'วันและเวลาอย่างน้อย 1 รอบ', ok: !!(first.date && first.start) },
      { label: 'กลุ่มเป้าหมาย', ok: state.targets.length > 0 },
      { label: 'วิทยากร', ok: state.hosts.length > 0 }
    ];
  }

  /* ================= เรนเดอร์แต่ละส่วน ================= */

  /* ตัวเลือกที่ไม่เปลี่ยนตามการกรอก (dropdown / radio) สร้างครั้งเดียวตอนเปิดหน้า
     ถ้าสร้างใหม่ทุกรอบ sync ช่องที่กำลังโฟกัสอยู่จะหลุดโฟกัสและ dropdown ที่เปิดค้างจะปิดเอง */
  function initOptions() {
    /* ประเภทเป็น dropdown ไม่ใช่ชิป เพราะเลือกได้อย่างเดียวและอยู่ติดกับ "หมวดหมู่"
       ที่เลือกได้หลายอัน — ใช้คนละรูปแบบกันช่วยให้แยกออกทันทีว่าอันไหนเลือกได้กี่ค่า */
    $('ac-kind').innerHTML = KINDS.map(optionHtml).join('');
    $('ac-mode').innerHTML = MODES.map(optionHtml).join('');
    $('ac-place').innerHTML = PLACES.map(optionHtml).join('');

    /* ชื่อรูปแบบ + ผลลัพธ์อยู่ในบรรทัดเดียวกัน เพราะ <option> ใส่คำอธิบายบรรทัดที่สองไม่ได้ */
    $('ac-reg').innerHTML = JOIN_MODES.map(function (m) {
      return '<option value="' + esc(m.key) + '">' + esc(m.label + ' — ' + m.effect) + '</option>';
    }).join('');

    /* ค่าเข้าร่วมมีสองทางที่ตัดกันชัด ใช้ radio ให้เห็นทั้งสองตัวเลือกพร้อมกัน
       ไม่ใช่ dropdown ที่ต้องกดก่อนถึงจะรู้ว่ามีอะไรให้เลือกบ้าง */
    $('ac-fee').innerHTML = FEES.map(function (f) {
      return '<label class="radio-item">' +
        '<input type="radio" name="ac-fee-opt" value="' + esc(f) + '" data-fee="' + esc(f) + '"' +
        (state.fee === f ? ' checked' : '') + '>' + esc(f) + '</label>';
    }).join('');
  }

  function optionHtml(v) { return '<option value="' + esc(v) + '">' + esc(v) + '</option>'; }

  function renderChips() {
    $('ac-kind').value = state.kind;
    $('ac-mode').value = state.mode;
    $('ac-place').value = state.place;
    $('ac-reg').value = state.join;

    /* ไอคอนช่วยให้จำหมวดหมู่ได้จากรูปทรง ไม่ต้องอ่านชื่อภาษาอังกฤษทุกครั้ง */
    $('ac-cats').innerHTML = CATS.map(function (c) {
      return chipHtml(c.name, state.cats.indexOf(c.name) > -1, 'data-cat', catIconSvg(c.icon));
    }).join('');

    $('ac-targets').innerHTML = TARGETS.map(function (t) {
      return chipHtml(t, state.targets.indexOf(t) > -1, 'data-target', '');
    }).join('');

    Array.prototype.forEach.call($('ac-fee').querySelectorAll('[data-fee]'), function (r) {
      r.checked = r.getAttribute('data-fee') === state.fee;
    });
  }

  function renderPicks() {
    $('ac-form-reg').innerHTML = FORM_REG.map(function (o) {
      var on = state.formReg === o.label;
      return '<div class="ac-pick-row">' +
        '<button type="button" class="ac-pick' + (on ? ' is-on' : '') + '" data-form-reg="' + esc(o.label) + '">' +
        markHtml(on, true) +
        '<span class="ac-pick-text">' +
          '<span class="ac-pick-title">' + esc(o.label) + '</span>' +
          '<span class="ac-pick-hint">' + esc(formHint(o)) + '</span>' +
        '</span></button>' +
        previewBtn(o.label) +
        '</div>';
    }).join('');

    $('ac-form-post').innerHTML = FORM_POST.map(function (o) {
      var on = state.formsPost.indexOf(o.label) > -1;
      return '<div class="ac-pick-row">' +
        '<button type="button" class="ac-pick' + (on ? ' is-on' : '') + '" data-form-post="' + esc(o.label) + '">' +
        markHtml(on, false) +
        '<span class="ac-pick-text">' +
          '<span class="ac-pick-title">' + esc(o.label) + '</span>' +
          '<span class="ac-pick-hint">' + esc(formHint(o)) + '</span>' +
        '</span></button>' +
        previewBtn(o.label) +
        '</div>';
    }).join('');

    $('ac-post-count').textContent = state.formsPost.length === 0
      ? 'ยังไม่ได้เลือก — ผู้เข้าร่วมจะไม่ได้รับแบบประเมินหลังกิจกรรม'
      : 'เลือกแล้ว ' + state.formsPost.length + ' ชุด · ส่งให้ผู้เข้าร่วมหลังเช็คอินจบกิจกรรม';
    $('ac-post-count').classList.toggle('is-warning', state.formsPost.length === 0);
  }

  /* ---------- ดูตัวอย่างแบบประเมิน ----------
     ชื่อชุดอย่างเดียวบอกไม่ได้ว่าข้างในถามอะไร ผู้ใช้จึงเลือกผิดชุดได้ง่าย
     ปุ่มนี้เปิดรายการคำถามจริงให้ดูก่อนตัดสินใจ โดยไม่ต้องออกจากหน้านี้ */
  function previewBtn(label) {
    return '<button type="button" class="ac-preview-btn" data-form-preview="' + esc(label) + '"' +
      ' title="ดูคำถามในชุดนี้" aria-label="ดูตัวอย่างคำถามของ ' + esc(label) + '">' +
      '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>' +
      '<span>ดูตัวอย่าง</span></button>';
  }

  function openFormPreview(label) {
    var o = formByLabel(label);
    if (!o) return;
    var need = o.questions.filter(function (q) { return q.required; }).length;

    var root = document.createElement('div');
    root.className = 'modal-overlay is-open ac-preview-overlay';
    root.innerHTML =
      '<div class="modal ac-preview-modal" role="dialog" aria-modal="true" aria-label="ตัวอย่างแบบประเมิน">' +
        '<div class="modal-header">' +
          '<div class="ac-preview-head">' +
            '<span class="ac-preview-name">' + esc(o.label) + '</span>' +
            '<span class="ac-preview-meta">' + o.questions.length + ' คำถาม · จำเป็นต้องตอบ ' + need + '</span>' +
          '</div>' +
          '<button type="button" class="modal-close" data-preview-close aria-label="ปิด">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
          '</button>' +
        '</div>' +
        '<div class="modal-body">' +
          '<ol class="ac-preview-list">' +
            o.questions.map(function (q) {
              return '<li class="ac-preview-q">' +
                '<span class="ac-preview-q-text">' + esc(q.text) +
                  (q.required ? '<span class="req-mark">*</span>' : '') + '</span>' +
                '<span class="ac-preview-q-type">' + esc(q.type) + '</span>' +
                '</li>';
            }).join('') +
          '</ol>' +
        '</div>' +
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
      : '<span class="ac-combo-placeholder">' + (state.courses.length ? 'คลิกเพื่อเลือกวิทยากร' : 'เลือกหลักสูตรก่อน') + '</span>';

    $('ac-host-panel').innerHTML = opts.length
      ? opts.map(function (h) {
          var on = state.hosts.indexOf(h) > -1;
          return '<button type="button" class="ac-combo-item' + (on ? ' is-on' : '') + '" role="option" aria-selected="' + on + '" data-host="' + esc(h) + '">' +
            markHtml(on, false) + esc(h) + '</button>';
        }).join('')
      : '<span class="ac-combo-empty">เลือกหลักสูตรก่อน แล้วรายชื่อวิทยากรของหลักสูตรนั้นจะแสดงที่นี่</span>';
  }

  function renderSlots() {
    var single = state.slots.length <= 1;
    $('ac-slots').innerHTML = state.slots.map(function (s, i) {
      return '<div class="ac-slot" data-slot="' + s.id + '">' +
        '<div class="ac-slot-head">' +
          '<span class="ac-slot-name">รอบที่ ' + (i + 1) + '</span>' +
          '<button type="button" class="ac-slot-remove" data-remove-slot="' + s.id + '"' + (single ? ' disabled' : '') +
            ' aria-label="ลบรอบที่ ' + (i + 1) + '">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
          '</button>' +
        '</div>' +
        '<div class="ac-slot-grid">' +
          slotField(s, 'date', 'วันที่จัด', 'date') +
          slotField(s, 'start', 'เริ่ม', 'time') +
          slotField(s, 'end', 'สิ้นสุด', 'time') +
          slotField(s, 'cap', 'รับได้ (คน)', 'number') +
        '</div>' +
        '</div>';
    }).join('');

    $('ac-add-slot').disabled = state.slots.length >= MAX_SLOTS;
  }

  /* วันที่กับเวลาใช้ตัวเลือกวงล้อของระบบ (datetime-picker.js) ไม่ใช้ของเบราว์เซอร์
     ค่าจริงเก็บที่ data-iso ส่วน value เป็นข้อความไทยไว้ให้คนอ่าน */
  function slotField(slot, key, label, type) {
    var v = esc(slot[key] || '');
    if (type === 'date' || type === 'time') {
      return '<label class="ac-slot-field">' +
        '<span class="ac-slot-label">' + esc(label) + '</span>' +
        '<input type="text" class="input" data-picker="' + type + '" data-iso="' + v + '"' +
        ' placeholder="' + (type === 'date' ? 'เลือกวันที่' : 'เลือกเวลา') + '"' +
        ' data-slot-id="' + slot.id + '" data-slot-key="' + key + '">' +
        '</label>';
    }
    return '<label class="ac-slot-field">' +
      '<span class="ac-slot-label">' + esc(label) + '</span>' +
      '<input type="' + type + '" class="input" value="' + v + '" min="0" inputmode="numeric" placeholder="0"' +
      ' data-slot-id="' + slot.id + '" data-slot-key="' + key + '">' +
      '</label>';
  }

  /* แถวช่วงเวลาแสดงเฉพาะสิ่งที่เปิดใช้งานไว้จริง ไม่งั้นผู้ใช้จะตั้งเวลาให้ระบบที่ไม่ได้เปิด */
  function activeWindows() {
    return [
      needsReg() ? { key: 'reg', label: 'เปิดให้ลงทะเบียน', hint: 'ผู้เข้าร่วมจองที่นั่งได้ในช่วงนี้' } : null,
      { key: 'chk', label: 'เปิดให้เช็คอิน', hint: 'สแกน QR หน้างานได้ในช่วงนี้' },
      hasSurvey() ? { key: 'srv', label: 'เปิดให้ทำแบบประเมิน', hint: 'ตอบแบบประเมินหลังกิจกรรมได้ในช่วงนี้' } : null
    ].filter(Boolean);
  }

  function renderWindows() {
    $('ac-windows').innerHTML = activeWindows().map(function (w) {
      var v = state.windows[w.key] || {};
      return '<div class="ac-window">' +
        '<div class="ac-window-text">' +
          '<span class="ac-window-label">' + esc(w.label) + '</span>' +
          '<span class="ac-window-hint">' + esc(w.hint) + '</span>' +
        '</div>' +
        '<div class="ac-window-range">' +
          '<span class="ac-window-sep">ตั้งแต่</span>' +
          winInput(w.key, 'from', 'date', v.from) +
          winInput(w.key, 'fromT', 'time', v.fromT) +
          '<span class="ac-window-sep">ถึง</span>' +
          winInput(w.key, 'to', 'date', v.to) +
          winInput(w.key, 'toT', 'time', v.toT) +
        '</div>' +
        '</div>';
    }).join('');
  }

  function winInput(group, key, type, value) {
    return '<input type="text" class="input ac-window-input" data-picker="' + type + '"' +
      ' data-iso="' + esc(value || '') + '" placeholder="' + (type === 'date' ? 'เลือกวันที่' : 'เวลา') + '"' +
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
    }).join('');
  }

  /* ---------- แผงขวา ---------- */
  function renderPreview() {
    var first = state.slots[0] || {};
    var totalCap = state.slots.reduce(function (n, s) { return n + (parseInt(s.cap, 10) || 0); }, 0);
    var hasFee = state.fee === FEES[1];

    $('ac-preview-cover').classList.toggle('is-filled', state.cover);
    $('ac-preview-cover').querySelector('.ac-preview-cover-text').textContent =
      state.cover ? 'cover-ปลูกผักปลอดสาร.jpg' : 'ยังไม่ได้อัปโหลดรูปปก';

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
    var on = state.publish;
    $('ac-qr-hint').textContent = on
      ? 'ใช้เช็คอินหน้างานและให้ผู้เข้าร่วมลงทะเบียนเอง'
      : 'QR จะใช้งานได้จริงหลังเผยแพร่กิจกรรม';

    $('ac-qr-list').innerHTML = QR_LINKS.map(function (q) {
      return '<div class="ac-qr-item' + (on ? '' : ' is-off') + '">' +
        '<span class="ac-qr-thumb">' + qrSvg(q.url) + '</span>' +
        '<span class="ac-qr-text">' +
          '<span class="ac-qr-label">' + esc(q.label) + '</span>' +
          '<span class="ac-qr-url">' + esc(q.url) + '</span>' +
        '</span>' +
        '<span class="ac-qr-actions">' +
          '<button type="button" class="ac-qr-btn" data-qr-download="' + esc(q.url) + '"' + (on ? '' : ' disabled') + ' aria-label="ดาวน์โหลด QR ' + esc(q.label) + '">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg></button>' +
          '<button type="button" class="ac-qr-btn" data-copy="' + esc(q.url) + '"' + (on ? '' : ' disabled') + ' aria-label="คัดลอกลิงก์ ' + esc(q.label) + '">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/></svg></button>' +
        '</span>' +
        '</div>';
    }).join('');

    $('ac-qr-all').disabled = !on;
  }

  /* QR จำลอง 17x17 ช่อง คำนวณจากลิงก์ ไม่ใช่ QR ที่สแกนได้จริง
     เมื่อต่อ backend ให้แทนด้วย <img src="{{qrUrl}}"> ที่ผูก token ของกิจกรรม */
  function qrSvg(seed) {
    var n = 17, cells = '', h = 0, i;
    for (i = 0; i < seed.length; i++) h = (h * 131 + seed.charCodeAt(i)) >>> 0;
    for (i = 0; i < n * n; i++) {
      h = (h * 1103515245 + 12345) >>> 0;
      if ((h >>> 16) % 100 < 46) cells += '<rect x="' + (i % n) + '" y="' + Math.floor(i / n) + '" width="1" height="1"/>';
    }
    [[0, 0], [n - 5, 0], [0, n - 5]].forEach(function (p) {
      cells += '<rect x="' + p[0] + '" y="' + p[1] + '" width="5" height="5" fill="none" stroke="currentColor" stroke-width="1"/>' +
        '<rect x="' + (p[0] + 2) + '" y="' + (p[1] + 2) + '" width="1" height="1"/>';
    });
    return '<svg viewBox="0 0 ' + n + ' ' + n + '" fill="currentColor" aria-hidden="true">' + cells + '</svg>';
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
    zone.classList.toggle('is-filled', state.cover);
    $('ac-cover-headline').textContent = state.cover
      ? 'cover-ปลูกผักปลอดสาร.jpg · 1.8MB'
      : 'ลากไฟล์มาวาง หรือเลือกจากเครื่อง';
    $('ac-cover-pick').textContent = state.cover ? 'เปลี่ยนรูป' : 'เลือกไฟล์';
    $('ac-cover-remove').hidden = !state.cover;
  }

  /* เรนเดอร์ทุกส่วนที่ขึ้นกับ state — ไม่แตะ input ที่ผู้ใช้กำลังพิมพ์ */
  function sync() {
    renderChips();
    renderPicks();
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
    $('ac-reg-detail').hidden = !needsReg();
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

  $('ac-kind').addEventListener('change', function () { state.kind = this.value; touch(); });
  $('ac-place').addEventListener('change', function () { state.place = this.value; touch(); });
  $('ac-mode').addEventListener('change', function () { state.mode = this.value; touch(); });

  /* เปลี่ยนรูปแบบการเข้าร่วมแล้วฟิลด์เงื่อนไข (ที่นั่งสำรอง · แบบฟอร์ม · ช่วงเวลา) จะซ่อน/แสดงตาม */
  $('ac-reg').addEventListener('change', function () { state.join = this.value; touch(); });

  /* radio ค่าเข้าร่วม — ผูกที่ container เพราะ input ถูกสร้างจาก JS */
  $('ac-fee').addEventListener('change', function (e) {
    var r = e.target.closest('[data-fee]');
    if (!r) return;
    state.fee = r.getAttribute('data-fee');
    if (state.fee !== FEES[1]) { state.feeAmount = ''; $('ac-fee-input').value = ''; }
    touch();
  });

  $('ac-audience').addEventListener('change', touch);
  $('ac-waitlist').addEventListener('change', touch);

  /* ช่องที่ใช้ตัวเลือกวงล้อเก็บค่าจริง (ISO) ไว้ที่ data-iso ส่วน .value เป็นข้อความไทย */
  function fieldValue(el) {
    return el.hasAttribute('data-picker') ? (el.getAttribute('data-iso') || '') : el.value;
  }

  /* input ของรอบกิจกรรมและช่วงเวลาถูกเรนเดอร์ใหม่ได้ จึงผูก event แบบ delegate */
  document.addEventListener('input', function (e) {
    var slot = e.target.closest('[data-slot-id]');
    if (slot) {
      var row = state.slots.filter(function (s) { return String(s.id) === slot.getAttribute('data-slot-id'); })[0];
      if (row) { row[slot.getAttribute('data-slot-key')] = fieldValue(slot); state.dirty = true; renderPreview(); renderChecklist(); }
      return;
    }
    var win = e.target.closest('[data-win-group]');
    if (win) {
      var g = win.getAttribute('data-win-group');
      state.windows[g] = state.windows[g] || {};
      state.windows[g][win.getAttribute('data-win-key')] = fieldValue(win);
      state.dirty = true;
    }
  });

  document.addEventListener('click', function (e) {
    var t = e.target;

    /* ---- chip ---- */
    var cat = t.closest('[data-cat]');
    if (cat) { toggleIn('cats', cat.getAttribute('data-cat')); return touch(); }

    var target = t.closest('[data-target]');
    if (target) { toggleIn('targets', target.getAttribute('data-target')); return touch(); }

    /* ---- การ์ดเลือก ---- */
    var fr = t.closest('[data-form-reg]');
    if (fr) { state.formReg = fr.getAttribute('data-form-reg'); return touch(); }

    var fp = t.closest('[data-form-post]');
    if (fp) { toggleIn('formsPost', fp.getAttribute('data-form-post')); return touch(); }

    /* ---- combobox ---- */
    var rmCourse = t.closest('[data-remove-course]');
    if (rmCourse) {
      e.stopPropagation();
      toggleIn('courses', rmCourse.getAttribute('data-remove-course'));
      pruneHosts();
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
      pruneHosts();
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
      state.slots.push({ id: state.nextSlotId++, date: '', start: '', end: '', cap: '' });
      state.dirty = true;
      renderSlots();
      return sync();
    }

    /* ---- รูปปก ---- */
    if (t.closest('#ac-cover-pick')) { state.cover = true; return touch(); }
    if (t.closest('#ac-cover-remove')) { state.cover = false; return touch(); }

    /* ---- toggle ---- */
    var tg = t.closest('[data-toggle]');
    if (tg) { var k = tg.getAttribute('data-toggle'); state[k] = !state[k]; return touch(); }

    /* ---- QR ---- */
    var copy = t.closest('[data-copy]');
    if (copy && !copy.disabled) {
      var url = copy.getAttribute('data-copy');
      if (navigator.clipboard) navigator.clipboard.writeText('https://' + url);
      if (window.TFC.showToast) window.TFC.showToast('คัดลอกลิงก์ ' + url + ' แล้ว', 'success');
      return;
    }
    var dl = t.closest('[data-qr-download]');
    if (dl && !dl.disabled) {
      if (window.TFC.showToast) window.TFC.showToast('ดาวน์โหลด QR เรียบร้อย', 'success');
      return;
    }
    if (t.closest('#ac-qr-all') && !$('ac-qr-all').disabled) {
      if (window.TFC.showToast) window.TFC.showToast('กำลังสร้างไฟล์ PDF รวม QR ทั้งหมด', 'info');
      return;
    }

    /* ---- ปุ่มล่าง ---- */
    if (t.closest('#ac-save-draft')) return saveDraft(true);
    if (t.closest('#ac-publish') && !$('ac-publish').disabled) {
      state.dirty = false;
      if (window.TFC.showToast) window.TFC.showToast('บันทึกและเผยแพร่กิจกรรมเรียบร้อย', 'success');
      return;
    }

    /* คลิกนอก combobox ให้ปิด */
    if (!t.closest('.ac-combo')) {
      if (state.combo) { state.combo = null; syncCombo(); }
    }
  });

  /* เอาวิทยากรที่ไม่ได้อยู่ในหลักสูตรที่เหลือออก ไม่งั้นจะค้างเป็นชื่อที่เลือกไม่ได้แล้ว */
  function pruneHosts() {
    var allowed = teachersForCourses();
    state.hosts = state.hosts.filter(function (h) { return allowed.indexOf(h) > -1; });
  }

  function syncCombo() {
    [['course', 'ac-course-panel', 'ac-course-control'], ['host', 'ac-host-panel', 'ac-host-control']]
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
    el.hidden = false;
    $('ac-autosave-text').textContent = 'บันทึกร่างล่าสุด ' +
      String(state.savedAt.getHours()).padStart(2, '0') + ':' +
      String(state.savedAt.getMinutes()).padStart(2, '0') + ' น.';
    if (manual && window.TFC.showToast) window.TFC.showToast('บันทึกฉบับร่างเรียบร้อย', 'success');
  }

  setInterval(function () { if (state.dirty) saveDraft(false); }, AUTOSAVE_MS);

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

  /* ---------- เริ่มต้น ---------- */
  initOptions();
  renderSlots();
  sync();
  syncCombo();
})();
