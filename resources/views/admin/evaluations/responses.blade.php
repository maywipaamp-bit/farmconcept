@extends('layouts.admin')

@section('title', 'ตอบแบบประเมิน')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <span class="is-current">ตอบแบบประเมิน</span>
  </nav>

  <div class="rl-head">
    <h1 class="rl-title">ตอบแบบประเมิน</h1>
    <button type="button" class="btn btn-outline" id="rl-export">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
      ส่งออก Excel
    </button>
  </div>

  <div class="rl-toolbar">
    <div class="rl-tabs" id="rl-tabs" role="tablist" aria-label="กรองตามรอบติดตาม"></div>
    <div class="rl-toolbar-right">
      <span class="rl-count" id="rl-count" aria-live="polite"></span>
      <div class="co-colpick" id="rl-search">
        <button type="button" class="co-search-btn" id="rl-search-btn" aria-expanded="false" aria-label="ค้นหาและกรอง">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>
        </button>
        <div class="co-colpick-panel rl-search-panel" id="rl-search-panel" hidden>
          <label class="co-field">
            <span class="co-field-label">คำค้นหา</span>
            <input type="text" class="input" id="rl-q" placeholder="รหัสบุคคล หรือชื่อรอบ" autocomplete="off">
          </label>
          <label class="co-field">
            <span class="co-field-label">แบบประเมิน</span>
            <select class="select" id="rl-form"></select>
          </label>
          <div class="fb-search-foot">
            <button type="button" class="co-link" id="rl-clear">ล้างค่า</button>
            <button type="button" class="btn btn-primary btn-sm" id="rl-apply">ดูผลลัพธ์</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card rl-card">
    <div class="rl-scroll">
      <div class="rl-table">
        <div class="rl-row rl-th">
          <div>#</div>
          <div>รหัสบุคคล</div>
          <div>แบบประเมิน</div>
          <div>รอบติดตาม</div>
          <div>ส่งเมื่อ</div>
          <div></div>
        </div>
        <div id="rl-rows"></div>
      </div>
    </div>
    <div class="rl-foot" id="rl-pagination"></div>
  </div>
@endsection

@section('modals')
<div class="modal-overlay" id="rl-detail-modal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="rl-detail-title">
    <div class="modal-header">
      <h3 class="modal-title" id="rl-detail-title">คำตอบที่ส่งเข้ามา</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p class="text-secondary small mb-3" id="rl-detail-meta"></p>
      <div id="rl-detail-body"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close-modal>ปิด</button>
    </div>
  </div>
</div>
@endsection

@push('page-script')
{{-- TFC.exportCsv อยู่ในไฟล์นี้ — ไม่ได้อยู่ในชุดสคริปต์กลางของ layout
     ก่อนหน้านี้ไม่ได้โหลด ทำให้ปุ่มส่งออกกดแล้วเงียบ (TypeError ใน console) --}}
<script src="@assetv('assets/js/activity-module.js')"></script>
<script>
/* ตอบแบบประเมิน — รายการคำตอบที่ส่งเข้ามาจริง ไม่ใช่รายชื่อแบบประเมิน

   รายการทั้งหมดมาพร้อมหน้า (หลักพันแถวเป็นอย่างมาก) กรองและแบ่งหน้าในเบราว์เซอร์
   ส่วนคำตอบรายข้อโหลดตอนกดดู เพราะผู้ใช้เปิดดูจริงไม่กี่ใบ */
