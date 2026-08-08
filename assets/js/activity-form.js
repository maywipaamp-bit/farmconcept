/* TheFarmConcept — Activity Form v2 (ฟอร์มกิจกรรมแบบแบ่งส่วน)
   โครงหน้าจอตามแบบที่ทีมงานส่งมา: ซ้ายกรอกทีละส่วน (7 ส่วน) พร้อมแถบ "ความครบถ้วน"
   ขวาเป็นตัวอย่างบนมือถือแบบ sticky ที่อัปเดตทันทีตามที่กรอก

   create.html และ edit.html เรียกใช้ตัวเดียวกัน ต่างกันแค่ mode + ข้อมูลตั้งต้น
   โหลดหลัง mock-data.js, toast.js, activity-module.js, field-widgets.js

   ใช้: TFC.renderActivityForm('mount-id', { mode: 'create' | 'edit', activity: {...} })

   การบันทึก
   - "บันทึกร่าง" ตรวจแค่ชื่อกิจกรรม
   - "บันทึกและเผยแพร่" ตรวจฟิลด์บังคับครบ + รอบกิจกรรมถูกต้อง แล้วสรุป error ไว้ให้ */
window.TFC = window.TFC || {};

(function () {
  var MAX_ROUNDS = 5;
  var NAME_MAX = 120;
  var DESC_MAX = 500;

  var ICON = {
    date: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    place: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    person: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    seat: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
    image: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
    trash: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>',
    qr: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM21 14h.01M17 21h.01M21 21h.01M14 21h.01"/></svg>'
  };

  var SECTIONS = [
    { no: 1, label: 'กิจกรรมนี้คืออะไร' },
    { no: 2, label: 'จัดที่ไหน เมื่อไหร่' },
    { no: 3, label: 'ใครเข้าร่วมได้ และค่าใช้จ่าย' },
    { no: 4, label: 'วิทยากร' },
    { no: 5, label: 'กำหนดแบบประเมิน' },
    { no: 6, label: 'การเผยแพร่' },
    { no: 7, label: 'สื่อสำหรับหน้างาน' }
  ];

  function esc(v) { return window.TFC.escapeHtml(v); }

  function chipRow(name, items, selected, multi) {
    return '<div class="choice-row" data-choice="' + name + '" data-multi="' + (multi ? '1' : '') + '">' +
      items.map(function (item) {
        var value = item.value || item;
        var active = multi ? selected.indexOf(value) !== -1 : selected === value;
        return '<button type="button" class="choice-chip' + (active ? ' is-active' : '') +
          '" data-value="' + esc(value) + '">' + esc(item.label || value) +
          (item.hint ? '<span class="choice-chip-hint">' + esc(item.hint) + '</span>' : '') +
          '</button>';
      }).join('') + '</div>';
  }

  function selectField(id, values, current, placeholder) {
    return '<select class="select" id="' + id + '">' +
      (placeholder ? '<option value="">' + esc(placeholder) + '</option>' : '') +
      values.map(function (v) {
        return '<option value="' + esc(v) + '"' + (v === current ? ' selected' : '') + '>' + esc(v) + '</option>';
      }).join('') + '</select>';
  }

  window.TFC.renderActivityForm = function (mountId, opts) {
    var mount = document.getElementById(mountId);
    if (!mount) return;

    opts = opts || {};
    var mock = window.TFC_MOCK;
    var isEdit = opts.mode === 'edit';
    var activity = opts.activity || {};

    /* ---------- state ---------- */
    var data = {
      name: activity.name || '',
      format: activity.format || '',
      type: activity.type || (mock.activityTypes || [])[0] || '',
      course: activity.course || '',
      description: activity.description || '',
      cover: activity.coverImage || '',
      area: (activity.areaList && activity.areaList[0]) || activity.area || '',
      venueMode: activity.venueMode || (mock.activityVenueModes || [])[0] || '',
      rounds: (isEdit ? window.TFC.activity.schedules(activity) : []).map(function (r) {
        return { date: r.date, timeStart: r.timeStart, timeEnd: r.timeEnd, capacity: r.capacity };
      }),
      hasFee: !!activity.hasFee,
      fee: activity.fee || '',
      registrationMode: activity.registrationMode || (mock.activityRegistrationModes || [])[0].value,
      participantType: activity.participantType || '',
      registerCloseDate: activity.registerCloseDate || '',
      targetGroups: (activity.targetGroups || []).slice(),
      instructors: (activity.instructorList || []).slice(),
      evaluationFormIds: (activity.evaluationFormIds || []).slice(),
      isPublished: !!activity.isPublished,
      isFeatured: !!activity.isFeatured,
      publishStart: activity.publishStart || '',
      publishEnd: activity.publishEnd || ''
    };
    if (!data.rounds.length) data.rounds.push({ date: '', timeStart: '', timeEnd: '', capacity: '' });

    var courses = [];
    (mock.programs || []).forEach(function (p) {
      (p.courses || []).forEach(function (c) { courses.push(c.name); });
    });
    var areaNames = (mock.areas || []).map(function (a) { return a.name; });
    var formats = (mock.activityFormats || []).filter(function (f) { return f.active; }).map(function (f) { return f.name; });
    var targetNames = (mock.targetGroups || []).map(function (g) { return g.name; });

    /* ---------- markup ---------- */
    function sectionHead(no, title, note) {
      return '<div class="form-section-head">' +
        '<span class="form-section-no">ส่วนที่ ' + no + '</span>' +
        '<h2 class="form-section-title">' + esc(title) + '</h2>' +
        (note ? '<span class="form-section-note" data-note="' + no + '">' + esc(note) + '</span>' : '') +
        '</div>';
    }

    mount.innerHTML =
      '<div class="form-steps">' +
      '<div class="form-steps-list">' +
      SECTIONS.map(function (s) {
        return '<button type="button" class="form-step-chip" data-step="' + s.no + '">' +
          '<span class="form-step-no">' + s.no + '</span>' + esc(s.label) + '</button>';
      }).join('') +
      '</div>' +
      '<div class="form-progress">ความครบถ้วน' +
      '<div class="progress"><div class="progress-bar" id="af-progress-bar" style="width:0%"></div></div>' +
      '<strong id="af-progress-text">0%</strong></div>' +
      '</div>' +

      '<form id="af-form" novalidate><div class="form-split"><div>' +

      /* ===== 1 ===== */
      '<section class="form-section" id="af-section-1">' +
      sectionHead(1, 'กิจกรรมนี้คืออะไร') +
      '<div class="form-group">' +
      '<label class="form-label" for="af-name">ชื่อกิจกรรม<span class="form-required">*</span></label>' +
      '<input class="input" id="af-name" maxlength="' + NAME_MAX + '" value="' + esc(data.name) + '">' +
      '<div class="field-hint"><span>ตั้งชื่อให้เห็นภาพว่าผู้เข้าร่วมจะได้ทำอะไร</span>' +
      '<span><span id="af-name-count">0</span>/' + NAME_MAX + '</span></div>' +
      '</div>' +

      '<div class="form-group">' +
      '<span class="form-label">หมวดหมู่<span class="form-required">*</span></span>' +
      chipRow('format', formats, data.format) +
      '</div>' +

      '<div class="form-row">' +
      '<div class="form-group mb-0">' +
      '<span class="form-label">ประเภท<span class="form-required">*</span></span>' +
      chipRow('type', mock.activityTypes || [], data.type) +
      '</div>' +
      '<div class="form-group mb-0">' +
      '<label class="form-label" for="af-course">หลักสูตรการเรียนรู้</label>' +
      selectField('af-course', courses, data.course, 'ไม่ผูกกับหลักสูตร') +
      '</div>' +
      '</div>' +

      '<div class="form-group">' +
      '<label class="form-label" for="af-desc">รายละเอียด<span class="form-required">*</span></label>' +
      '<textarea class="textarea" id="af-desc" maxlength="' + DESC_MAX + '">' + esc(data.description) + '</textarea>' +
      '<div class="field-hint"><span>ผู้เข้าร่วมจะได้ทำอะไร ได้อะไรกลับบ้าน และต้องเตรียมอะไรมาบ้าง</span>' +
      '<span><span id="af-desc-count">0</span>/' + DESC_MAX + '</span></div>' +
      '</div>' +

      '<div class="form-group mb-0">' +
      '<label class="form-label">รูปภาพปก<span class="form-required">*</span>' +
      '<span class="form-helper" style="display:block;">อัตราส่วน 16:9 · JPG/PNG ไม่เกิน 5MB</span></label>' +
      '<div class="image-slot" id="af-cover-slot" tabindex="0" role="button">' + ICON.image +
      '<span>ลากรูปมาวาง หรือคลิกเพื่อเลือกไฟล์</span>' +
      '<input type="file" class="hidden" accept="image/*" id="af-cover"></div>' +
      '</div>' +
      '</section>' +

      /* ===== 2 ===== */
      '<section class="form-section" id="af-section-2">' +
      sectionHead(2, 'จัดที่ไหน เมื่อไหร่', '') +
      '<div class="form-row">' +
      '<div class="form-group mb-0"><label class="form-label" for="af-area">สถานที่จัด<span class="form-required">*</span></label>' +
      selectField('af-area', areaNames, data.area, 'เลือกพื้นที่ดำเนินงาน') + '</div>' +
      '<div class="form-group mb-0"><label class="form-label" for="af-venue">รูปแบบ<span class="form-required">*</span></label>' +
      selectField('af-venue', mock.activityVenueModes || [], data.venueMode) + '</div>' +
      '</div>' +

      '<div class="form-group mb-0">' +
      '<span class="form-label">รอบกิจกรรม<span class="form-required">*</span>' +
      '<span class="form-helper" style="display:block;">กำหนดจำนวนรับแยกแต่ละรอบได้ (สูงสุด ' + MAX_ROUNDS + ' รอบ)</span></span>' +
      '<div id="af-rounds"></div>' +
      '<button type="button" class="btn btn-outline btn-sm" id="af-add-round">' +
      '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> เพิ่มรอบ</button>' +
      '</div>' +
      '</section>' +

      /* ===== 3 ===== */
      '<section class="form-section" id="af-section-3">' +
      sectionHead(3, 'ใครเข้าร่วมได้ และค่าใช้จ่าย') +
      '<div class="form-group">' +
      '<span class="form-label">ค่าเข้าร่วม<span class="form-required">*</span></span>' +
      chipRow('fee', [{ value: 'free', label: 'ไม่มีค่าใช้จ่าย' }, { value: 'paid', label: 'มีค่าเข้าร่วม' }],
        data.hasFee ? 'paid' : 'free') +
      '<div id="af-fee-wrap" class="' + (data.hasFee ? '' : 'hidden') + '" style="margin-top:var(--space-2);max-width:220px;">' +
      '<div class="flex items-center gap-2">' +
      '<input type="number" class="input" id="af-fee" min="0" value="' + esc(data.fee) + '">' +
      '<span class="small text-secondary" style="white-space:nowrap;">บาท / ท่าน</span></div></div>' +
      '</div>' +

      '<div class="form-group">' +
      '<span class="form-label">การลงทะเบียน<span class="form-required">*</span></span>' +
      chipRow('registrationMode', (mock.activityRegistrationModes || []).map(function (m) {
        return { value: m.value, label: m.value, hint: m.hint };
      }), data.registrationMode) +
      '</div>' +

      '<div class="form-row" id="af-register-fields">' +
      '<div class="form-group mb-0"><label class="form-label" for="af-participant">ใครลงทะเบียนได้</label>' +
      selectField('af-participant', mock.activityParticipantTypes || [], data.participantType, 'ทุกคน') + '</div>' +
      '<div class="form-group mb-0"><label class="form-label" for="af-close-date">ปิดรับสมัครวันที่</label>' +
      '<input type="hidden" id="af-close-date" data-thai-date></div>' +
      '</div>' +

      '<div class="form-group mb-0">' +
      '<span class="form-label">กลุ่มเป้าหมาย<span class="form-required">*</span>' +
      '<span class="form-helper" style="display:block;">ใช้สำหรับรายงานผล ไม่แสดงบนหน้าเว็บ</span></span>' +
      chipRow('targetGroups', targetNames, data.targetGroups, true) +
      '</div>' +
      '</section>' +

      /* ===== 4 ===== */
      '<section class="form-section" id="af-section-4">' +
      sectionHead(4, 'วิทยากร') +
      '<div class="form-group mb-0 multi-picker">' +
      '<label class="form-label" for="af-instructor-search">เลือกวิทยากร' +
      '<span class="form-helper" style="display:block;">พิมพ์ค้นหา เลือกได้มากกว่า 1 คน</span></label>' +
      '<div class="multi-picker-selected" id="af-instructor-tags"></div>' +
      '<input class="input" id="af-instructor-search" placeholder="พิมพ์ชื่อวิทยากร">' +
      '<div class="multi-picker-options" id="af-instructor-options"></div>' +
      '</div>' +
      '</section>' +

      /* ===== 5 ===== */
      '<section class="form-section" id="af-section-5">' +
      sectionHead(5, 'กำหนดแบบประเมิน') +
      '<div class="form-group mb-0 multi-picker">' +
      '<label class="form-label" for="af-survey-search">ชุดแบบประเมิน' +
      '<span class="form-helper" style="display:block;">พิมพ์ค้นหา เลือกได้มากกว่า 1 ชุด</span></label>' +
      '<div class="multi-picker-selected" id="af-survey-tags"></div>' +
      '<input class="input" id="af-survey-search" placeholder="พิมพ์ชื่อชุดแบบประเมิน">' +
      '<div class="multi-picker-options" id="af-survey-options"></div>' +
      '</div>' +
      '</section>' +

      /* ===== 6 ===== */
      '<section class="form-section" id="af-section-6">' +
      sectionHead(6, 'การเผยแพร่') +
      '<div class="switch-row">' +
      '<div class="switch-row-text"><strong>เผยแพร่บนหน้าเว็บผู้เข้าร่วม</strong>' +
      '<span id="af-publish-hint">ยังไม่เผยแพร่ — บันทึกไว้เป็นฉบับร่างได้</span></div>' +
      '<label class="switch"><input type="checkbox" id="af-publish"' + (data.isPublished ? ' checked' : '') +
      '><span class="switch-track"></span></label></div>' +

      '<div class="switch-row">' +
      '<div class="switch-row-text"><strong>ปักหมุดเป็นกิจกรรมแนะนำ</strong>' +
      '<span>แสดงในแถบแนะนำด้านบนสุดของหน้าเว็บ</span></div>' +
      '<label class="switch"><input type="checkbox" id="af-featured"' + (data.isFeatured ? ' checked' : '') +
      '><span class="switch-track"></span></label></div>' +

      '<div class="form-row mt-3' + (data.isPublished ? '' : ' hidden') + '" id="af-publish-fields">' +
      '<div class="form-group mb-0"><label class="form-label" for="af-publish-start">เริ่มแสดงผล</label>' +
      '<input type="datetime-local" class="input" id="af-publish-start" value="' + esc(data.publishStart) + '"></div>' +
      '<div class="form-group mb-0"><label class="form-label" for="af-publish-end">สิ้นสุดการแสดงผล</label>' +
      '<input type="datetime-local" class="input" id="af-publish-end" value="' + esc(data.publishEnd) + '"></div>' +
      '</div>' +
      '</section>' +

      /* ===== 7 ===== */
      '<section class="form-section" id="af-section-7">' +
      sectionHead(7, 'สื่อสำหรับหน้างาน') +
      '<div id="af-qr-area"></div>' +
      '</section>' +

      '<div class="alert alert-danger hidden" id="af-validation">' +
      '<span class="alert-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg></span>' +
      '<div><div class="alert-title">กรุณาตรวจสอบข้อมูลก่อนบันทึก</div><ul id="af-validation-list" class="alert-list"></ul></div>' +
      '</div>' +

      '<div class="form-actions">' +
      '<span class="form-actions-valid" id="af-valid-text"></span>' +
      '<button type="button" class="btn btn-outline btn-sm" data-go-back>ยกเลิก</button>' +
      '<button type="button" class="btn btn-secondary btn-sm" id="af-save-draft">บันทึกร่าง</button>' +
      '<button type="submit" class="btn btn-primary btn-sm" id="af-save-publish">' +
      (isEdit ? 'บันทึกการแก้ไข' : 'บันทึกและเผยแพร่') + '</button>' +
      '</div>' +

      '</div>' +

      /* ---------- ตัวอย่างบนมือถือ ---------- */
      '<aside class="form-split-side">' +
      '<div class="phone-frame"><div class="phone-notch"></div>' +
      '<div class="phone-screen">' +
      '<div class="preview-cover" id="af-preview-cover">ยังไม่ได้อัปโหลดรูปภาพปก</div>' +
      '<div class="preview-body" id="af-preview-body"></div>' +
      '</div></div>' +
      '<p class="preview-caption">ตัวอย่างที่ผู้เข้าร่วมจะเห็นบนหน้าเว็บ</p>' +
      '</aside></div></form>';

    /* ---------- helper อ่าน/เขียน ---------- */
    function el(id) { return document.getElementById(id); }
    function on(id, evt, fn) { var e = el(id); if (e) e.addEventListener(evt, fn); }

    /* ---------- รอบกิจกรรม ---------- */
    function roundHtml(round, index) {
      return '<div class="round-card" data-round="' + index + '">' +
        '<div class="round-card-head">รอบที่ ' + (index + 1) +
        '<button type="button" class="dynamic-row-remove" data-remove-round aria-label="ลบรอบนี้">' + ICON.trash + '</button>' +
        '</div>' +
        '<div class="round-fields">' +
        '<div><label class="form-label">วันที่จัด</label><input type="hidden" data-field="date" data-thai-date></div>' +
        '<div><label class="form-label">เริ่ม</label><input type="time" class="input" data-field="timeStart" value="' + esc(round.timeStart || '') + '"></div>' +
        '<div><label class="form-label">สิ้นสุด</label><input type="time" class="input" data-field="timeEnd" value="' + esc(round.timeEnd || '') + '"></div>' +
        '<div><label class="form-label">รับได้ (คน)</label><input type="number" class="input" min="1" data-field="capacity" value="' + esc(round.capacity || '') + '"></div>' +
        '</div></div>';
    }

    function renderRounds() {
      el('af-rounds').innerHTML = data.rounds.map(roundHtml).join('');
      /* ช่องวันที่เป็น widget ปฏิทินไทย ต้อง init ใหม่ทุกครั้งที่วาดแถว */
      window.TFC.initFieldWidgets(el('af-rounds'));
      data.rounds.forEach(function (round, index) {
        var row = el('af-rounds').querySelector('[data-round="' + index + '"]');
        window.TFC.setDateValue(row.querySelector('[data-field="date"]'), round.date);
      });
      el('af-add-round').disabled = data.rounds.length >= MAX_ROUNDS;
    }

    function readRounds() {
      return Array.prototype.map.call(el('af-rounds').querySelectorAll('.round-card'), function (row) {
        var read = function (f) { return row.querySelector('[data-field="' + f + '"]').value; };
        return { date: read('date'), timeStart: read('timeStart'), timeEnd: read('timeEnd'), capacity: read('capacity') };
      });
    }

    on('af-add-round', 'click', function () {
      data.rounds = readRounds();
      if (data.rounds.length >= MAX_ROUNDS) return;
      data.rounds.push({ date: '', timeStart: '', timeEnd: '', capacity: '' });
      renderRounds();
      sync();
    });

    el('af-rounds').addEventListener('click', function (e) {
      if (!e.target.closest('[data-remove-round]')) return;
      var index = Number(e.target.closest('.round-card').getAttribute('data-round'));
      data.rounds = readRounds();
      data.rounds.splice(index, 1);
      if (!data.rounds.length) data.rounds.push({ date: '', timeStart: '', timeEnd: '', capacity: '' });
      renderRounds();
      sync();
    });

    /* ---------- ชิปตัวเลือก ---------- */
    mount.addEventListener('click', function (e) {
      var chip = e.target.closest('.choice-chip');
      if (!chip) return;
      var group = chip.closest('[data-choice]');
      var name = group.getAttribute('data-choice');
      var multi = !!group.getAttribute('data-multi');
      var value = chip.getAttribute('data-value');

      if (multi) {
        var list = data[name];
        var at = list.indexOf(value);
        if (at === -1) list.push(value); else list.splice(at, 1);
        chip.classList.toggle('is-active', at === -1);
      } else {
        group.querySelectorAll('.choice-chip').forEach(function (c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');
        if (name === 'fee') {
          data.hasFee = value === 'paid';
          el('af-fee-wrap').classList.toggle('hidden', !data.hasFee);
        } else {
          data[name] = value;
        }
      }
      sync();
    });

    /* ---------- ตัวเลือกหลายค่าแบบค้นหา ---------- */
    function multiPicker(key, searchId, tagsId, optionsId, source, emptyText) {
      var search = el(searchId);

      function renderTags() {
        el(tagsId).innerHTML = data[key].map(function (value) {
          var item = source.filter(function (o) { return o.value === value; })[0];
          return '<span class="multiselect-tag">' + esc(item ? item.label : value) +
            '<button type="button" data-remove="' + esc(value) + '" aria-label="เอาออก">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
            '</button></span>';
        }).join('');
      }

      function renderOptions() {
        var term = search.value.trim().toLowerCase();
        var list = source.filter(function (o) {
          return !term || (o.label + ' ' + (o.note || '')).toLowerCase().indexOf(term) !== -1;
        });
        el(optionsId).innerHTML = list.length
          ? list.map(function (o) {
              return '<button type="button" class="multi-picker-option' +
                (data[key].indexOf(o.value) !== -1 ? ' is-selected' : '') + '" data-pick="' + esc(o.value) + '">' +
                esc(o.label) + (o.note ? '<span>' + esc(o.note) + '</span>' : '') + '</button>';
            }).join('')
          : '<div class="notification-empty">' + esc(emptyText) + '</div>';
      }

      search.addEventListener('input', renderOptions);

      el(optionsId).addEventListener('click', function (e) {
        var pick = e.target.closest('[data-pick]');
        if (!pick) return;
        var value = pick.getAttribute('data-pick');
        var at = data[key].indexOf(value);
        if (at === -1) data[key].push(value); else data[key].splice(at, 1);
        renderTags();
        renderOptions();
        sync();
      });

      el(tagsId).addEventListener('click', function (e) {
        var remove = e.target.closest('[data-remove]');
        if (!remove) return;
        data[key] = data[key].filter(function (v) { return v !== remove.getAttribute('data-remove'); });
        renderTags();
        renderOptions();
        sync();
      });

      renderTags();
      renderOptions();
    }

    multiPicker('instructors', 'af-instructor-search', 'af-instructor-tags', 'af-instructor-options',
      (mock.instructors || []).map(function (i) { return { value: i.name, label: i.name, note: i.expertise }; }),
      'ไม่พบวิทยากรที่ตรงกับคำค้นหา');

    multiPicker('evaluationFormIds', 'af-survey-search', 'af-survey-tags', 'af-survey-options',
      (mock.evaluationForms || []).map(function (f) { return { value: f.id, label: f.name, note: f.type }; }),
      'ไม่พบแบบประเมินที่ตรงกับคำค้นหา');

    /* ---------- รูปปก ---------- */
    var coverInput = el('af-cover');
    var coverSlot = el('af-cover-slot');

    function showCover(file) {
      if (!file || file.type.indexOf('image/') !== 0) return;
      if (file.size > 5 * 1024 * 1024) {
        if (window.TFC.showToast) window.TFC.showToast('ไฟล์ใหญ่เกิน 5MB กรุณาเลือกไฟล์ใหม่', 'danger');
        return;
      }
      var reader = new FileReader();
      reader.onload = function (e) {
        data.cover = e.target.result;
        coverSlot.innerHTML = '<img src="' + data.cover + '" alt="ภาพปกกิจกรรม">';
        sync();
      };
      reader.readAsDataURL(file);
    }

    coverSlot.addEventListener('click', function () { coverInput.click(); });
    coverInput.addEventListener('click', function (e) { e.stopPropagation(); });
    coverInput.addEventListener('change', function () { showCover(coverInput.files[0]); });
    ['dragover', 'dragenter'].forEach(function (evt) {
      coverSlot.addEventListener(evt, function (e) { e.preventDefault(); coverSlot.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
      coverSlot.addEventListener(evt, function () { coverSlot.classList.remove('is-dragover'); });
    });
    coverSlot.addEventListener('drop', function (e) {
      e.preventDefault();
      showCover(e.dataTransfer.files[0]);
    });
    if (data.cover) coverSlot.innerHTML = '<img src="' + esc(data.cover) + '" alt="ภาพปกกิจกรรม">';

    /* ---------- toggle เผยแพร่ ---------- */
    on('af-publish', 'change', function (e) {
      data.isPublished = e.target.checked;
      el('af-publish-fields').classList.toggle('hidden', !data.isPublished);
      el('af-publish-hint').textContent = data.isPublished
        ? 'กิจกรรมนี้จะแสดงบนหน้าเว็บผู้เข้าร่วม'
        : 'ยังไม่เผยแพร่ — บันทึกไว้เป็นฉบับร่างได้';
      sync();
    });
    on('af-featured', 'change', function (e) { data.isFeatured = e.target.checked; sync(); });

    /* ---------- เก็บค่าจากช่องกรอกทั่วไป ---------- */
    function collect() {
      data.name = el('af-name').value;
      data.description = el('af-desc').value;
      data.course = el('af-course').value;
      data.area = el('af-area').value;
      data.venueMode = el('af-venue').value;
      data.fee = el('af-fee').value;
      data.participantType = el('af-participant').value;
      data.registerCloseDate = el('af-close-date').value;
      data.publishStart = el('af-publish-start').value;
      data.publishEnd = el('af-publish-end').value;
      data.rounds = readRounds();
    }

    /* ---------- ความครบถ้วน ---------- */
    function requiredState() {
      return [
        { ok: !!data.name.trim(), section: 1 },
        { ok: !!data.format, section: 1 },
        { ok: !!data.type, section: 1 },
        { ok: !!data.description.trim(), section: 1 },
        { ok: !!data.cover, section: 1 },
        { ok: !!data.area, section: 2 },
        { ok: !!data.venueMode, section: 2 },
        { ok: data.rounds.some(function (r) { return r.date && r.timeStart && r.timeEnd && Number(r.capacity) > 0; }), section: 2 },
        { ok: !data.hasFee || Number(data.fee) > 0, section: 3 },
        { ok: !!data.registrationMode, section: 3 },
        { ok: data.targetGroups.length > 0, section: 3 },
        { ok: data.instructors.length > 0, section: 4 }
      ];
    }

    function syncProgress() {
      var items = requiredState();
      var done = items.filter(function (i) { return i.ok; }).length;
      var pct = Math.round((done / items.length) * 100);
      el('af-progress-bar').style.width = pct + '%';
      el('af-progress-text').textContent = pct + '%';
      el('af-valid-text').textContent = pct === 100
        ? 'กรอกครบแล้ว พร้อมเผยแพร่'
        : 'กรอกครบ ' + done + '/' + items.length + ' ข้อที่จำเป็น';

      /* ทำเครื่องหมายส่วนที่กรอกครบแล้วบนแถบด้านบน */
      SECTIONS.forEach(function (s) {
        var need = items.filter(function (i) { return i.section === s.no; });
        var chip = mount.querySelector('[data-step="' + s.no + '"]');
        chip.classList.toggle('is-done', need.length > 0 && need.every(function (i) { return i.ok; }));
      });
    }

    /* ---------- ตัวอย่างบนมือถือ ---------- */
    function metaRow(icon, text) {
      return '<div class="preview-meta">' + icon + '<span>' + esc(text) + '</span></div>';
    }

    function renderPreview() {
      var rounds = data.rounds.filter(function (r) { return r.date; });
      var dateText = rounds.length
        ? window.TFC.formatThaiDate(rounds[0].date) + (rounds[0].timeStart ? ' · ' + rounds[0].timeStart + '–' + rounds[0].timeEnd + ' น.' : '') +
          (rounds.length > 1 ? ' และอีก ' + (rounds.length - 1) + ' รอบ' : '')
        : 'ยังไม่ระบุวันที่';
      var seats = rounds.reduce(function (sum, r) { return sum + Number(r.capacity || 0); }, 0);

      el('af-preview-cover').innerHTML = data.cover
        ? '<img src="' + data.cover + '" alt="ภาพปกกิจกรรม">'
        : 'ยังไม่ได้อัปโหลดรูปภาพปก';

      el('af-preview-body').innerHTML =
        '<div class="tag-list">' +
        (data.format ? '<span class="tag tag-primary">' + esc(data.format) + '</span>' : '') +
        (data.type ? '<span class="tag">' + esc(data.type) + '</span>' : '') +
        '</div>' +
        '<div class="preview-title">' + esc(data.name || 'ชื่อกิจกรรม') + '</div>' +
        metaRow(ICON.date, dateText) +
        metaRow(ICON.place, data.area || 'ยังไม่ระบุสถานที่') +
        (data.instructors.length ? metaRow(ICON.person, data.instructors.join(', ')) : '') +
        (seats ? metaRow(ICON.seat, 'รับได้ ' + seats + ' คน') : '') +
        '<div class="preview-divider"></div>' +
        '<div class="preview-price">' + (data.hasFee && data.fee ? Number(data.fee).toLocaleString('th-TH') + ' บาท / ท่าน' : 'เข้าร่วมฟรี') + '</div>' +
        (data.description ? '<div class="preview-description">' + esc(data.description) + '</div>' : '') +
        '<button type="button" class="btn btn-primary btn-block" disabled>' +
        (data.registrationMode.indexOf('Walk-in') !== -1 ? 'เข้าร่วมได้เลย' : 'ลงทะเบียนเข้าร่วม') + '</button>';

      el('af-name-count').textContent = data.name.length;
      el('af-desc-count').textContent = data.description.length;

      /* ส่วนที่ 2: สรุปจำนวนรอบไว้ที่หัวข้อ */
      var note = mount.querySelector('[data-note="2"]');
      if (note) note.textContent = rounds.length ? rounds.length + ' รอบ · รับรวม ' + seats + ' คน' : '';

      /* ส่วนที่ 7: QR สร้างได้หลังเผยแพร่ */
      el('af-qr-area').innerHTML = data.isPublished
        ? '<div class="flex items-center gap-3">' + ICON.qr +
          '<div><div class="small font-medium">QR Code ลงทะเบียน / เช็คอิน</div>' +
          '<div class="caption text-secondary">ดาวน์โหลดได้หลังบันทึกกิจกรรม</div></div></div>'
        : '<div class="caption text-secondary">QR Code จะสร้างหลังเผยแพร่กิจกรรม</div>';
    }

    function sync() {
      collect();
      syncProgress();
      renderPreview();
    }

    /* ---------- แถบขั้นตอน ---------- */
    mount.querySelector('.form-steps-list').addEventListener('click', function (e) {
      var chip = e.target.closest('[data-step]');
      if (!chip) return;
      var target = el('af-section-' + chip.getAttribute('data-step'));
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      mount.querySelectorAll('[data-step]').forEach(function (c) { c.classList.remove('is-active'); });
      chip.classList.add('is-active');
    });

    /* ---------- validation + บันทึก ---------- */
    function validate(forPublish) {
      collect();
      var errors = [];
      if (!data.name.trim()) errors.push('กรุณากรอกชื่อกิจกรรม');
      if (!forPublish) return errors;

      if (!data.format) errors.push('กรุณาเลือกหมวดหมู่');
      if (!data.description.trim()) errors.push('กรุณากรอกรายละเอียดกิจกรรม');
      if (!data.cover) errors.push('กรุณาอัปโหลดรูปภาพปก');
      if (!data.area) errors.push('กรุณาเลือกสถานที่จัด');
      if (data.hasFee && !(Number(data.fee) > 0)) errors.push('เลือก "มีค่าเข้าร่วม" แล้ว กรุณาระบุจำนวนเงิน');
      if (!data.targetGroups.length) errors.push('กรุณาเลือกกลุ่มเป้าหมายอย่างน้อย 1 กลุ่ม');
      if (!data.instructors.length) errors.push('กรุณาเลือกวิทยากรอย่างน้อย 1 คน');

      var filled = data.rounds.filter(function (r) { return r.date || r.timeStart || r.timeEnd || r.capacity; });
      if (!filled.length) errors.push('กรุณาระบุรอบกิจกรรมอย่างน้อย 1 รอบ');
      filled.forEach(function (r, i) {
        var label = 'รอบที่ ' + (i + 1);
        if (!r.date) errors.push(label + ': กรุณาระบุวันที่จัด');
        if (!r.timeStart || !r.timeEnd) errors.push(label + ': กรุณาระบุเวลาเริ่มและสิ้นสุด');
        if (r.timeStart && r.timeEnd && r.timeStart >= r.timeEnd) errors.push(label + ': เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด');
        if (!(Number(r.capacity) > 0)) errors.push(label + ': จำนวนรับต้องมากกว่า 0');
      });
      filled.forEach(function (r, i) {
        filled.slice(i + 1).forEach(function (other, j) {
          if (r.date && r.date === other.date && r.timeStart < other.timeEnd && other.timeStart < r.timeEnd) {
            errors.push('รอบที่ ' + (i + 1) + ' และรอบที่ ' + (i + j + 2) + ': วันและเวลาทับซ้อนกัน');
          }
        });
      });

      if (data.isPublished && data.publishStart && data.publishEnd && data.publishStart >= data.publishEnd) {
        errors.push('วัน/เวลาสิ้นสุดการแสดงผลต้องมากกว่าวันที่เริ่ม');
      }
      return errors;
    }

    function showErrors(errors) {
      var box = el('af-validation');
      if (!errors.length) { box.classList.add('hidden'); return; }
      el('af-validation-list').innerHTML = errors.map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('');
      box.classList.remove('hidden');
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function save(button, forPublish, message) {
      var errors = validate(forPublish);
      showErrors(errors);
      if (errors.length) return;

      button.classList.add('btn-loading');
      button.disabled = true;
      setTimeout(function () {
        button.classList.remove('btn-loading');
        button.disabled = false;
        if (window.TFC.showToast) window.TFC.showToast(message, 'success');
        window.location.href = 'list.html';
      }, 700);
    }

    on('af-save-draft', 'click', function () {
      save(this, false, isEdit ? 'บันทึกฉบับร่างเรียบร้อย' : 'สร้างกิจกรรมเป็นฉบับร่างเรียบร้อย');
    });

    el('af-form').addEventListener('submit', function (e) {
      e.preventDefault();
      save(el('af-save-publish'), true, isEdit ? 'บันทึกการแก้ไขเรียบร้อย' : 'สร้างและเผยแพร่กิจกรรมเรียบร้อย');
    });

    /* ---------- เริ่มต้น ---------- */
    window.TFC.initFieldWidgets(mount);
    if (data.registerCloseDate) window.TFC.setDateValue('af-close-date', data.registerCloseDate);
    renderRounds();
    mount.addEventListener('input', sync);
    mount.addEventListener('change', sync);
    mount.querySelector('[data-step="1"]').classList.add('is-active');
    sync();
  };
})();
