{{-- แท่งกราฟแนวนอน — label ซ้าย + แท่ง + ตัวเลขขวา ใช้ร่วมกันหลายกราฟในหน้ารายงาน
     ต้องส่ง $bars ([{label,count,pct,tone?}]) — $unit ต่อท้ายตัวเลข ('คน' หรือ '%')
     tone ใส่เมื่อค่ามีความหมายระดับ (ดี/กลาง/ต้องปรับปรุง) เช่นแท่งคะแนนให้คะแนนรายข้อ --}}
<div class="aov-rp-hbars">
  @foreach ($bars as $bar)
    <div class="aov-rp-hbar-row">
      <span class="aov-rp-hbar-label" title="{{ $bar['label'] }}">{{ $bar['label'] }}</span>
      <span class="aov-rp-hbar-track">
        <span class="aov-rp-hbar-fill{{ isset($bar['tone']) ? ' is-'.$bar['tone'] : '' }}" style="width: {{ max($bar['pct'], 2) }}%"></span>
      </span>
      <span class="aov-rp-hbar-count">{{ $bar['count'] }}{{ $unit === '%' ? '%' : '' }}</span>
    </div>
  @endforeach
</div>
