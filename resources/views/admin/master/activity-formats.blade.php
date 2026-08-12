@extends('layouts.admin')

@section('title', 'หมวดหมู่กิจกรรม')

{{-- ตารางยืดเต็มจอ แถบแบ่งหน้าจึงติดขอบล่างเสมอ ข้อมูลล้นก็เลื่อนเฉพาะส่วนแถว --}}
@section('main-class', 'is-fill')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">หมวดหมู่กิจกรรม</span>
  </nav>
  <div class="page-header" id="fmt-page-header"></div>

  {{-- โครงเดียวกับหน้ารายการกิจกรรม: pill สถานะซ้าย · ช่องค้นหา + ปุ่มตัวกรองขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="fmt-counts"></div>
    <div class="list-filter-tools">
      {{-- ค้นหาพิมพ์แล้วกรองเลย ไม่ต้องกดปุ่ม จึงไม่มีปุ่มค้นหาข้างช่อง --}}
      <input type="search" class="input list-search-input" id="fmt-search"
             placeholder="ค้นหาชื่อหมวดหมู่" aria-label="ค้นหาหมวดหมู่กิจกรรม">
      <div id="fmt-search-popover"></div>
    </div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled is-dense">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อหมวดหมู่</th>
            <th class="cell-count">จำนวนจัดกิจกรรม</th>
            <th class="cell-center">สถานะ</th>
            <th class="col-updated cell-center">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="fmt-table-body"></tbody>
        {{-- แถบท้ายตารางเป็นแถวจริงในตาราง ผลรวมจึงตรงคอลัมน์ได้ --}}
        <tfoot><tr id="fmt-table-foot"></tr></tfoot>
      </table>
    </div>
  </div>
@endsection

@section('modals')
<div class="modal-overlay" id="fmt-form-modal">
  <div class="modal activity-format-form-modal">
    <div class="modal-header">
      <h3 class="modal-title" id="fmt-form-title">เพิ่มหมวดหมู่กิจกรรม</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="fmt-form">
      <div class="modal-body">
        <div class="activity-format-fields">
          <div class="activity-format-main-fields">
            <div class="form-group mb-0">
              <label class="form-label" for="fmt-name">ชื่อหมวดหมู่กิจกรรม<span class="form-required">*</span></label>
              <input class="input" id="fmt-name" data-validate required maxlength="60" autocomplete="off">
            </div>
            <div class="form-group mb-0">
              <label class="form-label" for="fmt-icon-trigger">ไอคอนหมวดหมู่<span class="form-required">*</span></label>
              <input type="hidden" id="fmt-icon">
              <button type="button" class="input activity-icon-trigger" id="fmt-icon-trigger"
                      aria-haspopup="listbox" aria-expanded="false">
                <span class="activity-icon-trigger-value" id="fmt-icon-trigger-value"></span>
                <svg class="activity-icon-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
              </button>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="fmt-active">สถานะ<span class="form-required">*</span></label>
            <div class="flex items-center gap-2" style="height:42px;">
              <label class="switch"><input type="checkbox" id="fmt-active" checked><span class="switch-track"></span></label>
              <span class="small text-secondary" id="fmt-active-label">ใช้งาน</span>
            </div>
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
<script src="@assetv('assets/js/activity-module.js')"></script>
<script src="@assetv('assets/js/master-list.js')"></script>
<script>
window.TFC_API = window.TFC_API || {};
window.TFC_API.activityFormats = @json(route('admin.master.activity-formats.index'));

/* แถวชุดแรกฝังมากับหน้า หน้าจอจึงวาดตารางได้ทันทีโดยไม่ต้องรอคำขอเพิ่ม
   หลังบันทึกหรือลบ dataService จะไปเอาของจริงจากเซิร์ฟเวอร์เองตามปกติ */
window.TFC_SEED = window.TFC_SEED || {};
window.TFC_SEED.activityFormats = @json($seedRows);
</script>
@endpush

@push('page-script')
<script>
(function () {
  /* จำนวนแถวคิดจากพื้นที่ที่เหลือจริงบนจอ ไม่ใช่เลข 10 ตายตัว
     statusKey = สถานะที่เลือกจากแถบนับจำนวน ('' = ทั้งหมด) */
  var pageState = { page: 1, pageSize: 10, statusKey: '' };
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

  var iconInput = $('fmt-icon');
  var iconTrigger = $('fmt-icon-trigger');
  var iconTriggerValue = $('fmt-icon-trigger-value');
  var iconPanel = document.createElement('div');
  iconPanel.className = 'activity-icon-panel';
  iconPanel.id = 'fmt-icon-panel';
  iconPanel.setAttribute('role', 'listbox');
  iconPanel.hidden = true;
  iconPanel.innerHTML = iconList.map(function (ic) {
    return '<button type="button" class="activity-icon-option" role="option" aria-selected="false"' +
      ' data-icon="' + window.TFC.escapeHtml(ic.value) + '">' +
      '<span class="activity-icon-option-image">' + iconSvg(ic.value) + '</span>' +
      '<span>' + window.TFC.escapeHtml(ic.label) + '</span></button>';
  }).join('');
  document.body.appendChild(iconPanel);

  function selectedIconData(value) {
    return iconList.filter(function (ic) { return ic.value === value; })[0] || iconList[0];
  }

  function setSelectedIcon(value) {
    selectedIcon = value || defaultIcon;
    iconInput.value = selectedIcon;
    var selected = selectedIconData(selectedIcon);
    iconTriggerValue.innerHTML = selected
      ? '<span class="activity-icon-option-image">' + iconSvg(selectedIcon) + '</span><span>' + window.TFC.escapeHtml(selected.label) + '</span>'
      : '';
    Array.prototype.forEach.call(iconPanel.querySelectorAll('.activity-icon-option'), function (option) {
      var active = option.getAttribute('data-icon') === selectedIcon;
      option.classList.toggle('is-active', active);
      option.setAttribute('aria-selected', active ? 'true' : 'false');
    });
  }

  function closeIconPanel() {
    iconPanel.hidden = true;
    iconTrigger.setAttribute('aria-expanded', 'false');
  }

  function openIconPanel() {
    var rect = iconTrigger.getBoundingClientRect();
    iconPanel.style.left = Math.round(rect.left) + 'px';
    iconPanel.style.top = Math.round(rect.bottom + 6) + 'px';
    iconPanel.style.width = Math.max(296, Math.round(rect.width)) + 'px';
    iconPanel.hidden = false;
    iconTrigger.setAttribute('aria-expanded', 'true');
  }

  iconTrigger.addEventListener('click', function () {
    if (iconPanel.hidden) openIconPanel(); else closeIconPanel();
  });

  iconPanel.addEventListener('click', function (e) {
    var option = e.target.closest('.activity-icon-option');
    if (!option) return;
    setSelectedIcon(option.getAttribute('data-icon'));
    closeIconPanel();
    iconTrigger.focus();
  });

  document.addEventListener('click', function (e) {
    if (iconPanel.hidden || iconTrigger.contains(e.target) || iconPanel.contains(e.target)) return;
    closeIconPanel();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !iconPanel.hidden) {
      closeIconPanel();
      iconTrigger.focus();
    }
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
        /* แถวที่เพิ่งเพิ่มอยู่บนสุดของหน้าแรก ต้องเด้งกลับหน้าแรกไม่งั้นบันทึกแล้วเหมือนไม่มีอะไรเกิดขึ้น
           การแก้ไขไม่แตะเลขหน้า ผู้ใช้จึงยังอยู่หน้าเดิมที่กำลังไล่ดูอยู่ */
        if (!editingId) pageState.page = 1;
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

    var row = rowOf(item.getAttribute('data-action-key').replace('fmt-delete-', ''));
    pendingDelete = row && window.TFC.prepareMasterDelete({
      modalId: 'fmt-delete-modal', messageId: 'fmt-delete-message', confirmId: 'fmt-delete-confirm',
      name: row.name, usageCount: row.deleteUsageCount,
      confirmMessage: 'ต้องการลบ "' + row.name + '" ใช่หรือไม่ การลบนี้ย้อนกลับไม่ได้'
    }) ? row : null;
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
  /* "12 ส.ค. 69 | 08.30" — ย่อ พ.ศ. เหลือ 2 หลักกันบรรทัดตกในคอลัมน์แคบ */
  function updatedStamp(row) {
    if (!row.updatedAt) return '-';

    var date = window.TFC.formatThaiDate(row.updatedAt).replace(/\d{2}(\d{2})$/, '$1');
    return window.TFC.escapeHtml(row.updatedTime ? date + ' | ' + row.updatedTime : date);
  }

  function renderTable() {
    return svc.list().then(function (all) {
      rows = all;

      window.TFC.renderStatusCounts('fmt-counts', rows, {
        active: pageState.statusKey,
        buckets: BUCKETS,
        onPick: function (key) {
          pageState.statusKey = key === pageState.statusKey ? '' : key;
          pageState.page = 1;
          renderTable();
        }
      });

      var keyword = (($('fmt-search') || {}).value || '').trim().toLowerCase();
      var statusFilter = (($('fmt-filter-status') || {}).value || '');

      /* ชิปด้านบนกับตัวกรองในแผงเป็นคนละชั้น ใช้ร่วมกันได้ ต้องผ่านทั้งคู่ */
      var filtered = rows.filter(function (f) {
        var statusLabel = f.active === false ? 'ไม่ใช้งาน' : 'ใช้งาน';

        return matchesStatus(f) &&
          (!keyword || f.name.toLowerCase().indexOf(keyword) !== -1) &&
          (!statusFilter || statusLabel === statusFilter);
      });

      var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
      if (pageState.page > pageCount) pageState.page = pageCount;
      var start = (pageState.page - 1) * pageState.pageSize;
      var pageRows = filtered.slice(start, start + pageState.pageSize);

      $('fmt-table-body').innerHTML = pageRows.map(function (f, i) {
        return '<tr>' +
          '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
          '<td><span class="master-avatar-cell"><span class="master-avatar">' + iconSvg(f.icon) + '</span>' +
          '<button type="button" class="cell-title-link font-medium" data-action-key="fmt-edit-' + window.TFC.escapeHtml(f.id) + '" data-open-modal="fmt-form-modal">' +
          window.TFC.escapeHtml(f.name) + '</button></span></td>' +
          '<td class="cell-count">' + Number(f.activityCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td class="nowrap cell-center">' + window.TFC.statusTextHTML({ options: mock.masterActiveStatuses, value: f.active === false ? 'ไม่ใช้งาน' : 'ใช้งาน' }) + '</td>' +
          /* คนแก้บรรทัดบน วันเวลาบรรทัดล่าง — โครงเดียวกับหน้าพื้นที่ดำเนินงาน */
          '<td class="cell-center"><div>' + window.TFC.escapeHtml(f.updatedBy || '-') + '</div>' +
          '<div class="caption text-secondary nowrap">' + updatedStamp(f) + '</div></td>' +
          '<td class="table-row-actions">' +
          window.TFC.actionMenuTrigger([
            { key: 'fmt-edit-' + f.id, label: 'แก้ไข', icon: 'edit', modal: 'fmt-form-modal', perm: 'master_data' },
            window.TFC.masterDeleteAction({ key: 'fmt-delete-' + f.id, label: 'ลบหมวดหมู่กิจกรรม', modal: 'fmt-delete-modal', perm: 'master_data', usageCount: f.deleteUsageCount })
          ]) +
          '</td></tr>';
      }).join('');

      /* ผลรวมคิดจากรายการที่ผ่านตัวกรองทั้งหมด ไม่ใช่เฉพาะแถวในหน้านี้
         ไม่งั้นตัวเลขจะเปลี่ยนไปมาทุกครั้งที่พลิกหน้า ทั้งที่ข้อมูลชุดเดิม */
      var sum_activityCount = filtered.reduce(function (acc, row) { return acc + Number(row.activityCount || 0); }, 0);
      $('fmt-table-foot').innerHTML =
        '<td colspan="2" id="fmt-foot-info"></td>' +
        '<td class="cell-count">' + sum_activityCount.toLocaleString('th-TH') + '</td>' +
        '<td colspan="3" id="fmt-foot-controls"></td>';

      window.TFC.renderPagination(null, {
        page: pageState.page,
        pageSize: pageState.pageSize,
        total: filtered.length,
        pageSizeOptions: window.TFC.pageSizeOptions(pageState.pageSize),
        infoTarget: 'fmt-foot-info',
        controlsTarget: 'fmt-foot-controls',
        onChange: function (p) { pageState.page = p; renderTable(); },
        onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
      });
    }).catch(function (err) {
      window.TFC.showToast('โหลดข้อมูลไม่สำเร็จ: ' + err.message, 'danger');
    });
  }

  /* ช่องค้นหาย้ายออกมาอยู่นอกแผงแล้ว ปุ่มนี้จึงเหลือแค่ตัวกรอง และเปลี่ยนไอคอนเป็นกรวยให้ตรงกับหน้าที่ */
  window.TFC.searchPopover('fmt-search-popover', {
    search: false,
    icon: 'filter',
    filters: [
      { id: 'status', inputId: 'fmt-filter-status', label: 'สถานะ', placeholder: 'สถานะทั้งหมด',
        options: (mock.masterActiveStatuses || []).map(function (o) { return { label: o.value }; }) }
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
    $('fmt-search').addEventListener(evt, function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        pageState.page = 1;
        renderTable();
      }, 200);
    });
  });

  /* ปุ่มส่งออกถูกเอาออกจากแถบเครื่องมือแล้ว ผูก event เฉพาะเมื่อยังมีปุ่มอยู่
     ฟังก์ชัน exportTableCsv ยังอยู่ครบ ถ้าจะเอาปุ่มกลับมาก็เพิ่ม element id เดิมได้ทันที */
  var exportBtn = $('fmt-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#fmt-table-body', 'หมวดหมู่กิจกรรม.csv');
    });
  }

  /* ต้องวัดหลัง DOM ของตารางอยู่ในหน้าแล้ว จึงคำนวณตรงนี้ ไม่ใช่ตอนประกาศ pageState */
  pageState.pageSize = window.TFC.fitPageSize('fmt-table-body', 52);

  renderTable();

  /* ย่อ/ขยายหน้าต่างแล้วจำนวนแถวต้องขยับตาม ไม่ใช่ค้างที่ค่าตอนเปิดหน้า
     หน่วงไว้กันการวาดตารางใหม่ทุกพิกเซลระหว่างลากขอบหน้าต่าง */
  var resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var next = window.TFC.fitPageSize('fmt-table-body', 52);
      if (next === pageState.pageSize) return;
      pageState.pageSize = next;
      pageState.page = 1;
      renderTable();
    }, 200);
  });
})();
</script>
@endpush
