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
        <div class="co-form-grid" id="co-form-grid">
          <label class="co-field">
            <span class="co-field-label">ชื่อ-นามสกุล<span class="form-required">*</span></span>
            <input type="text" class="input" id="co-input-name" required placeholder="เช่น สมชาย ใจดี">
          </label>
          <label class="co-field">
            <span class="co-field-label">เบอร์โทรศัพท์<span class="form-required">*</span></span>
            <input type="tel" class="input" id="co-input-phone" required placeholder="เช่น 081-234-5678">
          </label>
          <label class="co-field">
            <span class="co-field-label">เพศ</span>
            <select class="select" id="co-input-gender">
              <option value="ชาย">ชาย</option>
              <option value="หญิง">หญิง</option>
              <option value="อื่น ๆ">อื่น ๆ</option>
            </select>
          </label>
          <label class="co-field">
            <span class="co-field-label">พื้นที่ดำเนินงาน<span class="form-required">*</span></span>
            <select class="select" id="co-input-area" required>
              @foreach($areas as $area)
                <option value="{{ $area->id }}">{{ $area->name }}</option>
              @endforeach
            </select>
          </label>
          <label class="co-field">
            <span class="co-field-label">กลุ่มเป้าหมาย</span>
            <select class="select" id="co-input-target">
              @foreach($targetGroups as $tg)
                <option value="{{ $tg->id }}">{{ $tg->name }}</option>
              @endforeach
            </select>
          </label>
          <label class="co-field">
            <span class="co-field-label">วันที่เข้ากลุ่มตัวอย่าง<span class="form-required">*</span></span>
            <input type="date" class="input" id="co-input-entry" required value="{{ date('Y-m-d') }}">
          </label>
        </div>

        <div class="co-block mt-4">
          <label class="co-consent">
            <input type="checkbox" id="co-consent" required checked>
            <span>ได้รับความยินยอมในการเก็บข้อมูลแล้ว<span class="form-required">*</span></span>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="co-save">บันทึก</button>
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

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
<script src="@assetv('assets/js/followup-template-service.js')"></script>
<script src="@assetv('assets/js/cohort-data.js')"></script>
@endpush

@push('page-script')
<script>
(function () {
  var membersList = @json($members);
  var areasList = @json($areas->pluck('name'));
  var templatesList = @json($templates->pluck('name'));
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

  /* --- Add Cohort Form AJAX --- */
  var addForm = $('co-add-form');
  if (addForm) {
    addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var saveBtn = $('co-save');
      saveBtn.disabled = true;

      var payload = {
        name: $('co-input-name').value,
        phone: $('co-input-phone').value,
        gender: $('co-input-gender').value,
        area_id: $('co-input-area').value,
        target_group_id: $('co-input-target').value,
        entry_date: $('co-input-entry').value,
        consent: $('co-consent').checked ? 1 : 0
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
      .then(function (res) { return res.json(); })
      .then(function (res) {
        saveBtn.disabled = false;
        if (!res.success) {
          var msg = res.message || (res.errors ? Object.values(res.errors).flat().join('<br>') : 'เกิดข้อผิดพลาดในการบันทึก');
          if (window.TFC.showToast) window.TFC.showToast(msg, 'danger');
          return;
        }

        membersList.unshift(res.data);
        renderTable();

        if (window.TFC.closeModal) window.TFC.closeModal('co-add-modal');
        addForm.reset();

        $('co-saved-name').textContent = res.data.name + ' (' + res.data.pid + ')';
        $('co-saved-link').textContent = res.evalLink || '';
        $('co-bind-link').textContent = res.lineBindLink || '';

        if (window.TFC.openModal) window.TFC.openModal('co-saved-modal');
      })
      .catch(function () {
        saveBtn.disabled = false;
        if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'danger');
      });
    });
  }

  $('co-saved-copy')?.addEventListener('click', function () {
    navigator.clipboard.writeText($('co-saved-link').textContent);
    if (window.TFC.showToast) window.TFC.showToast('คัดลอกลิงก์เรียบร้อย', 'success');
  });

  $('co-bind-copy')?.addEventListener('click', function () {
    navigator.clipboard.writeText($('co-bind-link').textContent);
    if (window.TFC.showToast) window.TFC.showToast('คัดลอกลิงก์ผูก LINE เรียบร้อย', 'success');
  });

  renderTabs();
  fillFilters();
  renderTable();
})();
</script>
@endpush
