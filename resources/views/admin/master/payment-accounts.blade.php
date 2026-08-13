@extends('layouts.admin')

@section('title', 'ข้อมูลการรับชำระ')

@push('styles')
<link rel="stylesheet" href="@assetv('assets/css/payment-accounts.css')">
@endpush

@section('content')
<div class="payment-accounts-page">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">ข้อมูลการรับชำระ</span>
  </nav>

  <div class="page-header" id="payment-account-page-header"></div>

  <div class="payment-account-grid" id="payment-account-list" aria-live="polite"></div>
</div>
@endsection

@section('modals')
<div class="modal-overlay" id="payment-account-form-modal">
  <div class="modal payment-account-form-modal">
    <div class="modal-header">
      <h3 class="modal-title" id="payment-account-form-title">เพิ่มข้อมูลการรับชำระ</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="payment-account-form">
      <div class="modal-body">
        <div class="payment-account-form-grid">
          <div>
            <div class="form-group">
              <label class="form-label" for="payment-bank">ธนาคาร<span class="form-required">*</span></label>
              <select class="select" id="payment-bank" required data-validate>
                <option value="">เลือกธนาคาร</option>
                @foreach ($banks as $code => $name)
                  <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="payment-account-number">เลขที่บัญชี<span class="form-required">*</span></label>
              <input class="input" id="payment-account-number" required maxlength="30"
                     inputmode="numeric" autocomplete="off" placeholder="เช่น 035-3-67251-7" data-validate>
            </div>

            <div class="form-group">
              <label class="form-label" for="payment-account-name">ชื่อบัญชี<span class="form-required">*</span></label>
              <input class="input" id="payment-account-name" required maxlength="150"
                     autocomplete="off" placeholder="ชื่อเจ้าของบัญชี" data-validate>
            </div>

            <div class="payment-active-field">
              <label class="form-label" for="payment-active">เปิดรับชำระ</label>
              <label class="switch" title="เปิดหรือปิดรับชำระ">
                <input type="checkbox" id="payment-active">
                <span class="switch-track"></span>
              </label>
            </div>
          </div>

          <div class="payment-qr-field">
            <label class="form-label" for="payment-qr-input">ภาพ QR Code</label>
            <label class="upload-zone payment-qr-upload" for="payment-qr-input" id="payment-qr-zone">
              <span class="payment-qr-preview" id="payment-qr-preview"></span>
            </label>
            <input type="file" id="payment-qr-input" accept="image/jpeg,image/png,image/webp" hidden>
            <div class="payment-qr-actions">
              <label class="btn btn-outline btn-sm" for="payment-qr-input" id="payment-qr-pick">เลือกภาพ</label>
              <button type="button" class="btn btn-outline btn-sm" id="payment-qr-remove" hidden>ลบภาพ</button>
            </div>
            <p class="caption text-secondary payment-qr-help" id="payment-qr-help">JPG, PNG, WEBP ไม่เกิน {{ number_format($qrMaxBytes / 1024 / 1024, 0) }} MB</p>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="payment-account-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="payment-account-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบข้อมูลการรับชำระ</h3>
      <p class="text-secondary" id="payment-account-delete-message"></p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button type="button" class="btn btn-danger" id="payment-account-delete-confirm">ลบข้อมูลการรับชำระ</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/master-list.js')"></script>
<script>
window.TFC_API = window.TFC_API || {};
window.TFC_API.paymentAccounts = @json(route('admin.master.payment-accounts.index'));
window.TFC_SEED = window.TFC_SEED || {};
window.TFC_SEED.paymentAccounts = @json($seedRows);
window.TFC_PAYMENT_ACCOUNTS = {
  banks: @json($banks),
  qrMaxBytes: @json($qrMaxBytes)
};
</script>
@endpush

