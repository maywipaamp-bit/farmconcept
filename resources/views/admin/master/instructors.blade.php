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
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title" id="instr-form-title">เพิ่มวิทยากร</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- ต้องมี data-tabs ไม่งั้น navigation.js ไม่ผูกการสลับแท็บให้ และคลาสต้องเป็น modal-tabs
         ซึ่งเป็นคลาสที่ CSS ของโมดัลรู้จัก --}}
    <div class="modal-tabs" data-tabs>
      <button type="button" class="tab-item is-active" data-tab-target="instr-tab-info">ข้อมูลวิทยากร</button>
      <button type="button" class="tab-item" data-tab-target="instr-tab-history">ประวัติการเป็นวิทยากร</button>
    </div>

    <form id="instr-form">
      <div class="modal-body" data-tab-panel="instr-tab-info">
        {{-- ภาพติดบัตรขนาดเล็กแบบใบสมัครงาน วางคู่กับชื่อ/เบอร์โทร --}}
        <div class="photo-form-row mb-3">
          <div class="form-group mb-0">
            <label class="form-label">รูปภาพ</label>
            <div class="photo-slot" id="instr-photo-slot" tabindex="0" role="button" aria-label="เลือกรูปภาพวิทยากร">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>เพิ่มรูป</span>
              <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" id="instr-photo">
            </div>
          </div>
          <div>
            <div class="form-group">
              <label class="form-label" for="instr-name">ชื่อวิทยากร<span class="form-required">*</span></label>
              <input class="input" id="instr-name" data-validate required maxlength="150" autocomplete="off">
            </div>
            <div class="form-group mb-0">
              <label class="form-label" for="instr-phone">เบอร์โทร<span class="form-required">*</span></label>
              <input class="input" type="tel" id="instr-phone" data-validate required maxlength="30">
            </div>
          </div>
        </div>

        <div class="form-row mb-3">
          <div class="form-group mb-0">
            <label class="form-label">ความเชี่ยวชาญ</label>
            <div class="dynamic-row-list" id="instr-expertise-list"></div>
            <button type="button" class="dynamic-row-add" id="instr-add-expertise-btn" aria-label="เพิ่มความเชี่ยวชาญ" title="เพิ่มความเชี่ยวชาญ">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            </button>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">หลักสูตรที่สอน</label>
            <div class="dynamic-row-list" id="instr-course-list"></div>
            <button type="button" class="dynamic-row-add" id="instr-add-course-btn" aria-label="เพิ่มหลักสูตร" title="เพิ่มหลักสูตร">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="instr-bio">รายละเอียด</label>
          <textarea class="textarea" id="instr-bio" rows="3" maxlength="1000"></textarea>
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="instr-active">สถานะ<span class="form-required">*</span></label>
          <div class="flex items-center gap-2">
            <label class="switch"><input type="checkbox" id="instr-active" checked><span class="switch-track"></span></label>
            <span class="small text-secondary" id="instr-active-label">ใช้งาน</span>
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

  /* ---------- ความเชี่ยวชาญ ---------- */

  function expertiseRowHtml(value) {
    return '<div class="dynamic-row">' +
      '<span class="dynamic-row-order" data-row-order></span>' +
      '<input class="input" placeholder="เช่น เกษตรอินทรีย์" maxlength="100" value="' + window.TFC.escapeHtml(value) + '">' +
      '<button type="button" class="dynamic-row-remove" aria-label="ลบแถว">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
      '</button></div>';
  }
  var expertiseList = window.TFC.dynamicRowList('instr-expertise-list', 'instr-add-expertise-btn', expertiseRowHtml);
  expertiseList.reset([]);

  /* ---------- หลักสูตรที่สอน ----------
     ไม่ใส่ data-new-item-label เพื่อซ่อนปุ่ม "เพิ่มรายการใหม่" — เลือกได้เฉพาะหลักสูตรที่มีอยู่จริง
     จัดกลุ่มด้วย optgroup ตามโปรแกรม จะได้เห็นว่าหลักสูตรนั้นอยู่ใต้โปรแกรมใด */
  function courseRowHtml(value) {
    return '<div class="dynamic-row">' +
      '<span class="dynamic-row-order" data-row-order></span>' +
      '<select class="select" data-smart-select>' +
      '<option value="">เลือกหลักสูตร</option>' +
      CFG.programs.map(function (p) {
        if (!p.courses.length) return '';
        return '<optgroup label="' + window.TFC.escapeHtml(p.name) + '">' +
          p.courses.map(function (name) {
            return '<option value="' + window.TFC.escapeHtml(name) + '"' + (name === value ? ' selected' : '') + '>' +
              window.TFC.escapeHtml(name) + '</option>';
          }).join('') + '</optgroup>';
      }).join('') +
      '</select>' +
      '<button type="button" class="dynamic-row-remove" aria-label="ลบแถว">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
      '</button></div>';
  }
  var courseList = window.TFC.dynamicRowList('instr-course-list', 'instr-add-course-btn', courseRowHtml, function (row) {
    window.TFC.initSmartSelects(row);
  });
  courseList.reset([]);

  function valuesOf(containerId, selector) {
    return Array.prototype.map.call(
      $(containerId).querySelectorAll('.dynamic-row ' + selector),
      function (el) { return el.value.trim(); }
    ).filter(Boolean);
  }

  /* ---------- รูปวิทยากร ----------
     อัปทันทีที่เลือกไฟล์เฉพาะตอนแก้ไข (มีรหัสแล้ว) ตอนเพิ่มใหม่ต้องพักไฟล์ไว้ก่อน
     แล้วส่งตามทันทีที่บันทึกครั้งแรกสำเร็จ — แบบเดียวกับรูปปกกิจกรรม */
  var photoSlot = $('instr-photo-slot');
  var photoInput = $('instr-photo');
  var photoPlaceholder = photoSlot.innerHTML;
  var pendingPhoto = null;

  function showPhoto(src) {
    photoSlot.innerHTML = src ? '<img src="' + src + '" alt="รูปวิทยากร">' : photoPlaceholder;

    /* innerHTML สร้าง input file ตัวใหม่ที่ไม่มี event ผูกอยู่ทุกครั้ง ต้องทิ้งตัวใหม่แล้วเอา
       ตัวจริงกลับเข้ามา ไม่งั้นหลังกด "เพิ่มวิทยากร" (ซึ่งเรียก showPhoto('')) จะเลือกรูปไม่ได้อีกเลย */
    var stale = photoSlot.querySelector('input[type="file"]');
    if (stale) stale.remove();
    photoSlot.appendChild(photoInput);
  }

  photoSlot.addEventListener('click', function () { photoInput.click(); });
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
    window.TFC.readImageFile(file, { maxMB: 5 }, showPhoto);
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
        if (!res.ok) throw new Error(d.message || 'อัปโหลดรูปไม่สำเร็จ');
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
    $('instr-active').checked = true;
    $('instr-active-label').textContent = 'ใช้งาน';
    pendingPhoto = null;
    showPhoto('');
    expertiseList.reset([]);
    courseList.reset([]);
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
    $('instr-name').value = instructor.name || '';
    $('instr-phone').value = instructor.phone || '';
    $('instr-bio').value = instructor.bio || '';
    $('instr-active').checked = instructor.active !== false;
    $('instr-active-label').textContent = instructor.active !== false ? 'ใช้งาน' : 'ไม่ใช้งาน';
    pendingPhoto = null;
    showPhoto(instructor.photo || '');
    expertiseList.reset(instructor.expertiseList || []);
    courseList.reset(instructor.courses || []);
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
      expertiseList: valuesOf('instr-expertise-list', 'input'),
      courses: valuesOf('instr-course-list', 'select')
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
