@extends('layouts.admin')

@section('title', 'ตัวเลือกการลงทะเบียน')

@push('styles')
<link rel="stylesheet" href="@assetv('assets/css/registration-master.css')">
@endpush

@section('content')
<div class="registration-master-page">
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/admin/dashboard">แดชบอร์ด</a><span>/</span><span>พื้นฐาน</span><span>/</span><span class="is-current">ตัวเลือกการลงทะเบียน</span></nav>
  <div class="page-header" id="registration-option-header"></div>

  <div class="registration-master-layout">
    <aside class="registration-group-tabs" aria-label="ประเภทข้อมูล">
      @foreach ($groups as $key => $group)
        <button type="button" data-group="{{ $key }}" class="registration-group-tab{{ $loop->first ? ' is-active' : '' }}">
          <span>{{ $group['label'] }}</span><span class="registration-group-count" data-count="{{ $key }}">0</span>
        </button>
      @endforeach
    </aside>

    <section class="registration-master-card">
      <div class="registration-card-toolbar">
        <div><h2 id="registration-group-title"></h2><p class="text-secondary">รายการที่เปิดใช้งานจะแสดงเป็นตัวเลือกในฟิลด์ลงทะเบียน</p></div>
        <button type="button" class="btn btn-primary" id="registration-option-add" data-open-modal="registration-option-modal">+ เพิ่มรายการ</button>
      </div>
      <div class="registration-search"><input class="input" id="registration-option-search" type="search" placeholder="ค้นหาชื่อรายการ"></div>
      <div class="table-wrap">
        <table class="table registration-option-table">
          <thead><tr><th>ลำดับ</th><th>ชื่อรายการ</th><th>สถานะ</th><th>แก้ไขล่าสุด</th><th class="text-right">จัดการ</th></tr></thead>
          <tbody id="registration-option-body"></tbody>
        </table>
      </div>
      <div class="registration-empty" id="registration-option-empty" hidden>ยังไม่มีรายการในประเภทนี้</div>
    </section>
  </div>
</div>
@endsection

@section('modals')
<div class="modal-overlay" id="registration-option-modal">
  <div class="modal modal-sm">
    <div class="modal-header"><h3 class="modal-title" id="registration-option-modal-title">เพิ่มรายการ</h3><button type="button" class="modal-close" data-close-modal aria-label="ปิด">×</button></div>
    <form id="registration-option-form">
      <div class="modal-body">
        <div class="form-group"><label class="form-label" for="registration-option-label">ชื่อรายการ<span class="form-required">*</span></label><input class="input" id="registration-option-label" maxlength="160" required></div>
        <div class="form-group"><label class="form-label" for="registration-option-order">ลำดับการแสดงผล</label><input class="input" id="registration-option-order" type="number" min="0" max="999" value="0" required></div>
        <div class="registration-switch-row"><label for="registration-option-active">เปิดใช้งาน</label><label class="switch"><input type="checkbox" id="registration-option-active" checked><span class="switch-track"></span></label></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button><button type="submit" class="btn btn-primary" id="registration-option-save">บันทึก</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="registration-option-delete-modal">
  <div class="modal modal-sm"><div class="modal-body text-center"><h3 class="modal-title mb-3">ยืนยันการลบรายการ</h3><p class="text-secondary" id="registration-option-delete-message"></p></div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button><button type="button" class="btn btn-danger" id="registration-option-delete-confirm">ลบรายการ</button></div></div>
</div>
@endsection

@push('scripts')
<script>
window.TFC_API = window.TFC_API || {};
window.TFC_API.registrationOptions = @json(route('admin.master.registration-options.index'));
window.TFC_SEED = window.TFC_SEED || {};
window.TFC_SEED.registrationOptions = @json($seedRows);
window.TFC_REGISTRATION_GROUPS = @json($groups);
</script>
@endpush

