@extends('layouts.admin')

@section('title', 'ประสิทธิภาพกิจกรรม')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>รายงาน</span> <span>/</span>
    <span class="is-current">ประสิทธิภาพกิจกรรม</span>
  </nav>

  <div class="aov-header">
    <div class="aov-header-text">
      <h1 class="aov-title">ประสิทธิภาพกิจกรรม</h1>
      <p class="aov-rp-toolbar-note">
        Fill rate = ผู้ลงทะเบียนเทียบที่นั่งที่เปิดรับ · Check-in rate = ผู้มาจริงเทียบผู้ลงทะเบียน ·
        คะแนนเฉลี่ยจากแบบประเมินหลังกิจกรรม — สลับมุมมองได้โดยไม่ต้องเปลี่ยนหน้า
      </p>
    </div>
  </div>

  @include('admin.reports.partials.insights-nav', ['active' => 'performance'])

  {{-- ตัวเลือกมิติ — สลับชุดข้อมูลที่แสดง ทุกชุดถูกส่งมากับหน้าแล้ว ไม่ยิงคำขอเพิ่ม --}}
  <div class="aov-rp-insights-nav" id="perf-dims" role="tablist" aria-label="เลือกมิติ">
    <button type="button" class="status-pill is-active" data-dim="activity" role="tab" aria-selected="true">ตามกิจกรรม</button>
    <button type="button" class="status-pill" data-dim="area" role="tab" aria-selected="false">ตามพื้นที่</button>
    <button type="button" class="status-pill" data-dim="course" role="tab" aria-selected="false">ตามหลักสูตร-วิทยากร</button>
  </div>

  @php
      $dimensions = [
          'activity' => ['data' => $byActivity, 'unitLabel' => 'กิจกรรม', 'nameHead' => 'กิจกรรม'],
          'area' => ['data' => $byArea, 'unitLabel' => 'พื้นที่', 'nameHead' => 'พื้นที่'],
          'course' => ['data' => $byCourseInstructor, 'unitLabel' => 'หลักสูตร', 'nameHead' => 'หลักสูตร · วิทยากร'],
      ];
  @endphp

  @foreach ($dimensions as $dimKey => $dim)
    <div data-perf-panel="{{ $dimKey }}" @if(! $loop->first) hidden @endif>
      {{-- สามกราฟแท่งเทียบกันในแถวเดียว --}}
      <div class="aov-rp-row">
        <section class="card aov-rp-card">
          <h2 class="aov-section-title">Fill rate <span class="aov-rp-card-note">% ของที่นั่งที่เปิดรับ</span></h2>
          @if ($dim['data']['fillRateBars'] === [])
            <p class="aov-empty">ยังไม่มีข้อมูล</p>
          @else
            @include('admin.activities.partials.report-bar-list', ['bars' => $dim['data']['fillRateBars'], 'unit' => '%'])
          @endif
        </section>

        <section class="card aov-rp-card">
          <h2 class="aov-section-title">Check-in rate <span class="aov-rp-card-note">% ของผู้ลงทะเบียน</span></h2>
          @if ($dim['data']['checkinRateBars'] === [])
            <p class="aov-empty">ยังไม่มีข้อมูล</p>
          @else
            @include('admin.activities.partials.report-bar-list', ['bars' => $dim['data']['checkinRateBars'], 'unit' => '%'])
          @endif
        </section>

        <section class="card aov-rp-card">
          <h2 class="aov-section-title">คะแนนเฉลี่ย <span class="aov-rp-card-note">จาก 5</span></h2>
          @if ($dim['data']['scoreBars'] === [])
            <p class="aov-empty">ยังไม่มีคำตอบแบบประเมิน</p>
          @else
            @include('admin.activities.partials.report-bar-list', ['bars' => $dim['data']['scoreBars'], 'unit' => 'คะแนน'])
          @endif
        </section>
      </div>

      {{-- ตารางตัวเลขเต็ม --}}
      <div class="card aov-pt-card">
        <div class="aov-pt-toolbar">
          <h2 class="aov-section-title mb-0">ตัวเลขราย{{ $dim['unitLabel'] }}</h2>
        </div>
        @if ($dim['data']['table'] === [])
          <div class="state-placeholder">
            <div class="state-placeholder-title">ยังไม่มีข้อมูล</div>
          </div>
        @else
          <div class="aov-pt-scroll">
            <table class="aov-pt-table">
              <thead>
                <tr>
                  <th class="aov-pt-num">#</th>
                  <th>{{ $dim['nameHead'] }}</th>
                  @if ($dimKey !== 'activity')
                    <th>จำนวนกิจกรรม</th>
                  @endif
                  <th>ที่นั่งเปิดรับ</th>
                  <th>ลงทะเบียน</th>
                  <th>เช็คอิน</th>
                  <th>Fill rate</th>
                  <th>Check-in rate</th>
                  <th>คะแนนเฉลี่ย</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($dim['data']['table'] as $index => $row)
                  <tr>
                    <td class="aov-pt-num">{{ $index + 1 }}</td>
                    <td>{{ $row['activityLabel'] }}</td>
                    @if ($dimKey !== 'activity')
                      <td>{{ number_format($row['activityCount']) }}</td>
                    @endif
                    <td>{{ $row['capacity'] > 0 ? number_format($row['capacity']) : 'ไม่จำกัด' }}</td>
                    <td>{{ number_format($row['registered']) }}</td>
                    <td>{{ number_format($row['checkedIn']) }}</td>
                    <td>{{ $row['fillRate'] !== null ? $row['fillRate'].'%' : '—' }}</td>
                    <td>{{ $row['checkinRate'] !== null ? $row['checkinRate'].'%' : '—' }}</td>
                    <td>{{ $row['avgScore'] !== null ? number_format($row['avgScore'], 1) : '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  @endforeach
@endsection

@push('page-script')
<script>
(function () {
  var pills = document.querySelectorAll('#perf-dims [data-dim]');
  var panels = document.querySelectorAll('[data-perf-panel]');

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      pills.forEach(function (p) {
        p.classList.toggle('is-active', p === pill);
        p.setAttribute('aria-selected', p === pill ? 'true' : 'false');
      });
      panels.forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-perf-panel') !== pill.getAttribute('data-dim');
      });
    });
  });
})();
</script>
@endpush
