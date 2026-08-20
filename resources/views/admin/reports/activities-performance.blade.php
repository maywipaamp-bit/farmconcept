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
        อัตราการเต็มที่นั่ง อัตราเช็คอิน และคะแนนเฉลี่ย — สลับมุมมองได้โดยไม่ต้องเปลี่ยนหน้า ·
        อัตราของมิติที่รวมหลายกิจกรรมคิดจากผลรวม (คนรวม ÷ ที่นั่งรวม) ไม่ใช่เฉลี่ยของเปอร์เซ็นต์
      </p>
    </div>
  </div>

  @include('admin.reports.partials.insights-nav', ['active' => 'performance'])

  {{-- ตัวเลือกมิติ — สามชุดข้อมูลอยู่ในหน้าเดียวกันแล้ว JS แค่สลับว่าจะให้เห็นชุดไหน
       ไม่ยิงคำขอใหม่ตอนสลับ ผู้ใช้จึงเทียบสามมุมมองได้ทันทีโดยไม่มีจอรอ --}}
  <div class="aov-rp-insights-nav" id="perf-dims" role="tablist" aria-label="เลือกมิติที่ใช้ดู">
    <button type="button" class="status-pill is-active" data-dim="byActivity" role="tab" aria-selected="true">ตามกิจกรรม</button>
    <button type="button" class="status-pill" data-dim="byArea" role="tab" aria-selected="false">ตามพื้นที่</button>
    <button type="button" class="status-pill" data-dim="byCourseInstructor" role="tab" aria-selected="false">ตามหลักสูตร–วิทยากร</button>
  </div>

  @foreach (['byActivity' => 'กิจกรรม', 'byArea' => 'พื้นที่', 'byCourseInstructor' => 'หลักสูตร–วิทยากร'] as $dim => $dimLabel)
    @php($set = $$dim)
    <div data-dim-panel="{{ $dim }}" {{ $loop->first ? '' : 'hidden' }}>
      @if ($set['table'] === [])
        <div class="card aov-rp-card aov-rp-card--wide">
          <p class="aov-empty">ยังไม่มีข้อมูลในมิตินี้</p>
        </div>
      @else
        <div class="aov-rp-row">
          <section class="card aov-rp-card">
            <h2 class="aov-section-title">อัตราการเต็มที่นั่ง <span class="aov-rp-card-note">ตาม{{ $dimLabel }}</span></h2>
            @include('admin.activities.partials.report-bar-list', ['bars' => $set['fillRateBars'], 'unit' => '%'])
          </section>

          <section class="card aov-rp-card">
            <h2 class="aov-section-title">อัตราเช็คอิน <span class="aov-rp-card-note">ตาม{{ $dimLabel }}</span></h2>
            @include('admin.activities.partials.report-bar-list', ['bars' => $set['checkinRateBars'], 'unit' => '%'])
          </section>

          <section class="card aov-rp-card">
            <h2 class="aov-section-title">คะแนนเฉลี่ย <span class="aov-rp-card-note">เต็ม 5</span></h2>
            @if ($set['scoreBars'] === [])
              <p class="aov-empty">ยังไม่มีคำตอบแบบประเมิน</p>
            @else
              @include('admin.activities.partials.report-bar-list', ['bars' => $set['scoreBars'], 'unit' => ''])
            @endif
          </section>
        </div>

        <div class="card aov-pt-card">
          <div class="aov-pt-toolbar">
            <div>
              <h2 class="aov-section-title mb-0">ตารางเปรียบเทียบตาม{{ $dimLabel }}</h2>
              <div class="aov-pt-toolbar-sub">{{ number_format(count($set['table'])) }} รายการ</div>
            </div>
          </div>

          <div class="aov-pt-scroll">
            <table class="aov-pt-table">
              <thead>
                <tr>
                  <th class="aov-pt-num">#</th>
                  <th>{{ $dimLabel }}</th>
                  @if ($dim !== 'byActivity')<th>จำนวนกิจกรรม</th>@endif
                  <th>ที่นั่ง</th>
                  <th>ลงทะเบียน</th>
                  <th>เต็มที่นั่ง</th>
                  <th>เช็คอิน</th>
                  <th>อัตราเช็คอิน</th>
                  <th>คะแนนเฉลี่ย</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($set['table'] as $index => $row)
                  <tr>
                    <td class="aov-pt-num">{{ $index + 1 }}</td>
                    <td>{{ $row['activityLabel'] }}</td>
                    @if ($dim !== 'byActivity')<td>{{ number_format($row['activityCount']) }}</td>@endif
                    <td>{{ $row['capacity'] > 0 ? number_format($row['capacity']) : 'ไม่จำกัด' }}</td>
                    <td>{{ number_format($row['registered']) }}</td>
                    <td>{{ $row['fillRate'] === null ? '—' : $row['fillRate'].'%' }}</td>
                    <td>{{ number_format($row['checkedIn']) }}</td>
                    <td>{{ $row['checkinRate'] === null ? '—' : $row['checkinRate'].'%' }}</td>
                    <td>{{ $row['avgScore'] === null ? '—' : number_format($row['avgScore'], 1) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif
    </div>
  @endforeach
@endsection

@push('page-script')
<script type="module">
(function () {
  var pills = document.getElementById('perf-dims');

  pills.addEventListener('click', function (e) {
    var pill = e.target.closest('[data-dim]');
    if (!pill) return;

    var dim = pill.getAttribute('data-dim');

    pills.querySelectorAll('[data-dim]').forEach(function (btn) {
      var on = btn === pill;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', String(on));
    });

    document.querySelectorAll('[data-dim-panel]').forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-dim-panel') !== dim;
    });
  });
})();
</script>
@endpush
