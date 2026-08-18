{{--
    กราฟเส้นทั่วไปสำหรับหน้ารายงาน — เรขาคณิตและกลไก tooltip ชุดเดียวกับ
    admin.dashboard.rows.assessment (ใช้ dashboard-insight.js + คลาส .dbo-trend-* ตัวเดียวกัน)
    ต่างตรงที่รับได้ทั้งหนึ่งหรือหลายชุดข้อมูล และแกน Y ไม่ได้ตรึงที่ 0–100% เสมอไป

    ต้องส่งมา:
      $chart      = ChartMath::trendLine(...) — มี grid/ticks/series/categories
      $chartId    = คำนำหน้าคีย์ tooltip ให้ไม่ชนกันเมื่อมีกราฟนี้มากกว่าหนึ่งแผงในหน้าเดียว
      $tip        = closure จากบล็อกตัวแปรของหน้าเรียก (รูปแบบเดียวกับ admin.dashboard.body)
    ส่งเพิ่มได้ (ไม่บังคับ):
      $valueSuffix = ต่อท้ายตัวเลขใน tooltip เช่น ' คน', ' บาท', '%'
--}}
@php
    $valueSuffix ??= '';
    $seriesClass = ['a', 'b', 'c'];
@endphp

@if (($chart['series'] ?? []) === [])
    @include('admin.dashboard.empty', [
        'title' => 'ยังไม่มีข้อมูลพอวาดกราฟ',
        'note' => 'ต้องมีข้อมูลอย่างน้อยหนึ่งเดือนในช่วงที่รายงานนี้ดู',
    ])
@else
    <div class="dbo-trend">
        <div class="dbo-trend-ticks" aria-hidden="true">
            @foreach ($chart['ticks'] as $tick)
                <span>{{ $tick }}{{ $valueSuffix }}</span>
            @endforeach
        </div>

        <div class="dbo-trend-plot">
            <svg viewBox="0 0 600 240" width="100%" height="260" preserveAspectRatio="none" aria-hidden="true">
                @foreach ($chart['grid'] as $line)
                    <line class="dbo-trend-grid{{ $line['is_base'] ? ' is-base' : '' }}"
                          x1="0" y1="{{ $line['y'] }}" x2="600" y2="{{ $line['y'] }}"></line>
                @endforeach
                @foreach ($chart['series'] as $index => $series)
                    <polyline class="dbo-trend-line dbo-mark dbo-series--{{ $seriesClass[$index] ?? 'a' }}"
                              data-dbo-key="{{ $chartId }}-{{ $series['key'] }}"
                              points="{{ $series['points'] }}"></polyline>
                @endforeach
            </svg>

            <div class="dbo-trend-dots">
                @foreach ($chart['series'] as $index => $series)
                    @foreach ($series['dots'] as $i => $dot)
                        @continue($dot === null)
                        <span class="dbo-dot dbo-mark dbo-series--{{ $seriesClass[$index] ?? 'a' }}"
                              style="--dbo-x: {{ $dot['left'] }}%; --dbo-y: {{ $dot['top'] }}%"
                              {!! $tip($chartId . '-' . $series['key'], $series['label'], [
                                  [$chart['categories'][$i] ?? '', number_format($dot['value'], $dot['value'] == floor($dot['value']) ? 0 : 1) . $valueSuffix],
                              ]) !!}><span></span></span>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="dbo-trend-axis">
            @foreach ($chart['categories'] as $label)
                <div class="dbo-trend-axis-item">
                    <span class="dbo-trend-axis-label">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    @if (count($chart['series']) > 1)
        <div class="dbo-legend">
            @foreach ($chart['series'] as $index => $series)
                <span class="dbo-legend-item">
                    <span class="dbo-swatch dbo-swatch--line dbo-series--{{ $seriesClass[$index] ?? 'a' }}"></span>
                    <span class="dbo-legend-label">{{ $series['label'] }}</span>
                </span>
            @endforeach
        </div>
    @endif

    {{-- ตารางสำรอง — กราฟเส้นสื่อความหมายให้โปรแกรมอ่านหน้าจอไม่ได้ --}}
    <table class="dbo-sr">
        <caption>ตัวเลขของกราฟ{{ count($chart['series']) > 1 ? '' : ' ' . $chart['series'][0]['label'] }}</caption>
        <thead>
            <tr>
                <th scope="col">เดือน</th>
                @foreach ($chart['series'] as $series)
                    <th scope="col">{{ $series['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($chart['categories'] as $i => $label)
                <tr>
                    <th scope="row">{{ $label }}</th>
                    @foreach ($chart['series'] as $series)
                        @php($dot = $series['dots'][$i] ?? null)
                        <td>{{ $dot ? number_format($dot['value'], $dot['value'] == floor($dot['value']) ? 0 : 1) . $valueSuffix : '—' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
