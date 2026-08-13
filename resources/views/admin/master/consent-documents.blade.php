@extends('layouts.admin')

@section('title', 'เอกสารและความยินยอม')

@push('styles')
<link rel="stylesheet" href="@assetv('assets/css/registration-master.css')">
@endpush

@section('content')
<div class="registration-master-page">
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/admin/dashboard">แดชบอร์ด</a><span>/</span><span>พื้นฐาน</span><span>/</span><span class="is-current">เอกสารและความยินยอม</span></nav>
  <div class="page-header" id="consent-header"></div>
  <div class="consent-type-guide">
    @foreach ($consentTypes as $key => $label)
      <div><span class="consent-type-icon">{{ $loop->iteration }}</span><strong>{{ $label }}</strong></div>
    @endforeach
  </div>
  <section class="registration-master-card">
    <div class="registration-card-toolbar"><div><h2>รายการเอกสาร</h2><p class="text-secondary">แต่ละประเภทเปิดใช้งานได้ครั้งละ 1 เวอร์ชัน และรายการที่มีผู้ยอมรับแล้วจะลบไม่ได้</p></div><button type="button" class="btn btn-primary" id="consent-add" data-open-modal="consent-modal">+ เพิ่มเอกสาร</button></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>ประเภท</th><th>ชื่อเอกสาร</th><th>เวอร์ชัน</th><th>บังคับ</th><th>สถานะ</th><th>ผู้ยอมรับ</th><th class="text-right">จัดการ</th></tr></thead><tbody id="consent-body"></tbody></table></div>
    <div class="registration-empty" id="consent-empty" hidden>ยังไม่มีเอกสารความยินยอม</div>
  </section>
</div>
@endsection

