@extends('layouts.admin')

@section('title', 'กลุ่มเป้าหมาย')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">กลุ่มเป้าหมาย</span>
  </nav>
  <div class="page-header" id="target-group-page-header"></div>

  <div class="list-toolbar">
    <button type="button" class="filter-chip" id="target-group-filter-chip">
      <span id="target-group-filter-chip-label">แสดงทั้งหมด</span>
    </button>
    <span class="toolbar-divider"></span>
    <button type="button" class="icon-btn-sm" id="target-group-export" aria-label="ส่งออก Excel" title="ส่งออก Excel">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
    </button>
    <div class="ml-auto" id="target-group-search-popover"></div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อกลุ่มเป้าหมาย</th>
            <th>จำนวนเป้าหมาย (คน)</th>
            <th>ใช้กับกิจกรรม</th>
            <th>สถานะ</th>
            <th class="col-updated">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="target-group-table-body"></tbody>
      </table>
    </div>
  </div>
  <div id="target-group-pagination"></div>
@endsection

@section('modals')
<div class="modal-overlay" id="target-group-create-modal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title" id="tg-form-title">เพิ่มกลุ่มเป้าหมาย</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    {{-- ไม่มี data-mock-submit แล้ว — ฟอร์มนี้บันทึกลงฐานข้อมูลจริง
         ข้อความสำเร็จมาจากคำตอบของเซิร์ฟเวอร์ ไม่ใช่ข้อความสำเร็จรูปฝั่งหน้าจอ --}}
    <form id="tg-form">
      <p class="form-hint mb-3">ช่องที่มี <span class="form-required">*</span> จำเป็นต้องกรอก</p>
      <div class="modal-body">
        <div class="form-row mb-3">
          <div class="form-group mb-0">
            <label class="form-label" for="tg-name">ชื่อกลุ่มเป้าหมาย<span class="form-required">*</span></label>
            <input class="input" id="tg-name" data-validate required maxlength="100" autocomplete="off">
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="tg-target-count">จำนวนเป้าหมาย (คน)<span class="form-required">*</span></label>
            <input class="input" type="number" min="0" id="tg-target-count" data-validate required>
          </div>
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="tg-active">สถานะ<span class="form-required">*</span></label>
          <div class="flex items-center gap-2">
            <label class="switch"><input type="checkbox" id="tg-active" checked><span class="switch-track"></span></label>
            <span class="small text-secondary" id="tg-active-label">ใช้งาน</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="tg-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="target-group-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบกลุ่มเป้าหมาย</h3>
      <p class="text-secondary" id="tg-delete-message"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      {{-- ไม่มี data-close-modal เพราะต้องรอผลจากเซิร์ฟเวอร์ก่อนจึงปิด
           กลุ่มที่ถูกใช้อยู่จะถูกปฏิเสธ ถ้าปิดทันทีผู้ใช้จะไม่เห็นเหตุผล --}}
      <button class="btn btn-danger" id="tg-delete-confirm">ลบกลุ่มเป้าหมาย</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- statusTextHTML กับ exportTableCsv อยู่ในไฟล์นี้ ไม่ได้อยู่ใน bundle กลางของ layout --}}
<script src="{{ asset('assets/js/activity-module.js') }}"></script>
<script>
/* บอก dataService ว่า entity นี้ต่อฐานข้อมูลจริงแล้ว — อ่านตอนเรียก dataService() ในสคริปต์ของหน้า */
window.TFC_API = window.TFC_API || {};
window.TFC_API.targetGroups = @json(route('admin.master.target-groups.index'));
</script>
@endpush

