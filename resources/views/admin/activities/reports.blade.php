@extends('layouts.admin')

@section('title', 'รายงาน · '.$activity->name)

@php
  $checkinRate = $activity->registrations_count > 0
      ? round($activity->checked_in_count / $activity->registrations_count * 100)
      : null;
@endphp

@section('content')
  @include('admin.activities.partials.detail-header', ['activeTab' => 'reports'])

  <div class="aov-rp-toolbar">
    {{-- เลือกชุดรายงาน — ซ่อนการ์ดที่ไม่อยู่ในชุดที่เลือก ทีละชุดจะได้ไม่ต้องเลื่อนยาว --}}
    <div class="aov-rp-tabs" id="aov-rp-tabs" role="group" aria-label="เลือกชุดรายงาน">
      @foreach ($reportTabs as $tab)
        <button type="button" class="status-pill{{ $loop->first ? ' is-active' : '' }}" data-report-tab="{{ $tab['key'] }}">{{ $tab['label'] }}</button>
      @endforeach
    </div>

    {{-- เลือกกราฟที่จะแสดง — ซ่อนการ์ดทั้งใบ จำค่าไว้ต่อกิจกรรมใน localStorage --}}
    <div class="aov-pt-picker">
      <button type="button" class="btn btn-outline" id="aov-rp-charts-btn" aria-expanded="false">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 12h18M3 6h18M3 18h18"/><circle cx="8" cy="6" r="2" fill="currentColor" stroke="none"/><circle cx="16" cy="12" r="2" fill="currentColor" stroke="none"/><circle cx="10" cy="18" r="2" fill="currentColor" stroke="none"/></svg>
        เลือกกราฟที่แสดง
      </button>
      <div class="aov-pt-picker-panel aov-rp-picker-panel" id="aov-rp-charts-panel" hidden>
        <div class="aov-pt-picker-title">แสดงกราฟ</div>
        @foreach ($chartOptions as $chart)
          <label class="aov-pt-picker-item">
            <input type="checkbox" value="{{ $chart['key'] }}" checked>
            <span>{{ $chart['label'] }}</span>
          </label>
        @endforeach
      </div>
    </div>
  </div>

  {{-- ตัวเลขสรุปด่วน — อยู่ทุกชุดรายงาน เป็นบริบทของทุกกราฟด้านล่าง --}}
  <div class="aov-rp-kpis">
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ลงทะเบียน</span>
      <span class="aov-rp-kpi-value">{{ $activity->registrations_count }}@if ($activity->capacity > 0)<span class="aov-rp-kpi-of"> / {{ $activity->capacity }}</span>@endif</span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">เช็คอินแล้ว</span>
      <span class="aov-rp-kpi-value">{{ $activity->checked_in_count }}
        @if ($checkinRate !== null)<span class="aov-rp-kpi-of">คน · {{ $checkinRate }}%</span>@endif
      </span>
    </div>
    @if ($payment)
      <div class="aov-rp-kpi">
        <span class="aov-rp-kpi-label">รายรับรวม</span>
        <span class="aov-rp-kpi-value">{{ number_format($revenue) }}<span class="aov-rp-kpi-of">฿</span></span>
      </div>
    @endif
    @if ($survey)
      <div class="aov-rp-kpi">
        <span class="aov-rp-kpi-label">คะแนนความพึงพอใจ</span>
        <span class="aov-rp-kpi-value">
          @if ($survey['average'] !== null)
            {{ number_format($survey['average'], 1) }}<span class="aov-rp-kpi-of">/ 5</span>
          @else
            <span class="aov-rp-kpi-of">ยังไม่มีข้อมูล</span>
          @endif
        </span>
      </div>
    @endif
  </div>

  {{-- ============================================================
       คะแนนความพึงพอใจโดยรวม — การ์ดคะแนนสไตล์รีวิว ตัวเลขใหญ่นำสายตา
       ============================================================ --}}
  @if ($survey)
    <section class="card aov-rp-card aov-rp-card--wide" data-chart="survey" data-report="overview survey">
      <div class="aov-rp-survey-head">
        <h2 class="aov-section-title mb-0">คะแนนความพึงพอใจโดยรวม</h2>
        @if ($survey['average'] !== null)
          <span class="aov-pt-status {{ $survey['grade']['tone'] === 'success' ? 'is-in' : ($survey['grade']['tone'] === 'warning' ? 'is-mid' : 'is-low') }}">
            {{ $survey['grade']['label'] }}
          </span>
        @endif
      </div>

      @if ($survey['responseCount'] === 0)
        <p class="aov-empty">ยังไม่มีคำตอบแบบประเมิน</p>
      @else
        <div class="aov-rp-score">
          <div class="aov-rp-score-main">
            <span class="aov-rp-score-num">{{ number_format($survey['average'], 1) }}</span>
            <div class="aov-rp-score-stars" aria-hidden="true">
              @for ($i = 1; $i <= 5; $i++)
                <span class="aov-rp-star {{ $i <= round($survey['average']) ? 'is-filled' : '' }}">★</span>
              @endfor
            </div>
            <span class="aov-rp-score-total">{{ $survey['responseCount'] }} เรตติ้ง</span>
            @if ($survey['responseRate'] !== null)
              <span class="aov-rp-score-rate">อัตราการตอบ {{ $survey['responseRate'] }}% ของผู้เช็คอิน{{ $survey['isRepresentative'] ? '' : ' (ตัวอย่างยังน้อย)' }}</span>
            @endif
          </div>

          <div class="aov-rp-score-bars">
            @foreach ($survey['distribution'] as $band)
              <div class="aov-rp-score-row">
                <span class="aov-rp-score-row-label">{{ $band['star'] }} <span class="aov-rp-star is-filled aov-rp-star--sm">★</span></span>
                <span class="aov-rp-hbar-track">
                  <span class="aov-rp-hbar-fill" style="width: {{ max($band['percent'], 2) }}%"></span>
                </span>
                <span class="aov-rp-hbar-count">{{ $band['count'] }}</span>
              </div>
            @endforeach
          </div>
        </div>

        @if ($survey['commentCount'] > 0)
          <p class="aov-rp-survey-sub">มีความเห็นเป็นข้อความ {{ $survey['commentCount'] }} รายการ — อ่านทั้งหมดได้ที่แท็บแบบประเมิน</p>
        @endif
      @endif
    </section>
  @endif

  {{-- ============================================================
       สถานะกิจกรรม — เช็คอิน / การชำระเงิน / walk-in / รอบ
       ============================================================ --}}
  <div class="aov-rp-row">
    <section class="card aov-rp-card" data-chart="checkin" data-report="overview checkin">
      <h2 class="aov-section-title">สถานะเช็คอิน</h2>
      @if ($checkin['total'] === 0)
        <p class="aov-empty">ยังไม่มีผู้ลงทะเบียน</p>
      @else
        @include('admin.activities.partials.report-donut', ['donut' => $checkin, 'unit' => 'คน'])
      @endif
    </section>

    @if ($roundCheckinChart)
      <section class="card aov-rp-card" data-chart="roundCheckin" data-report="checkin">
        <h2 class="aov-section-title">เช็คอินแยกรายรอบ</h2>
        <p class="aov-rp-survey-sub aov-rp-survey-sub--tight">แท่งยาวเต็ม = ผู้ลงทะเบียนของรอบนั้นมาครบ</p>
        @include('admin.activities.partials.report-bar-list', ['bars' => $roundCheckinChart, 'unit' => 'คน'])
      </section>
    @endif

    @if ($payment)
      <section class="card aov-rp-card" data-chart="payment" data-report="overview registration">
        <h2 class="aov-section-title">สถานะการชำระเงิน</h2>
        @if ($payment['total'] === 0)
          <p class="aov-empty">ยังไม่มีผู้ลงทะเบียน</p>
        @else
          @include('admin.activities.partials.report-donut', ['donut' => $payment, 'unit' => 'คน'])
        @endif
      </section>
    @endif

    @if ($walkin)
      <section class="card aov-rp-card" data-chart="walkin" data-report="registration">
        <h2 class="aov-section-title">Walk-in กับลงทะเบียนล่วงหน้า</h2>
        @include('admin.activities.partials.report-donut', ['donut' => $walkin, 'unit' => 'คน'])
      </section>
    @endif

    @if ($roundChart)
      <section class="card aov-rp-card" data-chart="round" data-report="registration">
        <h2 class="aov-section-title">รอบที่ลงทะเบียน</h2>
        @include('admin.activities.partials.report-bar-list', ['bars' => $roundChart, 'unit' => 'คน'])
      </section>
    @endif
  </div>

  {{-- ============================================================
       ประชากรศาสตร์ผู้เข้าร่วม — เท่าที่แบบลงทะเบียนของกิจกรรมนี้เก็บจริง
       ============================================================ --}}
  @if ($demographics)
    <div class="aov-rp-row">
      @if ($demographics['gender']['segments'] ?? false)
        <section class="card aov-rp-card" data-chart="gender" data-report="registration">
          <h2 class="aov-section-title">เพศผู้เข้าร่วม</h2>
          @include('admin.activities.partials.report-donut', ['donut' => $demographics['gender'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($demographics['age'] ?? false)
        <section class="card aov-rp-card" data-chart="age" data-report="registration">
          <h2 class="aov-section-title">ช่วงอายุ</h2>
          @include('admin.activities.partials.report-bar-list', ['bars' => $demographics['age'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($demographics['occupation'] ?? false)
        <section class="card aov-rp-card" data-chart="occupation" data-report="registration">
          <h2 class="aov-section-title">อาชีพ</h2>
          @include('admin.activities.partials.report-bar-list', ['bars' => $demographics['occupation'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($demographics['source']['segments'] ?? false)
        <section class="card aov-rp-card" data-chart="source" data-report="registration">
          <h2 class="aov-section-title">ช่องทางที่รู้จักกิจกรรม</h2>
          @include('admin.activities.partials.report-donut', ['donut' => $demographics['source'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($demographics['area'] ?? false)
        <section class="card aov-rp-card" data-chart="area" data-report="registration">
          <h2 class="aov-section-title">พื้นที่</h2>
          @include('admin.activities.partials.report-bar-list', ['bars' => $demographics['area'], 'unit' => 'คน'])
        </section>
      @endif
    </div>

    @if ($demographics['interests'] ?? false)
      <section class="card aov-rp-card aov-rp-card--wide" data-chart="interests" data-report="registration">
        <h2 class="aov-section-title">ความสนใจ</h2>
        @include('admin.activities.partials.report-pills', ['items' => $demographics['interests']])
      </section>
    @endif
  @endif

  {{-- ============================================================
       ประชากรศาสตร์ของ "ผู้ที่มาจริง" — ชุดเดียวกับด้านบนแต่นับเฉพาะคนที่เช็คอินแล้ว
       อยู่ในรายงาน Check-in เพื่อตอบว่ากลุ่มที่มาจริงต่างจากกลุ่มที่สมัครไว้อย่างไร
       ============================================================ --}}
  @if ($checkinDemographics)
    <div class="aov-rp-row">
      @if ($checkinDemographics['gender']['segments'] ?? false)
        <section class="card aov-rp-card" data-chart="ckGender" data-report="checkin">
          <h2 class="aov-section-title">เพศ <span class="aov-rp-card-note">ผู้ที่มาจริง</span></h2>
          @include('admin.activities.partials.report-donut', ['donut' => $checkinDemographics['gender'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($checkinDemographics['age'] ?? false)
        <section class="card aov-rp-card" data-chart="ckAge" data-report="checkin">
          <h2 class="aov-section-title">ช่วงอายุ <span class="aov-rp-card-note">ผู้ที่มาจริง</span></h2>
          @include('admin.activities.partials.report-bar-list', ['bars' => $checkinDemographics['age'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($checkinDemographics['occupation'] ?? false)
        <section class="card aov-rp-card" data-chart="ckOccupation" data-report="checkin">
          <h2 class="aov-section-title">อาชีพ <span class="aov-rp-card-note">ผู้ที่มาจริง</span></h2>
          @include('admin.activities.partials.report-bar-list', ['bars' => $checkinDemographics['occupation'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($checkinDemographics['source']['segments'] ?? false)
        <section class="card aov-rp-card" data-chart="ckSource" data-report="checkin">
          <h2 class="aov-section-title">ช่องทางที่รู้จักกิจกรรม <span class="aov-rp-card-note">ผู้ที่มาจริง</span></h2>
          @include('admin.activities.partials.report-donut', ['donut' => $checkinDemographics['source'], 'unit' => 'คน'])
        </section>
      @endif

      @if ($checkinDemographics['area'] ?? false)
        <section class="card aov-rp-card" data-chart="ckArea" data-report="checkin">
          <h2 class="aov-section-title">พื้นที่ <span class="aov-rp-card-note">ผู้ที่มาจริง</span></h2>
          @include('admin.activities.partials.report-bar-list', ['bars' => $checkinDemographics['area'], 'unit' => 'คน'])
        </section>
      @endif
    </div>

    @if ($checkinDemographics['interests'] ?? false)
      <section class="card aov-rp-card aov-rp-card--wide" data-chart="ckInterests" data-report="checkin">
        <h2 class="aov-section-title">ความสนใจ <span class="aov-rp-card-note">ผู้ที่มาจริง</span></h2>
        @include('admin.activities.partials.report-pills', ['items' => $checkinDemographics['interests']])
      </section>
    @endif
  @endif

  {{-- ============================================================
       คำตอบแยกตามคำถาม — สไตล์เดียวกับสรุปผลของ Google Forms
       ============================================================ --}}
  @if (count($questionCharts))
    <h2 class="aov-rp-section-heading" data-report="survey">คำตอบแยกตามคำถาม</h2>
    <div class="aov-rp-row">
      @foreach ($questionCharts as $q)
        <section class="card aov-rp-card" data-chart="q{{ $q['id'] }}" data-report="survey">
          <h2 class="aov-section-title" title="{{ $q['label'] }}">{{ $q['label'] }}</h2>

          @if ($q['type'] === 'text')
            <p class="aov-rp-survey-sub aov-rp-survey-sub--tight">คำถามปลายเปิด · ตอบแล้ว {{ $q['answered'] }} คน</p>
            <p class="aov-empty">ดูคำตอบทั้งหมดได้ที่แท็บแบบประเมิน</p>
          @elseif ($q['answered'] === 0)
            <p class="aov-empty">ยังไม่มีคำตอบ</p>
          @elseif ($q['type'] === 'rating')
            <p class="aov-rp-survey-sub aov-rp-survey-sub--tight">เฉลี่ย {{ number_format($q['average'], 1) }} / 5 · ตอบแล้ว {{ $q['answered'] }} คน</p>
            @include('admin.activities.partials.report-bar-list', ['bars' => $q['bars'], 'unit' => 'คน'])
          @elseif ($q['type'] === 'consent')
            <p class="aov-rp-survey-sub aov-rp-survey-sub--tight">ยอมรับ {{ number_format($q['accepted']) }} จาก {{ number_format($q['answered']) }} คน</p>
            @include('admin.activities.partials.report-bar-list', ['bars' => $q['bars'], 'unit' => 'คน'])
          @elseif ($q['type'] === 'single')
            <p class="aov-rp-survey-sub aov-rp-survey-sub--tight">ตอบแล้ว {{ $q['answered'] }} คน</p>
            @include('admin.activities.partials.report-donut', ['donut' => $q['donut'], 'unit' => 'คน'])
          @else
            <p class="aov-rp-survey-sub aov-rp-survey-sub--tight">เลือกได้มากกว่าหนึ่งข้อ · ตอบแล้ว {{ $q['answered'] }} คน</p>
            @include('admin.activities.partials.report-bar-list', ['bars' => $q['bars'], 'unit' => '%'])
          @endif
        </section>
      @endforeach
    </div>
  @endif

  <p class="aov-empty" id="aov-rp-empty" hidden>ไม่มีกราฟในชุดรายงานนี้ — ลองเปิดกราฟเพิ่มที่ปุ่ม "เลือกกราฟที่แสดง"</p>
@endsection

@push('page-script')
<script type="module">
(function () {
  var storageKey = 'tfc-aov-reports-charts-' + @json($activity->code);
  var panel = document.getElementById('aov-rp-charts-panel');
  var button = document.getElementById('aov-rp-charts-btn');
  var tabsEl = document.getElementById('aov-rp-tabs');

  /* กราฟที่ผู้ใช้ปิดไว้ — จำค่าไว้ต่อกิจกรรม เปิดหน้าใหม่แล้วยังเป็นชุดเดิม */
  var hidden = [];
  try { hidden = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (e) {}

  var activeReport = tabsEl.querySelector('[data-report-tab]')
    ? tabsEl.querySelector('[data-report-tab]').getAttribute('data-report-tab')
    : '';

  /* การ์ดจะแสดงเมื่อ "อยู่ในชุดรายงานที่เลือก" และ "ไม่ถูกปิดจากแผงเลือกกราฟ" พร้อมกัน */
  function inActiveReport(el) {
    var groups = (el.getAttribute('data-report') || '').split(' ');
    return groups.indexOf(activeReport) !== -1;
  }

  function applyVisibility() {
    var shown = 0;

    document.querySelectorAll('[data-report]').forEach(function (el) {
      var chartKey = el.getAttribute('data-chart');
      var off = chartKey ? hidden.indexOf(chartKey) !== -1 : false;
      var visible = inActiveReport(el) && !off;
      el.hidden = !visible;
      if (visible && chartKey) shown++;
    });

    /* แถวที่การ์ดข้างในถูกซ่อนหมดต้องซ่อนทั้งแถวด้วย — ตัวแถวเองไม่มีความสูงแล้วก็จริง
       แต่ margin-bottom ยังนับอยู่ ทำให้เกิดช่องว่างค้างระหว่างส่วนที่เหลือ */
    document.querySelectorAll('.aov-rp-row').forEach(function (row) {
      var hasVisibleCard = Array.prototype.some.call(row.children, function (card) { return !card.hidden; });
      row.hidden = !hasVisibleCard;
    });

    document.getElementById('aov-rp-empty').hidden = shown > 0;

    panel.querySelectorAll('input[type=checkbox]').forEach(function (box) {
      box.checked = hidden.indexOf(box.value) === -1;
    });
  }

  tabsEl.addEventListener('click', function (e) {
    var tab = e.target.closest('[data-report-tab]');
    if (!tab) return;
    activeReport = tab.getAttribute('data-report-tab');
    tabsEl.querySelectorAll('[data-report-tab]').forEach(function (btn) {
      btn.classList.toggle('is-active', btn === tab);
    });
    applyVisibility();
  });

  button.addEventListener('click', function (e) {
    e.stopPropagation();
    var open = panel.hidden;
    panel.hidden = !open;
    button.setAttribute('aria-expanded', String(open));
  });

  panel.addEventListener('click', function (e) { e.stopPropagation(); });

  document.addEventListener('click', function () {
    panel.hidden = true;
    button.setAttribute('aria-expanded', 'false');
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); }
  });

  panel.addEventListener('change', function (e) {
    var box = e.target;
    if (box.type !== 'checkbox') return;
    if (box.checked) {
      hidden = hidden.filter(function (k) { return k !== box.value; });
    } else if (hidden.indexOf(box.value) === -1) {
      hidden.push(box.value);
    }
    try { localStorage.setItem(storageKey, JSON.stringify(hidden)); } catch (e2) {}
    applyVisibility();
  });

  applyVisibility();
})();
</script>
@endpush
