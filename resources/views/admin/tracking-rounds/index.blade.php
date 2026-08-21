@extends('layouts.admin')

@section('title', 'รอบติดตาม')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <span class="is-current">รอบติดตาม</span>
  </nav>

  <div class="fb-header">
    <h1 class="fb-title">รอบติดตาม</h1>
    <div class="flex gap-2">
      {{-- ปุ่ม QR ย้ายไปหน้า "ตอบแบบประเมิน" แล้ว — QR พาไปหน้าลงทะเบียนกลุ่มตัวอย่าง
           ซึ่งเป็นงานหาคนเข้ามาตอบ ไม่ใช่งานตั้งรอบติดตามที่ทำอยู่บนหน้านี้ --}}
      <a class="btn btn-primary" href="{{ route('admin.tracking-rounds.create') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        สร้างรอบติดตาม
      </a>
    </div>
  </div>

  <div class="fb-filter-bar">
    <div class="fb-tabs" id="fb-tabs" role="tablist"></div>
    <div class="fb-filter-right">
      <span class="fb-count" id="fb-count"></span>
      <div class="fb-search" id="fb-search">
        <button type="button" class="fb-search-btn" id="fb-search-btn" aria-expanded="false" aria-label="ค้นหาและกรอง">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>
        </button>
        <div class="fb-search-panel" id="fb-search-panel" hidden>
          <span class="fb-search-arrow"></span>
          <label class="co-field">
            <span class="co-field-label">คำค้นหา</span>
            <input type="text" class="input" id="fb-q" placeholder="ชื่อรอบติดตาม" autocomplete="off">
          </label>
          <label class="co-field">
            <span class="co-field-label">แบบประเมิน</span>
            <select class="select" id="fb-form"></select>
          </label>
          <div class="fb-search-foot">
            <button type="button" class="co-link" id="fb-clear">ล้างค่า</button>
            <button type="button" class="btn btn-primary btn-sm" id="fb-apply">ดูผลลัพธ์</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card fb-table-card">
    <div class="fb-table-scroll">
      <div class="fb-table">
        <div class="fb-tr fb-th">
          <div>#</div>
          <div>ชื่อรอบติดตาม</div>
          <div>ช่วงวันครบกำหนด</div>
          <div class="text-center">จำนวนติดตาม</div>
          <div>ตอบแล้ว</div>
          <div class="text-center">แจ้งเตือนได้</div>
          <div class="text-center">แจ้งเตือนไม่ได้</div>
          <div>สถานะ</div>
          <div></div>
        </div>
        <div id="fb-rows"></div>
      </div>
    </div>
    <div class="fb-foot" id="fb-foot"></div>
  </div>
@endsection