(function () {
  var rows = @json($responses);
  var ROUNDS = @json($rounds);
  var FORMS = @json($forms);

  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var TABS = ['ทั้งหมด'].concat(ROUNDS);
  var PAGE_SIZES = [20, 50, 100];
  var state = { tab: 'ทั้งหมด', q: '', form: 'ทุกแบบประเมิน', page: 1, pageSize: PAGE_SIZES[0] };

  /* วัน–เวลาไทย: 16 ส.ค. 2569 · 15:08 น. — ใช้ตัวช่วยกลางเรื่องวัน ส่วนเวลาต่อท้ายเอง */
  function at(iso) {
    if (!iso) return '—';
    return window.TFC.formatThaiDate(iso.slice(0, 10)) + ' · ' + iso.slice(11, 16) + ' น.';
  }

  function matchesTab(r) { return state.tab === 'ทั้งหมด' || r.context === state.tab; }

  function filtered() {
    var q = state.q.trim().toLowerCase();

    return rows.filter(function (r) {
      if (!matchesTab(r)) return false;
      if (state.form !== 'ทุกแบบประเมิน' && r.form !== state.form) return false;
      if (!q) return true;

      return [r.pid, r.context, r.form].some(function (v) {
        return String(v || '').toLowerCase().indexOf(q) > -1;
      });
    });
  }

  function renderTabs() {
    $('rl-tabs').innerHTML = TABS.map(function (t) {
      var n = t === 'ทั้งหมด' ? rows.length : rows.filter(function (r) { return r.context === t; }).length;
      return '<button type="button" role="tab" class="rl-tab' + (state.tab === t ? ' is-on' : '') +
        '" data-tab="' + esc(t) + '" aria-selected="' + (state.tab === t) + '">' +
        esc(t) + ' · ' + n + '</button>';
    }).join('');
  }

  function render() {
    var list = filtered();
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;

    $('rl-count').textContent = 'พบ ' + list.length + ' รายการ';

    $('rl-rows').innerHTML = list.slice(start, start + state.pageSize).map(function (r, i) {
      return '<div class="rl-row">' +
        '<div class="rl-no">' + (start + i + 1) + '</div>' +
        /* แสดงเฉพาะรหัสบุคคล ไม่แสดงชื่อ (คำสั่งทีม) — กลุ่มตัวอย่างเป็นข้อมูลนิรนาม */
        '<div class="rl-person">' +
          '<span class="rl-name">' + esc(r.pid) + '</span>' +
        '</div>' +
        '<div class="rl-cell">' + esc(r.form) + '</div>' +
        '<div class="rl-cell">' + esc(r.context) + '</div>' +
        '<div class="rl-cell rl-at">' + esc(at(r.at)) + '</div>' +
        '<div class="text-right">' +
          '<button type="button" class="btn btn-outline btn-sm" data-view="' + esc(r.id) + '">' +
            'ดูคำตอบ · ' + r.answers + '</button>' +
        '</div>' +
        '</div>';
    }).join('') || '<div class="fb-empty"><span class="fb-empty-title">ยังไม่มีคำตอบที่ตรงกับเงื่อนไข</span></div>';

    window.TFC.renderPagination('rl-pagination', {
      page: state.page,
      pageSize: state.pageSize,
      total: list.length,
      pageSizeOptions: PAGE_SIZES,
      footer: true,
      onPageChange: function (p) { state.page = p; render(); },
      onPageSizeChange: function (s) { state.pageSize = s; state.page = 1; render(); }
    });
  }

  /* ---------- ตัวกรอง ---------- */
  $('rl-form').innerHTML = ['ทุกแบบประเมิน'].concat(FORMS)
    .map(function (f) { return '<option>' + esc(f) + '</option>'; }).join('');

  $('rl-tabs').addEventListener('click', function (e) {
    var tab = e.target.closest('[data-tab]');
    if (!tab) return;
    state.tab = tab.getAttribute('data-tab');
    state.page = 1;
    renderTabs();
    render();
  });

  function setPanel(open) {
    $('rl-search-panel').hidden = !open;
    $('rl-search-btn').setAttribute('aria-expanded', open ? 'true' : 'false');
    $('rl-search-btn').classList.toggle('is-on', open);
  }

  $('rl-search-btn').addEventListener('click', function (e) {
    e.stopPropagation();
    setPanel($('rl-search-panel').hidden);
  });
  $('rl-search-panel').addEventListener('click', function (e) { e.stopPropagation(); });
  document.addEventListener('click', function () { setPanel(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setPanel(false); });

  $('rl-apply').addEventListener('click', function () {
    state.q = $('rl-q').value;
    state.form = $('rl-form').value;
    state.page = 1;
    setPanel(false);
    render();
  });

  $('rl-clear').addEventListener('click', function () {
    $('rl-q').value = '';
    $('rl-form').value = 'ทุกแบบประเมิน';
    state.q = ''; state.form = 'ทุกแบบประเมิน'; state.page = 1;
    render();
  });

  /* ---------- ดูคำตอบรายข้อ ---------- */
  $('rl-rows').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-view]');
    if (!btn) return;

    var id = btn.getAttribute('data-view');
    var row = rows.find(function (r) { return String(r.id) === id; });

    $('rl-detail-title').textContent = row ? row.pid + ' · ' + row.context : 'คำตอบที่ส่งเข้ามา';
    $('rl-detail-meta').textContent = 'กำลังโหลด…';
    $('rl-detail-body').innerHTML = '';
    window.TFC.openModal('rl-detail-modal');

    fetch('{{ url('admin/evaluations/responses') }}/' + encodeURIComponent(id), {
      headers: { 'Accept': 'application/json' }
    })
      .then(function (res) { return res.json(); })
      .then(function (res) {
        if (!res.success) throw new Error('โหลดไม่สำเร็จ');
        var d = res.data;

        $('rl-detail-meta').textContent = d.form + ' · ส่งเมื่อ ' + at(d.submittedAt);

        $('rl-detail-body').innerHTML = d.answers.length
          ? '<dl class="rl-answers">' + d.answers.map(function (a, i) {
              return '<div><dt>' + (i + 1) + '. ' + esc(a.question) + '</dt>' +
                '<dd>' + esc(a.answer) + '</dd></div>';
            }).join('') + '</dl>'
          : '<p class="text-secondary">ใบนี้ไม่มีคำตอบบันทึกไว้</p>';
      })
      .catch(function () {
        $('rl-detail-meta').textContent = '';
        $('rl-detail-body').innerHTML = '<p class="text-secondary">โหลดคำตอบไม่สำเร็จ กรุณาลองใหม่</p>';
      });
  });

  /* ---------- ส่งออก ---------- */
  $('rl-export').addEventListener('click', function () {
    var list = filtered();

    window.TFC.exportCsv('ตอบแบบประเมิน.csv',
      ['ลำดับ', 'รหัสบุคคล', 'แบบประเมิน', 'รอบติดตาม', 'จำนวนข้อที่ตอบ', 'ส่งเมื่อ'],
      list.map(function (r, i) {
        return [i + 1, r.pid, r.form, r.context, r.answers, at(r.at)];
      }));
  });

  renderTabs();
  render();
})();
</script>
@endpush
