@extends('layouts.admin')

@section('title', 'หมวดหมู่กิจกรรม')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">หมวดหมู่กิจกรรม</span>
  </nav>
  <div class="page-header" id="fmt-page-header"></div>

  <div class="list-toolbar">
    <button type="button" class="filter-chip" id="fmt-filter-chip">
      <span id="fmt-filter-chip-label">แสดงทั้งหมด</span>
    </button>
    <span class="toolbar-divider"></span>
    <button type="button" class="icon-btn-sm" id="fmt-export" aria-label="ส่งออก Excel" title="ส่งออก Excel">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
    </button>
    <div class="ml-auto" id="fmt-search-popover"></div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อหมวดหมู่</th>
            <th>ใช้กับกิจกรรม</th>
            <th>สถานะ</th>
            <th class="col-updated">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="fmt-table-body"></tbody>
      </table>
    </div>
  </div>
  <div id="fmt-pagination"></div>
@endsection

@section('modals')
<div class="modal-overlay" id="fmt-form-modal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title" id="fmt-form-title">เพิ่มหมวดหมู่กิจกรรม</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="fmt-form">
      <p class="form-hint mb-3">ช่องที่มี <span class="form-required">*</span> จำเป็นต้องกรอก</p>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="fmt-name">ชื่อหมวดหมู่กิจกรรม<span class="form-required">*</span></label>
          <input class="input" id="fmt-name" data-validate required maxlength="60" autocomplete="off">
        </div>
        <div class="form-group">
          <span class="form-label">ไอคอนหมวดหมู่<span class="form-required">*</span></span>
          <div class="icon-picker" id="fmt-icon-picker" role="radiogroup" aria-label="ไอคอนหมวดหมู่"></div>
          <div class="form-helper">ใช้ไอคอนนี้แสดงคู่กับชื่อหมวดหมู่ในฟอร์มกิจกรรม</div>
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="fmt-active">สถานะ<span class="form-required">*</span></label>
          <div class="flex items-center gap-2">
            <label class="switch"><input type="checkbox" id="fmt-active" checked><span class="switch-track"></span></label>
            <span class="small text-secondary" id="fmt-active-label">ใช้งาน</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="fmt-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="fmt-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบหมวดหมู่กิจกรรม</h3>
      <p class="text-secondary" id="fmt-delete-message"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button class="btn btn-danger" id="fmt-delete-confirm">ลบหมวดหมู่กิจกรรม</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- statusTextHTML กับ exportTableCsv อยู่ในไฟล์นี้ ไม่ได้อยู่ใน bundle กลางของ layout --}}
<script src="{{ asset('assets/js/activity-module.js') }}"></script>
<script>
window.TFC_API = window.TFC_API || {};
window.TFC_API.activityFormats = @json(route('admin.master.activity-formats.index'));
</script>
@endpush

