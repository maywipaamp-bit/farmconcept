@extends('layouts.admin')

@section('title', 'รายงานการเงิน')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>รายงาน</span> <span>/</span>
    <span class="is-current">การเงิน</span>
  </nav>

  <div class="aov-header">
    <div class="aov-header-text">
      <h1 class="aov-title">การเงิน</h1>
      <p class="aov-rp-toolbar-note">
        เฉพาะกิจกรรมที่เก็บค่าเข้าร่วม — รายรับที่ชำระแล้ว ยอดค้าง และเทียบกับที่คาดการณ์ไว้จากจำนวนที่นั่ง
      </p>
    </div>
  </div>

  @include('admin.reports.partials.insights-nav', ['active' => 'finance'])


  <div class="aov-rp-kpis">
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">รายรับที่ชำระแล้ว</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['paid']) }}<span class="aov-rp-kpi-of">บาท</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ค้างชำระ</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['pending']) }}<span class="aov-rp-kpi-of">บาท</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">รวมที่ควรได้รับ</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['forecast']) }}<span class="aov-rp-kpi-of">บาท</span></span>
    </div>
  </div>

  @php
      /* นิยามตัวช่วยทั้งหมดในบล็อกเดียว — ห้ามใช้รูปแบบย่อ php(...) ปนกับบล็อกนี้
         เพราะตัวคอมไพล์ของ Blade จับคู่ไดเรกทีฟตัวแรกกับตัวปิดตัวแรก แล้วจะกลืน HTML ระหว่างกลางไปทั้งก้อน */
      $baht = fn (float $v) => number_format($v).' ฿';

      $tip = function (string $key, string $title, array $lines): string {
          $payload = json_encode(['title' => $title, 'lines' => $lines], JSON_UNESCAPED_UNICODE);

          return 'data-dbo-key="' . e($key) . '" data-dbo-tip="' . e($payload) . '"';
      };
  @endphp
  <div class="dbo" id="dbo-body">
    <section class="dbo-card aov-rp-card--wide">
      <div class="dbo-card-head">
        <div class="dbo-card-title">
          <h2>แนวโน้มรายรับรายเดือน</h2>
          <span class="dbo-sub">6 เดือนล่าสุด · นับเฉพาะรายการที่ชำระแล้ว</span>
        </div>
      </div>
      @include('admin.reports.partials.trend-line', [
        'chart' => $monthlyTrend,
        'chartId' => 'revenue',
        'tip' => $tip,
        'valueSuffix' => ' ฿',
      ])
    </section>
  </div>

  <div class="dbo-tip" id="dbo-tip" role="tooltip" aria-hidden="true">
    <div class="dbo-tip-head">
      <span class="dbo-tip-dot" data-tip-dot></span>
      <span class="dbo-tip-title" data-tip-title></span>
    </div>
    <div class="dbo-tip-lines" data-tip-lines></div>
  </div>

  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <div>
        <h2 class="aov-section-title mb-0">คาดการณ์เทียบกับที่ได้รับจริง</h2>
        <div class="aov-pt-toolbar-sub">
          คาดการณ์ = ค่าเข้าร่วม × จำนวนที่นั่ง · กิจกรรมที่ไม่จำกัดที่นั่งใช้ยอดที่ลงทะเบียนจริงแทน
        </div>
      </div>
    </div>

    @if ($byActivity === [])
      <div class="state-placeholder">
        <div class="state-placeholder-title">ยังไม่มีกิจกรรมที่เก็บค่าเข้าร่วม</div>
        <div class="state-placeholder-desc">รายงานนี้จะมีข้อมูลเมื่อมีกิจกรรมที่กำหนดค่าเข้าร่วมไว้</div>
      </div>
    @else
      <div class="aov-pt-scroll">
        <table class="aov-pt-table">
          <thead>
            <tr>
              <th class="aov-pt-num">#</th>
              <th>กิจกรรม</th>
              <th>คาดการณ์</th>
              <th>ชำระแล้ว</th>
              <th>ค้างชำระ</th>
              <th>ได้ตามเป้า</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($byActivity as $index => $row)
              <tr>
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                <td>{{ $row['name'] }}</td>
                <td>{{ $baht($row['forecast']) }}</td>
                <td>{{ $baht($row['paid']) }}</td>
                <td>{{ $baht($row['pending']) }}</td>
                <td>
                  {{-- แถบเล็กในช่อง อ่านสัดส่วนได้เร็วกว่าเลข % เดี่ยว ๆ ตอนไล่หลายแถว --}}
                  <span class="aov-rp-inline-bar">
                    <span class="aov-rp-inline-bar-track">
                      <span class="aov-rp-inline-bar-fill" style="width: {{ min($row['attainment'], 100) }}%"></span>
                    </span>
                    <span class="aov-rp-inline-bar-value">{{ $row['attainment'] }}%</span>
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="aov-pt-foot">{{ count($byActivity) }} กิจกรรมที่เก็บค่าเข้าร่วม</div>
    @endif
  </div>
@endsection

@push('page-script')
  <script src="{{ asset('assets/js/dashboard-insight.js') }}"></script>
@endpush
