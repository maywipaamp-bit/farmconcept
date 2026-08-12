@extends('layouts.admin')

@section('title', 'ผู้ใช้')

{{-- ตารางยืดเต็มจอ แถบแบ่งหน้าจึงติดขอบล่างเสมอ ข้อมูลล้นก็เลื่อนเฉพาะส่วนแถว --}}
@section('main-class', 'is-fill')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>ผู้ใช้งาน</span> <span>/</span>
    <span class="is-current">ผู้ใช้</span>
  </nav>
  <div class="page-header" id="user-page-header"></div>

  {{-- โครงมาตรฐานของหน้ารายการ: pill สถานะซ้าย · ช่องค้นหา + ปุ่มตัวกรองขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="user-counts"></div>
    <div class="list-filter-tools">
      {{-- ค้นหาพิมพ์แล้วกรองเลย ไม่ต้องกดปุ่ม จึงไม่มีปุ่มค้นหาข้างช่อง --}}
      <input type="search" class="input list-search-input" id="user-search"
             placeholder="ค้นหาชื่อ หรือ Username" aria-label="ค้นหาผู้ใช้งาน">
      <div id="user-search-popover"></div>
    </div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled is-dense" id="user-table">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อ-นามสกุล</th>
            <th>Username</th>
            <th>บทบาท</th>
            <th class="cell-center">สถานะ</th>
            <th class="col-updated cell-center">เข้าสู่ระบบล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="user-table-body"></tbody>
        {{-- แถบท้ายตารางเป็นแถวจริงในตาราง เพื่อให้อยู่ในกรอบเดียวกับข้อมูล --}}
        <tfoot><tr id="user-table-foot"></tr></tfoot>
      </table>
    </div>
  </div>
@endsection

@section('modals')
{{-- modal-lg (720px) ไม่ใช่ค่าเริ่มต้น 960px — ฟอร์มนี้มี 7 ช่อง ถ้ากว้างเต็มที่
     ช่องกรอกจะยาวเกินความยาวข้อมูลที่กรอกจริงไปมาก อ่านแล้วรู้สึกว่างเปล่า --}}
<div class="modal-overlay" id="user-form-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title" id="user-form-title">เพิ่มผู้ใช้งาน</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="user-form">
      {{-- กริดเดียวสามคอลัมน์: รูป · ช่องซ้าย · ช่องขวา
           ทุกช่องจึงเรียงตรงคอลัมน์กันหมด — สถานะอยู่ตรงกับ Password และเบอร์โทร --}}
      <div class="modal-body user-form-grid">
        <div class="form-group mb-0 user-form-photo">
          <label class="form-label">รูปภาพ</label>
          <div class="photo-slot" id="user-photo-slot" tabindex="0" role="button" aria-label="เลือกรูปภาพผู้ใช้งาน">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>เพิ่มรูป</span>
            <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" id="user-avatar-input">
          </div>
          <div class="form-helper">.jpg / .png ไม่เกิน 2 MB</div>
        </div>

        <div class="form-group mb-0">
          <label class="form-label" for="user-name">ชื่อ-นามสกุล<span class="form-required">*</span></label>
          <input class="input" id="user-name" data-validate required placeholder="เช่น สุนิสา แก้วมณี">
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="user-phone">เบอร์โทร</label>
          <input class="input" type="tel" id="user-phone" maxlength="30" placeholder="08x-xxx-xxxx">
        </div>

        <div class="form-group mb-0">
          <label class="form-label" for="user-username">Username<span class="form-required">*</span></label>
          <input class="input" id="user-username" data-validate required placeholder="เช่น sunisa01">
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="user-password">Password<span class="form-required" id="user-password-required">*</span>
            {{-- คำอธิบายอยู่ในเครื่องหมาย ? ชี้เมาส์หรือ Tab มาโฟกัสแล้วจึงแสดง
                 ข้อความเปลี่ยนตามโหมด: เพิ่มใหม่บอกเงื่อนไขรหัส แก้ไขบอกว่าเว้นว่างได้ --}}
            <span class="tooltip label-hint" tabindex="0" role="note"
                  id="user-password-helper" data-tooltip="ตัวอักษรภาษาอังกฤษ ตัวเลข อย่างน้อย 4 ตัวอักษร"
                  aria-label="ตัวอักษรภาษาอังกฤษ ตัวเลข อย่างน้อย 4 ตัวอักษร">?</span>
          </label>
          {{-- ปุ่มสลับซ่อน/แสดง — รหัสที่พิมพ์ผิดโดยไม่รู้ตัวคือสาเหตุอันดับหนึ่งที่บันทึกแล้วเข้าไม่ได้ --}}
          <div class="input-with-action">
            <input class="input" type="password" id="user-password" autocomplete="new-password">
            <button type="button" class="input-action" id="user-password-toggle" aria-pressed="false">แสดง</button>
          </div>
        </div>

        {{-- บทบาทเป็นช่องติ๊กที่เห็นทุกตัวเลือกพร้อมกัน ไม่ใช่ dropdown ที่ต้องกดเปิดก่อน
             ตัวเลือกมีไม่กี่รายการและมักเลือกมากกว่าหนึ่ง การซ่อนไว้ทำให้ต้องกดเพิ่มโดยไม่จำเป็น --}}
        <div class="form-group mb-0 user-form-roles">
          <span class="form-label" id="user-roles-label">บทบาท<span class="form-required">*</span>
            <span class="form-label-note">เลือกได้มากกว่า 1 บทบาท</span>
          </span>
          <div class="check-grid" id="user-roles-menu" role="group" aria-labelledby="user-roles-label"></div>
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="user-active">สถานะ<span class="form-required">*</span></label>
          <div class="flex items-center gap-2">
            <label class="switch"><input type="checkbox" id="user-active" checked><span class="switch-track"></span></label>
            <span class="small text-secondary" id="user-active-label">ใช้งานอยู่</span>
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

{{-- ระงับสิทธิ์มีผลทันทีและดีดคนที่กำลังใช้งานอยู่ออกจากระบบ จึงต้องถามยืนยันก่อน
     ใช้กล่องเดียวกันทั้งระงับและคืนสิทธิ์ เปลี่ยนเฉพาะข้อความกับสีปุ่ม --}}
<div class="modal-overlay" id="user-suspend-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon mx-auto" id="user-suspend-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 9h2v6H9zM13 9h2v6h-2z"/></svg>
      </span>
      <h3 class="modal-title mb-3" id="user-suspend-title"></h3>
      <p class="text-secondary" id="user-suspend-message"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button class="btn" id="user-suspend-confirm"></button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
{{-- ชิปนับจำนวนกับตัวช่วยแบ่งหน้าอยู่ในไฟล์นี้ ไม่ได้อยู่ใน bundle กลางของ layout --}}
<script src="@assetv('assets/js/master-list.js')"></script>
@endpush

@push('page-script')
<script>
(function () {
  var pageState = { page: 1, pageSize: 10, statusKey: '' };
  var usersList = @json($users);
  var allRoles = @json($roles->pluck('name'));
  var STATUS_OPTIONS = [
    { value: 'ใช้งานอยู่', badge: 'badge-success' },
    { value: 'ระงับการใช้งาน', badge: 'badge-danger' }
  ];
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  window.TFC.renderPageHeader('user-page-header', {
    title: 'ผู้ใช้',
    actions: [
      {
        label: 'เพิ่มผู้ใช้งาน',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'user-add-btn', 'data-open-modal': 'user-form-modal' }
      }
    ]
  });

  /* --- บทบาท: ช่องติ๊กที่เห็นทุกตัวเลือกพร้อมกัน --- */
  var rolesMenu = document.getElementById('user-roles-menu');
  rolesMenu.innerHTML = allRoles.map(function (name) {
    return '<label class="checkbox-item">' +
      '<input type="checkbox" value="' + window.TFC.escapeHtml(name) + '" data-role-checkbox>' +
      '<span>' + window.TFC.escapeHtml(name) + '</span></label>';
  }).join('');

  function selectedRoles() {
    return Array.from(rolesMenu.querySelectorAll('[data-role-checkbox]:checked')).map(function (cb) { return cb.value; });
  }

  function setSelectedRoles(names) {
    rolesMenu.querySelectorAll('[data-role-checkbox]').forEach(function (cb) {
      cb.checked = (names || []).indexOf(cb.value) !== -1;
    });
  }

  /* --- ปุ่มแสดง/ซ่อนรหัสผ่าน --- */
  var passwordToggle = document.getElementById('user-password-toggle');
  passwordToggle.addEventListener('click', function () {
    var field = document.getElementById('user-password');
    var show = field.type === 'password';

    field.type = show ? 'text' : 'password';
    this.textContent = show ? 'ซ่อน' : 'แสดง';
    this.setAttribute('aria-pressed', String(show));
  });

  function resetPasswordToggle() {
    document.getElementById('user-password').type = 'password';
    passwordToggle.textContent = 'แสดง';
    passwordToggle.setAttribute('aria-pressed', 'false');
  }

  /* --- รูปผู้ใช้งาน: ช่องสี่เหลี่ยมกดเลือกไฟล์ แบบเดียวกับฟอร์มวิทยากร --- */
  var photoSlot = document.getElementById('user-photo-slot');
  var photoInput = document.getElementById('user-avatar-input');
  var photoPlaceholder = photoSlot.innerHTML;

  function showAvatar(name, src) {
    photoSlot.innerHTML = src ? '<img src="' + src + '" alt="รูปผู้ใช้งาน">' : photoPlaceholder;

    /* innerHTML สร้าง input file ตัวใหม่ที่ไม่มี event และไม่มีไฟล์ที่เลือกไว้ทุกครั้ง
       ต้องทิ้งตัวใหม่แล้วเอาตัวจริงกลับเข้ามา ไม่งั้นหลังกด "เพิ่มผู้ใช้งาน" จะเลือกรูปไม่ได้อีกเลย */
    var stale = photoSlot.querySelector('input[type="file"]');
    if (stale) stale.remove();
    photoSlot.appendChild(photoInput);
  }

  photoSlot.addEventListener('click', function () { photoInput.click(); });
  photoInput.addEventListener('click', function (e) { e.stopPropagation(); });

  photoSlot.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); photoInput.click(); }
  });

  photoInput.addEventListener('change', function () {
    var file = photoInput.files && photoInput.files[0];
    if (!file) return;

    window.TFC.readImageFile(file, { maxMB: 2 }, function (src) { showAvatar('', src); });
  });

  function initialsOf(name) {
    var parts = (name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '';
    return parts[0].charAt(0) + (parts[1] ? parts[1].charAt(0) : '');
  }

  /* คำอธิบายอยู่ในไอคอน "!" — ต้องแก้ทั้ง data-tooltip (ที่แสดง) และ aria-label (ที่โปรแกรมอ่านหน้าจออ่าน) */
  function setPasswordHint(text) {
    var hint = document.getElementById('user-password-helper');
    hint.setAttribute('data-tooltip', text);
    hint.setAttribute('aria-label', text);
  }

  document.getElementById('user-active').addEventListener('change', function () {
    document.getElementById('user-active-label').textContent = this.checked ? 'ใช้งานอยู่' : 'ระงับการใช้งาน';
  });

  function resetForm() {
    document.getElementById('user-form-title').textContent = 'เพิ่มผู้ใช้งาน';
    document.getElementById('user-form').reset();
    showAvatar('', '');
    resetPasswordToggle();
    document.getElementById('user-active').checked = true;
    document.getElementById('user-active-label').textContent = 'ใช้งานอยู่';
    document.getElementById('user-password').setAttribute('required', 'required');
    document.getElementById('user-password-required').classList.remove('hidden');
    setPasswordHint('ตัวอักษรภาษาอังกฤษ ตัวเลข อย่างน้อย 4 ตัวอักษร');
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
    document.getElementById('user-phone').value = user.phone || '';
    showAvatar(user.name, user.avatar);
    document.getElementById('user-username').value = user.username || '';
    document.getElementById('user-password').value = '';
    resetPasswordToggle();
    document.getElementById('user-password').removeAttribute('required');
    document.getElementById('user-password-required').classList.add('hidden');
    setPasswordHint('เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน');
    document.getElementById('user-active').checked = user.status !== 'ระงับการใช้งาน';
    document.getElementById('user-active-label').textContent = user.status !== 'ระงับการใช้งาน' ? 'ใช้งานอยู่' : 'ระงับการใช้งาน';
    setSelectedRoles(user.roles || []);
    document.getElementById('user-form').setAttribute('data-editing-id', user.id);
  });

  /* "12 ส.ค. 69 | 08.30" — ย่อ พ.ศ. เหลือ 2 หลักกันบรรทัดตกในคอลัมน์แคบ */
  function loginStamp(user) {
    if (!user.lastLoginDate) return 'ยังไม่เคยเข้าใช้';

    var date = window.TFC.formatThaiDate(user.lastLoginDate).replace(/\d{2}(\d{2})$/, '$1');
    return window.TFC.escapeHtml(user.lastLoginTime ? date + ' | ' + user.lastLoginTime : date);
  }

  /* ชิปนับจำนวนด้านบน — สถานะมีสองค่าเท่านั้น จึงเขียนไว้ตรงนี้ได้ */
  var BUCKETS = [
    { key: '', label: 'ทั้งหมด' },
    { key: 'ใช้งานอยู่', label: 'ใช้งานอยู่', match: function (u) { return u.status !== 'ระงับการใช้งาน'; } },
    { key: 'ระงับการใช้งาน', label: 'ระงับการใช้งาน', match: function (u) { return u.status === 'ระงับการใช้งาน'; } }
  ];

  function renderTable() {
    var keyword = ((document.getElementById('user-search') || {}).value || '').trim().toLowerCase();
    var roleFilter = (document.getElementById('user-filter-role') || {}).value || '';

    window.TFC.renderStatusCounts('user-counts', usersList, {
      active: pageState.statusKey || '',
      buckets: BUCKETS,
      onPick: function (key) {
        pageState.statusKey = key === pageState.statusKey ? '' : key;
        pageState.page = 1;
        renderTable();
      }
    });

    var bucket = BUCKETS.filter(function (b) { return b.key === pageState.statusKey; })[0];

    var filtered = usersList.filter(function (u) {
      var matchesKeyword = !keyword || u.name.toLowerCase().indexOf(keyword) !== -1 || (u.username || '').toLowerCase().indexOf(keyword) !== -1;
      var matchesRole = !roleFilter || (u.roles || []).indexOf(roleFilter) !== -1;
      var matchesStatus = !bucket || !bucket.match || bucket.match(u);
      return matchesKeyword && matchesRole && matchesStatus;
    });

    var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
    if (pageState.page > pageCount) pageState.page = pageCount;
    var start = (pageState.page - 1) * pageState.pageSize;
    var pageRows = filtered.slice(start, start + pageState.pageSize);

    document.getElementById('user-table-body').innerHTML = pageRows.map(function (u, i) {
      /* บทบาทเป็นข้อมูลประกอบ ไม่ใช่สถานะที่ต้องสะดุดตา — ข้อความสีเทาคั่นด้วยจุดกลาง
         ป้ายสีทำให้ทั้งคอลัมน์แย่งความสนใจจากชื่อคนซึ่งเป็นตัวเอกของแถว */
      var roleText = (u.roles || []).map(function (r) { return window.TFC.escapeHtml(r); }).join(' · ');
      var suspended = u.status === 'ระงับการใช้งาน';
      return '<tr>' +
        '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
        /* ชื่อคลิกได้ เปิดฟอร์มแก้ไขทันที — ใช้ data-action-key เดียวกับเมนู "แก้ไข"
           จึงไปเข้า handler เดิมที่มีอยู่แล้ว ไม่ต้องเขียน logic ใหม่ */
        '<td><button type="button" class="cell-person cell-person-btn" ' +
        'data-action-key="user-edit-' + window.TFC.escapeHtml(u.id) + '" data-open-modal="user-form-modal">' +
        '<span class="cell-avatar">' + window.TFC.escapeHtml(initialsOf(u.name)) +
        (u.avatar ? '<img src="' + window.TFC.escapeHtml(u.avatar) + '" alt="">' : '') +
        '</span><span class="cell-person-name">' + window.TFC.escapeHtml(u.name) + '</span></button></td>' +
        '<td>' + window.TFC.escapeHtml(u.username || '-') + '</td>' +
        '<td class="text-secondary">' + (roleText || '-') + '</td>' +
        /* ใช้ statusTextHTML ตัวเดียวกับตารางอื่นในระบบ — ตัวหนังสือสีบนพื้นจาง ไม่มีจุดนำหน้า */
        '<td class="cell-center nowrap">' + window.TFC.statusTextHTML({ options: STATUS_OPTIONS, value: u.status }) + '</td>' +
        '<td class="cell-center nowrap">' + loginStamp(u) + '</td>' +
        '<td class="table-row-actions">' +
        window.TFC.actionMenuTrigger([
          { key: 'user-edit-' + u.id, label: 'แก้ไข', icon: 'edit', modal: 'user-form-modal', perm: 'users' },
          { key: 'user-suspend-' + u.id, label: suspended ? 'คืนสิทธิ์' : 'ระงับสิทธิ์', icon: 'status',
            modal: 'user-suspend-modal', perm: 'users' },
          { key: 'user-delete-' + u.id, label: 'ลบ', icon: 'delete', modal: 'user-delete-modal', perm: 'users', danger: true }
        ]) +
        '</td></tr>';
    }).join('');

    /* แถบท้ายอยู่ในตาราง — ช่องซ้ายกินสี่คอลัมน์แรก ช่องขวาอีกสามคอลัมน์ที่เหลือ
       ไม่มีคอลัมน์ตัวเลขให้รวม จึงมีแค่ข้อความสรุปกับปุ่มเลขหน้า */
    document.getElementById('user-table-foot').innerHTML =
      '<td colspan="4" id="user-foot-info"></td>' +
      '<td colspan="3" id="user-foot-controls"></td>';

    window.TFC.renderPagination(null, {
      page: pageState.page,
      pageSize: pageState.pageSize,
      total: filtered.length,
      pageSizeOptions: [10, 20, 50],
      infoTarget: 'user-foot-info',
      controlsTarget: 'user-foot-controls',
      onChange: function (p) { pageState.page = p; renderTable(); },
      onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
    });
  }

  /* ช่องค้นหาย้ายออกมาอยู่นอกแผงแล้ว ปุ่มนี้จึงเหลือแค่ตัวกรอง และเปลี่ยนไอคอนเป็นกรวยให้ตรงกับหน้าที่ */
  window.TFC.searchPopover('user-search-popover', {
    search: false,
    icon: 'filter',
    filters: [
      { id: 'role', inputId: 'user-filter-role', label: 'บทบาท', placeholder: 'บทบาททั้งหมด',
        options: allRoles.map(function (n) { return { label: n }; }) }
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
    document.getElementById('user-search').addEventListener(evt, function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        pageState.page = 1;
        renderTable();
      }, 200);
    });
  });

  /* ปุ่มส่งออกถูกเอาออกจากแถบเครื่องมือแล้ว ผูก event เฉพาะเมื่อยังมีปุ่มอยู่
     ฟังก์ชัน exportTableCsv ยังอยู่ครบ ถ้าจะเอาปุ่มกลับมาก็เพิ่ม element id เดิมได้ทันที */
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
    formData.append('phone', document.getElementById('user-phone').value);
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
        /* แถวใหม่อยู่บนสุดของหน้าแรก ต้องเด้งกลับหน้าแรกไม่งั้นบันทึกแล้วเหมือนไม่มีอะไรเกิดขึ้น */
        usersList.unshift(res.data);
        pageState.page = 1;
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

  /* --- ระงับสิทธิ์ / คืนสิทธิ์ ---
     ระงับสิทธิ์มีผลทันที คนที่เปิดหน้าจอค้างไว้จะถูกดีดออกในคำขอถัดไป จึงต้องถามยืนยันก่อน
     กล่องยืนยันใช้ใบเดียวทั้งสองทาง เปลี่ยนแค่ข้อความกับสีปุ่มตามสถานะปัจจุบัน */
  var pendingSuspend = null;

  document.addEventListener('click', function (e) {
    var item = e.target.closest('[data-action-key^="user-suspend-"]');
    if (!item) return;

    var id = item.getAttribute('data-action-key').replace('user-suspend-', '');
    var user = usersList.filter(function (u) { return String(u.id) === String(id); })[0];
    if (!user) return;

    pendingSuspend = user;
    var suspending = user.status !== 'ระงับการใช้งาน';

    document.getElementById('user-suspend-title').textContent = suspending ? 'ยืนยันการระงับสิทธิ์' : 'ยืนยันการคืนสิทธิ์';
    document.getElementById('user-suspend-message').textContent = suspending
      ? 'ระงับสิทธิ์ ' + user.name + ' — เข้าสู่ระบบไม่ได้อีก และถ้ากำลังใช้งานค้างอยู่จะถูกออกจากระบบทันที คืนสิทธิ์ภายหลังได้'
      : 'คืนสิทธิ์ ' + user.name + ' ให้กลับมาเข้าสู่ระบบได้ตามปกติ';

    document.getElementById('user-suspend-icon').className = 'modal-confirm-icon mx-auto ' + (suspending ? 'is-danger' : 'is-success');

    var confirmBtn = document.getElementById('user-suspend-confirm');
    confirmBtn.textContent = suspending ? 'ระงับสิทธิ์' : 'คืนสิทธิ์';
    confirmBtn.className = 'btn ' + (suspending ? 'btn-danger' : 'btn-primary');
  });

  document.getElementById('user-suspend-confirm').addEventListener('click', function () {
    if (!pendingSuspend) return;

    var button = this;
    var id = pendingSuspend.id;
    button.disabled = true;

    fetch('{{ url('/admin/users') }}/' + id + '/toggle-status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: '_method=PATCH'
    })
    .then(function (res) { return res.json(); })
    .then(function (res) {
      if (!res.success) {
        if (window.TFC.showToast) window.TFC.showToast(res.message || 'เกิดข้อผิดพลาด', 'danger');
        return;
      }

      /* เขียนทับแถวเดิมในตำแหน่งเดิม ไม่ push ต่อท้าย ลำดับในตารางจึงไม่ขยับ */
      var idx = usersList.findIndex(function (u) { return String(u.id) === String(id); });
      if (idx !== -1) usersList[idx] = res.data;

      pendingSuspend = null;
      if (window.TFC.closeModal) window.TFC.closeModal('user-suspend-modal');
      renderTable();
      if (window.TFC.showToast) window.TFC.showToast(res.message, 'success');
    })
    .catch(function () {
      if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
    })
    .finally(function () { button.disabled = false; });
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
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: '_method=DELETE'
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
