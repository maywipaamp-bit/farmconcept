@extends('layouts.admin')

@section('title', 'กลุ่มตัวอย่าง')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span class="is-current">กลุ่มตัวอย่าง</span>
  </nav>

  <div class="co-header">
    <h1 class="co-title">กลุ่มตัวอย่าง</h1>
    <div class="co-header-actions">
      <button type="button" class="btn btn-outline" id="co-export">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
        ส่งออก Excel
      </button>
      <button type="button" class="btn btn-primary" data-open-modal="co-add-modal">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        เพิ่มกลุ่มตัวอย่าง
      </button>
    </div>
  </div>

  <div class="co-stats" id="co-stats"></div>

  <div class="co-filter-bar">
    <div class="co-tabs" id="co-tabs" role="tablist"></div>
    <div class="co-search" id="co-search">
      <button type="button" class="co-search-btn" id="co-search-btn" aria-expanded="false" aria-label="ค้นหาและกรอง">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>
      </button>
      <div class="co-search-panel" id="co-search-panel" hidden>
        <span class="co-search-arrow"></span>
        <label class="co-field">
          <span class="co-field-label">คำค้นหา</span>
          <input type="text" class="input" id="co-q" placeholder="ชื่อ รหัสบุคคล หรือเบอร์โทร" autocomplete="off">
        </label>
        <label class="co-field">
          <span class="co-field-label">รอบถัดไป</span>
          <select class="select" id="co-round"></select>
        </label>
        <label class="co-field">
          <span class="co-field-label">พื้นที่</span>
          <select class="select" id="co-area"></select>
        </label>
        <div class="co-search-foot">
          <button type="button" class="co-link" id="co-clear">ล้างค่า</button>
          <button type="button" class="btn btn-primary btn-sm" id="co-apply">ดูผลลัพธ์</button>
        </div>
      </div>
    </div>
  </div>

  <div class="card co-table-card">
    <div class="co-legend" id="co-legend"></div>
    <div class="co-table-scroll">
      <div class="co-table">
        <div class="co-tr co-th">
          <div>ชื่อ / รหัส</div>
          <div>พื้นที่</div>
          <div>กลุ่มเป้าหมาย</div>
          <div class="text-center">ก่อน</div>
          <div class="text-center">3 เดือน</div>
          <div class="text-center">6 เดือน</div>
          <div class="text-center">12 เดือน</div>
          <div>รอบถัดไป</div>
          <div>สถานะ</div>
          <div></div>
        </div>
        <div id="co-rows"></div>
      </div>
    </div>
    <div class="co-foot" id="co-foot"></div>
  </div>
@endsection

@section('modals')
<!-- เพิ่มกลุ่มตัวอย่าง -->
<div class="modal-overlay" id="co-add-modal">
  <div class="modal co-add">
    <div class="modal-header">
      <h3 class="modal-title">เพิ่มกลุ่มตัวอย่าง</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="co-add-form">
      <div class="modal-body">
        <div class="co-form-grid" id="co-form-grid"></div>

        <div class="co-block">
          <label class="form-label">รอบการติดตาม<span class="form-required">*</span></label>
          <div class="co-chips" id="co-round-chips"></div>
          <div class="co-due-table" id="co-due-table"></div>
        </div>

        <div class="co-block">
          <label class="co-consent">
            <input type="checkbox" id="co-consent">
            <span>ได้รับความยินยอมในการเก็บข้อมูลแล้ว<span class="form-required">*</span></span>
          </label>
          <label class="co-field" id="co-consent-file" hidden>
            <span class="co-field-label">แนบเอกสารความยินยอม</span>
            <input type="file" class="input" id="co-file" accept=".pdf,.jpg,.jpeg,.png">
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="co-save" disabled>บันทึก</button>
      </div>
    </form>
  </div>
</div>

