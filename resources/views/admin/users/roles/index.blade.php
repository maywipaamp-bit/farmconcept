@extends('layouts.admin')

@section('title', 'บทบาทและสิทธิ์')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span> <span class="is-current">บทบาทและสิทธิ์</span>
  </nav>
  <div class="page-header" id="role-page-header"></div>

  <div class="table-wrapper mb-5">
    <div class="table-scroll">
      <table class="data-table is-header-filled" id="role-table">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อบทบาท</th>
            <th>จำนวนผู้ใช้</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="role-table-body"></tbody>
      </table>
    </div>
  </div>
@endsection

@section('modals')
<div class="modal-overlay" id="role-create-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title" id="role-form-title">เพิ่มบทบาทใหม่</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="role-form">
      <div class="modal-body">
        <div class="form-row mb-4">
          <div class="form-group mb-0">
            <label class="form-label" for="role-name">ชื่อบทบาท<span class="form-required">*</span></label>
            <input class="input" id="role-name" data-validate required>
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="role-active">สถานะ<span class="form-required">*</span></label>
            <div class="flex items-center gap-2">
              <label class="switch"><input type="checkbox" id="role-active" checked><span class="switch-track"></span></label>
              <span class="small text-secondary" id="role-active-label">ใช้งาน</span>
            </div>
          </div>
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="role-desc">คำอธิบาย</label>
          <textarea class="textarea" id="role-desc" rows="2"></textarea>
        </div>

        <div class="form-group mt-4 mb-0">
          <label class="form-label">ตารางสิทธิ์การเข้าถึงเมนู (Permission Matrix)</label>
          <div class="form-helper mb-2">Mapping ตรงกับโครงสร้างเมนู Sidebar — ติ๊กหมวดหมู่หลักเพื่อเลือก/ยกเลิกเมนูย่อยทั้งหมดในหมวดนั้น</div>
          <div id="role-permission-matrix" style="max-height:320px;overflow-y:auto;border:1px solid var(--color-border);border-radius:var(--radius-input);padding:var(--space-3);"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="role-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="role-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบบทบาท</h3>
      <p class="text-secondary" id="role-delete-detail"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button class="btn btn-danger" id="role-delete-confirm">ลบบทบาท</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
