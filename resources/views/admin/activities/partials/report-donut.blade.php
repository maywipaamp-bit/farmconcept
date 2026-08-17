{{-- โดนัทสรุปสัดส่วน + legend ตัวเลขข้างขวา — ต้องส่ง $donut ({total, segments}) และ $unit
     แต่ละ segment มีสีได้สองแบบ: 'tone' (สื่อความหมายตายตัว เช่น success/warning/danger/muted/info)
     หรือ 'rank' (วนสี 0–4 ตามอันดับ ใช้กับข้อมูลที่ไม่มีความหมายสถานะ เช่น เพศ ตัวเลือกคำถาม) --}}
@php
  $cls = fn (array $segment) => isset($segment['tone']) ? 'is-'.$segment['tone'] : 'aov-rp-r'.$segment['rank'];
@endphp
<div class="aov-rp-donut-wrap">
  <div class="aov-rp-donut">
    {{-- r=76 · stroke 30 ตามสูตรเดียวกับแดชบอร์ดภาพรวม — หมุน -90° ให้ชิ้นแรกเริ่มที่ 12 นาฬิกา --}}
    <svg viewBox="0 0 200 200" width="140" height="140" role="img" aria-label="{{ $donut['total'] }} {{ $unit }}">
      @foreach ($donut['segments'] as $segment)
        @if ($segment['count'] > 0)
          <circle class="aov-rp-donut-seg {{ $cls($segment) }}"
                  cx="100" cy="100" r="76"
                  stroke-dasharray="{{ $segment['dash'] }}"
                  stroke-dashoffset="{{ $segment['offset'] }}"></circle>
        @endif
      @endforeach
    </svg>
    <div class="aov-rp-donut-center">
      <span class="aov-rp-donut-num">{{ $donut['total'] }}</span>
      <span class="aov-rp-donut-unit">{{ $unit }}</span>
    </div>
  </div>

  <div class="aov-rp-legend">
    @foreach ($donut['segments'] as $segment)
      <div class="aov-rp-legend-row">
        <span class="aov-rp-swatch {{ $cls($segment) }}"></span>
        <span class="aov-rp-legend-label">{{ $segment['label'] }}</span>
        <span class="aov-rp-legend-count">{{ $segment['count'] }}</span>
        <span class="aov-rp-legend-pct">{{ $segment['pct'] }}%</span>
      </div>
    @endforeach
  </div>
</div>
