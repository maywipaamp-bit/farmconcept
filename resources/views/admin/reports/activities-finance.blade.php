@extends('layouts.admin')

@section('title', 'การเงิน')

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
        เฉพาะกิจกรรมที่เก็บค่าเข้าร่วม — รายรับคิดจากยอดต่อที่นั่ง × จำนวนที่นั่งตามสถานะชำระเงิน ·
        คาดการณ์ = รายรับสูงสุดถ้าที่นั่งเต็ม
      </p>
    </div>
  </div>

  @include('admin.reports.partials.insights-nav', ['active' => 'finance'])

  {{-- ตัวเลขสรุป --}}
  <div class="aov-rp-kpis">
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ชำระแล้ว</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['paid']) }}<span class="aov-rp-kpi-of">บาท</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ค้างชำระ / รอตรวจสอบ</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['pending']) }}<span class="aov-rp-kpi-of">บาท</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">รายรับรวมตามยอดจอง</span>
      <span class="aov-rp-kpi-value">{{ number_format($kpis['forecast']) }}<span class="aov-rp-kpi-of">บาท</span></span>
    </div>
  </div>

  {{-- แนวโน้มรายเดือน --}}
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
          <h2>แนวโน้มรายรับรายเดือน</h2>
          <span class="dbo-sub">6 เดือนล่าสุด · นับตามเดือนที่จอง เฉพาะยอดที่ชำระแล้ว</span>
        </div>
      </div>
      @include('admin.reports.partials.trend-line', ['chart' => $monthlyTrend, 'chartId' => 'revenue', 'tip' => $tip, 'valueSuffix' => ' บาท'])
    </section>
  </div>

  <div class="dbo-tip" id="dbo-tip" role="tooltip" aria-hidden="true">
    <div class="dbo-tip-head">
      <span class="dbo-tip-dot" data-tip-dot></span>
      <span class="dbo-tip-title" data-tip-title></span>
    </div>
    <div class="dbo-tip-lines" data-tip-lines></div>
  </div>

  {{-- คาดการณ์ vs จริงต่อกิจกรรม --}}
  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <h2 class="aov-section-title mb-0">คาดการณ์เทียบรายรับจริงต่อกิจกรรม</h2>
    </div>
    @if ($byActivity === [])
      <div class="state-placeholder">
        <div class="state-placeholder-title">ยังไม่มีกิจกรรมที่เก็บค่าเข้าร่วม</div>
      </div>
    @else
      <div class="aov-pt-scroll">
        <table class="aov-pt-table">
          <thead>
            <tr>
              <th class="aov-pt-num">#</th>
              <th>กิจกรรม</th>
              <th>คาดการณ์ (บาท)</th>
              <th>ชำระแล้ว (บาท)</th>
              <th>ค้างชำระ (บาท)</th>
              <th>ทำได้จริง</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($byActivity as $index => $row)
              <tr>
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                <td>{{ $row['name'] }}</td>
                <td>{{ number_format($row['forecast']) }}</td>
                <td>{{ number_format($row['paid']) }}</td>
                <td>{{ number_format($row['pending']) }}</td>
                <td>
                  <span class="aov-rp-hbar-track" style="display:inline-block; width: 120px; vertical-align: middle;">
                    <span class="aov-rp-hbar-fill" style="width: {{ max(min($row['attainment'], 100), 2) }}%"></span>
                  </span>
                  {{ $row['attainment'] }}%
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection

@push('page-script')
  <script src="{{ asset('assets/js/dashboard-insight.js') }}"></script>
@endpush
