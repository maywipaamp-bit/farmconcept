@extends('layouts.admin')

@section('title', 'วิทยากร')

{{-- ตารางยืดเต็มจอ แถบแบ่งหน้าจึงติดขอบล่างเสมอ ข้อมูลล้นก็เลื่อนเฉพาะส่วนแถว --}}
@section('main-class', 'is-fill')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">วิทยากร</span>
  </nav>
  <div class="page-header" id="instructor-page-header"></div>

  {{-- โครงเดียวกับหน้ารายการกิจกรรม: pill สถานะซ้าย · ช่องค้นหา + ปุ่มตัวกรองขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="instructor-counts"></div>
    <div class="list-filter-tools">
      {{-- ค้นหาพิมพ์แล้วกรองเลย ไม่ต้องกดปุ่ม จึงไม่มีปุ่มค้นหาข้างช่อง --}}
      <input type="search" class="input list-search-input" id="instructor-search"
             placeholder="ค้นหาชื่อวิทยากร" aria-label="ค้นหาวิทยากร">
      <div id="instructor-search-popover"></div>
    </div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled is-dense">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อวิทยากร</th>
            <th>ความเชี่ยวชาญ</th>
            <th>หลักสูตรที่สอน</th>
            <th class="cell-count">จำนวนจัดกิจกรรม</th>
            <th class="cell-center">สถานะ</th>
            <th class="col-updated cell-center">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="instructor-table-body"></tbody>
        {{-- แถบท้ายตารางเป็นแถวจริงในตาราง ผลรวมจึงตรงคอลัมน์ได้ --}}
        <tfoot><tr id="instructor-table-foot"></tr></tfoot>
      </table>
    </div>
  </div>
@endsection

@section('modals')
<div class="modal-overlay" id="instructor-create-modal">
  <div class="modal modal-lg instructor-profile-modal">
    <div class="modal-header">
      <h3 class="modal-title" id="instr-form-title">เพิ่มวิทยากร</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- ต้องมี data-tabs ไม่งั้น navigation.js ไม่ผูกการสลับแท็บให้ และคลาสต้องเป็น modal-tabs
         ซึ่งเป็นคลาสที่ CSS ของโมดัลรู้จัก --}}
    <div class="modal-tabs instructor-history-tabs" data-tabs>
      <button type="button" class="tab-item is-active" data-tab-target="instr-tab-info">ข้อมูลวิทยากร</button>
      <button type="button" class="tab-item" data-tab-target="instr-tab-history">ประวัติการเป็นวิทยากร</button>
    </div>

    <form id="instr-form">
      <div class="modal-body" data-tab-panel="instr-tab-info">
        <div class="instructor-photo-section">
          <div class="photo-slot instructor-photo-slot" id="instr-photo-slot" tabindex="0" role="button" aria-label="เลือกรูปภาพวิทยากร">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>เพิ่มรูป</span>
            <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" id="instr-photo">
          </div>
          <button type="button" class="instructor-photo-action" id="instr-photo-action">เปลี่ยนรูปภาพ</button>
        </div>

        <div class="instructor-profile-fields">
          <div class="instructor-profile-field">
            <div class="instructor-profile-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <label for="instr-name">ชื่อวิทยากร<span class="form-required">*</span></label>
            </div>
            <input class="input" id="instr-name" data-validate required maxlength="150" autocomplete="off" placeholder="เช่น นายสมชาย ใจดี">
          </div>

          <div class="instructor-profile-field">
            <div class="instructor-profile-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.36 1.9.69 2.8a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0122 16.92z"/></svg>
              <label for="instr-phone">เบอร์โทร<span class="form-required">*</span></label>
            </div>
            <input class="input" type="tel" id="instr-phone" data-validate required maxlength="30" placeholder="เช่น 081-234-5678">
          </div>

          <div class="instructor-profile-field is-align-start">
            <div class="instructor-profile-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M9 18h6M10 22h4"/><path d="M8.5 14.5A7 7 0 1115.5 14.5c-.9.7-1.5 1.5-1.5 2.5h-4c0-1-.6-1.8-1.5-2.5z"/></svg>
              <span>ความเชี่ยวชาญ</span>
            </div>
            <div class="instructor-profile-control">
              <input type="hidden" id="instr-expertise" data-tags-input
                     data-tags-placeholder="พิมพ์ความเชี่ยวชาญแล้วกด Enter"
                     data-tags-options="{{ $expertiseOptions->toJson(JSON_UNESCAPED_UNICODE) }}">
            </div>
          </div>

          <div class="instructor-profile-field is-align-start">
            <div class="instructor-profile-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
              <span>หลักสูตรที่สอนได้</span>
            </div>
            <div class="instructor-profile-control">
              <div class="instructor-course-picker" id="instr-course-picker">
                <button type="button" class="multiselect instructor-course-trigger" id="instr-course-trigger"
                        aria-haspopup="listbox" aria-expanded="false">
                  <span class="instructor-course-placeholder">เลือกหลักสูตรที่สอนได้</span>
                  <svg class="instructor-course-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="instructor-course-panel" id="instr-course-panel" role="listbox" aria-multiselectable="true"></div>
              </div>
            </div>
          </div>

          <div class="instructor-profile-field is-align-start">
            <div class="instructor-profile-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20.59 13.41L11 3.83V3H4v7h.83l9.58 9.59a2 2 0 002.82 0l3.36-3.36a2 2 0 000-2.82z"/><circle cx="7.5" cy="6.5" r="1"/></svg>
              <label for="instr-search-tags">คำค้นหา (Tag)</label>
            </div>
            <input type="hidden" id="instr-search-tags" data-tags-input data-tags-placeholder="พิมพ์คำค้นหาแล้วกด Enter">
          </div>

          <div class="instructor-profile-field is-align-start">
            <div class="instructor-profile-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
              <label for="instr-bio">รายละเอียด</label>
            </div>
            <textarea class="textarea" id="instr-bio" rows="4" maxlength="1000" placeholder="รายละเอียดและประสบการณ์ของวิทยากร"></textarea>
          </div>

          <div class="instructor-profile-field">
            <div class="instructor-profile-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <label for="instr-active">สถานะ<span class="form-required">*</span></label>
            </div>
            <div class="flex items-center gap-2">
              <label class="switch"><input type="checkbox" id="instr-active" checked><span class="switch-track"></span></label>
              <span class="small text-secondary" id="instr-active-label">ใช้งาน</span>
            </div>
          </div>
        </div>
      </div>

      {{-- ประวัติมาจากกิจกรรมจริงในฐาน ไม่ใช่ข้อมูลที่กรอกเอง จึงเป็นอ่านอย่างเดียว --}}
      <div class="modal-body hidden" data-tab-panel="instr-tab-history">
        <div id="instr-history"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="instr-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="instructor-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบวิทยากร</h3>
      <p class="text-secondary" id="instr-delete-message"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button class="btn btn-danger" id="instr-delete-confirm">ลบวิทยากร</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
