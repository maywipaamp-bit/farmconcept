@extends('layouts.admin')

@section('title', 'ผู้เข้าร่วมทั้งหมด')

@section('content')
  {{-- "รายงาน" เป็นหัวข้อหมวด ไม่มีหน้าของตัวเอง จึงเป็นข้อความเปล่า ไม่ใช่ลิงก์ --}}
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>รายงาน</span> <span>/</span>
    <span class="is-current">ผู้เข้าร่วมทั้งหมด</span>
  </nav>

  <div class="aov-header">
    <div class="aov-header-text">
      <h1 class="aov-title">ผู้เข้าร่วมทั้งหมด</h1>
      {{-- บอกเกณฑ์การนับไว้ตรงนี้ ไม่ให้ตัวเลขถูกตีความผิดว่าเป็นจำนวนครั้งที่ลงทะเบียน --}}
      <p class="aov-rp-toolbar-note">ทุกครั้งที่มีคนมาร่วมกิจกรรมจริง (เช็คอินแล้ว) หนึ่งแถวคือหนึ่งครั้ง คนที่มาหลายกิจกรรมจึงมีหลายแถว · คอลัมน์ "เคยมาแล้ว" นับรวมทุกกิจกรรมของคนนั้น โดยยึดเบอร์โทรเป็นหลักและใช้อีเมลเมื่อไม่มีเบอร์</p>
    </div>
  </div>

  {{-- ภาพรวมฐานคนเป็นโดนัทสองวง แทนแถวการ์ดตัวเลขแบบเดิม

       การ์ดตัวเลขบอกได้แค่ยอดกับเปอร์เซ็นต์ทีละตัว ต้องอ่านทีละใบแล้วประกอบภาพเอง
       โดนัทบอกสัดส่วนได้ในสายตาเดียว ซึ่งเป็นสิ่งที่หน้านี้ต้องการจริง ๆ
       ส่วนยอดดิบยังอยู่ครบที่หัวตารางและชิปกรองด้านล่าง จึงไม่มีตัวเลขไหนหายไป --}}
  @if ($summary['total'] > 0)
    <div class="aov-rp-row aov-rp-row--donuts">
      <section class="card aov-rp-card">
        <h2 class="aov-section-title">ผู้เข้าร่วมแยกตามกลุ่ม</h2>
        @include('admin.activities.partials.report-donut', ['donut' => $byGroup, 'unit' => 'คน'])
      </section>

      <section class="card aov-rp-card">
        <h2 class="aov-section-title">จำนวนกิจกรรมที่เข้าร่วมต่อคน</h2>
        @include('admin.activities.partials.report-donut', ['donut' => $frequency, 'unit' => 'คน'])
      </section>
    </div>
  @endif

  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <div>
        <h2 class="aov-section-title mb-0">รายชื่อผู้เข้าร่วม</h2>
        <div class="aov-pt-toolbar-sub" id="ap-count">มาร่วมทั้งหมด {{ number_format($summary['registrations']) }} ครั้ง · {{ number_format($summary['total']) }} คน</div>
      </div>
      <div class="aov-pt-tools">
        <input type="search" class="input aov-ap-search" id="ap-search"
               placeholder="ค้นหาชื่อ เบอร์โทร หรืออีเมล" aria-label="ค้นหาผู้เข้าร่วม">
        <button type="button" class="btn btn-outline" id="ap-export" {{ $people->isEmpty() ? 'disabled' : '' }}>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
          ส่งออก Excel
        </button>
      </div>
    </div>

    {{-- ชิปกรอง — ชุดเดียวกับที่ KPI ด้านบนบอก กดแล้วเห็นว่าคนกลุ่มนั้นเป็นใครบ้าง --}}
    <div class="status-pills aov-ap-filters" id="ap-filters" role="group" aria-label="กรองผู้เข้าร่วม">
      <button type="button" class="status-pill is-active" data-filter="all">
        ทั้งหมด <span class="status-pill-count">{{ $summary['registrations'] }}</span>
      </button>
      <button type="button" class="status-pill" data-filter="cohort">
        เป็นกลุ่มตัวอย่าง <span class="status-pill-count">{{ $summary['cohort'] }}</span>
      </button>
      <button type="button" class="status-pill" data-filter="non-cohort">
        ยังไม่เป็นกลุ่มตัวอย่าง <span class="status-pill-count">{{ $summary['total'] - $summary['cohort'] }}</span>
      </button>
      <button type="button" class="status-pill" data-filter="repeat">
        กลับมาร่วมซ้ำ <span class="status-pill-count">{{ $summary['repeat'] }}</span>
      </button>
    </div>

    @if ($people->isEmpty())
      <div class="state-placeholder">
        <span class="state-placeholder-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg></span>
        <div class="state-placeholder-title">ยังไม่มีผู้มาร่วมกิจกรรม</div>
        <div class="state-placeholder-desc">รายชื่อจะแสดงที่นี่เมื่อมีผู้เช็คอินเข้าร่วมกิจกรรมใดกิจกรรมหนึ่ง</div>
      </div>
    @else
      <div class="aov-pt-scroll">
        <table class="aov-pt-table">
          <thead>
            <tr>
              <th class="aov-pt-num">#</th>
              <th>ชื่อผู้เข้าร่วม</th>
              <th>เบอร์โทร</th>
              <th>อีเมล</th>
              <th>พื้นที่</th>
              <th>กลุ่มตัวอย่าง</th>
              <th>กิจกรรมที่มาร่วม</th>
              <th>วันที่มาร่วม</th>
              <th>เคยมาแล้ว</th>
            </tr>
          </thead>
          <tbody id="ap-rows">
            @foreach ($people as $index => $person)
              <tr data-index="{{ $index }}"
                  data-cohort="{{ $person['isCohort'] ? '1' : '0' }}"
                  data-activities="{{ $person['activities'] }}"
                  data-person="{{ $person['phone'] ?: ($person['email'] ?: 'row:'.$index) }}"
                  data-search="{{ mb_strtolower($person['name'].' '.$person['phone'].' '.$person['email'].' '.$person['activityName']) }}">
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                <td>{{ $person['name'] }}</td>
                <td>{{ $person['phone'] ?: '—' }}</td>
                <td>{{ $person['email'] ?: '—' }}</td>
                <td>{{ $person['area'] ?: '—' }}</td>
                <td>
                  @if ($person['isCohort'])
                    <span class="aov-pt-status is-in">เป็นกลุ่มตัวอย่าง</span>
                    @if ($person['cohortSince'])
                      <div class="aov-pt-walkin">ตั้งแต่ {{ $person['cohortSince'] }}</div>
                    @endif
                  @else
                    <span class="aov-pt-status is-out">ยังไม่เป็น</span>
                  @endif
                </td>
                <td>
                  <div>{{ $person['activityName'] }}</div>
                  @if ($person['activityDate'])
                    <div class="aov-pt-walkin">{{ $person['activityDate'] }}</div>
                  @endif
                </td>
                <td>{{ $person['joinedAt'] ?: '—' }}</td>
                <td>
                  {{-- กดเพื่อดูว่าคนนี้ไปกิจกรรมไหนมาบ้าง — ข้อมูลอยู่ในหน้าแล้ว ไม่ต้องยิงคำขอเพิ่ม --}}
                  <button type="button" class="aov-pt-history" data-history-index="{{ $index }}">
                    {{ $person['activities'] }} ครั้ง
                  </button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="aov-pt-foot" id="ap-foot">แสดง {{ $people->count() }} จาก {{ $people->count() }} ครั้ง</div>
    @endif
  </div>