<!-- ยืนยันหลังบันทึก -->
<div class="modal-overlay" id="co-saved-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-success mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
      </span>
      <h3 class="modal-title mb-3">เพิ่มกลุ่มตัวอย่างแล้ว</h3>
      <p class="text-secondary" id="co-saved-name"></p>
      <div class="co-link-box">
        <div class="co-link-text">
          <span class="co-link-label">ลิงก์ทำแบบประเมินก่อนเข้าร่วม</span>
          <span class="co-link-url" id="co-saved-link"></span>
        </div>
        <button type="button" class="btn btn-outline btn-sm" id="co-saved-copy">คัดลอกลิงก์</button>
      </div>

      <div class="co-link-box">
        <div class="co-link-text">
          <span class="co-link-label">ลิงก์ผูก LINE (แจ้งเตือนอัตโนมัติ)</span>
          <span class="co-link-url" id="co-bind-link"></span>
        </div>
        <button type="button" class="btn btn-outline btn-sm" id="co-bind-copy">คัดลอกลิงก์</button>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-primary" data-close-modal>เสร็จสิ้น</button>
    </div>
  </div>
</div>
@endsection

{{-- ต้องการเฉพาะ TFC.exportTableCsv ของปุ่มส่งออก
     ส่วน cohort-data.js / followup-template-service.js เป็นชั้นข้อมูลของต้นแบบ
     หน้านี้อ่านข้อมูลจริงจากเซิร์ฟเวอร์แล้ว จึงไม่โหลดสองไฟล์นั้นอีก --}}
