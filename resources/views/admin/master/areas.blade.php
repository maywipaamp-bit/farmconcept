@extends('layouts.admin')

@section('title', 'พื้นที่ดำเนินงาน')

{{-- ตารางยืดเต็มจอ แถบแบ่งหน้าจึงติดขอบล่างเสมอ ข้อมูลล้นก็เลื่อนเฉพาะส่วนแถว --}}
@section('main-class', 'is-fill')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">พื้นที่ดำเนินงาน</span>
  </nav>
  <div class="page-header" id="area-page-header"></div>

  {{-- โครงเดียวกับหน้ารายการกิจกรรม: pill สถานะซ้าย · ช่องค้นหา + ปุ่มตัวกรองขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="area-counts"></div>
    <div class="list-filter-tools">
      {{-- ค้นหาพิมพ์แล้วกรองเลย ไม่ต้องกดปุ่ม จึงไม่มีปุ่มค้นหาข้างช่อง --}}
      <input type="search" class="input list-search-input" id="area-search"
             placeholder="ค้นหาชื่อพื้นที่ ผู้ประสานงาน" aria-label="ค้นหาพื้นที่ดำเนินงาน">
      <div id="area-search-popover"></div>
    </div>
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
            <th class="cell-count">จำนวนจัดกิจกรรม</th>
            <th class="cell-center">สถานะ</th>
            <th class="col-updated cell-center">ปรับปรุงล่าสุด</th>
            <th class="col-actions">จัดการ</th>
          </tr>
        </thead>
        <tbody id="area-table-body"></tbody>
        {{-- แถบท้ายตารางเป็นแถวจริงในตาราง ผลรวมจึงตรงคอลัมน์ได้ --}}
        <tfoot><tr id="area-table-foot"></tr></tfoot>
      </table>
    </div>
  </div>
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
            <label class="form-label" for="area-type">ประเภทพื้นที่<span class="form-required">*</span></label>
            <select class="select" id="area-type" required data-smart-select></select>
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
            {{-- เลือกจากหน่วยงานที่มีอยู่ได้ หรือพิมพ์ชื่อใหม่แล้วระบบสร้างให้ --}}
            <input type="hidden" id="area-partner-org" data-tags-input
                   data-tags-placeholder="พิมพ์ชื่อหน่วยงานแล้วกด Enter"
                   data-tags-options="{{ $partnerOrgs->toJson(JSON_UNESCAPED_UNICODE) }}">
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

/* ตัวเลือกทั้งหมดมาจากฐานข้อมูล ชุดเดียวกับที่เซิร์ฟเวอร์ใช้ตรวจตอนบันทึก
   จังหวัด/อำเภอเดิมอ่านจาก mock-data.js ซึ่งเป็นคนละแหล่งกับตาราง mst_districts */
window.TFC_AREA = {
  types: @json($types),
  groups: @json($groups),
  statuses: @json($statuses),
  districts: @json($districts),
  partnerOrgs: @json($partnerOrgs)
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

  /* ตัวเลือกที่ค่าเป็น id — value คือ id ส่วนข้อความที่เห็นคือ label */
  function idOptionsHtml(list, selectedId, blankLabel) {
    var head = blankLabel ? '<option value="">' + window.TFC.escapeHtml(blankLabel) + '</option>' : '';

    return head + list.map(function (o) {
      return '<option value="' + o.id + '"' + (String(o.id) === String(selectedId) ? ' selected' : '') + '>' +
        window.TFC.escapeHtml(o.label || o.name) + '</option>';
    }).join('');
  }

  /* ตัวเลือกที่ค่าเป็นข้อความ (สถานะ · จังหวัด) */
  function textOptionsHtml(list, selected) {
    return list.map(function (v) {
      return '<option value="' + window.TFC.escapeHtml(v) + '"' + (v === selected ? ' selected' : '') + '>' + window.TFC.escapeHtml(v) + '</option>';
    }).join('');
  }

  function populateStaticSelects() {
    $('area-type').innerHTML = idOptionsHtml(CFG.types, '');
    $('area-group').innerHTML = idOptionsHtml(CFG.groups, '');
    $('area-status').innerHTML = textOptionsHtml(CFG.statuses);
    $('area-province').innerHTML = textOptionsHtml(Object.keys(CFG.districts));
  }

  function populateDistricts(province, selectedDistrictId) {
    $('area-district').innerHTML = idOptionsHtml(CFG.districts[province] || [], selectedDistrictId);

    /* ค่าที่เก็บเป็น id ทำให้ป้ายที่ค้างอยู่ผิดแบบมองไม่ออก — ต้องซิงก์ป้ายทุกครั้งที่เปลี่ยนรายการ */
    if (window.TFC.refreshSmartSelects) window.TFC.refreshSmartSelects($('area-form'));
  }

  $('area-province').addEventListener('change', function () {
    populateDistricts(this.value, null);
  });

  /* วันที่สิ้นสุดต้องไม่มาก่อนวันที่เริ่ม — ปิดวันก่อนหน้าในปฏิทินไปเลย
     ห้ามอย่างเดียวตอนกดบันทึกไม่พอ ผู้ใช้เลือกไปแล้วค่อยรู้ว่าเลือกไม่ได้ */
  function syncEndDateLimit() {
    var start = $('area-start-date').getAttribute('data-iso') || '';
    var end = $('area-end-date');

    if (start) end.setAttribute('data-min-iso', start);
    else end.removeAttribute('data-min-iso');

    /* เลือกวันสิ้นสุดไว้ก่อน แล้วค่อยเลื่อนวันเริ่มไปหลังจากนั้น — ค่าที่ค้างอยู่ใช้ไม่ได้แล้ว */
    if (start && end.getAttribute('data-iso') && end.getAttribute('data-iso') < start) {
      setPickerDate('area-end-date', '');
      window.TFC.showToast('วันที่สิ้นสุดอยู่ก่อนวันที่เริ่มดำเนินการ ระบบล้างค่าให้แล้ว', 'warning');
    }
  }

  $('area-start-date').addEventListener('change', syncEndDateLimit);

  function resetAreaForm() {
    $('area-form-title').textContent = 'เพิ่มพื้นที่ดำเนินงาน';
    $('area-form').reset();
    populateStaticSelects();
    populateDistricts($('area-province').value, null);
    setPickerDate('area-start-date', '');
    setPickerDate('area-end-date', '');
    syncEndDateLimit();
    window.TFC.setTagsValue('area-partner-org', '');
    $('area-form').setAttribute('data-editing-id', '');
  }

  function fillAreaForm(area) {
    $('area-form-title').textContent = 'แก้ไขพื้นที่ดำเนินงาน';
    populateStaticSelects();
    $('area-name').value = area.name || '';
    $('area-type').value = area.areaTypeId || '';
    $('area-group').value = area.areaGroupId || '';
    $('area-province').value = area.province || '';
    populateDistricts(area.province, area.districtId);
    setPickerDate('area-start-date', area.startDate);
    setPickerDate('area-end-date', area.endDate);
    syncEndDateLimit();
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
      /* ส่ง id ไม่ใช่ชื่อ — จังหวัดไม่ต้องส่ง เพราะ district_id บอกจังหวัดอยู่แล้ว */
      areaTypeId: $('area-type').value,
      areaGroupId: $('area-group').value,
      districtId: $('area-district').value,
      /* ช่องวันที่ของระบบเก็บค่าจริงไว้ที่ data-iso ไม่ใช่ .value (ซึ่งเป็นข้อความไทยที่คนอ่าน) */
      startDate: $('area-start-date').getAttribute('data-iso') || null,
      endDate: $('area-end-date').getAttribute('data-iso') || null,
      /* tags input เก็บเป็นข้อความคั่นจุลภาค — แยกเป็นรายชื่อก่อนส่ง */
      partnerOrgs: ($('area-partner-org').value || '').split(',').map(function (v) { return v.trim(); }).filter(Boolean),
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

  /* เขต/อำเภอกดแล้วเปิดแผนที่ของพื้นที่นั้น — พื้นที่ที่ยังไม่ได้ใส่ลิงก์เป็นข้อความธรรมดา
     ไม่ทำปุ่มหรือไอคอนเพิ่ม หน้าตาตอนไม่ได้ชี้เมาส์จึงเหมือนเดิมทุกประการ

     ยอมรับเฉพาะ http/https — ค่าในฐานข้อมูลผ่าน validate url มาแล้ว แต่ข้อมูลเก่าที่ใส่ไว้ก่อนมีกฎนี้
     อาจเป็นอย่างอื่น ถ้าปล่อยผ่านจะกลายเป็นช่องให้ยิง javascript: ผ่านช่องกรอกได้ */
  function mapLink(a) {
    var text = window.TFC.escapeHtml(a.district || '');
    if (!text || !/^https?:\/\//i.test(a.mapUrl || '')) return text;

    return '<a class="cell-link-plain" href="' + window.TFC.escapeHtml(a.mapUrl) + '"' +
      ' target="_blank" rel="noopener noreferrer"' +
      ' aria-label="เปิดแผนที่ของ ' + window.TFC.escapeHtml(a.name) + '">' + text + '</a>';
  }

  /* เบอร์โทรกดแล้วโทรออก — href ใช้เฉพาะตัวเลข ส่วนที่แสดงคงขีดคั่นไว้ให้อ่านง่ายเหมือนเดิม */
  function phoneLink(phone) {
    var digits = String(phone).replace(/[^\d+]/g, '');
    var text = window.TFC.escapeHtml(phone);
    if (!digits) return text;

    return '<a class="cell-link-plain" href="tel:' + window.TFC.escapeHtml(digits) + '"' +
      ' aria-label="โทรหา ' + text + '">' + text + '</a>';
  }

  /* "12 ส.ค. 69 | 08.30" — ย่อ พ.ศ. เหลือ 2 หลักกันบรรทัดตกในคอลัมน์แคบ */
  function updatedStamp(a) {
    if (!a.updatedAt) return '-';

    var date = window.TFC.formatThaiDate(a.updatedAt).replace(/\d{2}(\d{2})$/, '$1');
    return window.TFC.escapeHtml(a.updatedTime ? date + ' | ' + a.updatedTime : date);
  }

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

      /* ค้นหาครอบคลุมสิ่งที่คนจำได้จริง ไม่ใช่แค่ชื่อพื้นที่
         ตัวกรองเทียบด้วย id ไม่ใช่ชื่อ — ชื่อเปลี่ยนได้ ถ้าเทียบชื่อจะกรองไม่เจอทันทีที่มีคนแก้คำ */
      var filtered = rows.filter(function (a) {
        var haystack = [a.name, a.coordinator, a.partnerOrg].join(' ').toLowerCase();

        return matchesStatus(a) &&
          (!keyword || haystack.indexOf(keyword) !== -1) &&
          (!typeFilter || String(a.areaTypeId) === typeFilter) &&
          (!groupFilter || String(a.areaGroupId) === groupFilter) &&
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
          /* บรรทัดรองแสดงแค่เขต/อำเภอ — จังหวัดซ้ำกันแทบทุกแถว ใส่ไปก็ไม่ได้ช่วยแยกแยะ */
          '<div class="caption text-secondary">' + mapLink(a) + '</div></td>' +
          '<td>' + window.TFC.escapeHtml(a.areaType || '-') + '</td>' +
          '<td>' + window.TFC.escapeHtml(a.areaGroup || '-') + '</td>' +
          /* ผู้ประสานงาน: ชื่อบรรทัดบน เบอร์โทรบรรทัดล่าง (แพทเทิร์นเดียวกับคอลัมน์ชื่อพื้นที่) */
          '<td><div>' + window.TFC.escapeHtml(a.coordinator || '-') + '</div>' +
          (a.coordinatorPhone ? '<div class="caption text-secondary">' + phoneLink(a.coordinatorPhone) + '</div>' : '') + '</td>' +
          '<td class="cell-count">' + Number(a.activityCount || 0).toLocaleString('th-TH') + '</td>' +
          '<td class="nowrap cell-center">' + window.TFC.statusTextHTML({ options: mock.areaStatusList, value: a.status }) + '</td>' +
          /* คนแก้บรรทัดบน วันเวลาบรรทัดล่าง — โครงเดียวกับคอลัมน์ผู้ประสานงาน */
          '<td class="cell-center"><div>' + window.TFC.escapeHtml(a.updatedBy || '-') + '</div>' +
          '<div class="caption text-secondary nowrap">' + updatedStamp(a) + '</div></td>' +
          '<td class="table-row-actions">' +
          window.TFC.actionMenuTrigger([
            { key: 'edit-' + a.id, label: 'แก้ไข', icon: 'edit', modal: 'area-form-modal', perm: 'areas' },
            { key: 'area-delete-' + a.id, label: 'ลบพื้นที่', icon: 'delete', modal: 'area-delete-modal', perm: 'areas', danger: true }
          ]) +
          '</td></tr>';
      }).join('') ||
        /* ไม่มีข้อมูลต้องบอกให้รู้ ไม่ใช่ปล่อยตารางว่างจนแยกไม่ออกว่ากรองไม่เจอหรือยังโหลดไม่เสร็จ
           colspan ต้องเท่าจำนวนหัวคอลัมน์ ไม่งั้นเส้นคั่นจะขาดกลางตาราง */
        '<tr class="table-empty-row"><td colspan="9">' +
        '<div class="table-empty">' +
        (rows.length ? 'ไม่พบข้อมูลตามเงื่อนไขที่เลือก ลองล้างคำค้นหาหรือตัวกรอง' : 'ยังไม่มีพื้นที่ดำเนินงานในระบบ') +
        '</div></td></tr>';

      /* ผลรวมคิดจากรายการที่ผ่านตัวกรองทั้งหมด ไม่ใช่เฉพาะแถวในหน้านี้
         ไม่งั้นตัวเลขจะเปลี่ยนไปมาทุกครั้งที่พลิกหน้า ทั้งที่ข้อมูลชุดเดิม */
      var sum_activityCount = filtered.reduce(function (acc, row) { return acc + Number(row.activityCount || 0); }, 0);
      $('area-table-foot').innerHTML =
        '<td colspan="5" id="area-foot-info"></td>' +
        '<td class="cell-count">' + sum_activityCount.toLocaleString('th-TH') + '</td>' +
        '<td colspan="3" id="area-foot-controls"></td>';

      window.TFC.renderPagination(null, {
        page: pageState.page,
        pageSize: pageState.pageSize,
        total: filtered.length,
        pageSizeOptions: window.TFC.pageSizeOptions(pageState.pageSize),
        infoTarget: 'area-foot-info',
        controlsTarget: 'area-foot-controls',
        onChange: function (p) { pageState.page = p; renderTable(); },
        onPageSizeChange: function (size) { pageState.pageSize = size; pageState.page = 1; renderTable(); }
      });
    }).catch(function (err) {
      window.TFC.showToast('โหลดข้อมูลไม่สำเร็จ: ' + err.message, 'danger');
    });
  }

  /* ช่องค้นหาย้ายออกมาอยู่นอกแผงแล้ว ปุ่มนี้จึงเหลือแค่ตัวกรอง และเปลี่ยนไอคอนเป็นกรวยให้ตรงกับหน้าที่
     ตัวเลือกส่ง value เป็น id ให้ตรงกับที่ฐานข้อมูลเก็บ ส่วน label คือชื่อที่ผู้ใช้เห็น */
  window.TFC.searchPopover('area-search-popover', {
    search: false,
    icon: 'filter',
    filters: [
      { id: 'type', inputId: 'area-filter-type', label: 'ประเภทพื้นที่', placeholder: 'ประเภทพื้นที่ทั้งหมด',
        options: CFG.types.map(function (o) { return { value: o.id, label: o.label }; }) },
      { id: 'group', inputId: 'area-filter-group', label: 'กลุ่มพื้นที่', placeholder: 'กลุ่มพื้นที่ทั้งหมด',
        options: CFG.groups.map(function (o) { return { value: o.id, label: o.label }; }) },
      { id: 'status', inputId: 'area-filter-status', label: 'สถานะ', placeholder: 'สถานะทั้งหมด',
        options: CFG.statuses.map(function (v) { return { label: v }; }) }
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
    $('area-search').addEventListener(evt, function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        pageState.page = 1;
        renderTable();
      }, 200);
    });
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