@endsection

@section('modals')
{{-- ประวัติกิจกรรมของคนคนนี้ --}}
<div class="modal-overlay" id="ap-history-modal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">กิจกรรมที่มาร่วม</h3>
        <div class="aov-pt-history-sub" id="ap-history-sub"></div>
      </div>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <table class="aov-pt-history-table">
        <thead>
          <tr><th class="aov-pt-num">#</th><th>ชื่อกิจกรรม</th><th>วันที่จัด</th></tr>
        </thead>
        <tbody id="ap-history-rows"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close-modal>ปิด</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
<script type="module">
(function () {
  var people = @json($people);
  var rows = Array.prototype.slice.call(document.querySelectorAll('#ap-rows tr'));
  if (rows.length === 0) return;

  var state = { filter: 'all', search: '' };

  /* กรองและค้นหาทำที่ฝั่งหน้าจอ — ข้อมูลทั้งชุดอยู่ในหน้าแล้วและเป็นหลักร้อยแถว
     ยิงกลับเซิร์ฟเวอร์ทุกครั้งที่พิมพ์จะช้ากว่าโดยไม่ได้อะไรเพิ่ม */
  function matches(tr) {
    var isCohort = tr.getAttribute('data-cohort') === '1';
    var activities = parseInt(tr.getAttribute('data-activities'), 10);

    if (state.filter === 'cohort' && !isCohort) return false;
    if (state.filter === 'non-cohort' && isCohort) return false;
    if (state.filter === 'repeat' && activities < 2) return false;

    return !state.search || tr.getAttribute('data-search').indexOf(state.search) !== -1;
  }

  function render() {
    var shown = 0;

    rows.forEach(function (tr) {
      var ok = matches(tr);
      tr.hidden = !ok;
      if (ok) {
        shown++;
        /* เลขลำดับต้องไล่ตามที่เห็นจริง ไม่ใช่ค้างเลขเดิมตอนกรองแล้วข้ามเป็นช่วง ๆ */
        tr.querySelector('.aov-pt-num').textContent = shown;
      }
    });

    document.getElementById('ap-foot').textContent = 'แสดง ' + shown + ' จาก ' + rows.length + ' ครั้ง';
    /* นับ "คน" จากเบอร์/อีเมลที่ไม่ซ้ำในแถวที่ยังแสดงอยู่ ไม่ใช่จำนวนแถว
       เพราะคนเดียวมีได้หลายแถว ตัวเลขสองตัวนี้จึงต่างกันเสมอเมื่อมีคนกลับมาซ้ำ */
    var seen = {};
    rows.forEach(function (tr) {
      if (!tr.hidden) seen[tr.getAttribute('data-person')] = 1;
    });
    document.getElementById('ap-count').textContent =
      'มาร่วมทั้งหมด ' + shown + ' ครั้ง · ' + Object.keys(seen).length + ' คน';
  }

  document.getElementById('ap-filters').addEventListener('click', function (e) {
    var pill = e.target.closest('[data-filter]');
    if (!pill) return;
    state.filter = pill.getAttribute('data-filter');
    this.querySelectorAll('[data-filter]').forEach(function (btn) {
      btn.classList.toggle('is-active', btn === pill);
    });
    render();
  });

  var searchTimer = null;
  ['input', 'search'].forEach(function (evt) {
    document.getElementById('ap-search').addEventListener(evt, function () {
      var value = this.value.trim().toLowerCase();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { state.search = value; render(); }, 200);
    });
  });

  /* popup ประวัติ — รายการกิจกรรมของแต่ละคนถูกส่งมากับหน้าแล้ว */
  document.querySelectorAll('[data-history-index]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var person = people[parseInt(btn.getAttribute('data-history-index'), 10)];
      if (!person) return;

      document.getElementById('ap-history-sub').textContent =
        person.name + (person.phone ? ' · ' + person.phone : '') + ' · เคยมาแล้ว ' + person.activities + ' ครั้ง';

      document.getElementById('ap-history-rows').innerHTML = person.history.map(function (item, i) {
        return '<tr><td class="aov-pt-num">' + (i + 1) + '</td>' +
          '<td>' + window.TFC.escapeHtml(item.name) + '</td>' +
          '<td>' + window.TFC.escapeHtml(item.date || '—') + '</td></tr>';
      }).join('');

      window.TFC.openModal('ap-history-modal');
    });
  });

  document.getElementById('ap-export').addEventListener('click', function () {
    var visible = rows.filter(function (tr) { return !tr.hidden; });
    window.TFC.exportCsv(
      'ผู้เข้าร่วมทั้งหมด.csv',
      ['#', 'ชื่อผู้เข้าร่วม', 'เบอร์โทร', 'อีเมล', 'พื้นที่', 'กลุ่มตัวอย่าง', 'เข้ากลุ่มตัวอย่างเมื่อ',
       'กิจกรรมที่มาร่วม', 'วันที่จัดกิจกรรม', 'วันที่มาร่วม', 'เคยมาแล้ว (ครั้ง)'],
      visible.map(function (tr, i) {
        var p = people[parseInt(tr.getAttribute('data-index'), 10)];
        return [i + 1, p.name, p.phone, p.email, p.area, p.isCohort ? 'เป็นกลุ่มตัวอย่าง' : 'ยังไม่เป็น',
                p.cohortSince, p.activityName, p.activityDate, p.joinedAt, p.activities];
      })
    );
  });

  render();
})();
</script>
@endpush