@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
<script>
(function () {
  var membersList = @json($members);
  var areasList = @json($areas->pluck('name'));
  var templatesList = @json($templates->pluck('name'));
  /* ตัวเลือกทุกช่องของฟอร์มมาจาก master data ทั้งหมด ไม่มีรายการไหนเขียนตายไว้ในหน้านี้ */
  var lookups = @json($lookups);
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var TABS = ['ทั้งหมด', 'ต้องติดตามรอบนี้', 'เกินกำหนด', 'ติดตามครบ', 'หลุดการติดตาม'];
  var PAGE_SIZES = [10, 20, 50];
  var state = { tab: 'ทั้งหมด', q: '', round: 'ทุกรอบ', area: 'ทุกพื้นที่', searchOpen: false, page: 1, pageSize: PAGE_SIZES[0] };
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  function renderStats() {
    var dueNow = membersList.filter(function (m) { return (m.rounds || []).some(function (r) { return r.state === 'รอติดตาม'; }); }).length;
    var overdue = membersList.filter(function (m) { return (m.rounds || []).some(function (r) { return r.state === 'เกินกำหนด'; }); }).length;
    var running = membersList.filter(function (m) { return m.status === 'กำลังติดตาม'; }).length;

    var cards = [
      { label: 'กลุ่มตัวอย่างทั้งหมด', value: membersList.length, hint: 'เป้าหมาย 120 คน', warn: false },
      { label: 'กำลังติดตาม', value: running, hint: Math.round((running / (membersList.length || 1)) * 100) + '% ของกลุ่ม', warn: false },
      { label: 'ต้องติดตามช่วงนี้', value: dueNow, hint: dueNow > 0 ? 'อยู่ในช่วงติดตาม' : 'ไม่มีรายการ', warn: dueNow > 0 },
      { label: 'เกินกำหนด', value: overdue, hint: overdue > 0 ? 'พ้นช่วงติดตามแล้ว' : 'ไม่มีรายการ', warn: overdue > 0 }
    ];

    $('co-stats').innerHTML = cards.map(function (c) {
      return '<div class="card co-stat">' +
        '<span class="co-stat-label">' + esc(c.label) + '</span>' +
        '<span class="co-stat-value' + (c.warn ? ' is-warn' : '') + '">' + c.value + ' <span class="co-stat-unit">คน</span></span>' +
        '<span class="co-stat-hint">' + esc(c.hint) + '</span>' +
        '</div>';
    }).join('');
  }

  function renderTabs() {
    $('co-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="co-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + '</button>';
    }).join('');
  }

  function fillFilters() {
    $('co-round').innerHTML = ['ทุกรอบ'].concat(templatesList)
      .map(function (v) { return '<option value="' + esc(v) + '">' + esc(v) + '</option>'; }).join('');
    $('co-area').innerHTML = ['ทุกพื้นที่'].concat(areasList)
      .map(function (v) { return '<option value="' + esc(v) + '">' + esc(v) + '</option>'; }).join('');
  }

  function filteredList() {
    var q = state.q.trim().toLowerCase();
    return membersList.filter(function (m) {
      var rounds = m.rounds || [];
      if (state.tab === 'ต้องติดตามรอบนี้' && !rounds.some(function (r) { return r.state === 'รอติดตาม'; })) return false;
      if (state.tab === 'เกินกำหนด' && !rounds.some(function (r) { return r.state === 'เกินกำหนด'; })) return false;
      if (state.tab === 'ติดตามครบ' && m.status !== 'ติดตามครบ') return false;
      if (state.tab === 'หลุดการติดตาม' && m.status !== 'หลุดการติดตาม') return false;
      if (state.round !== 'ทุกรอบ' && m.nextRound !== state.round) return false;
      if (state.area !== 'ทุกพื้นที่' && m.area !== state.area) return false;
      if (q && (m.name + ' ' + m.pid + ' ' + m.phone).toLowerCase().indexOf(q) < 0) return false;
      return true;
    });
  }

  var ROUND_ICONS = {
    'ตอบแล้ว': { cls: 'is-done', path: '<path d="M5 12.5l4.5 4.5L19 7.5"/>', w: 2 },
    'รอติดตาม': { cls: 'is-due', path: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.6V12l3 1.8"/>', w: 1.9 },
    'เกินกำหนด': { cls: 'is-over', path: '<path d="M6.5 6.5l11 11M17.5 6.5l-11 11"/>', w: 2 },
    'ยังไม่ถึงกำหนด': { cls: 'is-idle', path: '<path d="M6 12h12"/>', w: 1.8 },
    'ยุติการติดตาม': { cls: 'is-idle', path: '<path d="M6 12h12"/>', w: 1.8 }
  };

  function roundIcon(r) {
    var ic = ROUND_ICONS[r.state] || ROUND_ICONS['ยังไม่ถึงกำหนด'];
    var title = r.name + ' · ' + r.state + (r.at ? ' · ตอบเมื่อ ' + r.at : ' · ครบกำหนด ' + r.dueDate);
    return '<span class="co-round-icon ' + ic.cls + '" title="' + esc(title) + '">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' + ic.w + '" stroke-linecap="round" stroke-linejoin="round">' + ic.path + '</svg>' +
      '</span>';
  }

  function renderTable() {
    var list = filteredList();
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;
    var rows = list.slice(start, start + state.pageSize);

    var html = rows.map(function (m) {
      var rMap = {};
      (m.rounds || []).forEach(function (r) { rMap[r.name] = r; });

      var badgeCls = m.status === 'ติดตามครบ' ? 'is-done' : (m.status === 'เกินกำหนด' ? 'is-over' : (m.status === 'หลุดการติดตาม' ? 'is-idle' : 'is-due'));

      return '<div class="co-tr">' +
        '<div><a href="{{ url('/admin/cohort') }}/' + m.id + '" class="co-name">' + esc(m.name) + '</a><span class="co-sub">' + esc(m.pid) + '</span></div>' +
        '<div>' + esc(m.area) + '</div>' +
        '<div>' + esc(m.target) + '</div>' +
        '<div class="text-center">' + (rMap['ก่อนเข้าร่วม'] ? roundIcon(rMap['ก่อนเข้าร่วม']) : '—') + '</div>' +
        '<div class="text-center">' + (rMap['3 เดือน'] ? roundIcon(rMap['3 เดือน']) : '—') + '</div>' +
        '<div class="text-center">' + (rMap['6 เดือน'] ? roundIcon(rMap['6 เดือน']) : '—') + '</div>' +
        '<div class="text-center">' + (rMap['12 เดือน'] ? roundIcon(rMap['12 เดือน']) : '—') + '</div>' +
        '<div><span class="co-next">' + esc(m.nextRound || '—') + '</span></div>' +
        '<div><span class="co-badge ' + badgeCls + '">' + esc(m.status) + '</span></div>' +
        '<div><a href="{{ url('/admin/cohort') }}/' + m.id + '" class="btn btn-outline btn-sm">ดูข้อมูล</a></div>' +
        '</div>';
    }).join('');

    $('co-rows').innerHTML = html || '<div class="co-empty">ไม่พบข้อมูลกลุ่มตัวอย่างตามเงื่อนไขที่เลือก</div>';

    window.TFC.renderPagination('co-foot', {
      page: state.page,
      pageSize: state.pageSize,
      total: list.length,
      pageSizeOptions: PAGE_SIZES,
      footer: true,
      onChange: function (p) { state.page = p; renderTable(); },
      onPageSizeChange: function (sz) { state.pageSize = sz; state.page = 1; renderTable(); }
    });

    renderStats();
  }

  /* --- Event Listeners --- */
  $('co-tabs').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-tab]');
    if (!btn) return;
    state.tab = btn.getAttribute('data-tab');
    state.page = 1;
    renderTabs();
    renderTable();
  });

  $('co-search-btn').addEventListener('click', function () {
    state.searchOpen = !state.searchOpen;
    $('co-search-btn').classList.toggle('is-on', state.searchOpen);
    $('co-search-panel').hidden = !state.searchOpen;
  });

  $('co-apply').addEventListener('click', function () {
    state.q = $('co-q').value;
    state.round = $('co-round').value;
    state.area = $('co-area').value;
    state.page = 1;
    state.searchOpen = false;
    $('co-search-panel').hidden = true;
    renderTable();
  });

  $('co-clear').addEventListener('click', function () {
    $('co-q').value = '';
    $('co-round').value = 'ทุกรอบ';
    $('co-area').value = 'ทุกพื้นที่';
    state.q = ''; state.round = 'ทุกรอบ'; state.area = 'ทุกพื้นที่';
    state.page = 1;
    renderTable();
  });

  var exportBtn = $('co-export');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      window.TFC.exportTableCsv('#co-rows', 'รายการกลุ่มตัวอย่าง.csv');
    });
  }

  /* ================= ฟอร์มเพิ่มกลุ่มตัวอย่าง ================= */
  function toast(msg, tone) {
    if (window.TFC.showToast) window.TFC.showToast(msg, tone || 'info');
  }

  function pad2(n) { return n < 10 ? '0' + n : String(n); }

  function addDays(iso, n) {
    var p = iso.split('-').map(Number);
    var d = new Date(p[0], p[1] - 1, p[2] + n);
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }

  var form = {
    /* ว่างไว้จนกว่าจะกดรันเลข — เลขต้องมาจากเซิร์ฟเวอร์เท่านั้น
       นับต่อจากรายการบนหน้าจอไม่ได้ เพราะหน้าจอไม่เห็นคนที่คนอื่นเพิ่มระหว่างนี้ */
    personCode: '',
    name: '', phone: '',
    gender: '', ageRangeId: '', occupationId: '',
    areaId: '', targetGroupId: '', sourceCode: '',
    entryDate: lookups.today,
    status: lookups.statuses[0] || '',
    /* เก็บเป็น id ของรอบจาก master data ไม่ใช่จำนวนเดือน — ชุดรอบเปลี่ยนได้ตลอดที่หน้าตั้งค่า */
    rounds: lookups.followUpRounds.filter(function (r) { return r.checked; }).map(function (r) { return r.value; }),
    dueEdit: {}, editing: null,
    consent: false, consentFile: null, uploading: false,
    linkCopied: false, saving: false
  };

  function optionsHtml(list, value, placeholder) {
    var head = placeholder
      ? '<option value=""' + (value === '' ? ' selected' : '') + '>' + esc(placeholder) + '</option>'
      : '';
    return head + list.map(function (o) {
      return '<option value="' + esc(String(o.value)) + '"' +
        (String(o.value) === String(value) ? ' selected' : '') + '>' + esc(o.label) + '</option>';
    }).join('');
  }

  function selectHtml(id, list, value, placeholder) {
    return '<select class="select" id="' + id + '">' + optionsHtml(list, value, placeholder) + '</select>';
  }

  function fieldHtml(label, required, control) {
    return '<label class="co-field">' +
      '<span class="co-field-label">' + esc(label) + (required ? '<span class="form-required">*</span>' : '') + '</span>' +
      control + '</label>';
  }

  function plainList(values) {
    return values.map(function (v) { return { value: v, label: v }; });
  }

  function renderForm() {
    $('co-form-grid').innerHTML =
      '<label class="co-field">' +
        '<span class="co-field-label">รหัสบุคคล<span class="form-required">*</span></span>' +
        '<span class="co-pid-input">' +
          '<input type="text" class="input" id="co-f-pid" value="' + esc(form.personCode) + '" placeholder="กดปุ่มเพื่อรันเลข" disabled>' +
          '<button type="button" class="co-pid-btn" id="co-gen-pid">รันเลข</button>' +
        '</span></label>' +
      fieldHtml('ชื่อ–นามสกุล', true, '<input type="text" class="input" id="co-f-name" value="' + esc(form.name) + '" placeholder="ชื่อ นามสกุล">') +
      fieldHtml('เบอร์โทร', true, '<input type="tel" class="input" id="co-f-phone" value="' + esc(form.phone) + '" placeholder="08x-xxx-xxxx" inputmode="tel">') +

      fieldHtml('เพศ', true, selectHtml('co-f-gender', lookups.genders, form.gender, 'เลือกเพศ')) +
      fieldHtml('ช่วงอายุ', false, selectHtml('co-f-age', lookups.ageRanges, form.ageRangeId, 'ไม่ระบุ')) +
      fieldHtml('อาชีพ', false, selectHtml('co-f-job', lookups.occupations, form.occupationId, 'ไม่ระบุ')) +

      fieldHtml('พื้นที่', true, selectHtml('co-f-area', lookups.areas, form.areaId, 'เลือกพื้นที่')) +
      fieldHtml('กลุ่มเป้าหมาย', true, selectHtml('co-f-target', lookups.targetGroups, form.targetGroupId, 'เลือกกลุ่มเป้าหมาย')) +
      fieldHtml('แหล่งที่มา', true, selectHtml('co-f-source', lookups.sources, form.sourceCode, 'เลือกแหล่งที่มา')) +

      fieldHtml('วันที่เข้ากลุ่มตัวอย่าง', true, '<input type="date" class="input" id="co-f-base" value="' + esc(form.entryDate) + '" lang="th-TH">') +
      fieldHtml('สถานะ', false, selectHtml('co-f-status', plainList(lookups.statuses), form.status)) +
      '<label class="co-field">' +
        '<span class="co-field-label">ลิงก์แบบประเมิน</span>' +
        '<button type="button" class="co-copy-btn' + (form.linkCopied ? ' is-done' : '') + '" id="co-copy-link"' +
          (form.personCode ? '' : ' disabled') + '>' +
          (form.linkCopied ? 'คัดลอกลิงก์แล้ว' : 'คัดลอกลิงก์แบบประเมิน') + '</button>' +
      '</label>';
  }

  /* รอบติดตามทั้งหมดมาจากหน้าตั้งค่ารอบประเมิน เรียงตามลำดับที่ตั้งไว้ ไม่มี 3/6/12 เขียนตายที่นี่ */
  function renderRoundChips() {
    $('co-round-chips').innerHTML = lookups.followUpRounds.map(function (r) {
      var on = form.rounds.indexOf(r.value) > -1;
      return '<button type="button" class="co-chip' + (on ? ' is-on' : '') + '" data-round-chip="' + esc(String(r.value)) + '"' +
        ' role="checkbox" aria-checked="' + on + '">' +
        '<span class="co-chip-mark">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(r.label) + '</button>';
    }).join('');
  }

  function selectedRounds() {
    return lookups.followUpRounds
      .filter(function (r) { return form.rounds.indexOf(r.value) > -1; })
      .map(function (r) {
        return { value: r.value, label: r.label, due: form.dueEdit[r.value] || addDays(form.entryDate, r.offsetDays) };
      })
      .sort(function (a, b) { return a.due < b.due ? -1 : (a.due > b.due ? 1 : 0); });
  }

  /* ตารางย่อยแสดงวันครบกำหนดที่คำนวณจากวันที่เข้ากลุ่ม แก้ทับได้รายรอบ
     เรียงตามวันครบกำหนด ไม่ใช่ตามลำดับที่กด เพื่อให้อ่านเป็นไทม์ไลน์เสมอ */
  function renderDueTable() {
    var rounds = selectedRounds();

    if (!rounds.length || !form.entryDate) {
      $('co-due-table').innerHTML = '';
      return;
    }

    $('co-due-table').innerHTML =
      '<div class="co-due-row co-due-head"><div>รอบ</div><div>เกณฑ์กำหนดติดตาม</div><div></div></div>' +
      rounds.map(function (r) {
        var editing = form.editing === r.value;
        return '<div class="co-due-row">' +
          '<div class="co-due-name">' + esc(r.label) + '</div>' +
          '<div>' + (editing
            ? '<input type="date" class="input co-due-input" value="' + esc(r.due) + '" data-due-input="' + esc(String(r.value)) + '" lang="th-TH">'
            : '<span class="co-due-date">' + esc(window.TFC.formatThaiDate(r.due)) + '</span>') + '</div>' +
          '<div class="co-due-action">' +
            '<button type="button" class="co-icon-btn" data-due-edit="' + esc(String(r.value)) + '" aria-label="' +
              (editing ? 'บันทึกวันที่' : 'แก้วันครบกำหนด') + '">' +
              (editing
                ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>'
                : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M13.5 6.5l4 4"/></svg>') +
            '</button>' +
          '</div></div>';
      }).join('');
  }

  function formValid() {
    return !!(form.personCode && form.name.trim() && form.phone.trim() && form.gender &&
      form.areaId && form.targetGroupId && form.sourceCode && form.entryDate &&
      form.rounds.length && form.consent && !form.uploading && !form.saving);
  }

  function syncForm() {
    $('co-consent-file').hidden = !form.consent;
    var saveBtn = $('co-save');
    saveBtn.disabled = !formValid();
    saveBtn.textContent = form.saving ? 'กำลังบันทึก…' : 'บันทึก';
  }

  function renderAddModal() {
    renderForm();
    renderRoundChips();
    renderDueTable();
    syncForm();
  }

  /* --- เหตุการณ์ในฟอร์ม --- */
  document.addEventListener('click', function (e) {
    var genBtn = e.target.closest('#co-gen-pid');
    if (genBtn) {
      genBtn.disabled = true;
      fetch('{{ route('admin.cohort.lookups') }}', { headers: { 'Accept': 'application/json' } })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          form.personCode = res.nextPersonCode;
          form.linkCopied = false;
          renderAddModal();
        })
        .catch(function () {
          genBtn.disabled = false;
          toast('รันเลขรหัสบุคคลไม่สำเร็จ กรุณาลองใหม่', 'danger');
        });
      return;
    }

    if (e.target.closest('#co-copy-link')) {
      if (!form.personCode || !navigator.clipboard) return;
      navigator.clipboard.writeText(lookups.assessmentLinkBase + form.personCode).then(function () {
        form.linkCopied = true;
        renderAddModal();
      }, function () {
        toast('คัดลอกไม่สำเร็จ — ลิงก์คือ ' + lookups.assessmentLinkBase + form.personCode, 'warning');
      });
      return;
    }

    var chip = e.target.closest('[data-round-chip]');
    if (chip) {
      var rid = Number(chip.getAttribute('data-round-chip'));
      var at = form.rounds.indexOf(rid);
      if (at > -1) form.rounds.splice(at, 1); else form.rounds.push(rid);
      renderRoundChips();
      renderDueTable();
      syncForm();
      return;
    }

    var dueBtn = e.target.closest('[data-due-edit]');
    if (dueBtn) {
      var did = Number(dueBtn.getAttribute('data-due-edit'));
      if (form.editing === did) {
        var input = document.querySelector('[data-due-input="' + did + '"]');
        if (input && input.value) form.dueEdit[did] = input.value;
        form.editing = null;
      } else {
        form.editing = did;
      }
      renderDueTable();
      return;
    }
  });

  document.addEventListener('input', function (e) {
    var id = e.target.id;
    if (id === 'co-f-name') form.name = e.target.value;
    else if (id === 'co-f-phone') form.phone = e.target.value;
    else if (id === 'co-f-base') {
      form.entryDate = e.target.value;
      /* วันฐานเปลี่ยน = วันครบกำหนดทุกรอบต้องคำนวณใหม่ทั้งชุด
         วันที่ที่แก้ทับไว้ก่อนหน้าอ้างอิงวันฐานเดิม จึงต้องล้างทิ้ง ไม่ใช่คงไว้เงียบ ๆ */
      form.dueEdit = {};
      form.editing = null;
      renderDueTable();
    } else return;
    syncForm();
  });

  document.addEventListener('change', function (e) {
    var id = e.target.id;
    var map = {
      'co-f-gender': 'gender', 'co-f-age': 'ageRangeId', 'co-f-job': 'occupationId',
      'co-f-area': 'areaId', 'co-f-target': 'targetGroupId', 'co-f-source': 'sourceCode',
      'co-f-status': 'status'
    };

    if (map[id]) { form[map[id]] = e.target.value; return syncForm(); }
    if (id === 'co-consent') { form.consent = e.target.checked; return syncForm(); }
    if (id === 'co-file') uploadConsentFile(e.target);
  });

  /* อัปโหลดใบยินยอมก่อนกดบันทึก เซิร์ฟเวอร์คืน path มาให้ฟอร์มถือไว้
     ตรวจนามสกุลอีกครั้งฝั่งเซิร์ฟเวอร์เสมอ accept ของ input กันได้แค่หน้าจอ */
  function uploadConsentFile(input) {
    var file = input.files && input.files[0];
    if (!file) { form.consentFile = null; return syncForm(); }

    var body = new FormData();
    body.append('file', file);
    form.uploading = true;
    syncForm();

    fetch('{{ route('admin.cohort.upload-consent') }}', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: body
    })
      .then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); })
      .then(function (res) {
        form.uploading = false;
        if (!res.ok) {
          input.value = '';
          form.consentFile = null;
          toast(firstError(res.body) || 'อัปโหลดไฟล์ไม่สำเร็จ', 'danger');
        } else {
          form.consentFile = res.body.path;
          toast('แนบเอกสารความยินยอมแล้ว', 'success');
        }
        syncForm();
      })
      .catch(function () {
        form.uploading = false;
        input.value = '';
        form.consentFile = null;
        toast('อัปโหลดไฟล์ไม่สำเร็จ', 'danger');
        syncForm();
      });
  }

  function firstError(body) {
    if (body && body.errors) {
      var keys = Object.keys(body.errors);
      if (keys.length) return body.errors[keys[0]][0];
    }
    return body && body.message;
  }

  function resetForm() {
    form.personCode = ''; form.name = ''; form.phone = '';
    form.gender = ''; form.ageRangeId = ''; form.occupationId = '';
    form.areaId = ''; form.targetGroupId = ''; form.sourceCode = '';
    form.entryDate = lookups.today;
    form.status = lookups.statuses[0] || '';
    form.rounds = lookups.followUpRounds.filter(function (r) { return r.checked; }).map(function (r) { return r.value; });
    form.dueEdit = {}; form.editing = null;
    form.consent = false; form.consentFile = null; form.linkCopied = false;
    $('co-consent').checked = false;
    $('co-file').value = '';
    renderAddModal();
  }

  $('co-add-form').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!formValid()) return;

    form.saving = true;
    syncForm();

    /* ส่งวันครบกำหนดของทุกรอบไปด้วย ไม่ใช่แค่รายการรอบที่ติ๊ก
       เซิร์ฟเวอร์จะคำนวณ offset ของ "คนนี้" ย้อนกลับจากวันที่บนหน้าจอ
       วันที่ที่แอดมินแก้ทับจึงไม่หายไประหว่างทาง */
    var payload = {
      person_code: form.personCode,
      name: form.name.trim(),
      phone: form.phone.trim(),
      gender: form.gender,
      age_range_id: form.ageRangeId || null,
      occupation_id: form.occupationId || null,
      area_id: form.areaId,
      target_group_id: form.targetGroupId,
      source_code: form.sourceCode,
      entry_date: form.entryDate,
      status: form.status,
      rounds: selectedRounds().map(function (r) { return { template_id: r.value, due_date: r.due }; }),
      consent: form.consent ? 1 : 0,
      consent_file_path: form.consentFile
    };

    fetch('{{ route('admin.cohort.store') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    })
      .then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); })
      .then(function (res) {
        form.saving = false;

        if (!res.ok || !res.body.success) {
          syncForm();
          toast(firstError(res.body) || 'เกิดข้อผิดพลาดในการบันทึก', 'danger');
          return;
        }

        membersList.unshift(res.body.data);
        state.page = 1;
        renderTable();

        if (window.TFC.closeModal) window.TFC.closeModal('co-add-modal');

        $('co-saved-name').textContent = res.body.data.name + ' · ' + res.body.data.pid;
        $('co-saved-link').textContent = res.body.evalLink || '';
        $('co-bind-link').textContent = res.body.lineBindLink || '';
        $('co-saved-copy').textContent = 'คัดลอกลิงก์';
        $('co-bind-copy').textContent = 'คัดลอกลิงก์';

        if (window.TFC.openModal) window.TFC.openModal('co-saved-modal');
        resetForm();
      })
      .catch(function () {
        form.saving = false;
        syncForm();
        toast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'danger');
      });
  });

  /* clipboard ล้มเหลวได้จริง (ไม่ใช่ https หรือผู้ใช้ไม่อนุญาต) จึงรอผลก่อนค่อยบอกว่าสำเร็จ */
  function bindCopy(btnId, linkId, label) {
    $(btnId).addEventListener('click', function () {
      var link = $(linkId).textContent;
      if (!navigator.clipboard) return toast('คัดลอกไม่สำเร็จ — ลิงก์คือ ' + link, 'warning');
      navigator.clipboard.writeText(link).then(
        function () { $(btnId).textContent = 'คัดลอกแล้ว'; toast(label, 'success'); },
        function () { toast('คัดลอกไม่สำเร็จ — ลิงก์คือ ' + link, 'warning'); }
      );
    });
  }

  bindCopy('co-saved-copy', 'co-saved-link', 'คัดลอกลิงก์เรียบร้อย');
  bindCopy('co-bind-copy', 'co-bind-link', 'คัดลอกลิงก์ผูก LINE เรียบร้อย');

  renderTabs();
  fillFilters();
  renderTable();
  renderAddModal();
})();
</script>
@endpush
