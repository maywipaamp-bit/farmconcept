@extends('layouts.admin')

@section('title', 'สรุปผลแบบประเมิน')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <span class="is-current">สรุปผลแบบประเมิน</span>
  </nav>

  <div class="rl-head">
    <div>
      <h1 class="rl-title">สรุปผลแบบประเมิน</h1>
      <p class="pr-subtitle">
        รายงานวิจัยรายแบบประเมิน — ข้อมูลทั่วไปของกลุ่มตัวอย่าง · ตารางความถี่ · ค่าเฉลี่ย–S.D.
        · กราฟการเปลี่ยนแปลง แต่ละหัวข้อแยกการ์ด ส่งออก CSV ได้ทุกตาราง
      </p>
    </div>
  </div>

  @forelse($reports as $report)
    {{-- หัวเรื่องของแบบประเมิน — หนึ่งแบบมีหลายการ์ดตามหัวข้อ ไม่ยัดรวมการ์ดเดียวแล้วพับซ่อน --}}
    <div class="sm-form-head">
      <h2 class="sm-form-name">{{ $report['form'] }}</h2>
      <span class="sm-form-meta">ผู้ตอบ <b class="num">{{ $report['people'] }}</b> คน ·
        {{ count($report['rounds']) }} รอบ</span>
    </div>

    {{-- ---------- การ์ด 1: ข้อมูลทั่วไปของกลุ่มตัวอย่าง ---------- --}}
    <div class="card pr-card">
      <div class="pr-card-head">
        <h3 class="pr-form-name">ตารางที่ 1 — ข้อมูลทั่วไปของกลุ่มตัวอย่าง (n={{ $report['people'] }})</h3>
        <button type="button" class="btn btn-outline btn-sm" data-export-research
                data-file="ข้อมูลทั่วไป {{ $report['form'] }}">ส่งออก CSV</button>
      </div>

      <div class="sm-card-body an-research">
        <p class="an-research-note">ฐานร้อยละคือผู้ที่ตอบแบบประเมินชุดนี้อย่างน้อยหนึ่งรอบ</p>

        <div class="an-dist-wrap">
          <table class="an-research-table">
            <thead>
              <tr>
                <th class="is-head-q">ลักษณะของกลุ่มตัวอย่าง</th>
                <th>จำนวน (คน)</th>
                <th>ร้อยละ</th>
              </tr>
            </thead>
            <tbody>
              @foreach($report['demographics'] as $trait)
                <tr class="is-section"><td colspan="3">{{ $trait['name'] }}</td></tr>
                @foreach($trait['rows'] as $row)
                  <tr>
                    <td class="is-option">{{ $row['label'] }}</td>
                    <td class="an-research-cell">{{ $row['n'] }}</td>
                    <td class="an-research-cell">{{ number_format($row['pct'], 1) }}</td>
                  </tr>
                @endforeach
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    @if($report['rounds'] !== [])
      {{-- ---------- การ์ด 2: ตารางแจกแจงความถี่รายข้อ ---------- --}}
      <div class="card pr-card">
        <div class="pr-card-head">
          <h3 class="pr-form-name">ตารางที่ 2 — แจกแจงความถี่รายข้อ จำนวน (ร้อยละ)</h3>
          <button type="button" class="btn btn-outline btn-sm" data-export-research
                  data-file="ความถี่ {{ $report['form'] }}">ส่งออก CSV</button>
        </div>

        <div class="sm-card-body an-research">
          <p class="an-research-note">
            ร้อยละคิดจากผู้ที่ตอบข้อนั้นในรอบนั้น · n ใต้ชื่อรอบคือจำนวนผู้ตอบของรอบ
          </p>

          <div class="an-dist-wrap">
            <table class="an-research-table">
              <thead>
                <tr>
                  <th class="is-head-q">ข้อคำถาม / คำตอบ</th>
                  @foreach($report['rounds'] as $round)
                    <th>
                      {{ $round['name'] }}
                      <span class="an-research-n">(n={{ $round['n'] }})</span>
                    </th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($report['sections'] as $section)
                  @if($section['name'] !== null)
                    <tr class="is-section">
                      <td colspan="{{ count($report['rounds']) + 1 }}">{{ $section['name'] }}</td>
                    </tr>
                  @endif

                  @foreach($section['questions'] as $q)
                    @continue($q['answers'] === [])
                    <tr class="is-question">
                      <td colspan="{{ count($report['rounds']) + 1 }}">{{ $q['text'] }}</td>
                    </tr>

                    @foreach($q['answers'] as $answer)
                      <tr>
                        <td class="is-option">{{ $answer['label'] }}</td>
                        @foreach($answer['counts'] as $i => $count)
                          @php($total = $q['answerTotals'][$i])
                          @if($total === 0)
                            {{-- รอบนี้ไม่มีใครตอบข้อนี้เลย — ไม่มีฐานให้คิดร้อยละ --}}
                            <td class="an-research-cell is-zero">–</td>
                          @else
                            <td class="an-research-cell{{ $count === 0 ? ' is-zero' : '' }}">
                              {{ $count }} ({{ number_format($count / $total * 100, 1) }})
                            </td>
                          @endif
                        @endforeach
                      </tr>
                    @endforeach
                  @endforeach
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ---------- การ์ด 3: ค่าเฉลี่ยและส่วนเบี่ยงเบนมาตรฐาน ---------- --}}
      <div class="card pr-card">
        <div class="pr-card-head">
          <h3 class="pr-form-name">ตารางที่ 3 — ค่าเฉลี่ยและส่วนเบี่ยงเบนมาตรฐาน x̄ (S.D.)</h3>
          <button type="button" class="btn btn-outline btn-sm" data-export-research
                  data-file="ค่าเฉลี่ย {{ $report['form'] }}">ส่งออก CSV</button>
        </div>

        <div class="sm-card-body an-research">
          <p class="an-research-note">
            คะแนนคิดจากตำแหน่งของตัวเลือกบนสเกลของข้อนั้น (1 = ตัวเลือกแรก … k = ตัวเลือกสุดท้าย)
            เพราะแบบประเมินไม่ได้กำหนดคะแนนต่อตัวเลือกไว้ · ตอบคนเดียวคำนวณ S.D. ไม่ได้ (–)
          </p>

          <div class="an-dist-wrap">
            <table class="an-research-table">
              <thead>
                <tr>
                  <th class="is-head-q">ข้อคำถาม</th>
                  @foreach($report['rounds'] as $round)
                    <th>
                      {{ $round['name'] }}
                      <span class="an-research-n">x̄ (S.D.)</span>
                    </th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($report['sections'] as $section)
                  @if($section['name'] !== null)
                    <tr class="is-section">
                      <td colspan="{{ count($report['rounds']) + 1 }}">{{ $section['name'] }}</td>
                    </tr>
                  @endif

                  @foreach($section['questions'] as $q)
                    @continue($q['scaleMax'] < 2)
                    <tr>
                      <td class="is-option">{{ $q['text'] }} <span class="an-scale-note">(สเกล 1–{{ $q['scaleMax'] }})</span></td>
                      @foreach($q['stats'] as $stat)
                        @if($stat['mean'] === null)
                          <td class="an-research-cell is-zero">–</td>
                        @else
                          <td class="an-research-cell">{{
                            number_format($stat['mean'], 2).' ('.($stat['sd'] === null ? '–' : number_format($stat['sd'], 2)).')'
                          }}</td>
                        @endif
                      @endforeach
                    </tr>
                  @endforeach
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ---------- การ์ด 4: กราฟการเปลี่ยนแปลงสุทธิ ---------- --}}
      <div class="card pr-card">
        <div class="pr-card-head">
          <h3 class="pr-form-name">กราฟ — การเปลี่ยนแปลงสุทธิ สัดส่วนรอบล่าสุดเทียบรอบแรก (จุด)</h3>
        </div>

        <div class="sm-card-body">
          <p class="an-research-note an-graph-note">
            ค่าบวก = สัดส่วนผู้ตอบระดับนั้นเพิ่มขึ้น · ค่าลบ = ลดลง (หน่วยเป็นจุดร้อยละ)
            — โปรแกรมที่ได้ผลจะเห็นแท่งฝั่งเขียวพุ่งขึ้นและแท่งฝั่งแดงดิ่งลง
          </p>

          <div class="an-graphs">
            @foreach($report['sections'] as $section)
              @foreach($section['questions'] as $q)
                @continue($q['net'] === null)
                <figure class="an-graph">
                  <figcaption class="an-graph-title">{{ $q['text'] }}</figcaption>

                  <div class="an-net">
                    @foreach($q['net']['rows'] as $row)
                      @php($tone = $q['tones'][$row['label']] ?? null)
                      @php($h = round(abs($row['change']) / $q['net']['max'] * 100, 1))
                      <div class="an-net-col" title="{{ $row['label'] }} · {{ ($row['change'] >= 0 ? '+' : '') . number_format($row['change'], 1) }} จุด">
                        <span class="an-net-value {{ $row['change'] > 0 ? 'is-plus' : ($row['change'] < 0 ? 'is-minus' : '') }}">
                          {{ ($row['change'] >= 0 ? '+' : '') . number_format($row['change'], 1) }}
                        </span>
                        <div class="an-net-plot">
                          <div class="an-net-up">
                            @if($row['change'] > 0)
                              <span class="an-net-bar {{ $tone === null ? 'is-neutral-'.($loop->index % 5) : 'is-tone-'.$tone }}"
                                    style="height: {{ $h }}%"></span>
                            @endif
                          </div>
                          <div class="an-net-down">
                            @if($row['change'] < 0)
                              <span class="an-net-bar {{ $tone === null ? 'is-neutral-'.($loop->index % 5) : 'is-tone-'.$tone }}"
                                    style="height: {{ $h }}%"></span>
                            @endif
                          </div>
                        </div>
                        <span class="an-graph-col-label">{{ $row['label'] }}</span>
                      </div>
                    @endforeach
                  </div>

                  {{-- ประโยคสรุปเชิงผลลัพธ์ — เขียนจากตัวเลขจริงของข้อนั้น ไม่ใช่ template ลอย ๆ --}}
                  @php($gains = collect($q['net']['rows'])->filter(fn ($r) => $r['change'] > 0)->sortByDesc('change'))
                  @php($drops = collect($q['net']['rows'])->filter(fn ($r) => $r['change'] < 0)->sortBy('change'))
                  @if($gains->isNotEmpty() || $drops->isNotEmpty())
                    <figcaption class="an-net-caption">
                      {{ $q['net']['before'] }} → {{ $q['net']['after'] }}:
                      @if($gains->isNotEmpty())
                        สัดส่วน "{{ $gains->first()['label'] }}" เพิ่มขึ้น {{ number_format($gains->first()['change'], 1) }} จุด
                      @endif
                      @if($gains->isNotEmpty() && $drops->isNotEmpty()) · @endif
                      @if($drops->isNotEmpty())
                        "{{ $drops->first()['label'] }}" ลดลง {{ number_format(abs($drops->first()['change']), 1) }} จุด
                      @endif
                    </figcaption>
                  @endif
                </figure>
              @endforeach
            @endforeach
          </div>
        </div>
      </div>
    @endif
  @empty
    <div class="card pr-card">
      <div class="fb-empty"><span class="fb-empty-title">ยังไม่มีคำตอบแบบประเมินในระบบ</span></div>
    </div>
  @endforelse
@endsection

@push('page-script')
{{-- TFC.exportCsv อยู่ในไฟล์นี้ — ไม่ได้อยู่ในชุดสคริปต์กลางของ layout --}}
<script src="@assetv('assets/js/activity-module.js')"></script>
<script>
/* ส่งออกตารางเป็น CSV — อ่านจากตารางบนหน้าตรง ๆ
   ตัวเลขจะตรงกับที่ตาเห็นเสมอ ไม่มีทางที่ไฟล์กับหน้าจอเล่าคนละเรื่อง */
document.querySelectorAll('[data-export-research]').forEach(function (button) {
  button.addEventListener('click', function () {
    var table = button.closest('.pr-card').querySelector('.an-research-table');

    var headers = Array.prototype.map.call(
      table.querySelectorAll('thead th'),
      function (th) { return th.textContent.replace(/\s+/g, ' ').trim(); }
    );

    var rows = Array.prototype.map.call(table.querySelectorAll('tbody tr'), function (tr) {
      return Array.prototype.map.call(tr.children, function (td) {
        return td.textContent.replace(/\s+/g, ' ').trim();
      });
    });

    window.TFC.exportCsv(button.getAttribute('data-file') + '.csv', headers, rows);
  });
});
</script>
@endpush
