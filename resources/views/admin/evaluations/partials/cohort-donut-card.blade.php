{{-- การ์ดโดนัทประชากรกลุ่มตัวอย่างหนึ่งมุมมอง (เพศ / ช่วงอายุ / พื้นที่)
     ผู้ include ต้องส่ง $donut (name, data) และมี $num, $tip อยู่ใน scope --}}
<section class="dbo-card an-donut-card" aria-label="กลุ่มตัวอย่างตาม{{ $donut['name'] }}">
  <div class="dbo-card-title">
    <h2>กลุ่มตัวอย่างตาม{{ $donut['name'] }}</h2>
    <span class="dbo-sub">เฉพาะที่ยังติดตามอยู่</span>
  </div>

  @if($donut['data']['groups'] === [])
    @include('admin.dashboard.empty', [
      'title' => 'ยังไม่มีข้อมูล'.$donut['name'],
      'note' => 'ข้อมูลจะแสดงเมื่อมีการบันทึก'.$donut['name'].'ของกลุ่มตัวอย่าง',
    ])
  @else
    <div class="dbo-donut-wrap">
      <div class="dbo-donut">
        <svg viewBox="0 0 200 200" width="152" height="152" role="img"
             aria-label="สัดส่วนกลุ่มตัวอย่างตาม{{ $donut['name'] }} {{ $num($donut['data']['total']) }} คน">
          @foreach($donut['data']['groups'] as $group)
            <circle class="dbo-donut-seg dbo-mark dbo-r{{ $group['rank'] }}"
                    cx="100" cy="100" r="76"
                    stroke-dasharray="{{ $group['dash'] }}"
                    stroke-dashoffset="{{ $group['offset'] }}"
                    {!! $tip($donut['name'].'-'.$loop->index, $group['label'], [
                      ['จำนวน', $num($group['count']).' คน'],
                      ['สัดส่วน', $group['pct']],
                    ]) !!}></circle>
          @endforeach
        </svg>
        <div class="dbo-donut-center">
          <span class="dbo-num">{{ $num($donut['data']['total']) }}</span>
          <span class="dbo-donut-unit">คน</span>
        </div>
      </div>

      <div class="dbo-donut-legend">
        @foreach($donut['data']['groups'] as $group)
          <div class="dbo-donut-row dbo-hit"
               {!! $tip($donut['name'].'-'.$loop->index, $group['label'], [
                 ['จำนวน', $num($group['count']).' คน'],
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