@push('page-script')
<script>
(function () {
  var pageState = { page: 1, pageSize: 10 };
  var svc = window.TFC.dataService('activityFormats');
  var mock = window.TFC_MOCK || {};
  var rows = [];

  function $(id) { return document.getElementById(id); }
  function rowOf(code) { return rows.filter(function (r) { return r.id === code; })[0]; }

  /* ---------- ตัวเลือกไอคอน ----------
     เส้น SVG อยู่ใน mock-data.js ฝั่งเซิร์ฟเวอร์เก็บแค่ "ชื่อ" ไว้ตรวจว่าค่าที่ส่งมาวาดออกจริง
     (config/farmconcept.php → category_icons) เพิ่มไอคอนใหม่ต้องเพิ่มทั้งสองที่ */
  var iconList = mock.activityCategoryIcons || [];
  var defaultIcon = (iconList[0] || {}).value || '';
  var selectedIcon = defaultIcon;

  function iconSvg(value) {
    var found = iconList.filter(function (ic) { return ic.value === value; })[0] || iconList[0];
    if (!found) return '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + found.path + '</svg>';
  }

  var iconPicker = $('fmt-icon-picker');
  iconPicker.innerHTML = iconList.map(function (ic) {
    return '<button type="button" class="icon-picker-option" role="radio" aria-checked="false"' +
      ' data-icon="' + ic.value + '" title="' + window.TFC.escapeHtml(ic.label) + '"' +
      ' aria-label="' + window.TFC.escapeHtml(ic.label) + '">' + iconSvg(ic.value) + '</button>';
  }).join('');

  function setSelectedIcon(value) {
    selectedIcon = value || defaultIcon;
    Array.prototype.forEach.call(iconPicker.querySelectorAll('.icon-picker-option'), function (btn) {
      var on = btn.getAttribute('data-icon') === selectedIcon;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-checked', on ? 'true' : 'false');
    });
  }

  iconPicker.addEventListener('click', function (e) {
    var btn = e.target.closest('.icon-picker-option');
    if (btn) setSelectedIcon(btn.getAttribute('data-icon'));
  });
  setSelectedIcon(defaultIcon);

  /* ---------- หัวหน้าและฟอร์ม ---------- */

  window.TFC.renderPageHeader('fmt-page-header', {
    title: 'หมวดหมู่กิจกรรม',
    actions: [
      {
        label: 'เพิ่มหมวดหมู่กิจกรรม',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'fmt-add-btn', 'data-open-modal': 'fmt-form-modal' }
      }
    ]
  });

  $('fmt-active').addEventListener('change', function () {
    $('fmt-active-label').textContent = this.checked ? 'ใช้งาน' : 'ไม่ใช้งาน';
  });

  function resetForm() {
    $('fmt-form-title').textContent = 'เพิ่มหมวดหมู่กิจกรรม';
    $('fmt-form').reset();
    setSelectedIcon(defaultIcon);
    $('fmt-active').checked = true;
    $('fmt-active-label').textContent = 'ใช้งาน';
    $('fmt-form').setAttribute('data-editing-id', '');
  }
  $('fmt-add-btn').addEventListener('click', resetForm);

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-key^="fmt-edit-"]');
    if (!trigger) return;

    var fmt = rowOf(trigger.getAttribute('data-action-key').replace('fmt-edit-', ''));
    if (!fmt) return;

    $('fmt-form-title').textContent = 'แก้ไขหมวดหมู่กิจกรรม';
    $('fmt-name').value = fmt.name || '';
    setSelectedIcon(fmt.icon);
    $('fmt-active').checked = fmt.active !== false;
    $('fmt-active-label').textContent = fmt.active !== false ? 'ใช้งาน' : 'ไม่ใช้งาน';
    $('fmt-form').setAttribute('data-editing-id', fmt.id);
  });

  $('fmt-form').addEventListener('submit', function (e) {
    e.preventDefault();

    var editingId = this.getAttribute('data-editing-id');
    var payload = { name: $('fmt-name').value.trim(), icon: selectedIcon, active: $('fmt-active').checked };

    var submit = $('fmt-submit');
    submit.disabled = true;
    submit.textContent = 'กำลังบันทึก…';

    (editingId ? svc.update(editingId, payload) : svc.create(payload))
      .then(function () {
        window.TFC.closeModal('fmt-form-modal');
        window.TFC.showToast(editingId ? 'บันทึกหมวดหมู่แล้ว' : 'เพิ่มหมวดหมู่แล้ว', 'success');
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
    var item = e.target.closest('[data-action-key^="fmt-delete-"]');
    if (!item) return;

    pendingDelete = rowOf(item.getAttribute('data-action-key').replace('fmt-delete-', ''));
    $('fmt-delete-message').textContent = pendingDelete
      ? 'ต้องการลบ "' + pendingDelete.name + '" ใช่หรือไม่ การลบนี้ย้อนกลับไม่ได้'
      : '';
  });

  $('fmt-delete-confirm').addEventListener('click', function () {
    if (!pendingDelete) return;

    var button = this;
    button.disabled = true;

    svc.remove(pendingDelete.id)
      .then(function () {
        window.TFC.closeModal('fmt-delete-modal');
        window.TFC.showToast('ลบหมวดหมู่แล้ว', 'success');
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

      var keyword = (($('fmt-search') || {}).value || '').trim().toLowerCase();
      var filtered = rows.filter(function (f) {
        return !keyword || f.name.toLowerCase().indexOf(keyword) !== -1;
      });

      var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
      if (pageState.page > pageCount) pageState.page = pageCount;
      var start = (pageState.page - 1) * pageState.pageSize;
      var pageRows = filtered.slice(start, start + pageState.pageSize);

      $('fmt-table-body').innerHTML = pageRows.map(function (f, i) {
        return '<tr>' +
          '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
          '<td><span class="cell-icon">' + iconSvg(f.icon) + '<span>' + window.TFC.escapeHtml(f.name) + '</span></span></td>' +
          '<td>' + Number(f.activityCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td class="nowrap">' + window.TFC.statusTextHTML({ options: mock.masterActiveStatuses, value: f.active === false ? 'ไม่ใช้งาน' : 'ใช้งาน' }) + '</td>' +
          '<td><div class="cell-updated-at">' + (f.updatedAt ? window.TFC.formatThaiDate(f.updatedAt) : '-') + '</div></td>' +
          '<td class="table-row-actions">' +
          window.TFC.actionMenuTrigger([
            { key: 'fmt-edit-' + f.id, label: 'แก้ไข', icon: 'edit', modal: 'fmt-form-modal', perm: 'master_data' },
            { key: 'fmt-delete-' + f.id, label: 'ลบหมวดหมู่กิจกรรม', icon: 'delete', modal: 'fmt-delete-modal', perm: 'master_data', danger: true }
          ]) +
          '</td></tr>';
      }).join('');

      window.TFC.renderPagination('fmt-pagination', {
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
    chipId: 'fmt-filter-chip',
    chipLabelId: 'fmt-filter-chip-label',
    popoverId: 'fmt-search-popover',
    search: { id: 'fmt-search', placeholder: 'ค้นหาชื่อหมวดหมู่' },
    onApply: function (values, done) {
      pageState.page = 1;
      renderTable();
      done();
    }
  });

  var exportBtn = $('fmt-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#fmt-table-body', 'หมวดหมู่กิจกรรม.csv');
    });
  }

  renderTable();
})();
</script>
@endpush
