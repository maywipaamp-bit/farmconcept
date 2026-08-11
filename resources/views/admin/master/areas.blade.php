@extends('layouts.admin')

@section('title', 'พื้นที่ดำเนินงาน')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">พื้นที่ดำเนินงาน</span>
  </nav>
  <div class="page-header" id="area-page-header"></div>

  {{-- โครงเดียวกับหน้ารายการกิจกรรม: pill สถานะซ้าย · ปุ่มค้นหาขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="area-counts"></div>
    <div id="area-search-popover"></div>
  </div>

  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <table class="data-table is-header-filled is-dense">
        <thead>
          <tr>
            <th class="col-no">#</th>
            <th>ชื่อพื้นที่</th>
            <th>ประเภทพื้นที่</th>
            <th>กลุ่มพื้นที่</th>
            <th>ผู้ประสานงาน</th>
            <th>จำนวนจัดกิจกรรม</th>
            <th>สถานะ</th>
            <th class="col-updated">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="area-table-body"></tbody>
      </table>
    </div>
  </div>
  <div id="area-pagination"></div>
@endsection

@section('modals')
<div class="modal-overlay" id="area-form-modal">
  <div class="modal modal-xl">
    <div class="modal-header">
      <h3 class="modal-title" id="area-form-title">เพิ่มพื้นที่ดำเนินงาน</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิดหน้าต่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="area-form">
      <div class="modal-body">
        <div class="form-row-3 mb-4">
          <div class="form-group form-col-span-2 mb-0">
            <label class="form-label" for="area-name">ชื่อพื้นที่ดำเนินงาน<span class="form-required">*</span></label>
            <input class="input" id="area-name" data-validate required maxlength="150" autocomplete="off">
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-type">ประเภทพื้นที่</label>
            <select class="select" id="area-type" data-smart-select></select>
          </div>
        </div>

        <div class="form-row-3 mb-4">
          <div class="form-group mb-0">
            <label class="form-label" for="area-group">กลุ่มพื้นที่<span class="form-required">*</span></label>
            <select class="select" id="area-group" required data-smart-select></select>
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-province">จังหวัด</label>
            <select class="select" id="area-province" data-smart-select></select>
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-district">เขต/อำเภอ</label>
            <select class="select" id="area-district" data-smart-select></select>
          </div>
        </div>

        <div class="form-row-3 mb-4">
          <div class="form-group mb-0">
            <label class="form-label" for="area-start-date">วันที่เริ่มดำเนินการ</label>
            <input type="text" class="input" id="area-start-date" data-picker="date" placeholder="เลือกวันที่">
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-end-date">สิ้นสุด</label>
            <input type="text" class="input" id="area-end-date" data-picker="date" placeholder="เลือกวันที่">
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-partner-org">ร่วมกับหน่วยงาน</label>
            <input type="hidden" id="area-partner-org" data-tags-input data-tags-placeholder="พิมพ์ชื่อหน่วยงานแล้วกด Enter">
          </div>
        </div>

        <div class="form-row-3 mb-4">
          <div class="form-group mb-0">
            <label class="form-label" for="area-coordinator">ผู้ประสานงาน<span class="form-required">*</span></label>
            <input class="input" id="area-coordinator" data-validate required maxlength="150">
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-coordinator-phone">เบอร์โทร<span class="form-required">*</span></label>
            <input class="input" type="tel" id="area-coordinator-phone" pattern="0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}" placeholder="08x-xxx-xxxx" data-validate required>
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-coordinator-position">ตำแหน่ง</label>
            <input class="input" id="area-coordinator-position" maxlength="150">
          </div>
        </div>

        <div class="form-row-3 mb-0">
          <div class="form-group form-col-span-2 mb-0">
            <label class="form-label" for="area-map-url">ลิงก์ Google Map</label>
            <input class="input" type="url" id="area-map-url" maxlength="500">
          </div>
          <div class="form-group mb-0">
            <label class="form-label" for="area-status">สถานะ<span class="form-required">*</span></label>
            <select class="select" id="area-status" required data-smart-select></select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="area-submit">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="area-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบพื้นที่</h3>
      <p class="text-secondary" id="area-delete-message"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button class="btn btn-danger" id="area-delete-confirm">ลบพื้นที่</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
<script src="@assetv('assets/js/master-list.js')"></script>
<script src="@assetv('assets/js/field-widgets.js')"></script>
{{-- ต้องมาหลัง app.js เหมือนหน้ากิจกรรม จึงประกาศใน page-script ไม่ใช่ที่นี่ --}}
<script>
window.TFC_API = window.TFC_API || {};
window.TFC_API.areas = @json(route('admin.master.areas.index'));

/* แถวชุดแรกฝังมากับหน้า หน้าจอจึงวาดตารางได้ทันทีโดยไม่ต้องรอคำขอเพิ่ม
   หลังบันทึกหรือลบ dataService จะไปเอาของจริงจากเซิร์ฟเวอร์เองตามปกติ */
window.TFC_SEED = window.TFC_SEED || {};
window.TFC_SEED.areas = @json($seedRows);

/* ตัวเลือกมาจาก config ฝั่งเซิร์ฟเวอร์ ชุดเดียวกับที่ใช้ตรวจตอนบันทึก
   ถ้าปล่อยให้หน้าจออ่านจาก mock-data.js ต่อไป วันหนึ่งสองที่จะไม่ตรงกันแล้วบันทึกไม่ผ่านโดยไม่มีเหตุผลที่เห็นได้ */
window.TFC_AREA = {
  types: @json($types),
  groups: @json($groups),
  statuses: @json($statuses)
};
</script>
@endpush

@push('page-script')
{{-- ลำดับต้องตรงกับหน้ากิจกรรม: app.js → datetime-picker.js → สคริปต์ของหน้า
     ถ้าโหลดก่อน app.js ปฏิทินจะผูก event ไม่ติดและกดเลือกวันไม่ได้ --}}
<script src="@assetv('assets/js/datetime-picker.js')"></script>
<script>
(function () {
  /* จำนวนแถวคิดจากพื้นที่ที่เหลือจริงบนจอ ไม่ใช่เลข 10 ตายตัว
     statusKey = สถานะที่เลือกจากแถบนับจำนวน ('' = ทั้งหมด) */
  var pageState = { page: 1, pageSize: 10, statusKey: '' };
  var svc = window.TFC.dataService('areas');
  var mock = window.TFC_MOCK || {};
  var CFG = window.TFC_AREA;
  var rows = [];

  function $(id) { return document.getElementById(id); }
  function rowOf(code) { return rows.filter(function (r) { return r.id === code; })[0]; }

  /* ตั้งค่าช่องวันที่ของระบบ — ค่าจริงอยู่ที่ data-iso ส่วน .value เป็นข้อความไทยที่คนอ่าน
     ต้องเรียก decorate ใหม่ ไม่งั้นช่องจะยังโชว์ค่าเดิมเพราะถูกทำเครื่องหมายว่าจัดการแล้ว */
  function setPickerDate(id, iso) {
    var el = $(id);
    if (!el) return;

    el.setAttribute('data-iso', iso || '');
    el.removeAttribute('data-dtp-ready');
    if (window.TFC.datetimePicker) window.TFC.datetimePicker.decorate(el.parentElement);
  }

  function optionsHtml(list, selected) {
    return list.map(function (v) {
      return '<option value="' + window.TFC.escapeHtml(v) + '"' + (v === selected ? ' selected' : '') + '>' + window.TFC.escapeHtml(v) + '</option>';
    }).join('');
  }

  function populateStaticSelects() {
    $('area-type').innerHTML = optionsHtml(CFG.types);
    $('area-group').innerHTML = optionsHtml(CFG.groups);
    $('area-status').innerHTML = optionsHtml(CFG.statuses);
    $('area-province').innerHTML = optionsHtml(Object.keys(mock.provinceDistricts || {}));
  }

  function populateDistricts(province, selectedDistrict) {
    var districts = (mock.provinceDistricts || {})[province] || [];
    $('area-district').innerHTML = optionsHtml(districts, selectedDistrict);
  }

  $('area-province').addEventListener('change', function () {
    populateDistricts(this.value, null);
  });

  function resetAreaForm() {
    $('area-form-title').textContent = 'เพิ่มพื้นที่ดำเนินงาน';
    $('area-form').reset();
    populateStaticSelects();
    populateDistricts($('area-province').value, null);
    setPickerDate('area-start-date', '');
    setPickerDate('area-end-date', '');
    window.TFC.setTagsValue('area-partner-org', '');
    $('area-form').setAttribute('data-editing-id', '');
  }

  function fillAreaForm(area) {
    $('area-form-title').textContent = 'แก้ไขพื้นที่ดำเนินงาน';
    populateStaticSelects();
    $('area-name').value = area.name || '';
    $('area-type').value = area.areaType || '';
    $('area-group').value = area.areaGroup || '';
    $('area-province').value = area.province || '';
    populateDistricts(area.province, area.district);
    setPickerDate('area-start-date', area.startDate);
    setPickerDate('area-end-date', area.endDate);
    window.TFC.setTagsValue('area-partner-org', area.partnerOrg);
    $('area-coordinator').value = area.coordinator || '';
    $('area-coordinator-phone').value = area.coordinatorPhone || '';
    $('area-coordinator-position').value = area.coordinatorPosition || '';
    $('area-map-url').value = area.mapUrl || '';
    $('area-status').value = area.status || '';
    $('area-form').setAttribute('data-editing-id', area.id);
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-key^="edit-"]');
    if (!trigger) return;

    var area = rowOf(trigger.getAttribute('data-action-key').replace('edit-', ''));
    if (area) fillAreaForm(area);
  });

  window.TFC.renderPageHeader('area-page-header', {
    title: 'พื้นที่ดำเนินงาน',
    actions: [
      {
        label: 'เพิ่มพื้นที่ดำเนินงาน',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        attrs: { id: 'area-add-btn', 'data-open-modal': 'area-form-modal' }
      }
    ]
  });
  $('area-add-btn').addEventListener('click', resetAreaForm);
  populateStaticSelects();
  populateDistricts($('area-province').value, null);

  $('area-form').addEventListener('submit', function (e) {
    e.preventDefault();

    var editingId = this.getAttribute('data-editing-id');
    var payload = {
      name: $('area-name').value.trim(),
      areaType: $('area-type').value,
      areaGroup: $('area-group').value,
      province: $('area-province').value,
      district: $('area-district').value,
      /* ช่องวันที่ของระบบเก็บค่าจริงไว้ที่ data-iso ไม่ใช่ .value (ซึ่งเป็นข้อความไทยที่คนอ่าน) */
      startDate: $('area-start-date').getAttribute('data-iso') || null,
      endDate: $('area-end-date').getAttribute('data-iso') || null,
      partnerOrg: $('area-partner-org').value || null,
      coordinator: $('area-coordinator').value.trim(),
      coordinatorPhone: $('area-coordinator-phone').value.trim(),
      coordinatorPosition: $('area-coordinator-position').value.trim(),
      mapUrl: $('area-map-url').value.trim() || null,
      status: $('area-status').value
    };

    var submit = $('area-submit');
    submit.disabled = true;
    submit.textContent = 'กำลังบันทึก…';

    (editingId ? svc.update(editingId, payload) : svc.create(payload))
      .then(function () {
        window.TFC.closeModal('area-form-modal');
        window.TFC.showToast(editingId ? 'บันทึกพื้นที่แล้ว' : 'เพิ่มพื้นที่แล้ว', 'success');
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
    var item = e.target.closest('[data-action-key^="area-delete-"]');
    if (!item) return;

    pendingDelete = rowOf(item.getAttribute('data-action-key').replace('area-delete-', ''));
    $('area-delete-message').textContent = pendingDelete
      ? 'ต้องการลบ "' + pendingDelete.name + '" ใช่หรือไม่ การลบนี้ย้อนกลับไม่ได้'
      : '';
  });

  $('area-delete-confirm').addEventListener('click', function () {
    if (!pendingDelete) return;

    var button = this;
    button.disabled = true;

    svc.remove(pendingDelete.id)
      .then(function () {
        window.TFC.closeModal('area-delete-modal');
        window.TFC.showToast('ลบพื้นที่แล้ว', 'success');
        pendingDelete = null;
        return renderTable();
      })
      .catch(function (err) { window.TFC.showToast(err.message, 'danger'); })
      .finally(function () { button.disabled = false; });
  });

  /* ---------- ตาราง ---------- */

  /* แถบนับจำนวนมุมซ้ายบนของตาราง — สร้างจากสถานะจริงในฐานข้อมูล ไม่ใช่รายการที่เขียนไว้ตายตัว */
  var BUCKETS = [{ key: '', label: 'ทั้งหมด' }].concat(CFG.statuses.map(function (s) {
    return { key: s, label: s, match: function (r) { return r.status === s; } };
  }));

  function matchesStatus(row) {
    if (!pageState.statusKey) return true;

    var bucket = BUCKETS.filter(function (b) { return b.key === pageState.statusKey; })[0];
    return !bucket || !bucket.match || bucket.match(row);
  }
  function renderTable() {
    return svc.list().then(function (all) {
      rows = all;

      window.TFC.renderStatusCounts('area-counts', rows, {
        active: pageState.statusKey,
        buckets: BUCKETS,
        onPick: function (key) {
          pageState.statusKey = key === pageState.statusKey ? '' : key;
          pageState.page = 1;
          renderTable();
        }
      });

      var keyword = (($('area-search') || {}).value || '').trim().toLowerCase();
      var typeFilter = (($('area-filter-type') || {}).value || '');
      var groupFilter = (($('area-filter-group') || {}).value || '');
      var statusFilter = (($('area-filter-status') || {}).value || '');

      var filtered = rows.filter(function (a) {
        return matchesStatus(a) &&
          (!keyword || a.name.toLowerCase().indexOf(keyword) !== -1) &&
          (!typeFilter || a.areaType === typeFilter) &&
          (!groupFilter || a.areaGroup === groupFilter) &&
          (!statusFilter || a.status === statusFilter);
      });

      var pageCount = Math.max(1, Math.ceil(filtered.length / pageState.pageSize));
      if (pageState.page > pageCount) pageState.page = pageCount;
      var start = (pageState.page - 1) * pageState.pageSize;
      var pageRows = filtered.slice(start, start + pageState.pageSize);

      $('area-table-body').innerHTML = pageRows.map(function (a, i) {
        return '<tr>' +
          '<td class="col-no nowrap">' + (start + i + 1) + '</td>' +
          /* ชื่อพื้นที่คลิกได้ เปิดฟอร์มแก้ไขทันที — ใช้ data-action-key="edit-<id>" ตัวเดียวกับ
             เมนู "แก้ไข" ในคอลัมน์จัดการ จึงไปเข้า handler เดิมที่มีอยู่แล้ว ไม่ต้องเขียน logic ใหม่ */
          '<td><button type="button" class="cell-title-link font-medium" ' +
          'data-action-key="edit-' + window.TFC.escapeHtml(a.id) + '" data-open-modal="area-form-modal">' +
          window.TFC.escapeHtml(a.name) + '</button>' +
          '<div class="caption text-secondary">' + window.TFC.escapeHtml(a.district || '') + (a.district ? ' ' : '') + window.TFC.escapeHtml(a.province || '') + '</div></td>' +
          '<td>' + window.TFC.escapeHtml(a.areaType || '-') + '</td>' +
          '<td>' + window.TFC.escapeHtml(a.areaGroup || '-') + '</td>' +
          /* ผู้ประสานงาน: ชื่อบรรทัดบน เบอร์โทรบรรทัดล่าง (แพทเทิร์นเดียวกับคอลัมน์ชื่อพื้นที่) */
          '<td><div>' + window.TFC.escapeHtml(a.coordinator || '-') + '</div>' +
          (a.coordinatorPhone ? '<div class="caption text-secondary">' + window.TFC.escapeHtml(a.coordinatorPhone) + '</div>' : '') + '</td>' +
          '<td>' + Number(a.activityCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td class="nowrap">' + window.TFC.statusTextHTML({ options: mock.areaStatusList, value: a.status }) + '</td>' +
          /* ย่อ พ.ศ. เหลือ 2 หลัก (2569 -> 69) กันบรรทัดตกในคอลัมน์แคบ */
          '<td><div class="cell-updated-at">' + (a.updatedAt ? window.TFC.formatThaiDate(a.updatedAt).replace(/\d{2}(\d{2})$/, '$1') : '-') + '</div></td>' +
          '<td class="table-row-actions">' +
          window.TFC.actionMenuTrigger([
            { key: 'edit-' + a.id, label: 'แก้ไข', icon: 'edit', modal: 'area-form-modal', perm: 'areas' },
            { key: 'area-delete-' + a.id, label: 'ลบพื้นที่', icon: 'delete', modal: 'area-delete-modal', perm: 'areas', danger: true }
          ]) +
          '</td></tr>';
      }).join('');

      window.TFC.renderPagination('area-pagination', {
        page: pageState.page,
        pageSize: pageState.pageSize,
        total: filtered.length,
        pageSizeOptions: window.TFC.pageSizeOptions(pageState.pageSize),
        footer: true,
        onChange: function (p) { pageState.page = p; renderTable(); },
        onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
      });
    }).catch(function (err) {
      window.TFC.showToast('โหลดข้อมูลไม่สำเร็จ: ' + err.message, 'danger');
    });
  }

  window.TFC.searchPopover('area-search-popover', {
    search: { id: 'area-search', placeholder: 'ค้นหาชื่อพื้นที่' },
    filters: [
      { id: 'type', inputId: 'area-filter-type', label: 'ประเภทพื้นที่', placeholder: 'ประเภทพื้นที่ทั้งหมด',
        options: CFG.types.map(function (v) { return { label: v }; }) },
      { id: 'group', inputId: 'area-filter-group', label: 'กลุ่มพื้นที่', placeholder: 'กลุ่มพื้นที่ทั้งหมด',
        options: CFG.groups.map(function (v) { return { label: v }; }) },
      { id: 'status', inputId: 'area-filter-status', label: 'สถานะ', placeholder: 'สถานะทั้งหมด',
        options: CFG.statuses.map(function (v) { return { label: v }; }) }
    ],
    onSearch: function (values, done) {
      pageState.page = 1;
      renderTable();
      done();
    }
  });

  /* ปุ่มส่งออกถูกเอาออกจากแถบเครื่องมือแล้ว ผูก event เฉพาะเมื่อยังมีปุ่มอยู่
     ฟังก์ชัน exportTableCsv ยังอยู่ครบ ถ้าจะเอาปุ่มกลับมาก็เพิ่ม element id เดิมได้ทันที */
  var exportBtn = $('area-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#area-table-body', 'พื้นที่ดำเนินงาน.csv');
    });
  }

  /* ต้องวัดหลัง DOM ของตารางอยู่ในหน้าแล้ว จึงคำนวณตรงนี้ ไม่ใช่ตอนประกาศ pageState */
  pageState.pageSize = window.TFC.fitPageSize('area-table-body', 52);

  renderTable();

  /* ย่อ/ขยายหน้าต่างแล้วจำนวนแถวต้องขยับตาม ไม่ใช่ค้างที่ค่าตอนเปิดหน้า
     หน่วงไว้กันการวาดตารางใหม่ทุกพิกเซลระหว่างลากขอบหน้าต่าง */
  var resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var next = window.TFC.fitPageSize('area-table-body', 52);
      if (next === pageState.pageSize) return;
      pageState.pageSize = next;
      pageState.page = 1;
      renderTable();
    }, 200);
  });
})();
</script>
@endpush
