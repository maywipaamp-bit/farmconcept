{{-- โดนัทกลุ่มตัวอย่างจำแนกตามกลุ่มเป้าหมาย — ใช้ทั้งแดชบอร์ดและหน้าผลการวิเคราะห์
     ผู้ include ต้องมี $cohort, $num, $tip อยู่ใน scope --}}
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
