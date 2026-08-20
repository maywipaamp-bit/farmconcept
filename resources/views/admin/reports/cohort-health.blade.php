@extends('layouts.admin')

@section('title', 'สุขภาพกลุ่มตัวอย่าง')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <span>รายงาน</span> <span>/</span>
    <span class="is-current">สุขภาพกลุ่มตัวอย่าง</span>
  </nav>

  <div class="aov-header">
    <div class="aov-header-text">
      <h1 class="aov-title">สุขภาพกลุ่มตัวอย่าง</h1>
      <p class="aov-rp-toolbar-note">
        ติดตามคนกลุ่มเดิมข้ามหลายรอบเวลา — คะแนนสุขภาพคิดเป็น 0–100 จากตำแหน่งคำตอบบนสเกลของแต่ละข้อ
        (เกณฑ์เดียวกับแดชบอร์ดภาพรวมและผลตอบรายคน) · นับเฉพาะกลุ่มตัวอย่างที่ยังไม่ถอนตัว
      </p>
    </div>
  </div>

  {{-- ตัวกรอง — ส่งเป็น query string เพื่อให้คัดลอก URL ส่งต่อแล้วได้ผลชุดเดียวกัน --}}
  <form class="card aov-rp-card aov-rp-card--wide ch-filters" method="GET">
    <div class="ch-filter-grid">
      <div class="form-group">
        <label class="form-label" for="ch-from">เข้าร่วมตั้งแต่</label>
        <input class="input" type="date" id="ch-from" name="from" value="{{ $filters['from'] }}">
      </div>
      <div class="form-group">
        <label class="form-label" for="ch-to">ถึงวันที่</label>
        <input class="input" type="date" id="ch-to" name="to" value="{{ $filters['to'] }}">
      </div>
      <div class="form-group">
        <label class="form-label" for="ch-area">พื้นที่</label>
        <select class="select" id="ch-area" name="area">
          <option value="">ทุกพื้นที่</option>
          @foreach ($filters['areas'] as $area)
            <option value="{{ $area->id }}" @selected((string) $filters['area'] === (string) $area->id)>{{ $area->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="ch-status">สถานะการติดตาม</label>
        <select class="select" id="ch-status" name="status">
          <option value="">ทั้งหมด</option>
          <option value="complete" @selected($filters['status'] === 'complete')>ทำครบตามรอบ</option>
          <option value="pending" @selected($filters['status'] === 'pending')>ค้างรอบ</option>
        </select>
      </div>
      <div class="ch-filter-actions">
        <button type="submit" class="btn btn-primary">กรอง</button>
        <a class="btn btn-outline" href="{{ route('admin.reports.cohort-health.index') }}">ล้าง</a>
      </div>
    </div>
  </form>

  {{-- ตัวเลขสรุป --}}
  <div class="aov-rp-kpis">
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">กลุ่มตัวอย่างทั้งหมด</span>
      <span class="aov-rp-kpi-value">{{ number_format($summary['total']) }}<span class="aov-rp-kpi-of">คน</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ทำครบตามรอบ</span>
      <span class="aov-rp-kpi-value">{{ number_format($summary['complete']) }}<span class="aov-rp-kpi-of">คน</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ค้างรอบ ต้องติดตามเพิ่ม</span>
      <span class="aov-rp-kpi-value">{{ number_format($summary['pending']) }}<span class="aov-rp-kpi-of">คน · ค้าง {{ number_format($summary['missingRounds']) }} รอบ</span></span>
    </div>
    <div class="aov-rp-kpi">
      <span class="aov-rp-kpi-label">ผลดีขึ้น</span>
      <span class="aov-rp-kpi-value">{{ $summary['upPct'] }}%<span class="aov-rp-kpi-of">{{ number_format($summary['up']) }} จาก {{ number_format($summary['trendBase']) }} คน</span></span>
    </div>
  </div>

  {{-- สัดส่วนผลเทียบรอบล่าสุดกับรอบก่อนหน้า --}}
  <div class="aov-rp-row">
    <section class="card aov-rp-card">
      <h2 class="aov-section-title">
        ผลเทียบรอบล่าสุด
        <span class="aov-rp-card-note">เทียบกับรอบก่อนหน้าของคนเดียวกัน</span>
      </h2>
      @if ($summary['trendBase'] === 0)
        <p class="aov-empty">ต้องมีคนที่ตอบอย่างน้อยสองรอบจึงเทียบได้</p>
      @else
        @include('admin.activities.partials.report-bar-list', [
          'bars' => [
            ['label' => 'ดีขึ้น', 'count' => $summary['up'], 'pct' => $summary['upPct'], 'tone' => 'good'],
            ['label' => 'คงที่', 'count' => $summary['same'], 'pct' => $summary['samePct'], 'tone' => 'mid'],
            ['label' => 'แย่ลง', 'count' => $summary['down'], 'pct' => $summary['downPct'], 'tone' => 'low'],
          ],
          'unit' => 'คน',
        ])
      @endif
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">ค่าเฉลี่ยคะแนนสุขภาพ <span class="aov-rp-card-note">ก่อนเข้าร่วม เทียบ รอบล่าสุด</span></h2>
      @if ($beforeAfter === [])
        <p class="aov-empty">ยังไม่มีคนที่ตอบทั้งรอบแรกและรอบล่าสุด</p>
      @else
        @include('admin.activities.partials.report-bar-list', ['bars' => $beforeAfter, 'unit' => ''])
      @endif
    </section>

    <section class="card aov-rp-card">
      <h2 class="aov-section-title">สัดส่วนกลุ่มเสี่ยง <span class="aov-rp-card-note">จากคะแนนรอบล่าสุด</span></h2>
      @if ($riskDonut['total'] === 0)
        <p class="aov-empty">ยังไม่มีข้อมูล</p>
      @else
        @include('admin.activities.partials.report-donut', ['donut' => $riskDonut, 'unit' => 'คน'])
      @endif
    </section>
  </div>

  {{-- ตารางรายชื่อ --}}
  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <div>
        <h2 class="aov-section-title mb-0">รายชื่อกลุ่มตัวอย่าง</h2>
        <div class="aov-pt-toolbar-sub">
          {{-- กลุ่มตัวอย่างเป็นข้อมูลนิรนามตามข้อตกลงของโครงการ อ้างด้วยรหัสบุคคล ไม่ใช่ชื่อ --}}
          {{ number_format(count($people)) }} คน · อ้างอิงด้วยรหัสบุคคลตามข้อตกลงเรื่องข้อมูลนิรนาม
        </div>
      </div>
    </div>

    @if ($people === [])
      <div class="state-placeholder">
        <div class="state-placeholder-title">ไม่พบกลุ่มตัวอย่างที่ตรงกับตัวกรอง</div>
        <div class="state-placeholder-desc">ลองล้างตัวกรอง หรือขยายช่วงวันที่เข้าร่วม</div>
      </div>
    @else
      @php
        $riskLabel = ['normal' => 'ปกติ', 'watch' => 'เฝ้าระวัง', 'urgent' => 'ต้องติดตามด่วน', 'unknown' => 'ยังไม่มีคะแนน'];
        $riskClass = ['normal' => 'is-paid', 'watch' => 'is-pending', 'urgent' => 'is-unpaid', 'unknown' => ''];
        $dirLabel = ['up' => 'ดีขึ้น', 'same' => 'คงที่', 'down' => 'แย่ลง', 'unknown' => '—'];
      @endphp

      <div class="aov-pt-scroll">
        <table class="aov-pt-table">
          <thead>
            <tr>
              <th class="aov-pt-num">#</th>
              <th>รหัสบุคคล</th>
              <th>พื้นที่</th>
              <th>รอบล่าสุดที่ประเมิน</th>
              <th>สถานะการติดตาม</th>
              <th>คะแนนล่าสุด</th>
              <th>เทียบรอบก่อน</th>
              <th>ผลสรุป</th>
              <th>ประวัติ</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($people as $index => $person)
              <tr>
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                <td>{{ $person['personCode'] }}</td>
                <td>{{ $person['area'] }}</td>
                <td>
                  <div>{{ $person['latestRound'] ?? 'ยังไม่ได้ตอบ' }}</div>
                  <div class="aov-pt-walkin">ตอบแล้ว {{ $person['roundsAnswered'] }} รอบ</div>
                </td>
                <td>
                  @if ($person['followUpStatus'] === 'complete')
                    <span class="aov-pt-status is-in">ทำครบตามรอบ</span>
                  @else
                    <span class="aov-pt-status is-out">ค้าง {{ $person['roundsMissing'] }} รอบ</span>
                  @endif
                </td>
                <td>{{ $person['latestScore'] === null ? '—' : number_format($person['latestScore'], 1) }}</td>
                <td>
                  @if ($person['change'] === null)
                    —
                  @else
                    <span class="ch-change" data-dir="{{ $person['direction'] }}">
                      {{ $person['change'] > 0 ? '+' : '' }}{{ number_format($person['change'], 1) }} · {{ $dirLabel[$person['direction']] }}
                    </span>
                  @endif
                </td>
                <td><span class="aov-pt-status {{ $riskClass[$person['risk']] }}">{{ $riskLabel[$person['risk']] }}</span></td>
                <td>
                  <button type="button" class="aov-pt-action" data-person="{{ $person['participantId'] }}"
                          data-person-code="{{ $person['personCode'] }}">ดูแนวโน้ม</button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="aov-pt-foot">แสดง {{ count($people) }} คน</div>
    @endif
  </div>
@endsection

@section('modals')
{{-- แนวโน้มรายบุคคล — โหลดตอนกด ไม่ฝังมากับหน้าตั้งแต่แรก --}}
<div class="modal-overlay" id="ch-person-modal">
  <div class="modal ch-person-modal">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">แนวโน้มสุขภาพรายบุคคล</h3>
        <div class="aov-pt-history-sub" id="ch-person-sub"></div>
      </div>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="dbo" id="ch-person-chart"></div>
      <p class="registration-message" id="ch-person-message" aria-live="polite"></p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close-modal>ปิด</button>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script type="module">
(function () {
  var url = @json(route('admin.reports.cohort-health.person', ['participant' => '__ID__']));
  var sub = document.getElementById('ch-person-sub');
  var chart = document.getElementById('ch-person-chart');
  var message = document.getElementById('ch-person-message');
  var esc = window.TFC.escapeHtml;

  /* วาดกราฟเส้นจากค่าที่เซิร์ฟเวอร์คำนวณมาแล้ว — ใช้คลาส .dbo-trend-* ชุดเดียวกับแดชบอร์ด
     ไม่มีสูตรเรขาคณิตชุดที่สองในหน้านี้ ฝั่ง JS แค่ประกอบ HTML จากตัวเลขที่ได้มา */
  function render(data) {
    var c = data.chart;

    var ticks = c.ticks.map(function (t) { return '<span>' + esc(t) + '</span>'; }).join('');

    var grid = c.grid.map(function (line) {
      return '<line class="dbo-trend-grid' + (line.is_base ? ' is-base' : '') +
        '" x1="0" y1="' + line.y + '" x2="600" y2="' + line.y + '"></line>';
    }).join('');

    var series = c.series[0];

    var dots = series.dots.map(function (dot, i) {
      if (!dot) return '';
      return '<span class="dbo-dot dbo-series--a" style="--dbo-x: ' + dot.left + '%; --dbo-y: ' + dot.top + '%"' +
        ' title="' + esc(c.categories[i] || '') + ' · ' + dot.value + '"><span></span></span>';
    }).join('');

    var axis = c.categories.map(function (label) {
      return '<div class="dbo-trend-axis-item"><span class="dbo-trend-axis-label">' + esc(label) + '</span></div>';
    }).join('');

    chart.innerHTML =
      '<div class="dbo-trend">' +
        '<div class="dbo-trend-ticks" aria-hidden="true">' + ticks + '</div>' +
        '<div class="dbo-trend-plot">' +
          '<svg viewBox="0 0 600 240" width="100%" height="240" preserveAspectRatio="none" aria-hidden="true">' +
            grid +
            '<polyline class="dbo-trend-line dbo-series--a" points="' + series.points + '"></polyline>' +
          '</svg>' +
          '<div class="dbo-trend-dots">' + dots + '</div>' +
        '</div>' +
        '<div class="dbo-trend-axis">' + axis + '</div>' +
      '</div>' +
      /* ตารางสำรอง — กราฟเส้นอย่างเดียวโปรแกรมอ่านหน้าจอเก็บค่าไม่ได้ */
      '<table class="dbo-sr"><caption>คะแนนสุขภาพรายรอบ</caption>' +
        '<thead><tr><th scope="col">รอบ</th><th scope="col">คะแนน</th></tr></thead><tbody>' +
        data.rounds.map(function (r) {
          return '<tr><th scope="row">' + esc(r.roundName) + '</th><td>' + r.score + '</td></tr>';
        }).join('') +
      '</tbody></table>';
  }

  document.querySelectorAll('[data-person]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-person');

      sub.textContent = 'รหัสบุคคล ' + btn.getAttribute('data-person-code');
      chart.innerHTML = '';
      message.textContent = 'กำลังโหลด…';
      message.className = 'registration-message';
      window.TFC.openModal('ch-person-modal');

      fetch(url.replace('__ID__', encodeURIComponent(id)), { headers: { 'Accept': 'application/json' } })
        .then(function (res) {
          return res.json().catch(function () { return {}; }).then(function (body) {
            if (!res.ok) throw new Error(body.message || 'โหลดข้อมูลไม่สำเร็จ');
            return body;
          });
        })
        .then(function (data) {
          message.textContent = '';
          render(data);
        })
        .catch(function (err) {
          message.textContent = err.message;
          message.className = 'registration-message is-error';
        });
    });
  });
})();
</script>
@endpush
