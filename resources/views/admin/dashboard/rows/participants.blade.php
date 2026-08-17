{{-- แถว 2 — ผู้เข้าร่วมกิจกรรม: เพศ · ช่วงอายุ · อาชีพ · หลักสูตรยอดนิยม --}}
@php
    $people = $data['participants'];
    $cohortTotal = $data['cohort']['total'];
    $cohortShare = $people['total'] > 0
        ? number_format(($cohortTotal / $people['total']) * 100, 1) . '%'
        : '—';
@endphp

<section class="dbo-card" aria-labelledby="dbo-participants-title">
  <div class="dbo-total">
    <div class="dbo-total-main">
      <h2 class="dbo-total-label" id="dbo-participants-title">ผู้เข้าร่วมกิจกรรมทั้งหมด</h2>
      <span class="dbo-num">{{ $num($people['total']) }}</span>
      <span class="dbo-kpi-unit">คน</span>
    </div>
    {{-- บรรทัดเล็กบอกว่ากี่คนในนี้อยู่ในกลุ่มตัวอย่าง — ตัวเล็กกว่ายอดรวมชัดเจน
         ให้อ่านเป็นข้อมูลประกอบ ไม่ใช่ตัวเลขที่สองที่มาแข่งความเด่น --}}
    <span class="dbo-total-note">
      เข้าร่วมกลุ่มตัวอย่าง
      <span class="dbo-num">{{ $num($cohortTotal) }}</span> คน · {{ $cohortShare }}
    </span>
  </div>

  @if ($people['total'] === 0)
    @include('admin.dashboard.empty', [
      'title' => 'ยังไม่มีผู้ลงทะเบียนในช่วงเวลาที่เลือก',
      'note' => 'เมื่อมีผู้ลงทะเบียนเข้ากิจกรรม แผงเพศ ช่วงอายุ อาชีพ และหลักสูตรจะแสดงที่นี่',
    ])
  @else
    <div class="dbo-panels">

      {{-- แผง 1 — จำแนกตามเพศ --}}
      <div class="dbo-panel dbo-panel--gender">
        <span class="dbo-panel-title">จำแนกตามเพศ</span>
        @foreach ($people['gender'] as $sex)
          <div class="dbo-gender dbo-mark" data-sex="{{ $sex['key'] }}"
               {!! $tip('sex-' . $sex['key'], $sex['label'], [
                 ['จำนวน', $num($sex['count']) . ' คน'],
                 ['สัดส่วน', $sex['pct']],
               ]) !!}>
            <span class="dbo-gender-icon">
              @include('admin.dashboard.icon', [
                'name' => $sex['key'] === 'unknown' ? 'neutral' : $sex['key'],
                'size' => 22,
              ])
            </span>
            <div class="dbo-gender-text">
              <div class="dbo-gender-count">
                <span class="dbo-num">{{ $num($sex['count']) }}</span>
                <span class="dbo-gender-unit">คน</span>
              </div>
              <span class="dbo-gender-label">{{ $sex['label'] }} · {{ $sex['pct'] }}</span>
            </div>
          </div>
        @endforeach
      </div>

      {{-- แผง 2 — จำแนกตามอายุ
           กล่องนี้เป็นตัววัดความสูงของกราฟแท่งในแผงหลักสูตร (dbo-baseline-source)
           เพื่อให้เส้นฐานของสองกราฟอยู่ระดับเดียวกัน --}}
      <div class="dbo-panel dbo-panel--age">
        <span class="dbo-panel-title">จำแนกตามอายุ</span>
        <div class="dbo-hbars">
          @foreach ($people['age_bands'] as $band)
            <div class="dbo-hbar dbo-hit"
                 {!! $tip('age-' . $loop->index, $band['label'], [
                   ['จำนวน', $num($band['count']) . ' คน'],
                   ['สัดส่วน', $band['pct']],
                 ]) !!}>
              <span class="dbo-hbar-label">{{ $band['label'] }}</span>
              <div class="dbo-hbar-track">
                <span class="dbo-hbar-fill dbo-mark dbo-r{{ $band['rank'] }}"
                      data-dbo-key="age-{{ $loop->index }}"
                      style="--dbo-w: {{ $band['bar'] }}%"></span>
                <span class="dbo-hbar-count dbo-num">{{ $num($band['count']) }}</span>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- แผง 3 — จำแนกตามอาชีพ · แท่งแนวนอนชุดเดียวกับช่วงอายุ --}}
      <div class="dbo-panel dbo-panel--job">
        <span class="dbo-panel-title">จำแนกตามอาชีพ</span>
        @if ($people['occupations'] === [])
          @include('admin.dashboard.empty', [
            'title' => 'ยังไม่มีใบลงทะเบียนที่ระบุอาชีพ',
            'note' => 'ช่องอาชีพเปิดใช้อยู่ในแบบลงทะเบียนแล้ว แต่ยังไม่มีผู้ลงทะเบียนกรอกค่ามา',
          ])
        @else
          {{-- แสดง 5 แท่งพอดีความสูงของแผงช่วงอายุ ที่เหลือเลื่อนดู
               จำนวนอาชีพขึ้นกับ master data จะมีกี่รายการก็ได้ แต่การ์ดต้องสูงเท่าเดิมเสมอ --}}
          <div class="dbo-hbars dbo-hbars--scroll" tabindex="0" role="group"
               aria-label="จำแนกตามอาชีพ {{ count($people['occupations']) }} รายการ เลื่อนเพื่อดูทั้งหมด">
            @foreach ($people['occupations'] as $job)
              <div class="dbo-hbar dbo-hit"
                   {!! $tip('job-' . $loop->index, $job['label'], [
                     ['จำนวน', $num($job['count']) . ' คน'],
                     ['สัดส่วน', $job['pct']],
                   ]) !!}>
                <span class="dbo-hbar-label">{{ $job['label'] }}</span>
                <div class="dbo-hbar-track">
                  <span class="dbo-hbar-fill dbo-mark dbo-r{{ $job['rank'] }}"
                        data-dbo-key="job-{{ $loop->index }}"
                        style="--dbo-w: {{ $job['bar'] }}%"></span>
                  <span class="dbo-hbar-count dbo-num">{{ $num($job['count']) }}</span>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

    </div>
  @endif
</section>