@section('modals')
<div class="modal-overlay" id="consent-modal">
  <div class="modal consent-form-modal">
    <div class="modal-header"><h3 class="modal-title" id="consent-modal-title">เพิ่มเอกสาร</h3><button type="button" class="modal-close" data-close-modal aria-label="ปิด">×</button></div>
    <form id="consent-form">
      <div class="modal-body">
        <div class="consent-form-grid">
          <div class="form-group"><label class="form-label" for="consent-type">ประเภท<span class="form-required">*</span></label><select class="select" id="consent-type" required>@foreach ($consentTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
          <div class="form-group"><label class="form-label" for="consent-version">เวอร์ชัน<span class="form-required">*</span></label><input class="input" id="consent-version" required maxlength="20" placeholder="เช่น 1.0"></div>
        </div>
        <div class="form-group"><label class="form-label" for="consent-title">ชื่อเอกสาร<span class="form-required">*</span></label><input class="input" id="consent-title" required maxlength="160"></div>
        <div class="form-group"><label class="form-label" for="consent-content">รายละเอียดที่ผู้ลงทะเบียนต้องอ่าน<span class="form-required">*</span></label><textarea class="textarea consent-content" id="consent-content" required maxlength="20000" rows="8"></textarea></div>
        <div class="consent-form-grid">
          <div class="form-group"><label class="form-label" for="consent-effective-date">วันที่เริ่มใช้</label><input class="input" id="consent-effective-date" type="date"></div>
          <div class="consent-switches"><label><span>บังคับให้ยอมรับ</span><span class="switch"><input type="checkbox" id="consent-required" checked><span class="switch-track"></span></span></label><label><span>เปิดใช้งาน</span><span class="switch"><input type="checkbox" id="consent-active"><span class="switch-track"></span></span></label></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button><button type="submit" class="btn btn-primary" id="consent-save">บันทึก</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="consent-delete-modal"><div class="modal modal-sm"><div class="modal-body text-center"><h3 class="modal-title mb-3">ยืนยันการลบเอกสาร</h3><p class="text-secondary" id="consent-delete-message"></p></div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button><button type="button" class="btn btn-danger" id="consent-delete-confirm">ลบเอกสาร</button></div></div></div>
@endsection

@push('scripts')
<script>
window.TFC_API = window.TFC_API || {}; window.TFC_API.consentDocuments = @json(route('admin.master.consent-documents.index'));
window.TFC_SEED = window.TFC_SEED || {}; window.TFC_SEED.consentDocuments = @json($seedRows);
</script>
@endpush

@push('page-script')
<script>
(function () {
  var svc = window.TFC.dataService('consentDocuments'); var rows = []; var editing = null; var deleting = null;
  function $(id) { return document.getElementById(id); } function esc(v) { return window.TFC.escapeHtml(String(v == null ? '' : v)); }
  function render() { $('consent-body').innerHTML = rows.map(function (row) { return '<tr><td><span class="consent-type-badge">' + esc(row.typeLabel) + '</span></td><td><strong>' + esc(row.title) + '</strong></td><td>' + esc(row.version) + '</td><td>' + (row.required ? 'ใช่' : 'ไม่บังคับ') + '</td><td><span class="badge ' + (row.active ? 'badge-success' : 'badge-neutral') + '">' + (row.active ? 'เปิดใช้งาน' : 'ปิดใช้งาน') + '</span></td><td>' + esc(row.deleteUsageCount) + '</td><td class="text-right"><button class="btn btn-outline btn-sm" data-consent-edit="' + esc(row.id) + '" data-open-modal="consent-modal">แก้ไข</button> <button class="btn btn-outline btn-sm" data-consent-delete="' + esc(row.id) + '" data-open-modal="consent-delete-modal">ลบ</button></td></tr>'; }).join(''); $('consent-empty').hidden = rows.length > 0; }
  function load() { return svc.list().then(function (data) { rows = data; render(); }); }
  function reset() { editing = null; $('consent-form').reset(); $('consent-required').checked = true; $('consent-active').checked = false; $('consent-version').value = '1.0'; $('consent-modal-title').textContent = 'เพิ่มเอกสารความยินยอม'; }
  window.TFC.renderPageHeader('consent-header', { title: 'เอกสารและความยินยอม', description: 'แยกเงื่อนไขการใช้งาน การยอมรับ PDPA และการยินยอมเก็บข้อมูลกลุ่มตัวอย่างอย่างชัดเจน' });
  $('consent-add').addEventListener('click', reset);
  document.addEventListener('click', function (event) { var edit = event.target.closest('[data-consent-edit]'); if (edit) { editing = rows.find(function (row) { return row.id === edit.dataset.consentEdit; }); if (!editing) return; $('consent-modal-title').textContent = 'แก้ไขเอกสารความยินยอม'; $('consent-type').value = editing.type; $('consent-version').value = editing.version; $('consent-title').value = editing.title; $('consent-content').value = editing.content; $('consent-effective-date').value = editing.effectiveDate || ''; $('consent-required').checked = editing.required; $('consent-active').checked = editing.active; return; } var remove = event.target.closest('[data-consent-delete]'); if (remove) { deleting = rows.find(function (row) { return row.id === remove.dataset.consentDelete; }); if (!deleting) return; var used = Number(deleting.deleteUsageCount || 0); $('consent-delete-message').textContent = used ? 'เอกสารนี้มีผู้ยอมรับแล้ว ' + used + ' รายการ จึงไม่สามารถลบได้' : 'ต้องการลบ “' + deleting.title + '” ใช่หรือไม่'; $('consent-delete-confirm').disabled = used > 0; } });
  $('consent-form').addEventListener('submit', function (event) { event.preventDefault(); var button = $('consent-save'); button.disabled = true; var payload = { type: $('consent-type').value, version: $('consent-version').value.trim(), title: $('consent-title').value.trim(), content: $('consent-content').value.trim(), effectiveDate: $('consent-effective-date').value || null, required: $('consent-required').checked, active: $('consent-active').checked }; (editing ? svc.update(editing.id, payload) : svc.create(payload)).then(function () { window.TFC.closeModal('consent-modal'); window.TFC.showToast('บันทึกเอกสารแล้ว', 'success'); return load(); }).catch(function (error) { window.TFC.showToast(error.message, 'danger'); }).finally(function () { button.disabled = false; }); });
  $('consent-delete-confirm').addEventListener('click', function () { if (!deleting) return; var button = this; button.disabled = true; svc.remove(deleting.id).then(function () { window.TFC.closeModal('consent-delete-modal'); window.TFC.showToast('ลบเอกสารแล้ว', 'success'); deleting = null; return load(); }).catch(function (error) { window.TFC.showToast(error.message, 'danger'); }).finally(function () { button.disabled = false; }); });
  load();
})();
</script>
@endpush
