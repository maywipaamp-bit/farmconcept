@extends('layouts.admin')

@section('title', 'ผู้เข้าร่วมและช่องทาง')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>รายงาน</span> <span>/</span>
    <span class="is-current">ผู้เข้าร่วมและช่องทาง</span>
  </nav>

  <div class="aov-header">
    <div class="aov-header-text">
      <h1 class="aov-title">ผู้เข้าร่วมและช่องทาง</h1>
      <p class="aov-rp-toolbar-note">ประชากรผู้ลงทะเบียนรวมทุกกิจกรรม · ช่องทางที่พาคนมาได้จริง · และเส้นทางตั้งแต่ลงทะเบียนจนถึงทำแบบประเมิน</p>
    </div>
  </div>

  @include('admin.reports.partials.insights-nav', ['active' => 'participants'])

  {{-- Funnel — สี่ขั้นเรียงจากกว้างไปแคบ ความยาวแท่งเทียบกับขั้นแรกเสมอ --}}
  <section class="card aov-rp-card aov-rp-card--wide">
    <h2 class="aov-section-title">
      เส้นทางผู้เข้าร่วม
      <span class="aov-rp-card-note">เทียบกับจำนวนผู้ลงทะเบียนทั้งหมด</span>
    </h2>

    <div class="aov-rp-funnel">
      @foreach ($funnel as $stage)
        <div class="aov-rp-funnel-stage">
          <div class="aov-rp-funnel-head">
            <span class="aov-rp-funnel-label">{{ $stage['label'] }}</span>
            <span class="aov-rp-funnel-value">{{ number_format($stage['count']) }} <span class="aov-rp-funnel-pct">{{ $stage['pct'] }}%</span></span>
          </div>
          <span class="aov-rp-funnel-track">
            <span class="aov-rp-funnel-fill" style="width: {{ max($stage['pct'], 2) }}%"></span>
          </span>
        </div>
      @endforeach
    </div>

    {{-- บอกข้อจำกัดตรงนี้ ไม่ให้ตัวเลขขั้นสุดท้ายถูกอ่านว่าเป็นคนกลุ่มเดียวกับสามขั้นแรก --}}
    <p class="aov-rp-card-note">
      สามขั้นแรกไล่จากคนกลุ่มเดียวกัน · ขั้น “ทำแบบประเมิน” เป็นยอดคำตอบทั้งหมดในระบบ
      เพราะแบบประเมินหลังกิจกรรมเก็บแบบนิรนาม จับคู่กลับไปหาผู้ลงทะเบียนรายคนไม่ได้
    </p>
  </section>

  {{-- ประชากร --}}
  <div class="aov-rp-row">
    <section class="card aov-rp-card">
      <h2 class="aov-section-title">เพศ</h2>
      @if ($genderDonut['total'] === 0)
        <p class="aov-empty">ยังไม่มีข้อมูล</p>
      @else
        @include('admin.activities.partials.report-donut', ['donut' => $genderDonut, 'unit' => 'คน'])
      @endif
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">ช่วงอายุ</h2>
      @if ($ageDonut['total'] === 0)
        <p class="aov-empty">ยังไม่มีข้อมูล</p>
      @else
        @include('admin.activities.partials.report-donut', ['donut' => $ageDonut, 'unit' => 'คน'])
      @endif
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">อาชีพ</h2>
      @if ($occupationBars === [])
        <p class="aov-empty">ยังไม่มีข้อมูล</p>
      @else
        @include('admin.activities.partials.report-bar-list', ['bars' => $occupationBars, 'unit' => 'คน'])
      @endif
    </section>
  </div>

  {{-- ผู้เข้าร่วมซ้ำ --}}
  <div class="aov-rp-kpis">
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ผู้มาร่วมกิจกรรมจริง</span>
      <span class="aov-rp-kpi-value">{{ number_format($repeat['people']) }}<span class="aov-rp-kpi-of">คน</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">กลับมาร่วมซ้ำ</span>
      <span class="aov-rp-kpi-value">{{ number_format($repeat['repeat']) }}<span class="aov-rp-kpi-of">คน · {{ $repeat['repeatPct'] }}%</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">มาครั้งเดียว</span>
      <span class="aov-rp-kpi-value">{{ number_format($repeat['people'] - $repeat['repeat']) }}<span class="aov-rp-kpi-of">คน</span></span>
    </div>
  </div>

  {{-- ช่องทางรับรู้ --}}
  <div class="aov-rp-row">
    <section class="card aov-rp-card">
      <h2 class="aov-section-title">ช่องทางที่รู้จักกิจกรรม <span class="aov-rp-card-note">ตามจำนวนผู้ลงทะเบียน</span></h2>
      @if ($channelBars === [])
        <p class="aov-empty">ยังไม่มีข้อมูล</p>
      @else
        @include('admin.activities.partials.report-bar-list', ['bars' => $channelBars, 'unit' => 'คน'])
      @endif
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">
        คุณภาพของช่องทาง
        <span class="aov-rp-card-note">ลงทะเบียนแล้วมาจริงกี่ %</span>
      </h2>
      @if ($channels === [])
        <p class="aov-empty">ยังไม่มีข้อมูล</p>
      @else
        <div class="aov-pt-scroll">
          <table class="aov-pt-table">
            <thead>
              <tr>
                <th>ช่องทาง</th>
                <th>ลงทะเบียน</th>
                <th>มาจริง</th>
                <th>อัตรามาจริง</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($channels as $channel)
                <tr>
                  <td>{{ $channel['label'] }}</td>
                  <td>{{ number_format($channel['count']) }}</td>
                  <td>{{ number_format($channel['checkedIn']) }}</td>
                  <td>{{ $channel['checkinRate'] }}%</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>
  </div>
@endsection
