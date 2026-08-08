/* TheFarmConcept — Activity Form (ฟอร์มเต็มจอ เพิ่ม/แก้ไขกิจกรรม)
   Layout 2 คอลัมน์: ซ้าย = ฟอร์ม 4 section, ขวา = กรอบมือถือ preview ที่อัปเดตแบบ real-time
   create.html และ edit.html เรียกใช้ตัวเดียวกัน ต่างกันแค่ mode + ข้อมูลตั้งต้น (ไม่เขียนฟอร์มซ้ำ 2 ไฟล์)

   โหลดหลัง mock-data.js, dynamic-row.js, toast.js, activity-module.js
   ใช้: TFC.renderActivityForm('mount-id', { mode: 'create' | 'edit', activity: {...} })

   กติกาการบันทึก
   - "บันทึกร่าง" ตรวจแค่ชื่อกิจกรรม (ให้เก็บงานค้างไว้ก่อนได้)
   - "บันทึกและเผยแพร่" ตรวจครบทุกฟิลด์บังคับ + validation ของรอบกิจกรรม/ช่วงเวลาเผยแพร่ แล้วสรุป error ไว้ท้ายฟอร์ม */
window.TFC = window.TFC || {};

(function () {
  var MAX_SCHEDULES = 5;
  var NAME_MAX = 150;
  var DESC_MAX = 300;

  var ICON = {
    calendar: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    clock: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    pin: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    user: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    users: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/></svg>',
    trash: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>'
  };

  function esc(value) { return window.TFC.escapeHtml(value); }

  /* กล่องติ๊กหลายค่า (กลุ่มเป้าหมาย / พื้นที่ / วิทยากร) — ใช้ .checkbox-item เดิมของระบบ */
  function checkboxGroup(name, values, selected) {
    selected = selected || [];
    return '<div class="checkbox-grid" data-group="' + name + '">' + values.map(function (value, index) {
      var checked = selected.indexOf(value) !== -1 ? ' checked' : '';
      return '<label class="checkbox-item">' +
        '<input type="checkbox" name="' + name + '" value="' + esc(value) + '" id="' + name + '-' + index + '"' + checked + '>' +
        '<span>' + esc(value) + '</span></label>';
    }).join('') + '</div>';
  }

  function selectOptions(values, current, placeholder) {
    var head = placeholder ? '<option value="">' + esc(placeholder) + '</option>' : '';
    return head + values.map(function (value) {
      return '<option value="' + esc(value) + '"' + (value === current ? ' selected' : '') + '>' + esc(value) + '</option>';
    }).join('');
  }

  function checkedValues(name) {
    return Array.prototype.map.call(
      document.querySelectorAll('input[name="' + name + '"]:checked'),
      function (input) { return input.value; }
    );
  }

  function scheduleRowHtml(row) {
    row = row || {};
    return '<div class="dynamic-row schedule-row">' +
      '<span class="dynamic-row-order" data-row-order></span>' +
      '<div class="schedule-fields">' +
      '<input type="date" class="input" data-field="date" value="' + esc(row.date || '') + '" aria-label="วันที่จัดกิจกรรม">' +
      '<input type="time" class="input" data-field="timeStart" value="' + esc(row.timeStart || '') + '" aria-label="เวลาเริ่ม">' +
      '<input type="time" class="input" data-field="timeEnd" value="' + esc(row.timeEnd || '') + '" aria-label="เวลาสิ้นสุด">' +
      '<input type="number" class="input" data-field="capacity" min="1" placeholder="จำนวนรับ (คน)" value="' + esc(row.capacity || '') + '" aria-label="จำนวนรับสมัคร">' +
      '</div>' +
      '<button type="button" class="dynamic-row-remove" aria-label="ลบรอบกิจกรรม">' + ICON.trash + '</button>' +
      '</div>';
  }

  window.TFC.renderActivityForm = function (mountId, opts) {
    var mount = document.getElementById(mountId);
    if (!mount) return;

    opts = opts || {};
    var mock = window.TFC_MOCK;
    var isEdit = opts.mode === 'edit';
    var activity = opts.activity || {};
    var schedules = isEdit ? window.TFC.activity.schedules(activity) : [];

    var formats = (mock.activityFormats || []).filter(function (f) { return f.active; }).map(function (f) { return f.name; });
    var courses = [];
    (mock.programs || []).forEach(function (program) {
      (program.courses || []).forEach(function (course) { courses.push(course.name); });
    });
    var areaNames = (mock.areas || []).map(function (area) { return area.name; });
    var targetNames = (mock.targetGroups || []).map(function (group) { return group.name; });
    var instructorNames = (mock.instructors || []).map(function (person) { return person.name; });
    var evaluationForms = (mock.evaluationForms || []);

    /* ---------- 1. Markup ---------- */
    mount.innerHTML =
      '<form id="af-form" novalidate>' +
      '<div class="form-split">' +
      '<div>' +

      /* Section 4.1 — ข้อมูลทั่วไป */
      '<div class="card mb-5">' +
      '<h2 class="card-title">ข้อมูลทั่วไป</h2>' +

      '<div class="form-group">' +
      '<label class="form-label" for="af-cover">รูปภาพกิจกรรม<span class="form-required">*</span></label>' +
      '<div class="upload-zone" id="af-cover-zone">' +
      '<div class="upload-zone-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M17 8l-5-5-5 5M12 3v12"/></svg></div>' +
      '<div class="small">ลากไฟล์มาวางหรือคลิกเพื่อเลือก</div>' +
      '<div class="caption text-secondary">JPG/PNG · 16:9 · ไม่เกิน 5MB</div>' +
      '<input type="file" id="af-cover" class="hidden" accept="image/*">' +
      '</div>' +
      '<div id="af-cover-list" class="upload-file-list"></div>' +
      '</div>' +

      '<div class="form-row">' +
      '<div class="form-group form-col-span-2">' +
      '<label class="form-label" for="af-name">ชื่อกิจกรรม<span class="form-required">*</span></label>' +
      '<input class="input" id="af-name" maxlength="' + NAME_MAX + '" placeholder="ชื่อกิจกรรม" value="' + esc(activity.name || '') + '">' +
      '<div class="form-helper"><span id="af-name-count">0</span>/' + NAME_MAX + ' ตัวอักษร</div>' +
      '</div>' +

      '<div class="form-group">' +
      '<span class="form-label">ประเภท<span class="form-required">*</span></span>' +
      '<div class="checkbox-grid">' +
      (mock.activityTypes || []).map(function (type, index) {
        var checked = (activity.type || 'กิจกรรม') === type ? ' checked' : '';
        return '<label class="radio-item"><input type="radio" name="af-type" value="' + esc(type) + '" id="af-type-' + index + '"' + checked + '><span>' + esc(type) + '</span></label>';
      }).join('') +
      '</div></div>' +

      '<div class="form-group">' +
      '<label class="form-label" for="af-participant-type">ประเภทผู้เข้าร่วม</label>' +
      '<select class="select" id="af-participant-type">' + selectOptions(mock.activityParticipantTypes || [], activity.participantType) + '</select>' +
      
      '</div>' +

      '<div class="form-group">' +
      '<label class="form-label" for="af-format">รูปแบบกิจกรรม<span class="form-required">*</span></label>' +
      '<select class="select" id="af-format">' + selectOptions(formats, activity.format, 'เลือกรูปแบบกิจกรรม') + '</select>' +
      '</div>' +

      '<div class="form-group">' +
      '<label class="form-label" for="af-course">หลักสูตรการเรียนรู้</label>' +
      '<select class="select" id="af-course">' + selectOptions(courses, activity.course, 'ไม่ระบุหลักสูตร') + '</select>' +
      '</div>' +
      '</div>' +

      '<div class="form-group">' +
      '<span class="form-label">กลุ่มเป้าหมาย<span class="form-required">*</span></span>' +
      checkboxGroup('af-target', targetNames, activity.targetGroups) +
      '</div>' +

      '<div class="form-group">' +
      '<span class="form-label">พื้นที่ดำเนินงาน<span class="form-required">*</span></span>' +
      checkboxGroup('af-area', areaNames, activity.areaList) +
      '</div>' +

      '<div class="form-group">' +
      '<span class="form-label">วิทยากร</span>' +
      checkboxGroup('af-instructor', instructorNames, activity.instructorList) +
      '</div>' +

      '<div class="form-group">' +
      '<label class="checkbox-item mb-3">' +
      '<input type="checkbox" id="af-has-fee"' + (activity.hasFee ? ' checked' : '') + '>' +
      '<span>มีค่าสมัคร (ไม่ติ๊ก = เข้าร่วมฟรี)</span></label>' +
      '<div id="af-fee-wrap" class="' + (activity.hasFee ? '' : 'hidden') + '">' +
      '<label class="form-label" for="af-fee">ค่าลงทะเบียน (บาท)</label>' +
      '<input type="number" class="input" id="af-fee" min="0" placeholder="0" value="' + esc(activity.fee || '') + '">' +
      '</div></div>' +

      '<div class="form-group mb-0">' +
      '<label class="form-label" for="af-desc">รายละเอียด (แบบย่อ)<span class="form-required">*</span></label>' +
      '<textarea class="textarea" id="af-desc" maxlength="' + DESC_MAX + '" placeholder="รายละเอียดโดยย่อ">' + esc(activity.description || '') + '</textarea>' +
      '<div class="form-helper"><span id="af-desc-count">0</span>/' + DESC_MAX + ' ตัวอักษร</div>' +
      '</div>' +
      '</div>' +

      /* Section 4.2 — กำหนดการและจำนวนรับสมัคร */
      '<div class="card mb-5">' +
      '<h2 class="card-title">กำหนดการและจำนวนรับสมัคร</h2>' +
      '<div class="schedule-row schedule-row-head">' +
      '<span>#</span>' +
      '<div class="schedule-fields"><span>วันที่จัดกิจกรรม</span><span>เวลาเริ่ม</span><span>เวลาสิ้นสุด</span><span>จำนวนรับสมัคร (คน)</span></div>' +
      '<span></span>' +
      '</div>' +
      '<div class="dynamic-row-list" id="af-schedule-list"></div>' +
      '<button type="button" class="btn btn-outline btn-sm" id="af-add-schedule">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> เพิ่มรอบ</button>' +
      
      '</div>' +

      /* Section 4.3 — กำหนดแบบประเมิน */
      '<div class="card mb-5">' +
      '<h2 class="card-title">กำหนดแบบประเมิน</h2>' +
      '<div class="form-group">' +
      '<span class="form-label">ชุดแบบประเมิน (เลือกได้มากกว่า 1 ชุด)</span>' +
      (evaluationForms.length
        ? '<div class="checkbox-grid">' + evaluationForms.map(function (form, index) {
            var checked = (activity.evaluationFormIds || []).indexOf(form.id) !== -1 ? ' checked' : '';
            return '<label class="checkbox-item"><input type="checkbox" name="af-eval" value="' + form.id + '" id="af-eval-' + index + '"' + checked + '>' +
              '<span>' + esc(form.name) + '</span></label>';
          }).join('') + '</div>'
        : '<div class="form-helper">ยังไม่มีชุดแบบประเมินในระบบ</div>') +
      '</div>' +
      '<div class="form-row mb-0">' +
      '<div class="form-group mb-0"><label class="form-label" for="af-checkin-start">เปิดให้ Check-in / ทำแบบประเมิน ตั้งแต่</label>' +
      '<input type="datetime-local" class="input" id="af-checkin-start" value="' + esc(activity.checkinStart || '') + '"></div>' +
      '<div class="form-group mb-0"><label class="form-label" for="af-checkin-end">ถึง</label>' +
      '<input type="datetime-local" class="input" id="af-checkin-end" value="' + esc(activity.checkinEnd || '') + '"></div>' +
      '</div>' +
      
      '</div>' +

      /* Section 4.4 — กำหนดการเผยแพร่ */
      '<div class="card mb-5">' +
      '<h2 class="card-title">กำหนดการเผยแพร่</h2>' +
      '<label class="switch mb-4">' +
      '<input type="checkbox" id="af-publish"' + (activity.isPublished ? ' checked' : '') + '>' +
      '<span class="switch-track"></span><span>เผยแพร่กิจกรรมบนหน้าเว็บ</span></label>' +

      '<div id="af-publish-fields" class="' + (activity.isPublished ? '' : 'hidden') + '">' +
      '<div class="form-row">' +
      '<div class="form-group"><label class="form-label" for="af-publish-start">วัน/เวลาที่เริ่มเผยแพร่</label>' +
      '<input type="datetime-local" class="input" id="af-publish-start" value="' + esc(activity.publishStart || '') + '"></div>' +
      '<div class="form-group"><label class="form-label" for="af-publish-end">วัน/เวลาที่สิ้นสุดการเผยแพร่</label>' +
      '<input type="datetime-local" class="input" id="af-publish-end" value="' + esc(activity.publishEnd || '') + '"></div>' +
      '</div>' +
      '<div class="form-group">' +
      '<label class="form-label" for="af-visibility">ระดับการมองเห็น</label>' +
      '<select class="select" id="af-visibility">' + selectOptions(mock.activityVisibilityLevels || [], activity.visibility) + '</select>' +
      '</div>' +
      '<label class="checkbox-item mb-0">' +
      '<input type="checkbox" id="af-featured"' + (activity.isFeatured ? ' checked' : '') + '>' +
      '<span>แสดงในหน้าเว็บรายการแนะนำ</span></label>' +
      '</div>' +
      '</div>' +

      '</div>' +

      /* คอลัมน์ขวา — ตัวอย่างบนมือถือ */
      '<aside class="form-split-side">' +
      '<div class="phone-frame">' +
      '<div class="phone-notch"></div>' +
      '<div class="phone-screen">' +
      '<div class="preview-cover" id="af-preview-cover">ยังไม่ได้อัปโหลดรูปภาพกิจกรรม</div>' +
      '<div class="preview-body" id="af-preview-body"></div>' +
      '</div></div>' +
      '<p class="preview-caption">ตัวอย่างหน้ารายละเอียดกิจกรรมที่ผู้เข้าร่วมจะเห็น</p>' +
      '</aside>' +
      '</div>' +

      '<div class="alert alert-danger hidden" id="af-validation">' +
      '<span class="alert-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg></span>' +
      '<div><div class="alert-title">กรุณาตรวจสอบข้อมูลก่อนบันทึก</div><ul id="af-validation-list" class="alert-list"></ul></div>' +
      '</div>' +

      '<div class="form-action-bar">' +
      '<button type="button" class="btn btn-outline" data-go-back>ยกเลิก</button>' +
      '<button type="button" class="btn btn-secondary" id="af-save-draft">บันทึกร่าง</button>' +
      '<button type="submit" class="btn btn-primary" id="af-save-publish">บันทึกและเผยแพร่</button>' +
      '</div>' +
      '</form>';

    /* ---------- 2. รอบกิจกรรม (dynamic rows สูงสุด 5) ---------- */
    var addBtn = document.getElementById('af-add-schedule');
    var scheduleList = window.TFC.dynamicRowList('af-schedule-list', null, scheduleRowHtml);

    function syncAddButton() {
      var count = scheduleList.container.querySelectorAll('.schedule-row').length;
      addBtn.disabled = count >= MAX_SCHEDULES;
    }

    addBtn.addEventListener('click', function () {
      if (scheduleList.container.querySelectorAll('.schedule-row').length >= MAX_SCHEDULES) return;
      scheduleList.addRow({});
      syncAddButton();
      renderPreview();
    });

    scheduleList.container.addEventListener('click', function (e) {
      if (!e.target.closest('.dynamic-row-remove')) return;
      /* dynamic-row.js ลบแถวให้แล้ว — ที่นี่แค่ sync ปุ่มเพิ่มกับ preview */
      setTimeout(function () { syncAddButton(); renderPreview(); }, 0);
    });

    scheduleList.reset(schedules.length ? schedules : [{}]);
    syncAddButton();

    function readSchedules() {
      return Array.prototype.map.call(scheduleList.container.querySelectorAll('.schedule-row'), function (row) {
        var read = function (field) { return row.querySelector('[data-field="' + field + '"]').value; };
        return { date: read('date'), timeStart: read('timeStart'), timeEnd: read('timeEnd'), capacity: read('capacity') };
      });
    }

    /* ---------- 3. อ่านค่าทั้งฟอร์ม ---------- */
    function value(id) { return (document.getElementById(id) || {}).value || ''; }
    function checked(id) { return !!(document.getElementById(id) || {}).checked; }

    function readForm() {
      var typeInput = document.querySelector('input[name="af-type"]:checked');
      return {
        name: value('af-name'),
        type: typeInput ? typeInput.value : '',
        participantType: value('af-participant-type'),
        format: value('af-format'),
        course: value('af-course'),
        targetGroups: checkedValues('af-target'),
        areaList: checkedValues('af-area'),
        instructorList: checkedValues('af-instructor'),
        hasFee: checked('af-has-fee'),
        fee: Number(value('af-fee') || 0),
        description: value('af-desc'),
        schedules: readSchedules(),
        evaluationFormIds: checkedValues('af-eval'),
        checkinStart: value('af-checkin-start'),
        checkinEnd: value('af-checkin-end'),
        isPublished: checked('af-publish'),
        publishStart: value('af-publish-start'),
        publishEnd: value('af-publish-end'),
        visibility: value('af-visibility'),
        isFeatured: checked('af-featured')
      };
    }

    /* ---------- 4. Mobile preview (re-render ทุกครั้งที่ข้อมูลเปลี่ยน) ---------- */
    var previewBody = document.getElementById('af-preview-body');
    var previewCover = document.getElementById('af-preview-cover');

    function metaRow(icon, text) {
      return '<div class="preview-meta">' + icon + '<span>' + esc(text) + '</span></div>';
    }

    function renderPreview() {
      var data = readForm();
      var rounds = data.schedules.filter(function (row) { return row.date; });
      var dateText = rounds.length
        ? window.TFC.formatThaiDate(rounds[0].date) + (rounds.length > 1 ? ' และอีก ' + (rounds.length - 1) + ' รอบ' : '')
        : 'ยังไม่ระบุวันที่';
      var timeText = rounds.length && rounds[0].timeStart
        ? rounds[0].timeStart + ' - ' + (rounds[0].timeEnd || '?') + ' น.'
        : 'ยังไม่ระบุเวลา';
      var capacity = rounds.reduce(function (sum, row) { return sum + Number(row.capacity || 0); }, 0);

      previewBody.innerHTML =
        '<div class="tag-list">' +
        (data.type ? '<span class="tag tag-primary">' + esc(data.type) + '</span>' : '') +
        (data.format ? '<span class="tag">' + esc(data.format) + '</span>' : '') +
        '</div>' +
        '<div class="preview-title">' + esc(data.name || 'ชื่อกิจกรรม') + '</div>' +
        metaRow(ICON.calendar, dateText) +
        metaRow(ICON.clock, timeText) +
        metaRow(ICON.pin, data.areaList.join(', ') || 'ยังไม่ระบุพื้นที่') +
        (data.instructorList.length ? metaRow(ICON.user, data.instructorList.join(', ')) : '') +
        (capacity ? metaRow(ICON.users, 'รับสมัคร ' + capacity + ' คน') : '') +
        '<div class="preview-divider"></div>' +
        '<div class="preview-price">' + (data.hasFee && data.fee ? Number(data.fee).toLocaleString('th-TH') + ' บาท' : 'เข้าร่วมฟรี') + '</div>' +
        (data.description ? '<div class="preview-description">' + esc(data.description) + '</div>' : '') +
        (data.targetGroups.length
          ? '<div class="tag-list">' + data.targetGroups.map(function (group) {
              return '<span class="tag">' + esc(group) + '</span>';
            }).join('') + '</div>'
          : '') +
        '<button type="button" class="btn btn-primary btn-block" disabled>ลงทะเบียนเข้าร่วม</button>';

      document.getElementById('af-name-count').textContent = data.name.length;
      document.getElementById('af-desc-count').textContent = data.description.length;
    }

    /* ---------- 5. Upload รูปปก + preview ---------- */
    var coverInput = document.getElementById('af-cover');
    var coverZone = document.getElementById('af-cover-zone');
    var coverList = document.getElementById('af-cover-list');

    function showCover(file) {
      if (!file) return;
      if (file.size > 5 * 1024 * 1024) {
        if (window.TFC.showToast) window.TFC.showToast('ไฟล์ใหญ่เกิน 5MB กรุณาเลือกไฟล์ใหม่', 'danger');
        return;
      }
      var reader = new FileReader();
      reader.onload = function (e) {
        previewCover.innerHTML = '<img src="' + e.target.result + '" alt="ภาพปกกิจกรรม">';
      };
      reader.readAsDataURL(file);

      coverList.innerHTML = '';
      var item = document.createElement('div');
      item.className = 'upload-file-item';
      item.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
        '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>' +
        '<span class="file-name"></span>' +
        '<button type="button" class="upload-file-remove" aria-label="ลบไฟล์">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>';
      item.querySelector('.file-name').textContent = file.name;
      item.querySelector('.upload-file-remove').addEventListener('click', function (e) {
        e.stopPropagation();
        coverInput.value = '';
        coverList.innerHTML = '';
        previewCover.textContent = 'ยังไม่ได้อัปโหลดรูปภาพกิจกรรม';
      });
      coverList.appendChild(item);
    }

    coverZone.addEventListener('click', function () { coverInput.click(); });
    coverInput.addEventListener('click', function (e) { e.stopPropagation(); });
    coverInput.addEventListener('change', function () { showCover(coverInput.files[0]); });
    ['dragover', 'dragenter'].forEach(function (evt) {
      coverZone.addEventListener(evt, function (e) { e.preventDefault(); coverZone.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
      coverZone.addEventListener(evt, function () { coverZone.classList.remove('is-dragover'); });
    });
    coverZone.addEventListener('drop', function (e) {
      e.preventDefault();
      if (e.dataTransfer.files.length) {
        coverInput.files = e.dataTransfer.files;
        showCover(coverInput.files[0]);
      }
    });

    if (isEdit && activity.coverImage) {
      previewCover.innerHTML = '<img src="' + esc(activity.coverImage) + '" alt="ภาพปกกิจกรรม">';
    }

    /* ---------- 6. Toggle ค่าสมัคร / การเผยแพร่ ---------- */
    document.getElementById('af-has-fee').addEventListener('change', function (e) {
      document.getElementById('af-fee-wrap').classList.toggle('hidden', !e.target.checked);
      renderPreview();
    });

    document.getElementById('af-publish').addEventListener('change', function (e) {
      document.getElementById('af-publish-fields').classList.toggle('hidden', !e.target.checked);
    });

    /* ---------- 7. Validation ---------- */
    function markInvalid(id, invalid) {
      var el = document.getElementById(id);
      if (el) el.classList.toggle('is-invalid', invalid);
    }

    function validate(forPublish) {
      var data = readForm();
      var errors = [];

      if (!data.name.trim()) errors.push('กรุณากรอกชื่อกิจกรรม');
      markInvalid('af-name', !data.name.trim());

      if (!forPublish) return { errors: errors, data: data };

      if (!coverInput.files.length && !activity.coverImage) errors.push('กรุณาอัปโหลดรูปภาพกิจกรรม');
      if (!data.format) errors.push('กรุณาเลือกรูปแบบกิจกรรม');
      markInvalid('af-format', !data.format);
      if (!data.targetGroups.length) errors.push('กรุณาเลือกกลุ่มเป้าหมายอย่างน้อย 1 กลุ่ม');
      if (!data.areaList.length) errors.push('กรุณาเลือกพื้นที่ดำเนินงานอย่างน้อย 1 พื้นที่');
      if (!data.description.trim()) errors.push('กรุณากรอกรายละเอียด (แบบย่อ)');
      markInvalid('af-desc', !data.description.trim());
      if (data.hasFee && !(data.fee > 0)) errors.push('ติ๊ก "มีค่าสมัคร" แล้ว กรุณาระบุค่าลงทะเบียนมากกว่า 0 บาท');

      var rounds = data.schedules;
      var filled = rounds.filter(function (row) { return row.date || row.timeStart || row.timeEnd || row.capacity; });
      if (!filled.length) {
        errors.push('กรุณาระบุรอบกิจกรรมอย่างน้อย 1 รอบ');
      }
      filled.forEach(function (row, index) {
        var label = 'รอบที่ ' + (index + 1);
        if (!row.date) errors.push(label + ': กรุณาระบุวันที่จัดกิจกรรม');
        if (!row.timeStart || !row.timeEnd) errors.push(label + ': กรุณาระบุเวลาเริ่มและเวลาสิ้นสุด');
        if (row.timeStart && row.timeEnd && row.timeStart >= row.timeEnd) errors.push(label + ': เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด');
        if (!(Number(row.capacity) > 0)) errors.push(label + ': จำนวนรับสมัครต้องมากกว่า 0');
      });

      /* รอบที่วันเดียวกันห้ามเวลาทับซ้อน */
      filled.forEach(function (row, i) {
        filled.slice(i + 1).forEach(function (other, j) {
          if (!row.date || row.date !== other.date) return;
          if (row.timeStart < other.timeEnd && other.timeStart < row.timeEnd) {
            errors.push('รอบที่ ' + (i + 1) + ' และรอบที่ ' + (i + j + 2) + ': วันและเวลาทับซ้อนกัน');
          }
        });
      });

      if (data.checkinStart && data.checkinEnd && data.checkinStart >= data.checkinEnd) {
        errors.push('ช่วงเวลา Check-in / ทำแบบประเมิน: เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด');
      }

      if (data.isPublished) {
        if (!data.publishStart) errors.push('กรุณาระบุวัน/เวลาที่เริ่มเผยแพร่');
        if (data.publishStart && data.publishEnd && data.publishStart >= data.publishEnd) {
          errors.push('วัน/เวลาสิ้นสุดการเผยแพร่ต้องมากกว่าวันที่เริ่มเผยแพร่');
        }
      }

      return { errors: errors, data: data };
    }

    var validationBox = document.getElementById('af-validation');
    var validationList = document.getElementById('af-validation-list');

    function showErrors(errors) {
      if (!errors.length) {
        validationBox.classList.add('hidden');
        return;
      }
      validationList.innerHTML = errors.map(function (message) {
        return '<li>' + esc(message) + '</li>';
      }).join('');
      validationBox.classList.remove('hidden');
      validationBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /* ---------- 8. บันทึก (mock: ยังไม่เชื่อม Backend) ---------- */
    function save(button, forPublish, successMessage) {
      var result = validate(forPublish);
      showErrors(result.errors);
      if (result.errors.length) return;

      button.classList.add('btn-loading');
      button.disabled = true;
      setTimeout(function () {
        button.classList.remove('btn-loading');
        button.disabled = false;
        if (window.TFC.showToast) window.TFC.showToast(successMessage, 'success');
        window.location.href = 'list.html';
      }, 800);
    }

    document.getElementById('af-save-draft').addEventListener('click', function () {
      save(this, false, isEdit ? 'บันทึกฉบับร่างเรียบร้อย' : 'สร้างกิจกรรมเป็นฉบับร่างเรียบร้อย');
    });

    document.getElementById('af-form').addEventListener('submit', function (e) {
      e.preventDefault();
      save(document.getElementById('af-save-publish'), true, isEdit ? 'บันทึกและเผยแพร่กิจกรรมเรียบร้อย' : 'สร้างและเผยแพร่กิจกรรมเรียบร้อย');
    });

    /* ---------- 9. Sync preview แบบ real-time ---------- */
    mount.addEventListener('input', renderPreview);
    mount.addEventListener('change', renderPreview);
    renderPreview();
  };
})();
