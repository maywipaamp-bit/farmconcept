@extends('layouts.admin')

@section('title', 'บทบาท')

{{-- ตารางยืดเต็มจอ แถบแบ่งหน้าจึงติดขอบล่างเสมอ ข้อมูลล้นก็เลื่อนเฉพาะส่วนแถว --}}
@section('main-class', 'is-fill')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>ผู้ใช้งาน</span> <span>/</span>
    <span class="is-current">บทบาท</span>
  </nav>
  <div class="page-header" id="role-page-header"></div>

  {{-- โครงมาตรฐานของหน้ารายการ: pill สถานะซ้าย · ช่องค้นหา + ปุ่มตัวกรองขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="role-counts"></div>
    <div class="list-filter-tools">
      {{-- ค้นหาพิมพ์แล้วกรองเลย ไม่ต้องกดปุ่ม จึงไม่มีปุ่มค้นหาข้างช่อง --}}
      <input type="search" class="input list-search-input" id="role-search"
             placeholder="ค้นหาชื่อบทบาท คำอธิบาย" aria-label="ค้นหาบทบาท">
      <div id="role-search-popover"></div>
    </div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled is-dense" id="role-table">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อบทบาท</th>
            <th>คำอธิบาย</th>
            <th class="cell-count">จำนวนผู้ใช้</th>
            <th class="cell-center">สถานะ</th>
            <th class="col-updated cell-center">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="role-table-body"></tbody>
        {{-- แถบท้ายตารางเป็นแถวจริงในตาราง ผลรวมจึงตรงคอลัมน์ได้ --}}
        <tfoot><tr id="role-table-foot"></tr></tfoot>
      </table>
    </div>
  </div>
@endsection

@section('modals')
<div class="modal-overlay" id="role-create-modal">
  {{-- สองฝั่งต้องการความกว้างกว่าฟอร์มคอลัมน์เดียว ไม่งั้นตารางสิทธิ์จะถูกบีบจนชื่อเมนูตกบรรทัด --}}
  <div class="modal modal-xl">
    <div class="modal-header">
      <h3 class="modal-title" id="role-form-title">เพิ่มบทบาทใหม่</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="role-form">
      {{-- สองฝั่ง: ข้อมูลบทบาทซ้าย · สิทธิ์การเข้าถึงขวา คั่นด้วยเส้นเทาเส้นเดียว
           ตั้งค่าชื่อกับสิทธิ์ไปพร้อมกันได้โดยไม่ต้องเลื่อนหากัน --}}
      <div class="modal-body role-form-split">
        <div class="role-form-info">
          <div class="form-group">
            <label class="form-label" for="role-name">ชื่อบทบาท<span class="form-required">*</span></label>
            <input class="input" id="role-name" data-validate required placeholder="เช่น เจ้าหน้าที่ภาคสนาม">
          </div>

          <div class="form-group">
            <label class="form-label" for="role-desc">คำอธิบาย</label>
            <textarea class="textarea" id="role-desc" rows="4" placeholder="อธิบายสั้น ๆ ว่าบทบาทนี้ทำอะไรได้"></textarea>
          </div>

          <div class="form-group mb-0">
            <label class="form-label" for="role-active">สถานะ<span class="form-required">*</span></label>
            <div class="flex items-center gap-2">
              <label class="switch"><input type="checkbox" id="role-active" checked><span class="switch-track"></span></label>
              <span class="small text-secondary" id="role-active-label">ใช้งาน</span>
            </div>
          </div>

          {{-- สรุปจำนวนที่ติ๊กไว้ อยู่ท้ายฝั่งซ้ายเพราะเป็นผลของสิ่งที่เลือกทางขวา
               ผู้ใช้จึงรู้ยอดรวมโดยไม่ต้องเลื่อนรายการยาว ๆ กลับขึ้นไปนับเอง --}}
          <div class="role-form-summary">
            <span class="role-form-summary-label">เลือกแล้ว</span>
            <span><strong class="role-form-summary-count" id="role-perm-count">0</strong>
              <span class="text-muted">จาก <span id="role-perm-total">0</span> เมนู</span></span>
          </div>
        </div>

        {{-- ไม่มีกรอบรอบตารางสิทธิ์ — เส้นคั่นระหว่างสองฝั่งบอกขอบเขตอยู่แล้ว
             กรอบซ้อนอีกชั้นทำให้ดูเป็นกล่องในกล่อง --}}
        <div class="role-form-perms">
          <div class="role-perms-head">
            <div>
              <span class="form-label">สิทธิ์การเข้าถึงเมนู<span class="form-required">*</span></span>
              <div class="form-helper">ติ๊กหมวดหลักเพื่อเลือกเมนูย่อยทั้งหมดในหมวดนั้น</div>
            </div>
            {{-- ปุ่มคุมทั้งชุด ไม่ใช่รายหมวด — เดิมมีปุ่มซ้ำอยู่ทุกหมวดจนแย่งความสนใจจากตัวเลือกจริง --}}
            <div class="role-perms-actions">
              <button type="button" class="btn btn-sm" id="role-perm-all">เลือกทั้งหมด</button>
              <button type="button" class="btn btn-sm" id="role-perm-none">ล้างทั้งหมด</button>
            </div>
          </div>
          <div id="role-permission-matrix"></div>
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
{{-- ชิปนับจำนวนกับตัวช่วยแบ่งหน้าอยู่ในไฟล์นี้ ไม่ได้อยู่ใน bundle กลางของ layout --}}
<script src="@assetv('assets/js/master-list.js')"></script>
@endpush

@push('page-script')
<script>
(function () {
  var rolesList = @json($roles);
  var menuStructure = @json($menuStructure);
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  window.TFC.renderPageHeader('role-page-header', {
    title: 'บทบาท',
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
    /* หมวดที่ไม่มีเมนูย่อยก็เป็นการ์ดเหมือนกัน ต่างแค่ไม่มีอะไรให้กาง
       ถ้าทำเป็นบรรทัดเปล่า ๆ รายการจะดูเป็นสองแบบปนกันทั้งที่เป็นของระดับเดียวกัน */
    var children = item.children || [];

    var childrenHtml = children.map(function (child) {
      return '<label class="checkbox-item perm-child">' +
        '<input type="checkbox" data-perm="' + child.key + '" data-perm-parent="' + item.key + '"><span>' + window.TFC.escapeHtml(child.label) + '</span>' +
        '</label>';
    }).join('');

    /* หมวดที่มีเมนูย่อยพับเก็บได้ ค่าเริ่มต้นคือพับไว้ — 17 เมนูกางพร้อมกันทำให้ต้องเลื่อนยาว
       ตัวเลข "x/y" บอกว่าเลือกไปกี่อันในหมวดนั้นแล้ว ไม่ต้องกางออกมานับ */
    return '<div class="perm-card" data-perm-card="' + item.key + '">' +
      '<div class="perm-card-head">' +
      '<label class="checkbox-item font-medium">' +
      '<input type="checkbox" data-perm="' + item.key + '" data-perm-category="' + item.key + '"><span>' + window.TFC.escapeHtml(item.label) + '</span>' +
      '</label>' +
      (children.length
        ? '<button type="button" class="perm-card-toggle" data-perm-expand="' + item.key + '" aria-expanded="false"' +
          ' aria-label="กาง/พับเมนูย่อยของ ' + window.TFC.escapeHtml(item.label) + '">' +
          '<span class="perm-card-count" data-perm-count="' + item.key + '"></span>' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>' +
          '</button>'
        : '<span class="perm-card-count" data-perm-count="' + item.key + '"></span>') +
      '</div>' +
      (children.length ? '<div class="perm-card-children" hidden>' + childrenHtml + '</div>' : '') +
      '</div>';
  }

  matrixEl.innerHTML = (menuStructure || []).map(categoryBlockHtml).join('');

  /* กดที่แถวหัวหมวด (ไม่ใช่ที่ช่องติ๊ก) เพื่อกาง/พับเมนูย่อย */
  matrixEl.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-perm-expand]');
    if (!toggle) return;

    var card = toggle.closest('[data-perm-card]');
    var children = card.querySelector('.perm-card-children');
    var open = children.hasAttribute('hidden');

    children.toggleAttribute('hidden', !open);
    toggle.setAttribute('aria-expanded', String(open));
    card.classList.toggle('is-open', open);
  });

  matrixEl.addEventListener('change', function (e) {
    var cb = e.target.closest('[data-perm]');
    if (!cb) return;

    var categoryKey = cb.getAttribute('data-perm-category');

    /* ติ๊กหัวหมวด = ติ๊กเมนูย่อยทั้งหมดในหมวดนั้น */
    if (categoryKey) {
      matrixEl.querySelectorAll('[data-perm-parent="' + categoryKey + '"]').forEach(function (child) {
        child.checked = cb.checked;
      });
    }

    /* ติ๊กเมนูย่อย = หัวหมวดต้องติ๊กตามโดยอัตโนมัติ ไม่งั้นจะได้สิทธิ์ลูกแต่เข้าหมวดไม่ได้ */
    var parentKey = cb.getAttribute('data-perm-parent');
    if (parentKey && cb.checked) {
      var parent = matrixEl.querySelector('[data-perm-category="' + parentKey + '"]');
      if (parent) parent.checked = true;
    }

    syncPermCounts();
  });

  document.getElementById('role-perm-all').addEventListener('click', function () {
    matrixEl.querySelectorAll('[data-perm]').forEach(function (cb) { cb.checked = true; });
    syncPermCounts();
  });

  document.getElementById('role-perm-none').addEventListener('click', function () {
    matrixEl.querySelectorAll('[data-perm]').forEach(function (cb) { cb.checked = false; });
    syncPermCounts();
  });

  /* ตัวเลขทุกจุดคำนวณจากช่องติ๊กจริงในหน้า ไม่เก็บสถานะแยกไว้อีกชุด
     สองแหล่งความจริงคือที่มาของตัวเลขไม่ตรงกับสิ่งที่เห็น */
  function syncPermCounts() {
    var all = matrixEl.querySelectorAll('[data-perm]');
    var checked = matrixEl.querySelectorAll('[data-perm]:checked');

    document.getElementById('role-perm-count').textContent = checked.length;
    document.getElementById('role-perm-total').textContent = all.length;

    (menuStructure || []).forEach(function (item) {
      var badge = matrixEl.querySelector('[data-perm-count="' + item.key + '"]');
      var card = matrixEl.querySelector('[data-perm-card="' + item.key + '"]');
      if (!badge) return;

      var kids = matrixEl.querySelectorAll('[data-perm-parent="' + item.key + '"]');
      var kidsOn = matrixEl.querySelectorAll('[data-perm-parent="' + item.key + '"]:checked');
      var self = matrixEl.querySelector('[data-perm-category="' + item.key + '"]');

      badge.textContent = kids.length ? kidsOn.length + '/' + kids.length : (self && self.checked ? 'เลือกแล้ว' : '');
      if (card) card.classList.toggle('is-checked', !!(self && self.checked));
    });
  }

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

    syncPermCounts();
  }

  syncPermCounts();

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

  var pageState = { page: 1, pageSize: 10, statusKey: '' };

  var STATUS_OPTIONS = [
    { value: 'ใช้งาน', badge: 'badge-success' },
    { value: 'ระงับใช้งาน', badge: 'badge-danger' }
  ];

  var BUCKETS = [
    { key: '', label: 'ทั้งหมด' },
    { key: 'active', label: 'ใช้งาน', match: function (r) { return r.active !== false; } },
    { key: 'inactive', label: 'ระงับใช้งาน', match: function (r) { return r.active === false; } }
  ];

  function statusOf(role) { return role.active === false ? 'ระงับใช้งาน' : 'ใช้งาน'; }

  /* "12 ส.ค. 69 | 08.30" — ย่อ พ.ศ. เหลือ 2 หลักกันบรรทัดตกในคอลัมน์แคบ */
  function updatedStamp(role) {
    if (!role.updatedDate) return '-';

    var date = window.TFC.formatThaiDate(role.updatedDate).replace(/\d{2}(\d{2})$/, '$1');
    return window.TFC.escapeHtml(role.updatedTime ? date + ' | ' + role.updatedTime : date);
  }

  function renderRoleTable() {
    var keyword = ((document.getElementById('role-search') || {}).value || '').trim().toLowerCase();
    var statusFilter = (document.getElementById('role-filter-status') || {}).value || '';

    window.TFC.renderStatusCounts('role-counts', rolesList, {
      active: pageState.statusKey,
      buckets: BUCKETS,
      onPick: function (key) {
        pageState.statusKey = key === pageState.statusKey ? '' : key;
        pageState.page = 1;
        renderRoleTable();
      }
    });

    var bucket = BUCKETS.filter(function (b) { return b.key === pageState.statusKey; })[0];

    /* ชิปด้านบนกับตัวกรองในแผงเป็นคนละชั้น ใช้ร่วมกันได้ ต้องผ่านทั้งคู่ */
    var filtered = rolesList.filter(function (r) {
      var haystack = (r.name + ' ' + (r.description || '')).toLowerCase();

      return (!keyword || haystack.indexOf(keyword) !== -1) &&
        (!bucket || !bucket.match || bucket.match(r)) &&
        (!statusFilter || statusOf(r) === statusFilter);
    });

    var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
    if (pageState.page > pageCount) pageState.page = pageCount;
    var start = (pageState.page - 1) * pageState.pageSize;
    var pageRows = filtered.slice(start, start + pageState.pageSize);

    document.getElementById('role-table-body').innerHTML = pageRows.map(function (r, i) {
      return '<tr>' +
        '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
        /* ชื่อบทบาทคลิกได้ เปิดฟอร์มแก้ไขทันที — ใช้ data-action-key เดียวกับเมนู "แก้ไข" */
        '<td><button type="button" class="cell-title-link font-medium" ' +
        'data-action-key="role-edit-' + window.TFC.escapeHtml(r.id) + '" data-open-modal="role-create-modal">' +
        window.TFC.escapeHtml(r.name) + '</button></td>' +
        '<td class="text-secondary">' + window.TFC.escapeHtml(r.description || '-') + '</td>' +
        '<td class="cell-count">' + Number(r.userCount || 0).toLocaleString('th-TH') + '</td>' +
        '<td class="cell-center nowrap">' + window.TFC.statusTextHTML({ options: STATUS_OPTIONS, value: statusOf(r) }) + '</td>' +
        /* คนแก้บรรทัดบน วันเวลาบรรทัดล่าง — โครงเดียวกับหน้าอื่นในระบบ */
        '<td class="cell-center"><div>' + window.TFC.escapeHtml(r.updatedBy || '-') + '</div>' +
        '<div class="caption text-secondary nowrap">' + updatedStamp(r) + '</div></td>' +
        '<td class="table-row-actions">' +
        window.TFC.actionMenuTrigger([
          { key: 'role-edit-' + r.id, label: 'แก้ไข', icon: 'edit', modal: 'role-create-modal', perm: 'users' },
          { key: 'role-delete-' + r.id, label: 'ลบ', icon: 'delete', modal: 'role-delete-modal', perm: 'users', danger: true }
        ]) +
        '</td></tr>';
    }).join('') ||
      '<tr class="table-empty-row"><td colspan="7">' +
      '<div class="table-empty">' +
      (rolesList.length ? 'ไม่พบข้อมูลตามเงื่อนไขที่เลือก ลองล้างคำค้นหาหรือตัวกรอง' : 'ยังไม่มีบทบาทในระบบ') +
      '</div></td></tr>';

    /* ผลรวมคิดจากรายการที่ผ่านตัวกรองทั้งหมด ไม่ใช่เฉพาะแถวในหน้านี้ */
    var sumUsers = filtered.reduce(function (acc, r) { return acc + Number(r.userCount || 0); }, 0);

    document.getElementById('role-table-foot').innerHTML =
      '<td colspan="3" id="role-foot-info"></td>' +
      '<td class="cell-count">' + sumUsers.toLocaleString('th-TH') + '</td>' +
      '<td colspan="3" id="role-foot-controls"></td>';

    window.TFC.renderPagination(null, {
      page: pageState.page,
      pageSize: pageState.pageSize,
      total: filtered.length,
      pageSizeOptions: window.TFC.pageSizeOptions(pageState.pageSize),
      infoTarget: 'role-foot-info',
      controlsTarget: 'role-foot-controls',
      onChange: function (p) { pageState.page = p; renderRoleTable(); },
      onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderRoleTable(); }
    });
  }

  /* ช่องค้นหาย้ายออกมาอยู่นอกแผงแล้ว ปุ่มนี้จึงเหลือแค่ตัวกรอง และเปลี่ยนไอคอนเป็นกรวยให้ตรงกับหน้าที่ */
  window.TFC.searchPopover('role-search-popover', {
    search: false,
    icon: 'filter',
    filters: [
      { id: 'status', inputId: 'role-filter-status', label: 'สถานะ', placeholder: 'สถานะทั้งหมด',
        options: STATUS_OPTIONS.map(function (o) { return { label: o.value }; }) }
    ],
    onSearch: function (values, done) {
      pageState.page = 1;
      renderRoleTable();
      done();
    }
  });

  /* ค้นหาแบบพิมพ์แล้วกรองเลย — หน่วง 200ms กันวาดตารางใหม่ทุกตัวอักษร
     ปุ่มกากบาทของ input[type=search] ยิง 'search' ไม่ใช่ 'input' จึงต้องดักทั้งสองอีเวนต์ */
  var searchTimer = null;
  ['input', 'search'].forEach(function (evt) {
    document.getElementById('role-search').addEventListener(evt, function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        pageState.page = 1;
        renderRoleTable();
      }, 200);
    });
  });

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
        /* แถวใหม่อยู่บนสุดของหน้าแรก ต้องเด้งกลับหน้าแรกไม่งั้นบันทึกแล้วเหมือนไม่มีอะไรเกิดขึ้น */
        rolesList.unshift(res.data);
        pageState.page = 1;
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
