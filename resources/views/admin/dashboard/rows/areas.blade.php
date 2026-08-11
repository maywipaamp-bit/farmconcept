{{-- แถว 5 — พื้นที่ที่จัดกิจกรรมมากที่สุด (treemap) | กลุ่มตัวอย่างแต่ละพื้นที่ (แท่งซ้อน) --}}
@php
    $areas = $data['areas'];
@endphp

<div class="dbo-row">

  {{-- ---------- ซ้าย: treemap จำนวนกิจกรรมรายพื้นที่ ---------- --}}
  <section class="dbo-card dbo-card--treemap" aria-labelledby="dbo-treemap-title">
    <div class="dbo-card-head">
      <div class="dbo-card-title">
        <h2 id="dbo-treemap-title">พื้นที่ที่จัดกิจกรรมมากที่สุด</h2>
        <span class="dbo-sub">ขนาดกล่อง = จำนวนกิจกรรม</span>
      </div>
      @if ($areas['treemap'] !== [])
        <span class="dbo-note">
          รวม {{ $num($areas['activity_total']) }} ครั้ง · {{ $num($areas['area_count']) }} พื้นที่
          @if ($areas['hidden_count'] > 0)
            (แสดง {{ $num($areas['area_count'] - $areas['hidden_count']) }} อันดับแรก)
          @endif
        </span>
      @endif
    </div>

    @if ($areas['treemap'] === [])
      @include('admin.dashboard.empty', [
        'title' => 'ยังไม่มีกิจกรรมที่ผูกพื้นที่ในช่วงเวลาที่เลือก',
        'note' => 'นับเฉพาะกิจกรรมที่ไม่ใช่ฉบับร่างและระบุพื้นที่จัดไว้แล้ว',
      ])
    @else
      {{-- ตำแหน่งและขนาดกล่องเป็น % ของกรอบ คำนวณด้วย squarified treemap ฝั่งเซิร์ฟเวอร์
           จึงไม่ต้องรู้ขนาดจริงของกรอบและไม่มีจังหวะที่กล่องกระโดดหลัง JS ทำงาน --}}
      <div class="dbo-treemap" data-dbo-treemap>
        @foreach ($areas['treemap'] as $tile)
          <span class="dbo-tile dbo-mark dbo-r{{ $tile['rank'] }}{{ $tile['compact'] ? ' is-compact' : '' }}"
                style="--dbo-x: {{ $tile['left'] }}%; --dbo-y: {{ $tile['top'] }}%; --dbo-w: {{ $tile['width'] }}%; --dbo-h: {{ $tile['height'] }}%"
                {!! $tip('area-' . $loop->index, $tile['label'], [
                  ['จำนวนกิจกรรม', $num($tile['count']) . ' ครั้ง'],
                  ['สัดส่วน', $tile['pct']],
                ]) !!}>
            <span class="dbo-tile-name">{{ $tile['label'] }}</span>
            <span class="dbo-tile-count dbo-num">{{ $num($tile['count']) }} ครั้ง</span>
          </span>
        @endforeach
      </div>

      {{-- ขนาดกล่องสื่อจำนวนให้โปรแกรมอ่านหน้าจอไม่ได้ ตารางนี้จึงเป็นค่าที่อ่านได้จริง --}}
      <table class="dbo-sr">
        <caption>จำนวนกิจกรรมรายพื้นที่</caption>
        <thead>
          <tr>
            <th scope="col">พื้นที่</th>
            <th scope="col">จำนวนกิจกรรม</th>
            <th scope="col">สัดส่วน</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($areas['treemap'] as $tile)
            <tr>
              <th scope="row">{{ $tile['label'] }}</th>
              <td>{{ $num($tile['count']) }} ครั้ง</td>
              <td>{{ $tile['pct'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </section>

  {{-- ---------- ขวา: กลุ่มตัวอย่างแต่ละพื้นที่ ---------- --}}
  <section class="dbo-card dbo-card--areas" aria-labelledby="dbo-area-cohort-title">
    <div class="dbo-card-head">
      <div class="dbo-card-title">
        <h2 id="dbo-area-cohort-title">กลุ่มตัวอย่างแต่ละพื้นที่</h2>
        <span class="dbo-sub">แยกตามกลุ่มเป้าหมาย</span>
      </div>
      @if ($areas['samples'] !== [])
        <span class="dbo-note">รวม {{ $num($data['cohort']['total']) }} คน</span>
      @endif
    </div>

    @if ($areas['samples'] === [])
      @include('admin.dashboard.empty', [
        'title' => 'ยังไม่มีกลุ่มตัวอย่างในพื้นที่ใด',
        'note' => 'ผู้เข้าร่วมกลุ่มตัวอย่างต้องระบุพื้นที่และกลุ่มเป้าหมายไว้ในข้อมูลส่วนตัว',
      ])
    @else
      <div class="dbo-legend">
        @foreach ($areas['target_groups'] as $group)
          <span class="dbo-legend-item">
            <span class="dbo-swatch dbo-r{{ $group['rank'] }}"></span>
            <span class="dbo-legend-label">{{ $group['label'] }}</span>
          </span>
        @endforeach
      </div>

      <div class="dbo-areas">
        @foreach ($areas['samples'] as $area)
          <div class="dbo-area dbo-hit"
               {!! $tip('sample-' . $loop->index, $area['label'], [
                 ['กลุ่มตัวอย่าง', $num($area['count']) . ' คน'],
                 ['สัดส่วน', $area['pct']],
               ]) !!}>
            <span class="dbo-area-label" title="{{ $area['label'] }}">{{ $area['label'] }}</span>
            {{-- ความยาวรางเทียบกับพื้นที่ที่มากที่สุด · ท่อนข้างในเป็นสัดส่วนภายในพื้นที่นั้น --}}
            <span class="dbo-area-track" style="--dbo-w: {{ $area['bar'] }}%">
              @foreach ($area['segments'] as $segment)
                <span class="dbo-area-seg dbo-mark dbo-r{{ $segment['rank'] }}"
                      style="--dbo-w: {{ $segment['width'] }}%"
                      {!! $tip('sample-' . $loop->parent->index . '-' . $loop->index,
                              $area['label'] . ' · ' . $segment['label'], [
                        ['จำนวน', $num($segment['count']) . ' คน'],
                        ['สัดส่วนในพื้นที่', number_format($segment['width'], 1) . '%'],
                      ]) !!}></span>
              @endforeach
            </span>
            <span class="dbo-area-count dbo-num">{{ $num($area['count']) }}</span>
            <span class="dbo-area-pct dbo-num">{{ $area['pct'] }}</span>
          </div>
        @endforeach
      </div>
    @endif
  </section>
</div>
