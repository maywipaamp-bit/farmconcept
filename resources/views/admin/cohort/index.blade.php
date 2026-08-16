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
        {{-- กรองจากวันครบกำหนดของ "รอบถัดไป" — ตัวเดียวกับที่แสดงในคอลัมน์รอบถัดไป
             จะได้ตรงกับสิ่งที่เห็นบนจอ ไม่ใช่ไปจับรอบที่ตอบไปแล้วด้วย --}}
        <div class="co-field">
          <span class="co-field-label">ครบกำหนดรอบถัดไป</span>
          <div class="co-date-range">
            <input type="date" class="input" id="co-due-from" lang="th-TH" aria-label="ครบกำหนดตั้งแต่วันที่">
            <span class="co-date-range-sep">ถึง</span>
            <input type="date" class="input" id="co-due-to" lang="th-TH" aria-label="ครบกำหนดถึงวันที่">
          </div>
        </div>
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
        <div class="co-tr co-th" id="co-head"></div>
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
  var state = {
    tab: 'ทั้งหมด', q: '', round: 'ทุกรอบ', area: 'ทุกพื้นที่',
    dueFrom: '', dueTo: '', searchOpen: false, page: 1, pageSize: PAGE_SIZES[0]
  };
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  /* TFC.formatThaiDate ไม่ได้กันค่าว่าง — new Date(null) คืนวันที่ 1 ม.ค. 2513 ออกมาเฉย ๆ
     ห่อไว้ชั้นหนึ่งเพื่อให้ช่องที่ไม่มีวันที่ขึ้นขีดกลาง ไม่ใช่วันที่ปลอม */
  function fmt(iso) {
    return iso ? window.TFC.formatThaiDate(iso) : '—';
  }

  /* ชุดคอลัมน์รอบมาจากหน้าตั้งค่ารอบประเมิน ไม่ได้เขียน 3/6/12 ตายไว้
     เพิ่มหรือลบรอบที่หน้านั้นแล้วตารางนี้ตามทันที รวมถึงความกว้างของกริดด้วย */
  var ROUND_COLUMNS = templatesList;

  function tabCount(tab) {
    return membersList.filter(function (m) { return matchesTab(m, tab); }).length;
  }

  function matchesTab(m, tab) {
    var rounds = m.rounds || [];
    if (tab === 'ต้องติดตามรอบนี้') return rounds.some(function (r) { return r.state === 'รอติดตาม'; });
    if (tab === 'เกินกำหนด') return rounds.some(function (r) { return r.state === 'เกินกำหนด'; });
    if (tab === 'ติดตามครบ') return m.status === 'ติดตามครบ';
    if (tab === 'หลุดการติดตาม') return m.status === 'หลุดการติดตาม';
    return true;
  }

  /* จำนวนต่อท้ายชื่อแท็บ นับจากข้อมูลทั้งหมดเสมอ ไม่ใช่จากผลที่กรองอยู่
     ไม่งั้นเลขจะเปลี่ยนตามตัวเองแล้วอ่านไม่ได้ว่าแท็บอื่นมีกี่คน */
  function renderTabs() {
    $('co-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="co-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + ' · ' + tabCount(t) + '</button>';
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
      if (!matchesTab(m, state.tab)) return false;
      if (state.round !== 'ทุกรอบ' && m.nextRound !== state.round) return false;
      if (state.area !== 'ทุกพื้นที่' && m.area !== state.area) return false;
      /* เทียบเป็นข้อความ ISO ได้ตรง ๆ เพราะ YYYY-MM-DD เรียงตามตัวอักษรเท่ากับเรียงตามวัน */
      if (state.dueFrom && (!m.nextRoundDue || m.nextRoundDue < state.dueFrom)) return false;
      if (state.dueTo && (!m.nextRoundDue || m.nextRoundDue > state.dueTo)) return false;
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

  /* คำอธิบายตอนเอาเมาส์ชี้ — บอกทั้งวันครบกำหนดและวันที่ตอบเสมอ
     รอบที่ยังไม่ตอบก็ต้องเห็นว่า "ยังไม่ได้ตอบ" ไม่ใช่ซ่อนบรรทัดนั้นไปเฉย ๆ */
  function roundTitle(r) {
    return [
      r.name + ' · ' + r.state,
      'ครบกำหนด ' + fmt(r.dueDate),
      r.at ? 'ตอบเมื่อ ' + fmt(r.at) : 'ยังไม่ได้ตอบ'
    ].join('\n');
  }

  function roundIcon(r) {
    var ic = ROUND_ICONS[r.state] || ROUND_ICONS['ยังไม่ถึงกำหนด'];
    return '<span class="co-round-icon ' + ic.cls + '" title="' + esc(roundTitle(r)) + '" tabindex="0">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' + ic.w + '" stroke-linecap="round" stroke-linejoin="round">' + ic.path + '</svg>' +
      '</span>';
  }

  /* ความกว้างกริดผูกกับจำนวนรอบจริง ตั้งเป็นตัวแปร CSS ที่ .co-table ครั้งเดียว
     ไม่ต้องใส่ inline style ซ้ำทุกแถว และเพิ่มรอบใหม่แล้วหัวกับเนื้อไม่เลื่อนหลุดกัน */
  function applyGridWidth() {
    var roundCols = ROUND_COLUMNS.map(function () { return '52px'; }).join(' ');
    var table = document.querySelector('.co-table');

    table.style.setProperty('--co-cols',
      'minmax(170px, 1.6fr) 122px 108px 106px ' + roundCols + ' 158px 116px 44px');
    table.style.setProperty('--co-min-width',
      (170 + 122 + 108 + 106 + ROUND_COLUMNS.length * 52 + 158 + 116 + 44 + 120) + 'px');
  }

  function renderHead() {
    $('co-head').innerHTML =
      '<div>ชื่อ / รหัส</div>' +
      '<div>กลุ่มเป้าหมาย</div>' +
      '<div>วันที่เข้ากลุ่ม</div>' +
      '<div>LINE</div>' +
      ROUND_COLUMNS.map(function (name) {
        return '<div class="text-center">' + esc(name) + '</div>';
      }).join('') +
      '<div>รอบถัดไป</div>' +
      '<div>สถานะ</div>' +
      '<div></div>';
  }

  var STATUS_CLASS = {
    'ติดตามครบ': 'is-done',
    'เกินกำหนด': 'is-lost',
    'หลุดการติดตาม': 'is-stopped'
  };

  var NEXT_DUE_TONE = { 'รอติดตาม': ' is-due', 'เกินกำหนด': ' is-over' };

  function renderTable() {
    var list = filteredList();
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;
    var rows = list.slice(start, start + state.pageSize);

    var html = rows.map(function (m) {
      var rMap = {};
      (m.rounds || []).forEach(function (r) { rMap[r.name] = r; });

      var href = '{{ url('/admin/cohort') }}/' + m.id;

      return '<div class="co-tr">' +
        '<div class="co-name-cell">' +
          '<a href="' + href + '" class="co-name">' + esc(m.name) + '</a>' +
          '<span class="co-pid">' + esc(m.pid) + '</span>' +
        '</div>' +
        '<div class="co-cell">' + esc(m.target) + '</div>' +
        '<div class="co-cell">' + esc(fmt(m.entryDate)) + '</div>' +
        /* คนที่ยังไม่เชื่อม LINE คือคนที่ระบบส่งแจ้งเตือนให้ไม่ได้ ต้องเห็นตั้งแต่หน้ารายการ
           ไม่ใช่ไปรู้ตอนเปิดรอบติดตามแล้วพบว่าส่งไม่ออก */
        '<div class="co-line ' + (m.line ? 'is-linked' : 'is-unlinked') + '">' +
          (m.line ? 'เชื่อมแล้ว' : 'ยังไม่เชื่อม') + '</div>' +
        ROUND_COLUMNS.map(function (name) {
          return '<div class="co-round-cell">' + (rMap[name] ? roundIcon(rMap[name]) : '—') + '</div>';
        }).join('') +
        '<div class="co-next">' +
          '<span class="co-next-name">' + esc(m.nextRound || '—') + '</span>' +
          (m.nextRoundDue
            ? '<span class="co-next-due' + (NEXT_DUE_TONE[m.nextRoundState] || '') + '">ครบกำหนด ' + esc(fmt(m.nextRoundDue)) + '</span>'
            : '') +
        '</div>' +
        '<div><span class="co-status ' + (STATUS_CLASS[m.status] || '') + '">' + esc(m.status) + '</span></div>' +
        '<div><a href="' + href + '" class="btn btn-outline btn-sm">ดูข้อมูล</a></div>' +
        '</div>';
    }).join('');

    $('co-rows').innerHTML = html || '<div class="co-empty"><span class="co-empty-title">ไม่พบข้อมูลกลุ่มตัวอย่างตามเงื่อนไขที่เลือก</span></div>';

    window.TFC.renderPagination('co-foot', {
      page: state.page,
      pageSize: state.pageSize,
      total: list.length,
      pageSizeOptions: PAGE_SIZES,
      footer: true,
      onChange: function (p) { state.page = p; renderTable(); },
      onPageSizeChange: function (sz) { state.pageSize = sz; state.page = 1; renderTable(); }
    });
  }

  /* คำอธิบายสีไอคอนรายรอบ — ไม่งั้นต้องเอาเมาส์ชี้ทีละอันถึงจะรู้ว่าสีไหนแปลว่าอะไร */
  function renderLegend() {
    $('co-legend').innerHTML = ['ตอบแล้ว', 'รอติดตาม', 'เกินกำหนด', 'ยังไม่ถึงกำหนด'].map(function (s) {
      var ic = ROUND_ICONS[s];
      return '<span class="co-legend-item">' +
        '<span class="co-round-icon ' + ic.cls + '">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' + ic.w + '" stroke-linecap="round" stroke-linejoin="round">' + ic.path + '</svg>' +
        '</span>' + esc(s) + '</span>';
    }).join('');
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
    state.dueFrom = $('co-due-from').value;
    state.dueTo = $('co-due-to').value;

    /* กรอกช่วงกลับด้านมาให้สลับให้เอง ดีกว่าคืนผลลัพธ์ว่างแล้วให้ผู้ใช้เดาเองว่าผิดตรงไหน */
    if (state.dueFrom && state.dueTo && state.dueFrom > state.dueTo) {
      var swap = state.dueFrom;
      state.dueFrom = state.dueTo;
      state.dueTo = swap;
      $('co-due-from').value = state.dueFrom;
      $('co-due-to').value = state.dueTo;
    }

    state.page = 1;
    state.searchOpen = false;
    $('co-search-btn').classList.toggle('is-on', hasFilter());
    $('co-search-panel').hidden = true;
    renderTable();
  });

  function hasFilter() {
    return !!(state.q.trim() || state.dueFrom || state.dueTo ||
      state.round !== 'ทุกรอบ' || state.area !== 'ทุกพื้นที่');
  }

  $('co-clear').addEventListener('click', function () {
    $('co-q').value = '';
    $('co-round').value = 'ทุกรอบ';
    $('co-area').value = 'ทุกพื้นที่';
    $('co-due-from').value = '';
    $('co-due-to').value = '';
    state.q = ''; state.round = 'ทุกรอบ'; state.area = 'ทุกพื้นที่';
    state.dueFrom = ''; state.dueTo = '';
    state.page = 1;
    $('co-search-btn').classList.remove('is-on');
    renderTable();
  });

  /* ส่งออกทุกแถวที่ตรงเงื่อนไขที่กรองอยู่ ไม่ใช่แค่หน้าที่เปิดค้างอยู่
     และประกอบจากข้อมูลจริง ไม่ใช่ขูดจาก DOM — คอลัมน์ไอคอนรายรอบขูดออกมาแล้วได้ช่องว่าง */
  $('co-export').addEventListener('click', function () {
    var list = filteredList();

    if (!list.length) {
      return toast('ไม่มีข้อมูลให้ส่งออกตามเงื่อนไขที่เลือก', 'warning');
    }

    var headers = ['รหัสบุคคล', 'ชื่อ-นามสกุล', 'เบอร์โทร', 'เพศ', 'ช่วงอายุ', 'อาชีพ',
      'พื้นที่', 'กลุ่มเป้าหมาย', 'แหล่งที่มา', 'วันที่เข้ากลุ่ม'];

    /* รอบละสามคอลัมน์ — สถานะ / ครบกำหนด / ตอบเมื่อ ชื่อคอลัมน์มาจากรอบจริงในระบบ */
    ROUND_COLUMNS.forEach(function (name) {
      headers.push(name + ' - สถานะ', name + ' - ครบกำหนด', name + ' - ตอบเมื่อ');
    });

    headers.push('รอบถัดไป', 'ครบกำหนดรอบถัดไป', 'สถานะ', 'LINE', 'ความยินยอม');

    var rows = list.map(function (m) {
      var rMap = {};
      (m.rounds || []).forEach(function (r) { rMap[r.name] = r; });

      var row = [m.pid, m.name, m.phone, m.gender, m.age, m.job,
        m.area, m.target, m.source, fmt(m.entryDate)];

      ROUND_COLUMNS.forEach(function (name) {
        var r = rMap[name];
        row.push(r ? r.state : '—', r ? fmt(r.dueDate) : '—', r && r.at ? fmt(r.at) : '—');
      });

      row.push(m.nextRound, m.nextRoundDue ? fmt(m.nextRoundDue) : '—', m.status,
        m.line ? 'เชื่อมแล้ว' : 'ยังไม่เชื่อม', m.consent ? 'ยินยอมแล้ว' : 'รอยืนยัน');

      return row;
    });

    window.TFC.exportCsv('รายการกลุ่มตัวอย่าง.csv', headers, rows);
  });

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
    name: '', phone: '',
    gender: '', ageRangeId: '', occupationId: '',
    areaId: '', targetGroupId: '', sourceCode: '',
    entryDate: lookups.today,
    status: lookups.statuses[0] || '',
    /* เก็บเป็น id ของรอบจาก master data ไม่ใช่จำนวนเดือน — ชุดรอบเปลี่ยนได้ตลอดที่หน้าตั้งค่า */
    rounds: lookups.followUpRounds.filter(function (r) { return r.checked; }).map(function (r) { return r.value; }),
    dueEdit: {}, editing: null,
    consent: false, consentFile: null, uploading: false,
    saving: false
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
      /* รหัสบุคคลไม่มีให้กรอกและไม่มีปุ่มรันเลข — เซิร์ฟเวอร์ออกให้ตอนกดบันทึก
         รหัสที่จองไว้ตั้งแต่เปิดฟอร์มชนกันได้เสมอถ้ามีคนอื่นบันทึกแทรกระหว่างนั้น */
      '<label class="co-field">' +
        '<span class="co-field-label">รหัสบุคคล</span>' +
        '<input type="text" class="input" value="" placeholder="ระบบออกรหัสให้อัตโนมัติหลังบันทึก" disabled>' +
      '</label>' +
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
      /* คนที่เพิ่งเพิ่มยังไม่มีทางผูก LINE ได้ — ช่องนี้บอกสถานะจริงไว้ให้ตรงกับหน้ารายการ
         แล้วส่งลิงก์ผูกให้ในหน้าต่างยืนยันหลังบันทึก */
      '<label class="co-field">' +
        '<span class="co-field-label">การเชื่อม LINE</span>' +
        '<input type="text" class="input" value="ยังไม่เชื่อม — ส่งลิงก์ผูกให้หลังบันทึก" disabled>' +
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
    return !!(form.name.trim() && form.phone.trim() && form.gender &&
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
    form.name = ''; form.phone = '';
    form.gender = ''; form.ageRangeId = ''; form.occupationId = '';
    form.areaId = ''; form.targetGroupId = ''; form.sourceCode = '';
    form.entryDate = lookups.today;
    form.status = lookups.statuses[0] || '';
    form.rounds = lookups.followUpRounds.filter(function (r) { return r.checked; }).map(function (r) { return r.value; });
    form.dueEdit = {}; form.editing = null;
    form.consent = false; form.consentFile = null;
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
        /* จำนวนบนแท็บนับจากรายการทั้งหมด เพิ่มคนใหม่แล้วต้องอัปเดตด้วย ไม่ใช่แค่ตาราง */
        renderTabs();
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

  applyGridWidth();
  renderHead();
  renderLegend();
  renderTabs();
  fillFilters();
  renderTable();
  renderAddModal();
})();
</script>
@endpush