@push('page-script')
<script>
(function () {
  var svc = window.TFC.dataService('registrationOptions');
  var groups = window.TFC_REGISTRATION_GROUPS;
  var rows = [];
  var group = Object.keys(groups)[0];
  var editing = null;
  var deleting = null;
  function $(id) { return document.getElementById(id); }
  function esc(value) { return window.TFC.escapeHtml(String(value == null ? '' : value)); }
  function currentRows() {
    var keyword = $('registration-option-search').value.trim().toLowerCase();
    return rows.filter(function (row) { return row.group === group && (!keyword || row.label.toLowerCase().indexOf(keyword) !== -1); });
  }
  function render() {
    Object.keys(groups).forEach(function (key) {
      var count = rows.filter(function (row) { return row.group === key; }).length;
      document.querySelector('[data-count="' + key + '"]').textContent = count;
    });
    $('registration-group-title').textContent = groups[group].label;
    var visible = currentRows();
    $('registration-option-body').innerHTML = visible.map(function (row) {
      return '<tr><td>' + esc(row.sortOrder) + '</td><td><strong>' + esc(row.label) + '</strong><div class="caption text-secondary">' + esc(row.code) + '</div></td>' +
        '<td><span class="badge ' + (row.active ? 'badge-success' : 'badge-neutral') + '">' + (row.active ? 'เปิดใช้งาน' : 'ปิดใช้งาน') + '</span></td>' +
        '<td class="text-secondary">' + esc(row.updatedBy || '-') + '</td><td class="text-right"><button class="btn btn-outline btn-sm" data-edit="' + esc(row.id) + '" data-open-modal="registration-option-modal">แก้ไข</button> <button class="btn btn-outline btn-sm" data-delete="' + esc(row.id) + '" data-open-modal="registration-option-delete-modal">ลบ</button></td></tr>';
    }).join('');
    $('registration-option-empty').hidden = visible.length > 0;
  }
  function load() { return svc.list().then(function (data) { rows = data; render(); }); }
  function resetForm() {
    editing = null; $('registration-option-form').reset(); $('registration-option-active').checked = true;
    $('registration-option-order').value = rows.filter(function (row) { return row.group === group; }).length + 1;
    $('registration-option-modal-title').textContent = 'เพิ่ม' + groups[group].label;
  }
  window.TFC.renderPageHeader('registration-option-header', { title: 'ตัวเลือกการลงทะเบียน', description: 'จัดการช่วงอายุ อาชีพ ช่องทางรับรู้ กิจกรรมที่สนใจ และแหล่งที่มาของกลุ่มตัวอย่าง' });
  document.querySelectorAll('[data-group]').forEach(function (button) { button.addEventListener('click', function () { group = this.dataset.group; document.querySelectorAll('[data-group]').forEach(function (tab) { tab.classList.toggle('is-active', tab.dataset.group === group); }); render(); }); });
  $('registration-option-search').addEventListener('input', render);
  $('registration-option-add').addEventListener('click', resetForm);
  document.addEventListener('click', function (event) {
    var edit = event.target.closest('[data-edit]');
    if (edit) { editing = rows.find(function (row) { return row.id === edit.dataset.edit; }); if (!editing) return; group = editing.group; $('registration-option-modal-title').textContent = 'แก้ไข' + groups[group].label; $('registration-option-label').value = editing.label; $('registration-option-order').value = editing.sortOrder; $('registration-option-active').checked = editing.active; return; }
    var remove = event.target.closest('[data-delete]');
    if (remove) { deleting = rows.find(function (row) { return row.id === remove.dataset.delete; }); if (!deleting) return; var used = Number(deleting.deleteUsageCount || 0); $('registration-option-delete-message').textContent = used ? 'รายการนี้ถูกนำไปใช้แล้ว ' + used + ' รายการ จึงไม่สามารถลบได้' : 'ต้องการลบ “' + deleting.label + '” ใช่หรือไม่'; $('registration-option-delete-confirm').disabled = used > 0; }
  });
  $('registration-option-form').addEventListener('submit', function (event) { event.preventDefault(); var button = $('registration-option-save'); button.disabled = true; var payload = { group: group, label: $('registration-option-label').value.trim(), sortOrder: Number($('registration-option-order').value), active: $('registration-option-active').checked }; (editing ? svc.update(editing.id, payload) : svc.create(payload)).then(function () { window.TFC.closeModal('registration-option-modal'); window.TFC.showToast('บันทึกข้อมูลแล้ว', 'success'); return load(); }).catch(function (error) { window.TFC.showToast(error.message, 'danger'); }).finally(function () { button.disabled = false; }); });
  $('registration-option-delete-confirm').addEventListener('click', function () { if (!deleting) return; var button = this; button.disabled = true; svc.remove(deleting.id).then(function () { window.TFC.closeModal('registration-option-delete-modal'); window.TFC.showToast('ลบรายการแล้ว', 'success'); deleting = null; return load(); }).catch(function (error) { window.TFC.showToast(error.message, 'danger'); }).finally(function () { button.disabled = false; }); });
  load();
})();
</script>
@endpush
