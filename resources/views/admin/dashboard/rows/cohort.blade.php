{{-- แถว 3 — กลุ่มตัวอย่าง (โดนัท) | การตอบแบบประเมินสุขภาพ (แท่งรายรอบ) --}}
@php
    $cohort = $data['cohort'];
    $survey = $data['survey_rounds'];
@endphp

<div class="dbo-row">

  {{-- ---------- ซ้าย: โดนัทกลุ่มตัวอย่างจำแนกตามกลุ่มเป้าหมาย ---------- --}}
  <section class="dbo-card dbo-card--cohort" aria-labelledby="dbo-cohort-title">
    <div class="dbo-card-title">
      <h2 id="dbo-cohort-title">กลุ่มตัวอย่าง</h2>
      <span class="dbo-sub">จำแนกตามกลุ่มเป้าหมาย</span>
    </div>

    @if ($cohort['groups'] === [])
      @include('admin.dashboard.empty', [
        'title' => 'ยังไม่มีผู้เข้าร่วมกลุ่มตัวอย่าง',
        'note' => 'เพิ่มผู้เข้าร่วมเข้ากลุ่มตัวอย่างที่เมนู ประเมินสุขภาพ › กลุ่มตัวอย่าง แล้วสัดส่วนจะแสดงที่นี่',
      ])
    @else
      <div class="dbo-donut-wrap">
        <div class="dbo-donut">
          {{-- r=76 · stroke 30 (hover 34) ตาม handoff — ค่า dasharray คิดจากเส้นรอบวงของรัศมีนี้
               หมุน -90° ให้ชิ้นแรกเริ่มที่ 12 นาฬิกา --}}
          <svg viewBox="0 0 200 200" width="152" height="152" role="img"
               aria-label="สัดส่วนกลุ่มตัวอย่าง {{ $num($cohort['total']) }} คน จำแนกตามกลุ่มเป้าหมาย">
            @foreach ($cohort['groups'] as $group)
              <circle class="dbo-donut-seg dbo-mark dbo-r{{ $group['rank'] }}"
                      cx="100" cy="100" r="76"
                      stroke-dasharray="{{ $group['dash'] }}"
                      stroke-dashoffset="{{ $group['offset'] }}"
                      {!! $tip('cohort-' . $loop->index, $group['label'], [
                        ['จำนวน', $num($group['count']) . ' คน'],
                        ['สัดส่วน', $group['pct']],
                      ]) !!}></circle>
            @endforeach
          </svg>
          <div class="dbo-donut-center">
            <span class="dbo-num">{{ $num($cohort['total']) }}</span>
            <span class="dbo-donut-unit">คน</span>
          </div>
        </div>

        <div class="dbo-donut-legend">
          @foreach ($cohort['groups'] as $group)
            <div class="dbo-donut-row dbo-hit"
                 {!! $tip('cohort-' . $loop->index, $group['label'], [
                   ['จำนวน', $num($group['count']) . ' คน'],
                   ['สัดส่วน', $group['pct']],
                 ]) !!}>
              <span class="dbo-swatch dbo-r{{ $group['rank'] }}"></span>
              <span class="dbo-donut-row-label" title="{{ $group['label'] }}">{{ $group['label'] }}</span>
              <span class="dbo-donut-row-count dbo-num">{{ $num($group['count']) }}</span>
              <span class="dbo-donut-row-pct dbo-num">{{ $group['pct'] }}</span>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </section>

  {{-- ---------- ขวา: อัตราการตอบแบบติดตามรายรอบ ---------- --}}
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
</div>