@push('page-script')
<script>
(function () {
  var svc = window.TFC.dataService('paymentAccounts');
  var CFG = window.TFC_PAYMENT_ACCOUNTS;
  var rows = [];
  var pendingDelete = null;
  var selectedQrFile = null;
  var previewObjectUrl = '';
  var removeExistingQr = false;

  function $(id) { return document.getElementById(id); }
  function rowOf(code) { return rows.filter(function (row) { return row.id === code; })[0]; }
  function csrf() {
    var tag = document.querySelector('meta[name="csrf-token"]');
    return tag ? tag.getAttribute('content') : '';
  }

  function readJson(response) {
    return response.json().catch(function () { return {}; }).then(function (data) {
      if (response.ok) return data;
      var message = data.errors
        ? Object.keys(data.errors).map(function (key) { return data.errors[key][0]; }).join(' · ')
        : (data.message || 'ทำรายการไม่สำเร็จ');
      throw new Error(message);
    });
  }

  window.TFC.renderPageHeader('payment-account-page-header', {
    title: 'ข้อมูลการรับชำระ',
    description: 'กำหนดบัญชีและ QR Code สำหรับรับชำระ เปิดใช้งานได้ครั้งละ 1 บัญชี',
    actions: [{
      label: 'เพิ่มข้อมูลการรับชำระ',
      icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
      attrs: { id: 'payment-account-add', 'data-open-modal': 'payment-account-form-modal' }
    }]
  });

  function bankIcon(code) {
    return '<span class="payment-account-bank-icon" data-bank="' + window.TFC.escapeHtml(code || '') + '" aria-hidden="true">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M3 9h18L12 4 3 9Z"/><path d="M5 10v7M9.5 10v7M14.5 10v7M19 10v7M3 20h18M4 17h16"/>' +
      '</svg>' +
    '</span>';
  }

  function updatedStamp(row) {
    if (!row.updatedAt) return '-';
    var date = window.TFC.formatThaiDate(row.updatedAt).replace(/\d{2}(\d{2})$/, '$1');
    return window.TFC.escapeHtml((row.updatedBy || '-') + ' · ' + date + (row.updatedTime ? ' ' + row.updatedTime : ''));
  }

  function qrHtml(row) {
    return row.qrCode
      ? '<img src="' + window.TFC.escapeHtml(row.qrCode) + '" alt="QR Code สำหรับบัญชี ' + window.TFC.escapeHtml(row.accountNumber) + '">'
      : '<span><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM19 14h2v7h-7v-2"/></svg><br>ยังไม่มี QR Code</span>';
  }

  function cardHtml(row) {
    var code = window.TFC.escapeHtml(row.id);
    return '<article class="payment-account-card' + (row.active ? ' is-active' : '') + '">' +
      '<div class="payment-account-card-main">' +
        bankIcon(row.bankCode) +
        '<div class="payment-account-details">' +
          '<h2 class="payment-account-bank"><span>บัญชี :</span> ' + window.TFC.escapeHtml(row.bankName) + '</h2>' +
          '<p class="payment-account-number"><span>เลขที่บัญชี :</span> ' + window.TFC.escapeHtml(row.accountNumber) + '</p>' +
          '<p class="payment-account-name"><span>ชื่อบัญชี :</span> ' + window.TFC.escapeHtml(row.accountName) + '</p>' +
          '<p class="payment-account-updated">แก้ไขล่าสุด ' + updatedStamp(row) + '</p>' +
        '</div>' +
        '<div class="payment-account-qr">' + qrHtml(row) + '</div>' +
      '</div>' +
      '<div class="payment-account-card-footer">' +
        '<label class="payment-account-status-control">' +
          '<span class="switch"><input type="checkbox" data-payment-toggle="' + code + '"' + (row.active ? ' checked' : '') + '><span class="switch-track"></span></span>' +
          '<span class="payment-account-status-label">' + (row.active ? 'เปิดรับชำระเงินออนไลน์' : 'ปิดรับชำระเงินออนไลน์') + '</span>' +
        '</label>' +
        '<div class="payment-account-actions">' +
          '<button type="button" class="btn btn-outline btn-sm" data-payment-edit="' + code + '" data-open-modal="payment-account-form-modal">แก้ไข</button>' +
          '<button type="button" class="btn btn-outline btn-sm" data-payment-delete="' + code + '" data-open-modal="payment-account-delete-modal">ลบ</button>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  function renderList() {
    return svc.list().then(function (all) {
      rows = all;
      $('payment-account-list').innerHTML = rows.length
        ? rows.map(cardHtml).join('')
        : '<div class="payment-account-empty"><div class="font-medium mb-2">ยังไม่มีข้อมูลการรับชำระ</div><div class="caption">กด “เพิ่มข้อมูลการรับชำระ” เพื่อเริ่มต้น</div></div>';
    }).catch(function (error) {
      $('payment-account-list').innerHTML = '<div class="payment-account-empty">โหลดข้อมูลไม่สำเร็จ</div>';
      window.TFC.showToast('โหลดข้อมูลไม่สำเร็จ: ' + error.message, 'danger');
    });
  }

  function setActiveLabel() {
    $('payment-active').setAttribute('aria-label', $('payment-active').checked ? 'เปิดรับชำระ' : 'ปิดรับชำระ');
  }

  function clearObjectUrl() {
    if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    previewObjectUrl = '';
  }

  function qrPlaceholder() {
    return '<span class="payment-qr-placeholder"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM19 14h2v7h-7v-2"/></svg>เลือกภาพ QR Code</span>';
  }

  function showQrPreview(url) {
    $('payment-qr-preview').innerHTML = url
      ? '<img src="' + window.TFC.escapeHtml(url) + '" alt="ตัวอย่าง QR Code">'
      : qrPlaceholder();
  }

  function resetForm() {
    clearObjectUrl();
    $('payment-account-form').reset();
    $('payment-account-form').setAttribute('data-editing-id', '');
    $('payment-account-form-title').textContent = 'เพิ่มข้อมูลการรับชำระ';
    $('payment-active').checked = !rows.some(function (row) { return row.active; });
    selectedQrFile = null;
    removeExistingQr = false;
    $('payment-qr-remove').hidden = true;
    $('payment-qr-pick').textContent = 'เลือกภาพ';
    $('payment-qr-help').textContent = 'JPG, PNG, WEBP ไม่เกิน ' + Math.round(CFG.qrMaxBytes / 1024 / 1024) + ' MB';
    showQrPreview('');
    setActiveLabel();
  }

  function editForm(row) {
    clearObjectUrl();
    $('payment-account-form').reset();
    $('payment-account-form').setAttribute('data-editing-id', row.id);
    $('payment-account-form-title').textContent = 'แก้ไขข้อมูลการรับชำระ';
    $('payment-bank').value = row.bankCode || '';
    $('payment-bank').dispatchEvent(new Event('change', { bubbles: true }));
    $('payment-account-number').value = row.accountNumber || '';
    $('payment-account-name').value = row.accountName || '';
    $('payment-active').checked = row.active === true;
    selectedQrFile = null;
    removeExistingQr = false;
    $('payment-qr-remove').hidden = !row.qrCode;
    $('payment-qr-pick').textContent = row.qrCode ? 'เปลี่ยนภาพ' : 'เลือกภาพ';
    $('payment-qr-help').textContent = 'JPG, PNG, WEBP ไม่เกิน ' + Math.round(CFG.qrMaxBytes / 1024 / 1024) + ' MB';
    showQrPreview(row.qrCode || '');
    setActiveLabel();
  }

  function uploadQr(code, file) {
    var body = new FormData();
    body.append('qrCode', file);
    return fetch(window.TFC_API.paymentAccounts + '/' + encodeURIComponent(code) + '/qr-code', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: body
    }).then(readJson);
  }

  function deleteQr(code) {
    return fetch(window.TFC_API.paymentAccounts + '/' + encodeURIComponent(code) + '/qr-code', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json',
        'X-HTTP-Method-Override': 'DELETE'
      },
      credentials: 'same-origin'
    }).then(readJson);
  }

  $('payment-account-add').addEventListener('click', resetForm);
  $('payment-active').addEventListener('change', setActiveLabel);

  $('payment-qr-input').addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;
    if (file.size > CFG.qrMaxBytes) {
      this.value = '';
      window.TFC.showToast('ภาพ QR Code มีขนาดใหญ่เกินกำหนด', 'danger');
      return;
    }
    clearObjectUrl();
    selectedQrFile = file;
    removeExistingQr = false;
    previewObjectUrl = URL.createObjectURL(file);
    showQrPreview(previewObjectUrl);
    $('payment-qr-remove').hidden = false;
    $('payment-qr-pick').textContent = 'เปลี่ยนภาพ';
    $('payment-qr-help').textContent = file.name;
  });

  $('payment-qr-remove').addEventListener('click', function () {
    clearObjectUrl();
    selectedQrFile = null;
    removeExistingQr = true;
    $('payment-qr-input').value = '';
    $('payment-qr-remove').hidden = true;
    $('payment-qr-pick').textContent = 'เลือกภาพ';
    $('payment-qr-help').textContent = 'JPG, PNG, WEBP ไม่เกิน ' + Math.round(CFG.qrMaxBytes / 1024 / 1024) + ' MB';
    showQrPreview('');
  });

  document.addEventListener('click', function (event) {
    var edit = event.target.closest('[data-payment-edit]');
    if (edit) {
      var editRow = rowOf(edit.getAttribute('data-payment-edit'));
      if (editRow) editForm(editRow);
      return;
    }

    var remove = event.target.closest('[data-payment-delete]');
    if (remove) {
      var deleteRow = rowOf(remove.getAttribute('data-payment-delete'));
      pendingDelete = deleteRow && window.TFC.prepareMasterDelete({
        modalId: 'payment-account-delete-modal',
        messageId: 'payment-account-delete-message',
        confirmId: 'payment-account-delete-confirm',
        name: deleteRow.accountName,
        usageCount: deleteRow.deleteUsageCount,
        confirmMessage: 'ต้องการลบบัญชี ' + deleteRow.bankName + ' เลขที่ ' + deleteRow.accountNumber + ' ใช่หรือไม่ การลบนี้ย้อนกลับไม่ได้'
      }) ? deleteRow : null;
    }
  });

  document.addEventListener('change', function (event) {
    var toggle = event.target.closest('[data-payment-toggle]');
    if (!toggle) return;
    var row = rowOf(toggle.getAttribute('data-payment-toggle'));
    if (!row) return;

    toggle.disabled = true;
    svc.update(row.id, {
      bankCode: row.bankCode,
      accountNumber: row.accountNumber,
      accountName: row.accountName,
      active: toggle.checked
    }).then(function () {
      window.TFC.showToast(toggle.checked ? 'เปิดบัญชีรับชำระแล้ว' : 'ปิดบัญชีรับชำระแล้ว', 'success');
      return renderList();
    }).catch(function (error) {
      toggle.checked = row.active;
      window.TFC.showToast(error.message, 'danger');
    }).finally(function () {
      toggle.disabled = false;
    });
  });

  $('payment-account-form').addEventListener('submit', function (event) {
    event.preventDefault();
    var editingId = this.getAttribute('data-editing-id');
    var payload = {
      bankCode: $('payment-bank').value,
      accountNumber: $('payment-account-number').value.trim(),
      accountName: $('payment-account-name').value.trim(),
      active: $('payment-active').checked
    };
    var submit = $('payment-account-submit');
    submit.disabled = true;
    submit.textContent = 'กำลังบันทึก…';

    (editingId ? svc.update(editingId, payload) : svc.create(payload))
      .then(function (saved) {
        if (selectedQrFile) return uploadQr(saved.id, selectedQrFile);
        if (editingId && removeExistingQr) return deleteQr(saved.id);
        return null;
      })
      .then(function () {
        window.TFC.closeModal('payment-account-form-modal');
        window.TFC.showToast(editingId ? 'บันทึกข้อมูลการรับชำระแล้ว' : 'เพิ่มข้อมูลการรับชำระแล้ว', 'success');
        return renderList();
      })
      .catch(function (error) { window.TFC.showToast(error.message, 'danger'); })
      .finally(function () {
        submit.disabled = false;
        submit.textContent = 'บันทึก';
      });
  });

  $('payment-account-delete-confirm').addEventListener('click', function () {
    if (!pendingDelete) return;
    var button = this;
    button.disabled = true;
    svc.remove(pendingDelete.id)
      .then(function () {
        window.TFC.closeModal('payment-account-delete-modal');
        window.TFC.showToast('ลบข้อมูลการรับชำระแล้ว', 'success');
        pendingDelete = null;
        return renderList();
      })
      .catch(function (error) { window.TFC.showToast(error.message, 'danger'); })
      .finally(function () { button.disabled = false; });
  });

  resetForm();
  renderList();
})();
</script>
@endpush
