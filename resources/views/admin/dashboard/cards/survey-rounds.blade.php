{{-- การตอบแบบประเมินสุขภาพรายรอบ — ใช้ทั้งแดชบอร์ดและหน้าผลการวิเคราะห์
     ผู้ include ต้องมี $survey, $num, $tip อยู่ใน scope --}}
<section class="dbo-card dbo-card--rounds" aria-labelledby="dbo-rounds-title">
  <div class="dbo-card-head">
    <div class="dbo-card-title">
      <h2 id="dbo-rounds-title">การตอบแบบประเมินสุขภาพ</h2>
      <span class="dbo-sub">
        {{ count($survey['rounds']) }} รอบ · ฐานกลุ่มตัวอย่าง {{ $num($survey['base']) }} คน
      </span>
    </div>
    <div class="dbo-legend">
      <span class="dbo-legend-item">
        <span class="dbo-swatch dbo-r1"></span>
        <span class="dbo-legend-label">ตอบแล้ว</span>
      </span>
      <span class="dbo-legend-item">
        <span class="dbo-swatch" style="--dbo-c: var(--dbo-track-deep)"></span>
        <span class="dbo-legend-label">ยังไม่ตอบ</span>
      </span>
    </div>
  </div>

  @if ($survey['rounds'] === [])
    @include('admin.dashboard.empty', [
      'title' => 'ยังไม่มีรอบติดตามที่ถึงกำหนด',
      'note' => 'รอบติดตามถูกสร้างให้รายคนตามวันเข้าร่วม ตั้งระยะห่างของรอบได้ที่เมนู พื้นฐาน › ตั้งค่ารอบประเมิน',
    ])
  @else
    <div class="dbo-rounds">
      @foreach ($survey['rounds'] as $round)
        <div class="dbo-round dbo-hit"
             {!! $tip('round-' . $loop->index, 'รอบ ' . $round['label'], [
               ['ตอบแล้ว', $num($round['done']) . ' คน'],
               ['ยังไม่ตอบ', $num($round['missing']) . ' คน'],
               ['อัตราตอบกลับ', $round['pct'] . '%'],
             ]) !!}>
          <span class="dbo-round-label">{{ $round['label'] }}</span>
          <div class="dbo-round-body">
            <span class="dbo-round-track">
              <span class="dbo-round-fill dbo-r{{ $round['rank'] }}" style="--dbo-w: {{ $round['pct'] }}%"></span>
            </span>
            <span class="dbo-round-stat">
              <span class="dbo-num">{{ $round['pct'] }}%</span>
              <span class="dbo-round-stat-label">ตอบแล้ว</span>
            </span>
            {{-- เกินหนึ่งในสี่ของฐานยังไม่ตอบ = ต้องตามเป็นพิเศษ จึงเปลี่ยนเป็นสีเตือน --}}
            <span class="dbo-round-stat dbo-round-stat--miss"
                  data-tone="{{ $round['assigned'] > 0 && $round['missing'] / $round['assigned'] > 0.25 ? 'warn' : 'ok' }}">
              <span class="dbo-num">{{ $num($round['missing']) }}</span>
              <span class="dbo-round-stat-label">ยังไม่ตอบ</span>
            </span>
          </div>
        </div>
      @endforeach
    </div>

    <div class="dbo-round-stats">
      @foreach ($survey['stats'] as $stat)
        <div class="dbo-round-summary" data-tone="{{ $stat['tone'] }}">
          <span class="dbo-num">{{ $stat['value'] }}</span>
          <span class="dbo-round-summary-label">{{ $stat['label'] }}</span>
        </div>
      @endforeach
    </div>
  @endif
</section>
