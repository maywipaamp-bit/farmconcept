@extends('layouts.admin')

@section('title', 'โปรแกรมการเรียนรู้')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">โปรแกรมการเรียนรู้</span>
  </nav>
  <div class="page-header" id="program-page-header"></div>

  {{-- โครงเดียวกับหน้ารายการกิจกรรม: pill สถานะซ้าย · ปุ่มค้นหาขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="program-counts"></div>
    <div id="program-search-popover"></div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled is-dense">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อโปรแกรมการเรียนรู้</th>
            <th>หลักสูตร</th>
            <th>ใช้กับกิจกรรม</th>
            <th>สถานะ</th>
            <th class="col-updated">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="program-table-body"></tbody>
      </table>
    </div>
  </div>
  <div id="program-pagination"></div>
@endsection

@section('modals')
<div class="modal-overlay" id="program-create-modal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title" id="program-form-title">เพิ่มโปรแกรมการเรียนรู้</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="program-form">
      <div class="modal-body">
        <div class="form-row-3 mb-3">
          <div class="form-group form-col-span-2 mb-0">
            <label class="form-label" for="program-name">ชื่อโปรแกรมการเรียนรู้<span class="form-required">*</span></label>
            <input class="input" id="program-name" data-validate required maxlength="150" autocomplete="off">
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="program-active">สถานะ<span class="form-required">*</span></label>
            <div class="flex items-center gap-2" style="height:38px;">
              <label class="switch"><input type="checkbox" id="program-active" checked><span class="switch-track"></span></label>
              <span class="small text-secondary" id="program-active-label">ใช้งาน</span>
            </div>
          </div>
        </div>
        {{-- is-cols-3: ช่องหลักสูตรกว้างเท่าชื่อโปรแกรมด้านบน ปุ่มลบ/เพิ่มอยู่ตรงคอลัมน์เดียวกับสถานะ --}}
        <div class="form-group mb-0">
          <label class="form-label">หลักสูตร</label>
          <div class="dynamic-row-list is-cols-3" id="program-courses-list"></div>
          <button type="button" class="dynamic-row-add is-cols-3" id="program-add-course-btn" aria-label="เพิ่มหลักสูตร" title="เพิ่มหลักสูตร">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
          </button>
          <div class="form-helper">หลักสูตรที่มีกิจกรรมใช้อยู่จะไม่ถูกลบ แม้เอาออกจากรายการนี้</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="program-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="program-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบโปรแกรม</h3>
      <p class="text-secondary" id="program-delete-message"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button class="btn btn-danger" id="program-delete-confirm">ลบโปรแกรม</button>
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
window.TFC_API.programs = @json(route('admin.master.programs.index'));

/* แถวชุดแรกฝังมากับหน้า หน้าจอจึงวาดตารางได้ทันทีโดยไม่ต้องรอคำขอเพิ่ม
   หลังบันทึกหรือลบ dataService จะไปเอาของจริงจากเซิร์ฟเวอร์เองตามปกติ */
window.TFC_SEED = window.TFC_SEED || {};
window.TFC_SEED.programs = @json($seedRows);
</script>
@endpush

@push('page-script')
<script>
(function () {
  /* จำนวนแถวคิดจากพื้นที่ที่เหลือจริงบนจอ ไม่ใช่เลข 10 ตายตัว
     statusKey = สถานะที่เลือกจากแถบนับจำนวน ('' = ทั้งหมด) */
  var pageState = { page: 1, pageSize: 10, statusKey: '' };
  var svc = window.TFC.dataService('programs');
  var mock = window.TFC_MOCK || {};
  var rows = [];

  function $(id) { return document.getElementById(id); }
  function rowOf(code) { return rows.filter(function (r) { return r.id === code; })[0]; }

  function courseRowHtml(value) {
    return '<div class="dynamic-row">' +
      '<span class="dynamic-row-order" data-row-order></span>' +
      '<input class="input" placeholder="ชื่อหลักสูตร" maxlength="150" value="' + window.TFC.escapeHtml(value) + '">' +
      '<button type="button" class="dynamic-row-remove" aria-label="ลบแถว">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
      '</button></div>';
  }

  var coursesList = window.TFC.dynamicRowList('program-courses-list', 'program-add-course-btn', courseRowHtml);
  coursesList.reset([]);

  /* อ่านชื่อหลักสูตรจากแถวที่ยังเหลืออยู่ ทิ้งแถวที่ผู้ใช้เปิดไว้แต่ไม่ได้กรอก */
  function coursesFromForm() {
    return Array.prototype.map.call(
      $('program-courses-list').querySelectorAll('.dynamic-row input'),
      function (input) { return input.value.trim(); }
    ).filter(Boolean);
  }

  window.TFC.renderPageHeader('program-page-header', {
    title: 'โปรแกรมการเรียนรู้',
    actions: [
      {
        label: 'เพิ่มโปรแกรมใหม่',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'program-add-btn', 'data-open-modal': 'program-create-modal' }
      }
    ]
  });

  $('program-active').addEventListener('change', function () {
    $('program-active-label').textContent = this.checked ? 'ใช้งาน' : 'ไม่ใช้งาน';
  });

  function resetForm() {
    $('program-form-title').textContent = 'เพิ่มโปรแกรมการเรียนรู้';
    $('program-form').reset();
    $('program-active').checked = true;
    $('program-active-label').textContent = 'ใช้งาน';
    coursesList.reset([]);
    $('program-form').setAttribute('data-editing-id', '');
  }
  $('program-add-btn').addEventListener('click', resetForm);

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-key^="program-edit-"]');
    if (!trigger) return;

    var program = rowOf(trigger.getAttribute('data-action-key').replace('program-edit-', ''));
    if (!program) return;

    $('program-form-title').textContent = 'แก้ไขโปรแกรมการเรียนรู้';
    $('program-name').value = program.name || '';
    $('program-active').checked = program.active !== false;
    $('program-active-label').textContent = program.active !== false ? 'ใช้งาน' : 'ไม่ใช้งาน';
    coursesList.reset((program.courses || []).map(function (c) { return c.name; }));
    $('program-form').setAttribute('data-editing-id', program.id);
  });

  $('program-form').addEventListener('submit', function (e) {
    e.preventDefault();

    var editingId = this.getAttribute('data-editing-id');
    var payload = {
      name: $('program-name').value.trim(),
      active: $('program-active').checked,
      courses: coursesFromForm()
    };

    var submit = $('program-submit');
    submit.disabled = true;
    submit.textContent = 'กำลังบันทึก…';

    (editingId ? svc.update(editingId, payload) : svc.create(payload))
      .then(function () {
        window.TFC.closeModal('program-create-modal');
        window.TFC.showToast(editingId ? 'บันทึกโปรแกรมแล้ว' : 'เพิ่มโปรแกรมแล้ว', 'success');
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
    var item = e.target.closest('[data-action-key^="program-delete-"]');
    if (!item) return;

    pendingDelete = rowOf(item.getAttribute('data-action-key').replace('program-delete-', ''));
    $('program-delete-message').textContent = pendingDelete
      ? 'ต้องการลบ "' + pendingDelete.name + '" พร้อมหลักสูตรที่ยังไม่มีใครใช้ ใช่หรือไม่ การลบนี้ย้อนกลับไม่ได้'
      : '';
  });

  $('program-delete-confirm').addEventListener('click', function () {
    if (!pendingDelete) return;

    var button = this;
    button.disabled = true;

    svc.remove(pendingDelete.id)
      .then(function () {
        window.TFC.closeModal('program-delete-modal');
        window.TFC.showToast('ลบโปรแกรมแล้ว', 'success');
        pendingDelete = null;
        return renderTable();
      })
      .catch(function (err) { window.TFC.showToast(err.message, 'danger'); })
      .finally(function () { button.disabled = false; });
  });

  /* ---------- ตาราง ---------- */

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
  function renderTable() {
    return svc.list().then(function (all) {
      rows = all;

      window.TFC.renderStatusCounts('program-counts', rows, {
        active: pageState.statusKey,
        buckets: BUCKETS,
        onPick: function (key) {
          pageState.statusKey = key === pageState.statusKey ? '' : key;
          pageState.page = 1;
          renderTable();
        }
      });

      var keyword = (($('program-search') || {}).value || '').trim().toLowerCase();
      var filtered = rows.filter(function (p) {
        return matchesStatus(p) && (!keyword || p.name.toLowerCase().indexOf(keyword) !== -1);
      });

      var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
      if (pageState.page > pageCount) pageState.page = pageCount;
      var start = (pageState.page - 1) * pageState.pageSize;
      var pageRows = filtered.slice(start, start + pageState.pageSize);

      $('program-table-body').innerHTML = pageRows.map(function (p, i) {
        /* แสดงหลักสูตรครบทุกรายการเป็นบุลเล็ต อ่านง่ายกว่าการต่อกันด้วยจุดคั่น
           แถวจะสูงตามจำนวนหลักสูตร ซึ่งยอมรับได้เพราะข้อมูลต้องครบตามที่ออกแบบ */
        var courseList = p.courses || [];
        var coursesHtml = courseList.length
          ? '<ul class="cell-bullets">' +
            courseList.map(function (c) { return '<li>' + window.TFC.escapeHtml(c.name) + '</li>'; }).join('') +
            '</ul>'
          : '<span class="text-secondary">-</span>';

        return '<tr>' +
          '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
          '<td><button type="button" class="cell-title-link font-medium" data-action-key="program-edit-' + window.TFC.escapeHtml(p.id) + '" data-open-modal="program-create-modal">' +
          window.TFC.escapeHtml(p.name) + '</button></td>' +
          '<td>' + coursesHtml + '</td>' +
          '<td>' + Number(p.activityCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td class="nowrap">' + window.TFC.statusTextHTML({ options: mock.masterActiveStatuses, value: p.active === false ? 'ไม่ใช้งาน' : 'ใช้งาน' }) + '</td>' +
          '<td><div class="cell-updated-at">' + (p.updatedAt ? window.TFC.formatThaiDate(p.updatedAt) : '-') + '</div></td>' +
          '<td class="table-row-actions">' +
          window.TFC.actionMenuTrigger([
            { key: 'program-edit-' + p.id, label: 'แก้ไข', icon: 'edit', modal: 'program-create-modal', perm: 'master_data' },
            { key: 'program-delete-' + p.id, label: 'ลบโปรแกรม', icon: 'delete', modal: 'program-delete-modal', perm: 'master_data', danger: true }
          ]) +
          '</td></tr>';
      }).join('');

      window.TFC.renderPagination('program-pagination', {
        page: pageState.page,
        pageSize: pageState.pageSize,
        total: filtered.length,
        pageSizeOptions: window.TFC.pageSizeOptions(pageState.pageSize),
        footer: true,
        onChange: function (p) { pageState.page = p; renderTable(); },
        onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
      });
    }).catch(function (err) {
      window.TFC.showToast('โหลดข้อมูลไม่สำเร็จ: ' + err.message, 'danger');
    });
  }

  window.TFC.searchPopover('program-search-popover', {
    search: { id: 'program-search', placeholder: 'ค้นหาชื่อโปรแกรม' },
    onSearch: function (values, done) {
      pageState.page = 1;
      renderTable();
      done();
    }
  });

  /* ปุ่มส่งออกถูกเอาออกจากแถบเครื่องมือแล้ว ผูก event เฉพาะเมื่อยังมีปุ่มอยู่
     ฟังก์ชัน exportTableCsv ยังอยู่ครบ ถ้าจะเอาปุ่มกลับมาก็เพิ่ม element id เดิมได้ทันที */
  var exportBtn = $('program-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#program-table-body', 'โปรแกรมการเรียนรู้.csv');
    });
  }

  /* ต้องวัดหลัง DOM ของตารางอยู่ในหน้าแล้ว จึงคำนวณตรงนี้ ไม่ใช่ตอนประกาศ pageState */
  pageState.pageSize = window.TFC.fitPageSize('program-table-body', 52);

  renderTable();

  /* ย่อ/ขยายหน้าต่างแล้วจำนวนแถวต้องขยับตาม ไม่ใช่ค้างที่ค่าตอนเปิดหน้า
     หน่วงไว้กันการวาดตารางใหม่ทุกพิกเซลระหว่างลากขอบหน้าต่าง */
  var resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var next = window.TFC.fitPageSize('program-table-body', 52);
      if (next === pageState.pageSize) return;
      pageState.pageSize = next;
      pageState.page = 1;
      renderTable();
    }, 200);
  });
})();
</script>
@endpush
