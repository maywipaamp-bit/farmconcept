@extends('layouts.admin')

@section('title', 'รายการผู้ใช้งาน')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span> <span class="is-current">ผู้ใช้งาน</span>
  </nav>
  <div class="page-header" id="user-page-header"></div>

  <div class="list-toolbar">
    <button type="button" class="filter-chip" id="user-filter-chip">
      <span id="user-filter-chip-label">แสดงทั้งหมด</span>
    </button>
    <span class="toolbar-divider"></span>
    <button type="button" class="icon-btn-sm" id="user-export" aria-label="ส่งออก Excel" title="ส่งออก Excel">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
    </button>
    <div class="ml-auto" id="user-search-popover"></div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled" id="user-table">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อ-นามสกุล</th>
            <th>Username</th>
            <th>บทบาท</th>
            <th>สถานะ</th>
            <th class="col-updated">เข้าสู่ระบบล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="user-table-body"></tbody>
      </table>
    </div>
  </div>
  <div id="user-pagination"></div>
@endsection

@section('modals')
<div class="modal-overlay" id="user-form-modal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title" id="user-form-title">เพิ่มผู้ใช้งาน</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="user-form">
      <div class="modal-body">
        <div class="profile-avatar-row">
          <span class="profile-avatar" id="user-avatar-preview"></span>
          <div class="profile-avatar-actions">
            <label class="btn btn-outline" for="user-avatar-input">เลือกรูปภาพ</label>
            <input type="file" id="user-avatar-input" accept="image/*" class="sr-only">
            <p class="caption text-secondary">ไฟล์ .jpg หรือ .png ขนาดไม่เกิน 2 MB</p>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="user-name">ชื่อ-นามสกุล<span class="form-required">*</span></label>
          <input class="input" id="user-name" data-validate required>
        </div>
        <div class="form-row">
          <div class="form-group mb-0">
            <label class="form-label" for="user-username">Username<span class="form-required">*</span></label>
            <input class="input" id="user-username" data-validate required>
            <div class="form-helper">ตัวอักษรภาษาอังกฤษ ตัวเลข หรืออักขระพิเศษ</div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="user-password">Password<span class="form-required" id="user-password-required">*</span></label>
            <input class="input" type="password" id="user-password">
            <div class="form-helper" id="user-password-helper">อย่างน้อย 4 ตัวอักษร</div>
          </div>
        </div>
        <div class="form-group">
          <span class="form-label" id="user-roles-label">บทบาท<span class="form-required">*</span></span>
          <div class="dropdown w-full">
            <button type="button" class="multiselect text-left" id="user-roles-trigger" data-dropdown-toggle aria-haspopup="true" aria-expanded="false" aria-labelledby="user-roles-label">
              <span id="user-roles-tags" class="text-secondary">เลือกบทบาท...</span>
            </button>
            <div class="dropdown-menu is-full-width" id="user-roles-menu"></div>
          </div>
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="user-active">สถานะ<span class="form-required">*</span></label>
          <div class="flex items-center gap-2">
            <label class="switch"><input type="checkbox" id="user-active" checked><span class="switch-track"></span></label>
            <span class="small text-secondary" id="user-active-label">ใช้งาน</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="user-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="user-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบผู้ใช้งาน</h3>
      <p class="text-secondary">ลบบัญชี <strong id="user-delete-name"></strong> ผู้ใช้งานจะไม่สามารถเข้าสู่ระบบได้อีก การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button class="btn btn-danger" id="user-delete-confirm">ลบผู้ใช้งาน</button>
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
  var pageState = { page: 1, pageSize: 10 };
  var usersList = @json($users);
  var allRoles = @json($roles->pluck('name'));
  var statusBadge = { 'ใช้งานอยู่': 'badge-success', 'ระงับการใช้งาน': 'badge-danger' };
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  window.TFC.renderPageHeader('user-page-header', {
    title: 'รายการผู้ใช้งาน',
    actions: [
      {
        label: 'เพิ่มผู้ใช้งาน',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'user-add-btn', 'data-open-modal': 'user-form-modal' }
      }
    ]
  });

  /* --- Multi-select role field (checkbox dropdown) --- */
  var rolesMenu = document.getElementById('user-roles-menu');
  rolesMenu.innerHTML = allRoles.map(function (name) {
    return '<label class="checkbox-item dropdown-item">' +
      '<input type="checkbox" value="' + window.TFC.escapeHtml(name) + '" data-role-checkbox>' +
      '<span>' + window.TFC.escapeHtml(name) + '</span></label>';
  }).join('');

  function selectedRoles() {
    return Array.from(rolesMenu.querySelectorAll('[data-role-checkbox]:checked')).map(function (cb) { return cb.value; });
  }

  function renderRoleTags() {
    var selected = selectedRoles();
    var tagsEl = document.getElementById('user-roles-tags');
    if (!selected.length) {
      tagsEl.className = 'text-secondary';
      tagsEl.textContent = 'เลือกบทบาท...';
      return;
    }
    tagsEl.className = 'flex flex-wrap gap-1';
    tagsEl.innerHTML = selected.map(function (name) {
      return '<span class="multiselect-tag">' + window.TFC.escapeHtml(name) +
        '<button type="button" data-remove-role="' + window.TFC.escapeHtml(name) + '" aria-label="เอาบทบาท ' + window.TFC.escapeHtml(name) + ' ออก">' +
        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button></span>';
    }).join('');
  }

  rolesMenu.addEventListener('change', renderRoleTags);

  document.getElementById('user-roles-tags').addEventListener('click', function (e) {
    var removeBtn = e.target.closest('[data-remove-role]');
    if (!removeBtn) return;
    e.stopPropagation();
    var name = removeBtn.getAttribute('data-remove-role');
    var cb = rolesMenu.querySelector('[data-role-checkbox][value="' + CSS.escape(name) + '"]');
    if (cb) cb.checked = false;
    renderRoleTags();
  });

  function setSelectedRoles(names) {
    rolesMenu.querySelectorAll('[data-role-checkbox]').forEach(function (cb) {
      cb.checked = (names || []).indexOf(cb.value) !== -1;
    });
    renderRoleTags();
  }

  /* --- รูปผู้ใช้งาน: กรอบวงกลม ตัวย่อชื่อกรณีไม่มีรูป --- */
  var avatarPreview = document.getElementById('user-avatar-preview');
  var avatarPicker = window.TFC.attachAvatarPicker(avatarPreview, document.getElementById('user-avatar-input'), { maxMB: 2 });

  function initialsOf(name) {
    var parts = (name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '';
    return parts[0].charAt(0) + (parts[1] ? parts[1].charAt(0) : '');
  }

  function showAvatar(name, src) {
    avatarPreview.textContent = initialsOf(name);
    avatarPicker.set(src || '');
  }

  document.getElementById('user-name').addEventListener('input', function () {
    showAvatar(this.value, avatarPicker.get());
  });

  document.getElementById('user-active').addEventListener('change', function () {
    document.getElementById('user-active-label').textContent = this.checked ? 'ใช้งาน' : 'ระงับการใช้งาน';
  });

  function resetForm() {
    document.getElementById('user-form-title').textContent = 'เพิ่มผู้ใช้งาน';
    document.getElementById('user-form').reset();
    showAvatar('', '');
    document.getElementById('user-active').checked = true;
    document.getElementById('user-active-label').textContent = 'ใช้งาน';
    document.getElementById('user-password').setAttribute('required', 'required');
    document.getElementById('user-password-required').classList.remove('hidden');
    document.getElementById('user-password-helper').textContent = 'อย่างน้อย 4 ตัวอักษร';
    setSelectedRoles([]);
    document.getElementById('user-form').setAttribute('data-editing-id', '');
  }
  document.getElementById('user-add-btn').addEventListener('click', resetForm);

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-key^="user-edit-"]');
    if (!trigger) return;
    var id = trigger.getAttribute('data-action-key').replace('user-edit-', '');
    var user = usersList.filter(function (u) { return String(u.id) === String(id); })[0];
    if (!user) return;
    document.getElementById('user-form-title').textContent = 'แก้ไขผู้ใช้งาน';
    document.getElementById('user-name').value = user.name || '';
    showAvatar(user.name, user.avatar);
    document.getElementById('user-username').value = user.username || '';
    document.getElementById('user-password').value = '';
    document.getElementById('user-password').removeAttribute('required');
    document.getElementById('user-password-required').classList.add('hidden');
    document.getElementById('user-password-helper').textContent = 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน';
    document.getElementById('user-active').checked = user.status !== 'ระงับการใช้งาน';
    document.getElementById('user-active-label').textContent = user.status !== 'ระงับการใช้งาน' ? 'ใช้งาน' : 'ระงับการใช้งาน';
    setSelectedRoles(user.roles || []);
    document.getElementById('user-form').setAttribute('data-editing-id', user.id);
  });

  function renderTable() {
    var keyword = ((document.getElementById('user-search') || {}).value || '').trim().toLowerCase();
    var roleFilter = (document.getElementById('user-filter-role') || {}).value || '';
    var filtered = usersList.filter(function (u) {
      var matchesKeyword = !keyword || u.name.toLowerCase().indexOf(keyword) !== -1 || (u.username || '').toLowerCase().indexOf(keyword) !== -1;
      var matchesRole = !roleFilter || (u.roles || []).indexOf(roleFilter) !== -1;
      return matchesKeyword && matchesRole;
    });

    var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
    if (pageState.page > pageCount) pageState.page = pageCount;
    var start = (pageState.page - 1) * pageState.pageSize;
    var pageRows = filtered.slice(start, start + pageState.pageSize);

    document.getElementById('user-table-body').innerHTML = pageRows.map(function (u, i) {
      var roleBadges = (u.roles || []).map(function (r) { return '<span class="badge badge-info">' + window.TFC.escapeHtml(r) + '</span>'; }).join(' ');
      var suspended = u.status === 'ระงับการใช้งาน';
      return '<tr>' +
        '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
        '<td><span class="cell-person"><span class="cell-avatar">' + window.TFC.escapeHtml(initialsOf(u.name)) +
        (u.avatar ? '<img src="' + window.TFC.escapeHtml(u.avatar) + '" alt="">' : '') +
        '</span><span class="cell-person-name">' + window.TFC.escapeHtml(u.name) + '</span></span></td>' +
        '<td>' + window.TFC.escapeHtml(u.username || '-') + '</td>' +
        '<td>' + (roleBadges || '-') + '</td>' +
        '<td><span class="badge ' + (statusBadge[u.status] || 'badge-neutral') + '">' + window.TFC.escapeHtml(u.status) + '</span></td>' +
        '<td>' + (u.lastLogin ? window.TFC.formatThaiDate(u.lastLogin) : '-') + '</td>' +
        '<td class="table-row-actions">' +
        window.TFC.actionMenuTrigger([
          { key: 'user-edit-' + u.id, label: 'แก้ไข', icon: 'edit', modal: 'user-form-modal', perm: 'users' },
          { key: 'user-suspend-' + u.id, label: suspended ? 'คืนสิทธิ์' : 'ระงับสิทธิ์', icon: 'status', perm: 'users' },
          { key: 'user-delete-' + u.id, label: 'ลบ', icon: 'delete', modal: 'user-delete-modal', perm: 'users', danger: true }
        ]) +
        '</td></tr>';
    }).join('');

    window.TFC.renderPagination('user-pagination', {
      page: pageState.page,
      pageSize: pageState.pageSize,
      total: filtered.length,
      pageSizeOptions: [10, 20, 50],
      footer: true,
      onChange: function (p) { pageState.page = p; renderTable(); },
      onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
    });
  }

  window.TFC.attachListToolbar({
    chipId: 'user-filter-chip',
    chipLabelId: 'user-filter-chip-label',
    popoverId: 'user-search-popover',
    search: { id: 'user-search', placeholder: 'ค้นหาชื่อหรือ Username' },
    filters: [
      { id: 'role', inputId: 'user-filter-role', label: 'บทบาท', placeholder: 'บทบาททั้งหมด',
        options: allRoles.map(function (n) { return { label: n }; }) }
    ],
    onApply: function (values, done) {
      pageState.page = 1;
      renderTable();
      done();
    }
  });

  var exportBtn = document.getElementById('user-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#user-table', 'รายการผู้ใช้งาน.csv');
    });
  }

  /* --- Form submit (AJAX POST/PUT) --- */
  var form = document.getElementById('user-form');
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var submitBtn = document.getElementById('user-submit');
    var editingId = form.getAttribute('data-editing-id');
    var roles = selectedRoles();

    if (!roles.length) {
      if (window.TFC.showToast) window.TFC.showToast('กรุณาเลือกบทบาทอย่างน้อย 1 บทบาท', 'danger');
      return;
    }

    var formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('name', document.getElementById('user-name').value);
    formData.append('username', document.getElementById('user-username').value);
    formData.append('status', document.getElementById('user-active').checked ? 'ใช้งานอยู่' : 'ระงับการใช้งาน');
    
    roles.forEach(function (r) {
      formData.append('roles[]', r);
    });

    var password = document.getElementById('user-password').value;
    if (password) {
      formData.append('password', password);
    }

    var fileInput = document.getElementById('user-avatar-input');
    if (fileInput.files && fileInput.files[0]) {
      formData.append('avatar', fileInput.files[0]);
    }

    var url = '{{ route('admin.users.store') }}';
    if (editingId) {
      url = '{{ url('/admin/users') }}/' + editingId;
      formData.append('_method', 'PUT');
    }

    submitBtn.disabled = true;

    fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: formData
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
        var idx = usersList.findIndex(function (u) { return String(u.id) === String(editingId); });
        if (idx !== -1) usersList[idx] = res.data;
      } else {
        usersList.unshift(res.data);
      }

      renderTable();
      if (window.TFC.closeModal) window.TFC.closeModal('user-form-modal');
      if (window.TFC.showToast) window.TFC.showToast(res.message || 'บันทึกเรียบร้อย', 'success');
    })
    .catch(function (err) {
      submitBtn.disabled = false;
      if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'danger');
    });
  });

  /* --- Toggle status (ระงับสิทธิ์ / คืนสิทธิ์) --- */
  document.addEventListener('click', function (e) {
    var item = e.target.closest('[data-action-key^="user-suspend-"]');
    if (!item) return;
    var id = item.getAttribute('data-action-key').replace('user-suspend-', '');
    
    fetch('{{ url('/admin/users') }}/' + id + '/toggle-status', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(function (res) { return res.json(); })
    .then(function (res) {
      if (!res.success) {
        if (window.TFC.showToast) window.TFC.showToast(res.message || 'เกิดข้อผิดพลาด', 'danger');
        return;
      }
      var idx = usersList.findIndex(function (u) { return String(u.id) === String(id); });
      if (idx !== -1) usersList[idx] = res.data;
      renderTable();
      if (window.TFC.showToast) window.TFC.showToast(res.message, 'success');
    })
    .catch(function () {
      if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
    });
  });

  /* --- Delete user --- */
  var pendingDeleteId = null;
  document.addEventListener('click', function (e) {
    var item = e.target.closest('[data-action-key^="user-delete-"]');
    if (!item) return;
    pendingDeleteId = item.getAttribute('data-action-key').replace('user-delete-', '');
    var user = usersList.filter(function (u) { return String(u.id) === String(pendingDeleteId); })[0];
    document.getElementById('user-delete-name').textContent = user ? user.name : '';
  });

  document.getElementById('user-delete-confirm').addEventListener('click', function () {
    if (!pendingDeleteId) return;
    var confirmBtn = this;
    confirmBtn.disabled = true;

    fetch('{{ url('/admin/users') }}/' + pendingDeleteId, {
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
      usersList = usersList.filter(function (u) { return String(u.id) !== String(pendingDeleteId); });
      pendingDeleteId = null;
      renderTable();
      if (window.TFC.closeModal) window.TFC.closeModal('user-delete-modal');
      if (window.TFC.showToast) window.TFC.showToast(res.message || 'ลบผู้ใช้งานเรียบร้อย', 'success');
    })
    .catch(function () {
      confirmBtn.disabled = false;
      if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
    });
  });

  renderTable();
})();
</script>
@endpush
