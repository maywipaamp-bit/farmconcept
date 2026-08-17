{{-- แท็กพร้อมจำนวน — สไตล์เดียวกับป้ายหัวข้อรีวิวทั่วไป (เช่น "คุณภาพอาหารดี 96")
     ต้องส่ง $items ([{label,count}]) เรียงจากมากไปน้อยแล้วจากผู้เรียก --}}
<div class="aov-rp-pills">
  @foreach ($items as $item)
    <span class="aov-rp-pill">
      <span class="aov-rp-pill-label">{{ $item['label'] }}</span>
      <span class="aov-rp-pill-count">{{ $item['count'] }}</span>
    </span>
  @endforeach
</div>