@push('page-script')
<script>
(function () {
  var batches = @json($batches);
  var forms = @json($forms->pluck('label'));
  var STATES = @json($states);
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var esc = window.TFC.escapeHtml;
  var fmt = window.TFC.formatThaiDate;
  var $ = function (id) { return document.getElementById(id); };

  var TABS = ['ทั้งหมด'].concat(STATES);
  var PAGE_SIZES = [10, 20, 50];

  var state = {
    tab: 'ทั้งหมด', q: '', form: 'ทุกแบบประเมิน',
    searchOpen: false, menu: null, page: 1, pageSize: PAGE_SIZES[0]
  };

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return batches.filter(function (b) {
      if (state.tab !== 'ทั้งหมด' && b.state !== state.tab) return false;
      if (state.form !== 'ทุกแบบประเมิน' && b.form !== state.form) return false;
      if (q && b.name.toLowerCase().indexOf(q) < 0) return false;
      return true;
    });
  }

  function hasFilter() { return !!state.q.trim() || state.form !== 'ทุกแบบประเมิน'; }

  function renderTabs() {
    $('fb-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="fb-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + '</button>';
    }).join('');
  }

  function fillFilters() {
    $('fb-form').innerHTML = ['ทุกแบบประเมิน'].concat(forms)
      .map(function (v) { return '<option value="' + esc(v) + '">' + esc(v) + '</option>'; }).join('');
  }

  function syncSearch() {
    $('fb-search-btn').classList.toggle('is-on', state.searchOpen || hasFilter());
    $('fb-search-btn').setAttribute('aria-expanded', String(state.searchOpen));
    $('fb-search-panel').hidden = !state.searchOpen;
  }

  var STATE_CLASS = {
    'กำลังดำเนินการ': 'is-active', 'รอเริ่ม': 'is-waiting',
    'เสร็จสิ้น': 'is-done', 'ยกเลิกแล้ว': 'is-cancelled'
  };

  function rowHtml(b, index) {
    var open = state.menu === b.id;

    return '<div class="fb-tr is-clickable" data-open="' + esc(b.id) + '">' +
      '<div class="fb-cell fb-nums fb-no">' + index + '</div>' +
      '<div class="fb-name-cell"><span class="fb-name">' + esc(b.name) + '</span></div>' +
      '<div class="fb-cell fb-nums">' + esc(fmt(b.from)) + ' – ' + esc(fmt(b.to)) + '</div>' +
      '<div class="fb-cell fb-nums text-center">' + b.total + '</div>' +
      /* ตัวเลข "ตอบแล้ว/ทั้งหมด" อ่านได้ตรงกว่าแท่งกราฟ — แท่งบอกสัดส่วนคร่าว ๆ ที่ตัวเลขบอกอยู่แล้ว
         และกินความกว้างจนคอลัมน์อื่นถูกบีบ */
      '<div class="fb-cell fb-nums">' + b.answered + '/' + b.total + '</div>' +
      '<div class="fb-cell fb-nums text-center">' + b.notifiable + '</div>' +
      /* คนที่แจ้งเตือนไม่ได้ต้องเด่นพอให้แอดมินเห็นว่ามีงานต้องตามเอง */
      '<div class="fb-cell fb-nums text-center' + (b.unreachable > 0 ? ' is-warn' : '') + '">' + b.unreachable + '</div>' +
      '<div><span class="fb-state ' + STATE_CLASS[b.state] + '">' + esc(b.state) + '</span></div>' +
      '<div class="fb-actions">' +
        '<button type="button" class="fb-more' + (open ? ' is-on' : '') + '" data-menu="' + esc(b.id) +
          '" aria-haspopup="menu" aria-expanded="' + open + '" aria-label="เมนูจัดการ ' + esc(b.name) + '">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>' +
        '</button>' +
        (open
          /* ส่งแจ้งเตือนอีกครั้งถูกตัดออกจากเมนูนี้ — การส่งซ้ำทั้งรอบยิงถึงคนที่ตอบไปแล้วด้วย
             ตอนนี้ส่งเป็นรายคนได้จากหน้ารายละเอียด ซึ่งเห็นว่าใครยังไม่ตอบก่อนกดส่ง */
          ? '<div class="fb-menu" role="menu">' +
              '<a class="fb-menu-item" role="menuitem" href="' + esc(b.url) + '">ดูรายละเอียด</a>' +
              '<button type="button" class="fb-menu-item is-danger" role="menuitem" data-cancel="' + esc(b.id) + '"' +
                (b.cancelled ? ' disabled' : '') + '>ยกเลิกรอบติดตาม</button>' +
            '</div>'
          : '') +
      '</div>' +
      '</div>';
  }

  var EMPTY_HTML = '<div class="fb-empty">' +
    '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>' +
    '<span class="fb-empty-title">ยังไม่มีรอบติดตามที่ตรงกับเงื่อนไข</span></div>';

  function render() {
    var list = filtered();
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;
    var pageRows = list.slice(start, start + state.pageSize);

    $('fb-rows').innerHTML = pageRows.length
      ? pageRows.map(function (b, i) { return rowHtml(b, start + i + 1); }).join('')
      : EMPTY_HTML;

    $('fb-count').textContent = 'แสดง ' + list.length + ' จาก ' + batches.length + ' รอบ';
    $('fb-foot').innerHTML = '<div id="fb-pagination"></div>';

    window.TFC.renderPagination('fb-pagination', {
      page: state.page, pageSize: state.pageSize, total: list.length, pageSizeOptions: PAGE_SIZES,
      onChange: function (p) { state.page = p; render(); },
      onPageSizeChange: function (s) { state.pageSize = s; state.page = 1; render(); }
    });

    flipMenu();
    syncSearch();
  }

  function flipMenu() {
    var menu = document.querySelector('.fb-menu');
    var scroller = document.querySelector('.fb-table-scroll');
    if (!menu || !scroller) return;
    menu.classList.remove('is-up');
    if (menu.getBoundingClientRect().bottom > scroller.getBoundingClientRect().bottom) menu.classList.add('is-up');
  }

  function replaceBatch(data) {
    var i = batches.findIndex(function (b) { return b.id === data.id; });
    if (i > -1) batches[i] = data;
  }

  function post(url, method) {
    return fetch(url, {
      method: method || 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); });
  }

  /* ---------- เหตุการณ์ ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var tab = t.closest('[data-tab]');
    if (tab) { state.tab = tab.getAttribute('data-tab'); state.page = 1; state.menu = null; renderTabs(); return render(); }

    if (t.closest('#fb-search-btn')) { state.searchOpen = !state.searchOpen; return syncSearch(); }

    if (t.closest('#fb-clear')) {
      state.q = ''; state.form = 'ทุกแบบประเมิน';
      $('fb-q').value = ''; $('fb-form').value = 'ทุกแบบประเมิน';
      state.page = 1;
      return render();
    }

    if (t.closest('#fb-apply')) {
      state.q = $('fb-q').value;
      state.form = $('fb-form').value;
      state.page = 1;
      state.searchOpen = false;
      return render();
    }

    var menu = t.closest('[data-menu]');
    if (menu) {
      e.stopPropagation();
      var mid = menu.getAttribute('data-menu');
      state.menu = state.menu === mid ? null : mid;
      return render();
    }

    var cancel = t.closest('[data-cancel]');
    if (cancel) {
      e.stopPropagation();
      if (cancel.disabled) return;
      var cid = cancel.getAttribute('data-cancel');
      post('{{ url('admin/tracking-rounds') }}/' + encodeURIComponent(cid) + '/cancel', 'PATCH').then(function (res) {
        state.menu = null;
        if (!res.ok) return render(), window.TFC.showToast(res.body.message || 'ยกเลิกไม่สำเร็จ', 'danger');
        replaceBatch(res.body.data);
        render();
        window.TFC.showToast(res.body.message, 'info');
      });
      return;
    }

    /* คลิกที่แถว (นอกเมนู) เข้าหน้ารายละเอียด */
    var row = t.closest('[data-open]');
    if (row && !t.closest('.fb-actions')) {
      var b = batches.find(function (x) { return x.id === row.getAttribute('data-open'); });
      if (b) location.href = b.url;
      return;
    }

    if (state.menu && !t.closest('.fb-actions')) { state.menu = null; render(); }
    if (state.searchOpen && !t.closest('.fb-search')) { state.searchOpen = false; syncSearch(); }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (state.menu) { state.menu = null; render(); }
    if (state.searchOpen) { state.searchOpen = false; syncSearch(); }
  });

  fillFilters();
  renderTabs();
  render();
})();
</script>
@endpush
