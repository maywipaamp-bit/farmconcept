@extends('layouts.admin')

@section('title', 'ภาพรวมกิจกรรม')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>รายงาน</span> <span>/</span>
    <span class="is-current">ภาพรวมกิจกรรม</span>
  </nav>

  <div class="aov-header">
    <div class="aov-header-text">
      <h1 class="aov-title">ภาพรวมกิจกรรม</h1>
      <p class="aov-rp-toolbar-note">สรุปภาพกว้างของทุกกิจกรรมในระบบ — จำนวน แนวโน้มรายเดือน กิจกรรมยอดนิยม และคะแนนความพึงพอใจโดยรวม</p>
    </div>
  </div>

  @include('admin.reports.partials.insights-nav', ['active' => 'overview'])

  {{-- ตัวเลขสรุป --}}
  <div class="aov-rp-kpis">
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">กิจกรรมทั้งหมด</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['activities']) }}<span class="aov-rp-kpi-of">กิจกรรม</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ผู้ลงทะเบียนสะสม</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['registrations']) }}<span class="aov-rp-kpi-of">คน</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">เช็คอินสำเร็จ</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['checkedIn']) }}<span class="aov-rp-kpi-of">คน · {{ $kpis['checkinRate'] }}%</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">คะแนนความพึงพอใจเฉลี่ย</span>
      <span class="aov-rp-kpi-value">{{ $kpis['avgScore'] !== null ? number_format($kpis['avgScore'], 1) : '—' }}<span class="aov-rp-kpi-of">จาก 5</span></span>
    </div>
  </div>

  {{-- แนวโน้มรายเดือน — ต้องอยู่ใต้ .dbo เพราะสี/เส้นของกราฟอ้างตัวแปรที่ประกาศไว้ใต้คลาสนั้น --}}
  @php
      $tip = function (string $key, string $title, array $lines): string {
          $payload = json_encode(['title' => $title, 'lines' => $lines], JSON_UNESCAPED_UNICODE);

          return 'data-dbo-key="' . e($key) . '" data-dbo-tip="' . e($payload) . '"';
      };
  @endphp
  <div class="dbo" id="dbo-body">
    <section class="dbo-card aov-rp-card--wide">
      <div class="dbo-card-head">
        <div class="dbo-card-title">
          <h2>แนวโน้มกิจกรรมและผู้ลงทะเบียนรายเดือน</h2>
          <span class="dbo-sub">6 เดือนล่าสุด</span>
        </div>
      </div>
      @include('admin.reports.partials.trend-line', ['chart' => $monthlyTrend, 'chartId' => 'monthly', 'tip' => $tip])
    </section>

    <section class="dbo-card aov-rp-card--wide">
      <div class="dbo-card-head">
        <div class="dbo-card-title">
          <h2>แนวโน้มคะแนนความพึงพอใจเฉลี่ยรายเดือน</h2>
          <span class="dbo-sub">คะแนนเต็ม 5 · เฉพาะเดือนที่มีคำตอบ</span>
        </div>
      </div>
      @include('admin.reports.partials.trend-line', ['chart' => $scoreTrend, 'chartId' => 'score', 'tip' => $tip])
    </section>
  </div>

  <div class="dbo-tip" id="dbo-tip" role="tooltip" aria-hidden="true">
    <div class="dbo-tip-head">
      <span class="dbo-tip-dot" data-tip-dot></span>
      <span class="dbo-tip-title" data-tip-title></span>
    </div>
    <div class="dbo-tip-lines" data-tip-lines></div>
  </div>

  {{-- Top 5 กิจกรรม + สถานะกิจกรรม --}}
  <div class="aov-rp-row">
    <section class="card aov-rp-card" data-report="overview">
      <h2 class="aov-section-title">กิจกรรมยอดนิยม 5 อันดับ <span class="aov-rp-card-note">ตามจำนวนผู้ลงทะเบียน</span></h2>
      @if ($topActivities === [])
        <p class="aov-empty">ยังไม่มีผู้ลงทะเบียน</p>
      @else
        @include('admin.activities.partials.report-bar-list', ['bars' => $topActivities, 'unit' => 'คน'])
      @endif
    </section>

    <section class="card aov-rp-card" data-report="overview">
      <h2 class="aov-section-title">สถานะกิจกรรม</h2>
      @if ($statusDonut['total'] === 0)
        <p class="aov-empty">ยังไม่มีกิจกรรมในระบบ</p>
      @else
        @include('admin.activities.partials.report-donut', ['donut' => $statusDonut, 'unit' => 'กิจกรรม'])
      @endif
    </section>
  </div>
@endsection

@push('page-script')
  <script src="{{ asset('assets/js/dashboard-insight.js') }}"></script>
@endpush