<script src="@assetv('assets/js/master-list.js')"></script>
<script src="@assetv('assets/js/dynamic-row.js')"></script>
<script src="@assetv('assets/js/field-widgets.js')"></script>
<script>
window.TFC_API = window.TFC_API || {};
window.TFC_API.instructors = @json(route('admin.master.instructors.index'));

/* แถวชุดแรกฝังมากับหน้า หน้าจอจึงวาดตารางได้ทันทีโดยไม่ต้องรอคำขอเพิ่ม
   หลังบันทึกหรือลบ dataService จะไปเอาของจริงจากเซิร์ฟเวอร์เองตามปกติ */
window.TFC_SEED = window.TFC_SEED || {};
window.TFC_SEED.instructors = @json($seedRows);

/* หลักสูตรที่เลือกได้มาจากฐานข้อมูลจริง ไม่ใช่รายการที่เขียนไว้ใน mock-data.js */
window.TFC_INSTRUCTOR = {
  programs: @json($programs),
  expertiseOptions: @json($expertiseOptions),
  photoUrlTemplate: @json(route('admin.master.instructors.photo.store', '__CODE__')),
  photoMaxBytes: @json($photoMaxBytes)
};
</script>
@endpush

@push('page-script')
<script>
(function () {
  /* จำนวนแถวคิดจากพื้นที่ที่เหลือจริงบนจอ ไม่ใช่เลข 10 ตายตัว
     statusKey = สถานะที่เลือกจากแถบนับจำนวน ('' = ทั้งหมด) */
  var pageState = { page: 1, pageSize: 10, statusKey: '' };
  var svc = window.TFC.dataService('instructors');
  var mock = window.TFC_MOCK || {};
  var CFG = window.TFC_INSTRUCTOR;
  var rows = [];

  function $(id) { return document.getElementById(id); }
  function rowOf(code) { return rows.filter(function (r) { return r.id === code; })[0]; }

  /* ปิดประวัติ Autofill ของเบราว์เซอร์เฉพาะช่องนี้
     รายการที่ยังแสดงอยู่จึงมาจาก data-tags-options ในฐานข้อมูลวิทยากรเท่านั้น */
  var expertiseTagField = document.querySelector('#instr-expertise + .tags-input .tags-input-field');
  if (expertiseTagField) {
    expertiseTagField.name = 'instructor_expertise_tags';
    expertiseTagField.autocomplete = 'new-password';
    expertiseTagField.setAttribute('autocorrect', 'off');
    expertiseTagField.setAttribute('autocapitalize', 'off');
    expertiseTagField.spellcheck = false;
  }

  /* ---------- หลักสูตรที่สอนได้: Multi-select แบ่งกลุ่มตามโปรแกรม ---------- */
  var selectedCourses = [];
  var coursePicker = $('instr-course-picker');
  var courseTrigger = $('instr-course-trigger');
  var coursePanel = $('instr-course-panel');

  function renderCoursePicker() {
    var placeholder = '<span class="instructor-course-placeholder">เลือกหลักสูตรที่สอนได้</span>';
    var chips = selectedCourses.map(function (name) {
      return '<span class="multiselect-tag"><span>' + window.TFC.escapeHtml(name) + '</span>' +
        '<button type="button" data-remove-course="' + window.TFC.escapeHtml(name) + '" aria-label="ลบ ' + window.TFC.escapeHtml(name) + '">' +
        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
        '</button></span>';
    }).join('');

    courseTrigger.innerHTML = (chips || placeholder) +
      '<svg class="instructor-course-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';

    coursePanel.innerHTML = '<div class="instructor-course-search-wrap">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>' +
      '<input type="search" class="instructor-course-search" id="instr-course-search" placeholder="ค้นหาหลักสูตร" autocomplete="off">' +
      '</div><div class="instructor-course-results">' + CFG.programs.map(function (program) {
      if (!program.courses.length) return '';
      return '<div class="instructor-course-group" data-program-name="' + window.TFC.escapeHtml(program.name.toLowerCase()) + '">' +
        '<div class="instructor-course-group-title">' + window.TFC.escapeHtml(program.name) + '</div>' +
        program.courses.map(function (name) {
          var checked = selectedCourses.indexOf(name) !== -1;
          return '<label class="instructor-course-option' + (checked ? ' is-selected' : '') + '" data-course-name="' + window.TFC.escapeHtml(name.toLowerCase()) + '">' +
            '<input type="checkbox" value="' + window.TFC.escapeHtml(name) + '"' + (checked ? ' checked' : '') + '>' +
            '<span class="instructor-course-check"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>' +
            '<span>' + window.TFC.escapeHtml(name) + '</span></label>';
        }).join('') + '</div>';
    }).join('') + '<p class="instructor-course-empty hidden">ไม่พบหลักสูตรที่ค้นหา</p></div>';
  }

  function setCourses(values) {
    selectedCourses = Array.from(new Set(values || []));
    renderCoursePicker();
  }

  function setCoursePanel(open) {
    coursePicker.classList.toggle('is-open', open);
    coursePanel.classList.toggle('is-open', open);
    courseTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      /* ย้าย Dropdown ไปไว้ใต้ body ชั่วคราว เพื่อไม่ให้ overflow/stacking ของ Popup
         ตัดกล่องหรือวางทับจนตัวเลือกคลิกไม่ได้ */
      document.body.appendChild(coursePanel);
      positionCoursePanel();
    }
  }

  function positionCoursePanel() {
    var rect = courseTrigger.getBoundingClientRect();
    var gap = 2;
    var maxHeight = 240;
    var roomBelow = window.innerHeight - rect.bottom - 12;
    var roomAbove = rect.top - 12;
    var openAbove = roomBelow < 180 && roomAbove > roomBelow;
    var available = Math.max(120, Math.min(maxHeight, openAbove ? roomAbove - gap : roomBelow - gap));

    coursePanel.style.left = rect.left + 'px';
    coursePanel.style.width = rect.width + 'px';
    coursePanel.style.maxHeight = available + 'px';
    coursePanel.style.top = openAbove ? Math.max(12, rect.top - available - gap) + 'px' : (rect.bottom + gap) + 'px';
  }

  courseTrigger.addEventListener('click', function (e) {
    var remove = e.target.closest('[data-remove-course]');
    if (remove) {
      e.stopPropagation();
      setCourses(selectedCourses.filter(function (name) { return name !== remove.getAttribute('data-remove-course'); }));
      return;
    }
    setCoursePanel(!coursePicker.classList.contains('is-open'));
  });

  coursePanel.addEventListener('change', function (e) {
    if (!e.target.matches('input[type="checkbox"]')) return;
    var name = e.target.value;
    if (e.target.checked) setCourses(selectedCourses.concat(name));
    else setCourses(selectedCourses.filter(function (item) { return item !== name; }));
  });

  coursePanel.addEventListener('input', function (e) {
    if (!e.target.matches('.instructor-course-search')) return;
    var query = e.target.value.trim().toLowerCase();
    var visibleCount = 0;

    coursePanel.querySelectorAll('.instructor-course-group').forEach(function (group) {
      var programMatches = (group.getAttribute('data-program-name') || '').indexOf(query) !== -1;
      var visibleInGroup = 0;
      group.querySelectorAll('.instructor-course-option').forEach(function (option) {
        var visible = !query || programMatches || (option.getAttribute('data-course-name') || '').indexOf(query) !== -1;
        option.classList.toggle('hidden', !visible);
        if (visible) visibleInGroup += 1;
      });
      group.classList.toggle('hidden', visibleInGroup === 0);
      visibleCount += visibleInGroup;
    });

    var empty = coursePanel.querySelector('.instructor-course-empty');
    if (empty) empty.classList.toggle('hidden', visibleCount > 0);
  });

  document.addEventListener('click', function (e) {
    if (!coursePicker.contains(e.target) && !coursePanel.contains(e.target)) setCoursePanel(false);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setCoursePanel(false);
  });

  window.addEventListener('resize', function () {
    if (coursePicker.classList.contains('is-open')) positionCoursePanel();
  });

  setCourses([]);

  /* ---------- รูปวิทยากร ----------
     อัปทันทีที่เลือกไฟล์เฉพาะตอนแก้ไข (มีรหัสแล้ว) ตอนเพิ่มใหม่ต้องพักไฟล์ไว้ก่อน
     แล้วส่งตามทันทีที่บันทึกครั้งแรกสำเร็จ — แบบเดียวกับรูปปกกิจกรรม */
  var photoSlot = $('instr-photo-slot');
  var photoInput = $('instr-photo');
  var pendingPhoto = null;

  function showPhoto(src) {
    var img = photoSlot.querySelector('img');
    if (src) {
      if (!img) {
        img = document.createElement('img');
        img.alt = 'รูปวิทยากร';
        photoSlot.insertBefore(img, photoSlot.firstChild);
      }
      img.src = src;
      photoSlot.classList.add('has-photo');
    } else if (img) {
      img.remove();
      photoSlot.classList.remove('has-photo');
    } else {
      photoSlot.classList.remove('has-photo');
    }
  }

  photoSlot.addEventListener('click', function () { photoInput.click(); });
  $('instr-photo-action').addEventListener('click', function () { photoInput.click(); });
  photoSlot.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      photoInput.click();
    }
  });
  photoInput.addEventListener('click', function (e) { e.stopPropagation(); });

  photoInput.addEventListener('change', function () {
    var file = photoInput.files && photoInput.files[0];
    if (!file) return;

    /* PHP ตัดไฟล์ที่ใหญ่เกินเพดานทิ้งก่อนถึง Laravel ฝั่งเซิร์ฟเวอร์จึงเห็นเป็น
       "ไม่ได้แนบไฟล์มา" แล้วฟ้องผิดเรื่อง — บอกให้ชัดตั้งแต่ตรงนี้ */
    if (file.size > CFG.photoMaxBytes) {
      photoInput.value = '';
      window.TFC.showToast('รูปใหญ่เกินไป (' + (file.size / 1048576).toFixed(1) + ' MB) เครื่องนี้รับได้ไม่เกิน ' +
        (CFG.photoMaxBytes / 1048576).toFixed(1) + ' MB', 'danger');
      return;
    }

    pendingPhoto = file;
    window.TFC.readImageFile(file, { maxMB: Math.ceil(CFG.photoMaxBytes / 1048576) || 5 }, showPhoto);
  });

  function uploadPendingPhoto(code) {
    if (!pendingPhoto) return Promise.resolve();

    var data = new FormData();
    data.append('photo', pendingPhoto);

    return fetch(CFG.photoUrlTemplate.replace('__CODE__', code), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      body: data
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (d) {
        if (!res.ok) {
          var msg = d.errors
            ? Object.keys(d.errors).map(function (k) { return d.errors[k][0]; }).join(' · ')
            : (d.message || 'อัปโหลดรูปไม่สำเร็จ');
          throw new Error(msg);
        }
        return d;
      });
    }).finally(function () {
      pendingPhoto = null;
      photoInput.value = '';
    });
  }

  /* ---------- ประวัติการเป็นวิทยากร ---------- */

  function renderHistory(instructor) {
    var box = $('instr-history');
    if (!box) return;

    if (!instructor) {
      box.innerHTML = '<div class="empty-state"><p class="text-secondary">บันทึกวิทยากรก่อน ระบบจึงจะเริ่มเก็บประวัติการเป็นวิทยากรให้อัตโนมัติ</p></div>';
      return;
    }

    var history = instructor.history || [];

    if (!history.length) {
      box.innerHTML = '<div class="empty-state"><p class="text-secondary">ยังไม่มีประวัติการเป็นวิทยากรในระบบ</p></div>';
      return;
    }

    var totalJoined = history.reduce(function (s, a) { return s + (a.registered || 0); }, 0);

    box.innerHTML =
      '<p class="page-description mb-3">รวม ' + history.length + ' กิจกรรม · ผู้เข้าร่วมสะสม ' + totalJoined.toLocaleString('th-TH') + ' คน</p>' +
      '<div class="table-wrapper"><div class="table-scroll"><table class="data-table is-header-filled is-dense">' +
      '<thead><tr><th class="col-no">#</th><th>ชื่อกิจกรรม</th><th>วันที่</th><th>ผู้เข้าร่วม</th><th>สถานะ</th></tr></thead><tbody>' +
      history.map(function (a, i) {
        return '<tr>' +
          '<td class="col-no">' + (i + 1) + '</td>' +
          '<td>' + window.TFC.escapeHtml(a.name) + '</td>' +
          '<td class="nowrap">' + (a.startDate ? window.TFC.formatThaiDate(a.startDate) : '-') + '</td>' +
          '<td>' + (a.registered != null ? a.registered : '-') + '</td>' +
          '<td class="nowrap">' + window.TFC.statusTextHTML({ options: mock.activityStatuses, value: a.status }) + '</td>' +
          '</tr>';
      }).join('') +
      '</tbody></table></div></div>';
  }

  /* ---------- หัวหน้าและฟอร์ม ---------- */

  window.TFC.renderPageHeader('instructor-page-header', {
    title: 'วิทยากร',
    actions: [
      {
        label: 'เพิ่มวิทยากร',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'instr-add-btn', 'data-open-modal': 'instructor-create-modal' }
      }
    ]
  });

  $('instr-active').addEventListener('change', function () {
    $('instr-active-label').textContent = this.checked ? 'ใช้งาน' : 'ไม่ใช้งาน';
  });

  /* กลับมาที่แท็บแรกเสมอเมื่อเปิด popup ใหม่ */
  function resetTabs() {
    document.querySelectorAll('#instructor-create-modal .tab-item').forEach(function (t, i) {
      t.classList.toggle('is-active', i === 0);
    });
    document.querySelectorAll('#instructor-create-modal [data-tab-panel]').forEach(function (p, i) {
      p.classList.toggle('hidden', i !== 0);
    });
  }

  function resetForm() {
    $('instr-form-title').textContent = 'เพิ่มวิทยากร';
    $('instr-form').reset();
    $('instructor-create-modal').classList.add('is-create-mode');
    $('instr-active').checked = true;
    $('instr-active-label').textContent = 'ใช้งาน';
    pendingPhoto = null;
    showPhoto('');
    window.TFC.setTagsValue('instr-expertise', '');
    setCourses([]);
    window.TFC.setTagsValue('instr-search-tags', '');
    $('instr-form').setAttribute('data-editing-id', '');
    renderHistory(null);
    resetTabs();
  }
  $('instr-add-btn').addEventListener('click', resetForm);

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-key^="instr-edit-"]');
    if (!trigger) return;

    var instructor = rowOf(trigger.getAttribute('data-action-key').replace('instr-edit-', ''));
    if (!instructor) return;

    $('instr-form-title').textContent = 'แก้ไขวิทยากร';
    $('instructor-create-modal').classList.remove('is-create-mode');
    $('instr-name').value = instructor.name || '';
    $('instr-phone').value = instructor.phone || '';
    $('instr-bio').value = instructor.bio || '';
    $('instr-active').checked = instructor.active !== false;
    $('instr-active-label').textContent = instructor.active !== false ? 'ใช้งาน' : 'ไม่ใช้งาน';
    pendingPhoto = null;
    showPhoto(instructor.photo || '');
    window.TFC.setTagsValue('instr-expertise', (instructor.expertiseList || []).join(', '));
    setCourses(instructor.courses || []);
    window.TFC.setTagsValue('instr-search-tags', (instructor.searchTags || []).join(', '));
    $('instr-form').setAttribute('data-editing-id', instructor.id);
    renderHistory(instructor);
    resetTabs();
  });

  $('instr-form').addEventListener('submit', function (e) {
    e.preventDefault();

    var editingId = this.getAttribute('data-editing-id');
    var payload = {
      name: $('instr-name').value.trim(),
      phone: $('instr-phone').value.trim(),
      bio: $('instr-bio').value.trim(),
      active: $('instr-active').checked,
      searchTags: $('instr-search-tags').value.split(',').map(function (tag) { return tag.trim(); }).filter(Boolean),
      expertiseList: $('instr-expertise').value.split(',').map(function (item) { return item.trim(); }).filter(Boolean),
      courses: selectedCourses.slice()
    };

    var submit = $('instr-submit');
    submit.disabled = true;
    submit.textContent = 'กำลังบันทึก…';

    (editingId ? svc.update(editingId, payload) : svc.create(payload))
      .then(function (row) { return uploadPendingPhoto(row.id); })
      .then(function () {
        window.TFC.closeModal('instructor-create-modal');
        window.TFC.showToast(editingId ? 'บันทึกวิทยากรแล้ว' : 'เพิ่มวิทยากรแล้ว', 'success');
        /* แถวที่เพิ่งเพิ่มอยู่บนสุดของหน้าแรก ต้องเด้งกลับหน้าแรกไม่งั้นบันทึกแล้วเหมือนไม่มีอะไรเกิดขึ้น
           การแก้ไขไม่แตะเลขหน้า ผู้ใช้จึงยังอยู่หน้าเดิมที่กำลังไล่ดูอยู่ */
        if (!editingId) pageState.page = 1;
        return renderTable();
      })
      .catch(function (err) { window.TFC.showToast(err.message, 'danger'); })
      .finally(function () {
        submit.disabled = false;
        submit.textContent = 'บันทึก';
      });
  });

  /* ---------- ลบ ---------- */

  var pendingDelete = null;

  document.addEventListener('click', function (e) {
    var item = e.target.closest('[data-action-key^="instr-delete-"]');
    if (!item) return;

    var row = rowOf(item.getAttribute('data-action-key').replace('instr-delete-', ''));
    pendingDelete = row && window.TFC.prepareMasterDelete({
      modalId: 'instructor-delete-modal', messageId: 'instr-delete-message', confirmId: 'instr-delete-confirm',
      name: row.name, usageCount: row.deleteUsageCount,
      confirmMessage: 'ต้องการลบ "' + row.name + '" ใช่หรือไม่ การลบนี้ย้อนกลับไม่ได้'
    }) ? row : null;
  });

  $('instr-delete-confirm').addEventListener('click', function () {
    if (!pendingDelete) return;

    var button = this;
    button.disabled = true;

    svc.remove(pendingDelete.id)
      .then(function () {
        window.TFC.closeModal('instructor-delete-modal');
        window.TFC.showToast('ลบวิทยากรแล้ว', 'success');
        pendingDelete = null;
        return renderTable();
      })
      .catch(function (err) { window.TFC.showToast(err.message, 'danger'); })
      .finally(function () { button.disabled = false; });
  });

  /* ---------- ตาราง ---------- */

  /* ยังไม่มีรูปให้ใช้อักษรตัวแรกของชื่อแทน — วงกลมว่างเปล่าอ่านไม่ออกว่าเป็นใคร */
  function avatarHtml(row) {
    if (row.photo) {
      return '<span class="master-avatar"><img src="' + window.TFC.escapeHtml(row.photo) + '" alt=""></span>';
    }

    var initial = (row.name || '').trim().charAt(0) || '?';
    return '<span class="master-avatar"><span class="master-avatar-initial">' + window.TFC.escapeHtml(initial) + '</span></span>';
  }


  /* แถบนับจำนวนมุมซ้ายบนของตาราง — กดเพื่อกรอง */
  var BUCKETS = [
    { key: '', label: 'ทั้งหมด' },
    { key: 'on', label: 'ใช้งาน', match: function (r) { return r.active !== false; } },
    { key: 'off', label: 'ไม่ใช้งาน', match: function (r) { return r.active === false; } }
  ];

  function matchesStatus(row) {
    if (!pageState.statusKey) return true;

    var bucket = BUCKETS.filter(function (b) { return b.key === pageState.statusKey; })[0];
    return !bucket || !bucket.match || bucket.match(row);
  }
  /* "12 ส.ค. 69 | 08.30" — ย่อ พ.ศ. เหลือ 2 หลักกันบรรทัดตกในคอลัมน์แคบ */
  function updatedStamp(row) {
    if (!row.updatedAt) return '-';

    var date = window.TFC.formatThaiDate(row.updatedAt).replace(/\d{2}(\d{2})$/, '$1');
    return window.TFC.escapeHtml(row.updatedTime ? date + ' | ' + row.updatedTime : date);
  }

  function renderTable() {
    return svc.list().then(function (all) {
      rows = all;

      window.TFC.renderStatusCounts('instructor-counts', rows, {
        active: pageState.statusKey,
        buckets: BUCKETS,
        onPick: function (key) {
          pageState.statusKey = key === pageState.statusKey ? '' : key;
          pageState.page = 1;
          renderTable();
        }
      });

      var keyword = (($('instructor-search') || {}).value || '').trim().toLowerCase();
      var statusFilter = (($('instructor-filter-status') || {}).value || '');

      /* ชิปด้านบนกับตัวกรองในแผงเป็นคนละชั้น ใช้ร่วมกันได้ ต้องผ่านทั้งคู่ */
      var filtered = rows.filter(function (r) {
        var statusLabel = r.active === false ? 'ไม่ใช้งาน' : 'ใช้งาน';

        return matchesStatus(r) &&
          (!keyword || r.name.toLowerCase().indexOf(keyword) !== -1) &&
          (!statusFilter || statusLabel === statusFilter);
      });

      var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
      if (pageState.page > pageCount) pageState.page = pageCount;
      var start = (pageState.page - 1) * pageState.pageSize;
      var pageRows = filtered.slice(start, start + pageState.pageSize);

      $('instructor-table-body').innerHTML = pageRows.map(function (r, i) {
        var expertise = (r.expertiseList || []).length
          ? '<ul class="cell-bullets">' + r.expertiseList.map(function (x) { return '<li>' + window.TFC.escapeHtml(x) + '</li>'; }).join('') + '</ul>'
          : '<span class="text-secondary">-</span>';

        var courses = (r.courses || []).length
          ? '<ul class="cell-bullets">' + r.courses.map(function (x) { return '<li>' + window.TFC.escapeHtml(x) + '</li>'; }).join('') + '</ul>'
          : '<span class="text-secondary">-</span>';

        return '<tr>' +
          '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
          '<td><span class="master-avatar-cell">' + avatarHtml(r) +
          '<span><button type="button" class="cell-title-link font-medium" data-action-key="instr-edit-' + window.TFC.escapeHtml(r.id) + '" data-open-modal="instructor-create-modal">' +
          window.TFC.escapeHtml(r.name) + '</button>' +
          '<div class="cell-updated-at">' + window.TFC.escapeHtml(r.phone || '-') + '</div></span></span></td>' +
          '<td>' + expertise + '</td>' +
          '<td>' + courses + '</td>' +
          '<td class="cell-count">' + Number(r.activityCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td class="nowrap cell-center">' + window.TFC.statusTextHTML({ options: mock.masterActiveStatuses, value: r.active === false ? 'ไม่ใช้งาน' : 'ใช้งาน' }) + '</td>' +
          /* คนแก้บรรทัดบน วันเวลาบรรทัดล่าง — โครงเดียวกับหน้าพื้นที่ดำเนินงาน */
          '<td class="cell-center"><div>' + window.TFC.escapeHtml(r.updatedBy || '-') + '</div>' +
          '<div class="caption text-secondary nowrap">' + updatedStamp(r) + '</div></td>' +
          '<td class="table-row-actions">' +
          window.TFC.actionMenuTrigger([
            { key: 'instr-edit-' + r.id, label: 'แก้ไข', icon: 'edit', modal: 'instructor-create-modal', perm: 'master_data' },
            window.TFC.masterDeleteAction({ key: 'instr-delete-' + r.id, label: 'ลบวิทยากร', modal: 'instructor-delete-modal', perm: 'master_data', usageCount: r.deleteUsageCount })
          ]) +
          '</td></tr>';
      }).join('');

      /* ผลรวมคิดจากรายการที่ผ่านตัวกรองทั้งหมด ไม่ใช่เฉพาะแถวในหน้านี้
         ไม่งั้นตัวเลขจะเปลี่ยนไปมาทุกครั้งที่พลิกหน้า ทั้งที่ข้อมูลชุดเดิม */
      var sum_activityCount = filtered.reduce(function (acc, row) { return acc + Number(row.activityCount || 0); }, 0);
      $('instructor-table-foot').innerHTML =
        '<td colspan="4" id="instructor-foot-info"></td>' +
        '<td class="cell-count">' + sum_activityCount.toLocaleString('th-TH') + '</td>' +
        '<td colspan="3" id="instructor-foot-controls"></td>';

      window.TFC.renderPagination(null, {
        page: pageState.page,
        pageSize: pageState.pageSize,
        total: filtered.length,
        pageSizeOptions: window.TFC.pageSizeOptions(pageState.pageSize),
        infoTarget: 'instructor-foot-info',
        controlsTarget: 'instructor-foot-controls',
        onChange: function (p) { pageState.page = p; renderTable(); },
        onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
      });
    }).catch(function (err) {
      window.TFC.showToast('โหลดข้อมูลไม่สำเร็จ: ' + err.message, 'danger');
    });
  }

  /* ช่องค้นหาย้ายออกมาอยู่นอกแผงแล้ว ปุ่มนี้จึงเหลือแค่ตัวกรอง และเปลี่ยนไอคอนเป็นกรวยให้ตรงกับหน้าที่ */
  window.TFC.searchPopover('instructor-search-popover', {
    search: false,
    icon: 'filter',
    filters: [
      { id: 'status', inputId: 'instructor-filter-status', label: 'สถานะ', placeholder: 'สถานะทั้งหมด',
        options: (mock.masterActiveStatuses || []).map(function (o) { return { label: o.value }; }) }
    ],
    onSearch: function (values, done) {
      pageState.page = 1;
      renderTable();
      done();
    }
  });

  /* ค้นหาแบบพิมพ์แล้วกรองเลย — หน่วง 200ms กันวาดตารางใหม่ทุกตัวอักษร
     ปุ่มกากบาทของ input[type=search] ยิง 'search' ไม่ใช่ 'input' จึงต้องดักทั้งสองอีเวนต์ */
  var searchTimer = null;
  ['input', 'search'].forEach(function (evt) {
    $('instructor-search').addEventListener(evt, function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        pageState.page = 1;
        renderTable();
      }, 200);
    });
  });

  /* ปุ่มส่งออกถูกเอาออกจากแถบเครื่องมือแล้ว ผูก event เฉพาะเมื่อยังมีปุ่มอยู่
     ฟังก์ชัน exportTableCsv ยังอยู่ครบ ถ้าจะเอาปุ่มกลับมาก็เพิ่ม element id เดิมได้ทันที */
  var exportBtn = $('instructor-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#instructor-table-body', 'วิทยากร.csv');
    });
  }

  /* ต้องวัดหลัง DOM ของตารางอยู่ในหน้าแล้ว จึงคำนวณตรงนี้ ไม่ใช่ตอนประกาศ pageState */
  pageState.pageSize = window.TFC.fitPageSize('instructor-table-body', 52);

  renderTable();

  /* ย่อ/ขยายหน้าต่างแล้วจำนวนแถวต้องขยับตาม ไม่ใช่ค้างที่ค่าตอนเปิดหน้า
     หน่วงไว้กันการวาดตารางใหม่ทุกพิกเซลระหว่างลากขอบหน้าต่าง */
  var resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var next = window.TFC.fitPageSize('instructor-table-body', 52);
      if (next === pageState.pageSize) return;
      pageState.pageSize = next;
      pageState.page = 1;
      renderTable();
    }, 200);
  });
})();
</script>
@endpush
