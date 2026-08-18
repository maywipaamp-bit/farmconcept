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
      <p class="aov-rp-toolbar-note">โครงสร้างผู้ลงทะเบียนทุกกิจกรรมรวมกัน — เพศ อายุ อาชีพ การกลับมาซ้ำ ช่องทางที่รู้จักกิจกรรม และเส้นทางจากลงทะเบียนถึงทำแบบประเมิน</p>
    </div>
  </div>

  @include('admin.reports.partials.insights-nav', ['active' => 'participants'])

  {{-- เพศ / อายุ / อาชีพ --}}
  <div class="aov-rp-row">
    <section class="card aov-rp-card">
      <h2 class="aov-section-title">เพศ</h2>
      @if ($genderDonut['total'] === 0)
        <p class="aov-empty">ยังไม่มีผู้ลงทะเบียน</p>
      @else
        @include('admin.activities.partials.report-donut', ['donut' => $genderDonut, 'unit' => 'คน'])
      @endif
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">ช่วงอายุ</h2>
      @if ($ageDonut['total'] === 0)
        <p class="aov-empty">ยังไม่มีผู้ลงทะเบียน</p>
      @else
        @include('admin.activities.partials.report-donut', ['donut' => $ageDonut, 'unit' => 'คน'])
      @endif
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">อาชีพ</h2>
      @if ($occupationBars === [])
        <p class="aov-empty">ยังไม่มีผู้ลงทะเบียน</p>
      @else
        @include('admin.activities.partials.report-bar-list', ['bars' => $occupationBars, 'unit' => 'คน'])
      @endif
    </section>
  </div>

  {{-- ผู้เข้าร่วมซ้ำ + Funnel --}}
  <div class="aov-rp-row">
    <section class="card aov-rp-card">
      <h2 class="aov-section-title">การกลับมาร่วมซ้ำ <span class="aov-rp-card-note">นับจากคนที่เช็คอินแล้ว</span></h2>
      <div class="aov-rp-kpis" style="margin-bottom: 0;">
        <div class="aov-rp-kpi">
          <span class="aov-rp-kpi-label">ผู้มาร่วมจริง</span>
          <span class="aov-rp-kpi-value">{{ number_format($repeat['people']) }}<span class="aov-rp-kpi-of">คน</span></span>
        </div>
        <div class="aov-rp-kpi">
          <span class="aov-rp-kpi-label">กลับมาร่วมซ้ำ</span>
          <span class="aov-rp-kpi-value">{{ number_format($repeat['repeat']) }}<span class="aov-rp-kpi-of">คน · {{ $repeat['repeatPct'] }}%</span></span>
        </div>
      </div>
      <p class="aov-rp-card-note" style="margin-top: var(--space-3);">
        ดูรายชื่อเป็นรายคนได้ที่ <a href="{{ route('admin.reports.people') }}">ผู้เข้าร่วมทั้งหมด</a>
      </p>
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">เส้นทางผู้เข้าร่วม <span class="aov-rp-card-note">ลงทะเบียน → ชำระ → เช็คอิน → ประเมิน</span></h2>
      @include('admin.activities.partials.report-bar-list', ['bars' => $funnel, 'unit' => 'คน'])
      <p class="aov-rp-card-note">
        ขั้น "ชำระเงิน" นับกิจกรรมฟรีว่าผ่านขั้นนี้ทันที ·
        ขั้น "ทำแบบประเมิน" เป็นยอดคำตอบรวม (แบบประเมินไม่ระบุตัวตน จึงเทียบรายคนกับขั้นก่อนหน้าไม่ได้)
      </p>
    </section>
  </div>

  {{-- ประสิทธิภาพช่องทางรับรู้ --}}
  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <h2 class="aov-section-title mb-0">ประสิทธิภาพช่องทางรับรู้</h2>
    </div>
    @if ($channels === [])
      <div class="state-placeholder">
        <div class="state-placeholder-title">ยังไม่มีข้อมูลช่องทาง</div>
        <div class="state-placeholder-desc">ช่องทางรับรู้จะแสดงเมื่อผู้ลงทะเบียนเลือกตอบว่ารู้จักกิจกรรมจากที่ไหน</div>
      </div>
    @else
      <div class="aov-pt-scroll">
        <table class="aov-pt-table">
          <thead>
            <tr>
              <th class="aov-pt-num">#</th>
              <th>ช่องทาง</th>
              <th>ลงทะเบียน</th>
              <th>มาจริง (เช็คอิน)</th>
              <th>อัตรามาจริง</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($channels as $index => $row)
              <tr>
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                <td>{{ $row['label'] }}</td>
                <td>{{ number_format($row['count']) }}</td>
                <td>{{ number_format($row['checkedIn']) }}</td>
                <td>{{ $row['checkinRate'] }}%</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