<script>
(function () {
  var rolesList = @json($roles);
  var menuStructure = @json($menuStructure);
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  window.TFC.renderPageHeader('role-page-header', {
    title: 'บทบาทและสิทธิ์',
    actions: [
      {
        label: 'เพิ่มบทบาทใหม่',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'role-add-btn', 'data-open-modal': 'role-create-modal' }
      }
    ]
  });

  /* --- Permission Matrix builder --- */
  var matrixEl = document.getElementById('role-permission-matrix');

  function categoryBlockHtml(item) {
    if (!item.children || !item.children.length) {
      return '<label class="checkbox-item mb-2" style="display:flex;">' +
        '<input type="checkbox" data-perm="' + item.key + '"><span>' + window.TFC.escapeHtml(item.label) + '</span>' +
        '</label>';
    }
    var childrenHtml = item.children.map(function (child) {
      return '<label class="checkbox-item small" style="display:flex;">' +
        '<input type="checkbox" data-perm="' + child.key + '" data-perm-parent="' + item.key + '"><span>' + window.TFC.escapeHtml(child.label) + '</span>' +
        '</label>';
    }).join('');
    return '<div class="mb-3">' +
      '<div class="flex items-center justify-between flex-wrap gap-1 mb-1">' +
      '<label class="checkbox-item font-medium" style="display:flex;">' +
      '<input type="checkbox" data-perm="' + item.key + '" data-perm-category="' + item.key + '"><span>' + window.TFC.escapeHtml(item.label) + '</span>' +
      '</label>' +
      '<span class="flex gap-2">' +
      '<button type="button" class="btn btn-text btn-sm" data-select-all="' + item.key + '">เลือกทั้งหมด</button>' +
      '<button type="button" class="btn btn-text btn-sm" data-clear-all="' + item.key + '">ยกเลิกทั้งหมด</button>' +
      '</span></div>' +
      '<div style="padding-left:var(--space-5);display:flex;flex-direction:column;gap:var(--space-1);">' + childrenHtml + '</div>' +
      '</div>';
  }

  matrixEl.innerHTML = (menuStructure || []).map(categoryBlockHtml).join('');

  matrixEl.addEventListener('click', function (e) {
    var selectBtn = e.target.closest('[data-select-all]');
    var clearBtn = e.target.closest('[data-clear-all]');
    if (!selectBtn && !clearBtn) return;
    var key = (selectBtn || clearBtn).getAttribute(selectBtn ? 'data-select-all' : 'data-clear-all');
    var checked = !!selectBtn;
    matrixEl.querySelectorAll('[data-perm="' + key + '"], [data-perm-parent="' + key + '"]').forEach(function (cb) {
      cb.checked = checked;
    });
  });

  matrixEl.addEventListener('change', function (e) {
    var cb = e.target.closest('[data-perm-category]');
    if (!cb) return;
    var key = cb.getAttribute('data-perm-category');
    matrixEl.querySelectorAll('[data-perm-parent="' + key + '"]').forEach(function (child) {
      child.checked = cb.checked;
    });
  });

  function getMenuPermissions() {
    var result = {};
    matrixEl.querySelectorAll('[data-perm]').forEach(function (cb) {
      result[cb.getAttribute('data-perm')] = cb.checked;
    });
    return result;
  }

  function setMenuPermissions(menuPermissions) {
    menuPermissions = menuPermissions || {};
    matrixEl.querySelectorAll('[data-perm]').forEach(function (cb) {
      cb.checked = !!menuPermissions[cb.getAttribute('data-perm')];
    });
  }

  document.getElementById('role-active').addEventListener('change', function () {
    document.getElementById('role-active-label').textContent = this.checked ? 'ใช้งาน' : 'ระงับใช้งาน';
  });

  function resetForm() {
    document.getElementById('role-form-title').textContent = 'เพิ่มบทบาทใหม่';
    document.getElementById('role-form').reset();
    document.getElementById('role-active').checked = true;
    document.getElementById('role-active-label').textContent = 'ใช้งาน';
    setMenuPermissions({});
    document.getElementById('role-form').setAttribute('data-editing-id', '');
  }
  document.getElementById('role-add-btn').addEventListener('click', resetForm);

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-key^="role-edit-"]');
    if (!trigger) return;
    var id = trigger.getAttribute('data-action-key').replace('role-edit-', '');
    var role = rolesList.filter(function (r) { return String(r.id) === String(id); })[0];
    if (!role) return;
    document.getElementById('role-form-title').textContent = 'แก้ไขบทบาทและสิทธิ์: ' + role.name;
    document.getElementById('role-name').value = role.name || '';
    document.getElementById('role-desc').value = role.description || '';
    document.getElementById('role-active').checked = role.active !== false;
    document.getElementById('role-active-label').textContent = role.active !== false ? 'ใช้งาน' : 'ระงับใช้งาน';
    setMenuPermissions(role.menuPermissions || {});
    document.getElementById('role-form').setAttribute('data-editing-id', role.id);
  });

  function renderRoleTable() {
    document.getElementById('role-table-body').innerHTML = rolesList.map(function (r, i) {
      return '<tr>' +
        '<td class="col-no nowrap">' + (i + 1) + '</td>' +
        '<td>' + window.TFC.escapeHtml(r.name) + '</td>' +
        '<td>' + (r.userCount || 0) + ' บัญชี</td>' +
        '<td class="table-row-actions">' +
        window.TFC.actionMenuTrigger([
          { key: 'role-edit-' + r.id, label: 'แก้ไข', icon: 'edit', modal: 'role-create-modal', perm: 'users' },
          { key: 'role-delete-' + r.id, label: 'ลบ', icon: 'delete', modal: 'role-delete-modal', perm: 'users', danger: true }
        ]) +
        '</td></tr>';
    }).join('');
  }

  /* --- Form submit (AJAX POST/PUT) --- */
  var form = document.getElementById('role-form');
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var submitBtn = document.getElementById('role-submit');
    var editingId = form.getAttribute('data-editing-id');
    var menuPermissions = getMenuPermissions();

    var payload = {
      name: document.getElementById('role-name').value,
      description: document.getElementById('role-desc').value,
      is_active: document.getElementById('role-active').checked,
      permissions: menuPermissions
    };

    var url = '{{ route('admin.users.roles.store') }}';
    var method = 'POST';

    if (editingId) {
      url = '{{ url('/admin/users/roles') }}/' + editingId;
      payload._method = 'PUT';
    }

    submitBtn.disabled = true;

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    })
    .then(function (res) { return res.json(); })
    .then(function (res) {
      submitBtn.disabled = false;
      if (!res.success) {
        var msg = res.message || (res.errors ? Object.values(res.errors).flat().join('<br>') : 'เกิดข้อผิดพลาดในการบันทึก');
        if (window.TFC.showToast) window.TFC.showToast(msg, 'danger');
        return;
      }

      if (editingId) {
        var idx = rolesList.findIndex(function (r) { return String(r.id) === String(editingId); });
        if (idx !== -1) rolesList[idx] = res.data;
      } else {
        rolesList.push(res.data);
      }

      renderRoleTable();
      if (window.TFC.closeModal) window.TFC.closeModal('role-create-modal');
      if (window.TFC.showToast) window.TFC.showToast(res.message || 'บันทึกเรียบร้อย', 'success');
    })
    .catch(function () {
      submitBtn.disabled = false;
      if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'danger');
    });
  });

  /* --- Delete role --- */
  var pendingDeleteId = null;
  document.addEventListener('click', function (e) {
    var item = e.target.closest('[data-action-key^="role-delete-"]');
    if (!item) return;
    pendingDeleteId = item.getAttribute('data-action-key').replace('role-delete-', '');
    var role = rolesList.filter(function (r) { return String(r.id) === String(pendingDeleteId); })[0];
    var count = role ? (role.userCount || 0) : 0;
    document.getElementById('role-delete-detail').textContent = count
      ? 'ลบบทบาท "' + role.name + '" ที่มีผู้ใช้งานอยู่ ' + count + ' บัญชี บัญชีเหล่านั้นจะไม่มีบทบาทนี้อีกต่อไป'
      : 'ลบบทบาท "' + (role ? role.name : '') + '" การดำเนินการนี้ไม่สามารถย้อนกลับได้';
  });

  document.getElementById('role-delete-confirm').addEventListener('click', function () {
    if (!pendingDeleteId) return;
    var confirmBtn = this;
    confirmBtn.disabled = true;

    fetch('{{ url('/admin/users/roles') }}/' + pendingDeleteId, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(function (res) { return res.json(); })
    .then(function (res) {
      confirmBtn.disabled = false;
      if (!res.success) {
        if (window.TFC.showToast) window.TFC.showToast(res.message || 'เกิดข้อผิดพลาดในการลบ', 'danger');
        return;
      }
      rolesList = rolesList.filter(function (r) { return String(r.id) !== String(pendingDeleteId); });
      pendingDeleteId = null;
      renderRoleTable();
      if (window.TFC.closeModal) window.TFC.closeModal('role-delete-modal');
      if (window.TFC.showToast) window.TFC.showToast(res.message || 'ลบบทบาทเรียบร้อย', 'success');
    })
    .catch(function () {
      confirmBtn.disabled = false;
      if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
    });
  });

  renderRoleTable();
})();
</script>
@endpush
