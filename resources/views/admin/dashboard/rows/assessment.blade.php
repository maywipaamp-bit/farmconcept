{{-- แถว 4 — ผลประเมินก่อนและหลังเข้าร่วม: กราฟเส้น 2 เส้น + ผลวิเคราะห์แนวโน้ม --}}
@php
    $assessment = $data['assessment'];
    $chart = $assessment['chart'];

    /* ป้ายของสองเส้น — ชื่อรอบจริงจากฐานข้อมูล ไม่ใช่ข้อความตายตัว */
    $seriesLabels = [
        'before' => $assessment['before_label'] ?: 'ก่อนเข้าร่วม',
        'after' => $assessment['after_label'] ?: 'หลังเข้าร่วม',
    ];
@endphp

<section class="dbo-card" aria-labelledby="dbo-assessment-title">
  <div class="dbo-card-head">
    <div class="dbo-card-title">
      <h2 id="dbo-assessment-title">ผลประเมินก่อนและหลังเข้าร่วม</h2>
      @if ($assessment['topics'] !== [])
        <span class="dbo-sub">ฐานผู้ตอบ {{ $num($assessment['base']) }} คน</span>
      @endif
    </div>
    @if ($assessment['topics'] !== [])
      <div class="dbo-legend">
        @foreach ($seriesLabels as $key => $label)
          <span class="dbo-legend-item">
            <span class="dbo-swatch dbo-swatch--line dbo-series--{{ $key }}"></span>
            <span class="dbo-legend-label">{{ $label }}</span>
          </span>
        @endforeach
      </div>
    @endif
  </div>

  @if ($assessment['topics'] === [])
    @include('admin.dashboard.empty', [
      'title' => 'ยังเทียบผลก่อน–หลังไม่ได้',
      'note' => 'ต้องมีคำตอบแบบติดตามสุขภาพอย่างน้อยสองรอบ และคำถามต้องระบุหัวข้อ (dimension) ไว้',
    ])
  @else
    <div class="dbo-compare">
      <div class="dbo-trend">
        <div class="dbo-trend-ticks" aria-hidden="true">
          @foreach ($chart['ticks'] as $tick)
            <span>{{ $tick }}</span>
          @endforeach
        </div>

        <div class="dbo-trend-plot">
          {{-- viewBox คงที่ 600x240 · preserveAspectRatio="none" ให้กราฟยืดเต็มความกว้างที่มี
               ตำแหน่งจุดคิดมาจากฝั่งเซิร์ฟเวอร์เป็น % แล้ว จึงตรงกับเส้นเสมอทุกความกว้าง --}}
          <svg viewBox="0 0 600 240" width="100%" height="260" preserveAspectRatio="none" aria-hidden="true">
            @foreach ($chart['grid'] as $line)
              <line class="dbo-trend-grid{{ $line['is_base'] ? ' is-base' : '' }}"
                    x1="0" y1="{{ $line['y'] }}" x2="600" y2="{{ $line['y'] }}"></line>
            @endforeach
            @foreach ($chart['series'] as $series)
              <polyline class="dbo-trend-line dbo-mark dbo-series--{{ $series['key'] }}"
                        data-dbo-key="trend-{{ $series['key'] }}"
                        points="{{ $series['points'] }}"></polyline>
            @endforeach
          </svg>

          <div class="dbo-trend-dots">
            @foreach ($chart['series'] as $series)
              @foreach ($series['dots'] as $dot)
                <span class="dbo-dot dbo-mark dbo-series--{{ $series['key'] }}"
                      style="--dbo-x: {{ $dot['left'] }}%; --dbo-y: {{ $dot['top'] }}%"
                      {{-- "คะแนนเฉลี่ย" ไม่ใช่ "ผู้ผ่านเกณฑ์" — ค่านี้คือค่าเฉลี่ยของหัวข้อเป็น %
                           ไม่มีเกณฑ์ผ่าน/ตกในการคำนวณ ป้ายเดิมชวนให้อ่านผิดความหมาย --}}
                      {!! $tip('trend-' . $series['key'], $seriesLabels[$series['key']], [
                        ['หัวข้อ', $dot['topic']],
                        ['คะแนนเฉลี่ย', number_format($dot['value'], 1) . '%'],
                      ]) !!}><span></span></span>

                @if ($dot['label_top'] !== null)
                  {{-- ป้ายที่ชนกับป้ายอื่นถูกซ่อนไว้ตั้งแต่ฝั่งเซิร์ฟเวอร์ ค่ายังอ่านได้จาก tooltip
                       และจากตารางสำรองท้ายการ์ด --}}
                  <span class="dbo-dot-label dbo-mark dbo-series--{{ $series['key'] }}"
                        data-dbo-key="trend-{{ $series['key'] }}"
                        style="--dbo-x: {{ $dot['left'] }}%; --dbo-y: {{ $dot['label_top'] }}%"
                        aria-hidden="true">{{ number_format($dot['value'], 1) }}%</span>
                @endif
              @endforeach
            @endforeach
          </div>
        </div>

        <div class="dbo-trend-axis">
          @foreach ($assessment['topics'] as $topic)
            <div class="dbo-trend-axis-item">
              {{-- ชื่อถูกตัดที่ 2 บรรทัด — title= ให้ชี้อ่านชื่อเต็มได้ --}}
              <span class="dbo-trend-axis-label" title="{{ $topic['label'] }}">{{ $topic['label'] }}</span>
              <span class="dbo-trend-gain dbo-num" data-tone="{{ $topic['gain'] >= 0 ? 'up' : 'down' }}">
                {{ $topic['gain'] >= 0 ? '+' : '' }}{{ number_format($topic['gain'], 1) }} จุด
              </span>
            </div>
          @endforeach
        </div>
      </div>

      <div class="dbo-insights">
        <span class="dbo-insights-title">ผลวิเคราะห์แนวโน้ม</span>
        @foreach ($assessment['insights'] as $insight)
          <div class="dbo-insight" data-tone="{{ $insight['tone'] }}">
            <span class="dbo-insight-icon">
              @include('admin.dashboard.icon', ['name' => $insight['icon'], 'size' => 16])
            </span>
            <div class="dbo-insight-text">
              <div class="dbo-insight-head">
                <span class="dbo-num">{{ $insight['value'] }}</span>
                <span class="dbo-insight-title">{{ $insight['title'] }}</span>
              </div>
              <span class="dbo-insight-note">{{ $insight['note'] }}</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- ตารางสำรอง — กราฟเส้นสื่อความสัมพันธ์ก่อน/หลังให้โปรแกรมอ่านหน้าจอไม่ได้ --}}
    <table class="dbo-sr">
      <caption>ผลประเมินรายหัวข้อ {{ $seriesLabels['before'] }} เทียบ {{ $seriesLabels['after'] }}</caption>
      <thead>
        <tr>
          <th scope="col">หัวข้อประเมิน</th>
          <th scope="col">{{ $seriesLabels['before'] }}</th>
          <th scope="col">{{ $seriesLabels['after'] }}</th>
          <th scope="col">ส่วนต่าง</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($assessment['topics'] as $topic)
          <tr>
            <th scope="row">{{ $topic['label'] }}</th>
            <td>{{ number_format($topic['before'], 1) }}%</td>
            <td>{{ number_format($topic['after'], 1) }}%</td>
            <td>{{ $topic['gain'] >= 0 ? '+' : '' }}{{ number_format($topic['gain'], 1) }} จุด</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</section>