@push('page-script')
<script>
(function () {
  var pageState = { page: 1, pageSize: 10 };
  var svc = window.TFC.dataService('targetGroups');
  var mock = window.TFC_MOCK || {};
  var rows = [];

  window.TFC.renderPageHeader('target-group-page-header', {
    title: 'กลุ่มเป้าหมาย',
    actions: [
      {
        label: 'เพิ่มกลุ่มเป้าหมาย',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'tg-add-btn', 'data-open-modal': 'target-group-create-modal' }
      }
    ]
  });

  function $(id) { return document.getElementById(id); }
  function rowOf(code) { return rows.filter(function (r) { return r.id === code; })[0]; }

  $('tg-active').addEventListener('change', function () {
    $('tg-active-label').textContent = this.checked ? 'ใช้งาน' : 'ไม่ใช้งาน';
  });

  /* ---------- ฟอร์มเพิ่ม/แก้ ---------- */

  function resetForm() {
    $('tg-form-title').textContent = 'เพิ่มกลุ่มเป้าหมาย';
    $('tg-form').reset();
    $('tg-active').checked = true;
    $('tg-active-label').textContent = 'ใช้งาน';
    $('tg-form').setAttribute('data-editing-id', '');
  }
  $('tg-add-btn').addEventListener('click', resetForm);

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-key^="tg-edit-"]');
    if (!trigger) return;

    var group = rowOf(trigger.getAttribute('data-action-key').replace('tg-edit-', ''));
    if (!group) return;

    $('tg-form-title').textContent = 'แก้ไขกลุ่มเป้าหมาย';
    $('tg-name').value = group.name || '';
    $('tg-target-count').value = group.targetCount != null ? group.targetCount : '';
    $('tg-active').checked = group.active !== false;
    $('tg-active-label').textContent = group.active !== false ? 'ใช้งาน' : 'ไม่ใช้งาน';
    $('tg-form').setAttribute('data-editing-id', group.id);
  });

  $('tg-form').addEventListener('submit', function (e) {
    e.preventDefault();

    var editingId = this.getAttribute('data-editing-id');
    var payload = {
      name: $('tg-name').value.trim(),
      targetCount: Number($('tg-target-count').value) || 0,
      active: $('tg-active').checked
    };

    /* กดแล้วต้องเข้าสถานะรออย่างชัดเจน ไม่งั้นกดซ้ำจะสร้างซ้ำ */
    var submit = $('tg-submit');
    submit.disabled = true;
    submit.textContent = 'กำลังบันทึก…';

    (editingId ? svc.update(editingId, payload) : svc.create(payload))
      .then(function () {
        window.TFC.closeModal('target-group-create-modal');
        window.TFC.showToast(editingId ? 'บันทึกกลุ่มเป้าหมายแล้ว' : 'เพิ่มกลุ่มเป้าหมายแล้ว', 'success');
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
    var item = e.target.closest('[data-action-key^="tg-delete-"]');
    if (!item) return;

    pendingDelete = rowOf(item.getAttribute('data-action-key').replace('tg-delete-', ''));
    $('tg-delete-message').textContent = pendingDelete
      ? 'ต้องการลบ "' + pendingDelete.name + '" ใช่หรือไม่ การลบนี้ย้อนกลับไม่ได้'
      : '';
  });

  $('tg-delete-confirm').addEventListener('click', function () {
    if (!pendingDelete) return;

    var button = this;
    button.disabled = true;

    svc.remove(pendingDelete.id)
      .then(function () {
        window.TFC.closeModal('target-group-delete-modal');
        window.TFC.showToast('ลบกลุ่มเป้าหมายแล้ว', 'success');
        pendingDelete = null;
        return renderTable();
      })
      .catch(function (err) { window.TFC.showToast(err.message, 'danger'); })
      .finally(function () { button.disabled = false; });
  });

  /* ---------- ตาราง ---------- */

  function renderTable() {
    return svc.list().then(function (all) {
      rows = all;

      var keyword = (($('target-group-search') || {}).value || '').trim().toLowerCase();
      var filtered = rows.filter(function (g) {
        return !keyword || g.name.toLowerCase().indexOf(keyword) !== -1;
      });

      var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
      if (pageState.page > pageCount) pageState.page = pageCount;
      var start = (pageState.page - 1) * pageState.pageSize;
      var pageRows = filtered.slice(start, start + pageState.pageSize);

      $('target-group-table-body').innerHTML = pageRows.map(function (g, i) {
        return '<tr>' +
          '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
          '<td>' + window.TFC.escapeHtml(g.name) + '</td>' +
          '<td>' + Number(g.targetCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td>' + Number(g.activityCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td class="nowrap">' + window.TFC.statusTextHTML({ options: mock.masterActiveStatuses, value: g.active === false ? 'ไม่ใช้งาน' : 'ใช้งาน' }) + '</td>' +
          '<td><div class="cell-updated-at">' + (g.updatedAt ? window.TFC.formatThaiDate(g.updatedAt) : '-') + '</div></td>' +
          '<td class="table-row-actions">' +
          window.TFC.actionMenuTrigger([
            { key: 'tg-edit-' + g.id, label: 'แก้ไข', icon: 'edit', modal: 'target-group-create-modal', perm: 'master_data' },
            { key: 'tg-delete-' + g.id, label: 'ลบกลุ่มเป้าหมาย', icon: 'delete', modal: 'target-group-delete-modal', perm: 'master_data', danger: true }
          ]) +
          '</td></tr>';
      }).join('');

      window.TFC.renderPagination('target-group-pagination', {
        page: pageState.page,
        pageSize: pageState.pageSize,
        total: filtered.length,
        pageSizeOptions: [10, 20, 50],
        footer: true,
        onChange: function (p) { pageState.page = p; renderTable(); },
        onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
      });
    }).catch(function (err) {
      window.TFC.showToast('โหลดข้อมูลไม่สำเร็จ: ' + err.message, 'danger');
    });
  }

  window.TFC.attachListToolbar({
    chipId: 'target-group-filter-chip',
    chipLabelId: 'target-group-filter-chip-label',
    popoverId: 'target-group-search-popover',
    search: { id: 'target-group-search', placeholder: 'ค้นหาชื่อกลุ่มเป้าหมาย' },
    onApply: function (values, done) {
      pageState.page = 1;
      renderTable();
      done();
    }
  });

  var exportBtn = $('target-group-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#target-group-table-body', 'กลุ่มเป้าหมาย.csv');
    });
  }

  renderTable();
})();
</script>
@endpush
