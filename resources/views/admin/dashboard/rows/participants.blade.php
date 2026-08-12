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
        <div class="dbo-hbars" data-dbo-baseline-source>
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

      {{-- แผง 4 — หลักสูตรที่มีผู้เข้าร่วมสูงสุด (กราฟแท่งตั้ง)
           ตารางอันดับข้าง ๆ ถูกตัดออก — ตัวเลขบนหัวแท่งกับ tooltip บอกข้อมูลชุดเดียวกันอยู่แล้ว --}}
      <div class="dbo-panel dbo-panel--courses">
        @if ($people['top_courses'] === [])
          @include('admin.dashboard.empty', [
            'title' => 'ยังไม่มีกิจกรรมที่ผูกหลักสูตร',
            'note' => 'อันดับหลักสูตรนับจากผู้ลงทะเบียนของกิจกรรมที่ระบุหลักสูตรไว้แล้ว',
          ])
        @else
          <div class="dbo-courses-chart">
            {{-- ไม่ต่อท้ายว่า "N อันดับแรก" — จำนวนแท่งที่เห็นบอกอยู่แล้ว --}}
            <span class="dbo-panel-title">หลักสูตรที่มีผู้เข้าร่วมสูงสุด</span>
            <div class="dbo-bars">
              @foreach ($people['top_courses'] as $course)
                <div class="dbo-bar dbo-hit"
                     {!! $tip('course-' . $course['no'], $course['label'], [
                       ['ผู้เข้าร่วม', $num($course['count']) . ' คน'],
                       ['สัดส่วน', $course['pct']],
                     ]) !!}>
                  <span class="dbo-bar-count dbo-num">{{ $num($course['count']) }}</span>
                  <span class="dbo-bar-slot">
                    <span class="dbo-bar-fill dbo-mark dbo-r{{ $course['rank'] }}"
                          data-dbo-key="course-{{ $course['no'] }}"
                          style="--dbo-h: {{ $course['bar'] }}%"></span>
                  </span>
                </div>
              @endforeach
            </div>
            {{-- aria-hidden เพราะเป็นข้อความซ้ำกับตารางสำหรับโปรแกรมอ่านหน้าจอด้านล่าง
                 ป้ายถูกตัดที่ 2 บรรทัด ชี้เพื่อดูชื่อเต็มใน tooltip (คีย์เดียวกับแท่งของมัน
                 จึงไฮไลต์แท่งที่คู่กันให้ด้วย) · title= เป็นทางสำรองเมื่อ JS ยังไม่ทำงาน --}}
            <div class="dbo-bar-labels" aria-hidden="true">
              @foreach ($people['top_courses'] as $course)
                <span class="dbo-bar-label" title="{{ $course['label'] }}"
                      {!! $tip('course-' . $course['no'], $course['label'], [
                        ['ผู้เข้าร่วม', $num($course['count']) . ' คน'],
                        ['สัดส่วน', $course['pct']],
                      ]) !!}>{{ $course['label'] }}</span>
              @endforeach
            </div>
          </div>

          {{-- ขนาดแท่งสื่อจำนวนให้โปรแกรมอ่านหน้าจอไม่ได้ ตารางนี้จึงเป็นค่าที่อ่านได้จริง --}}
          <table class="dbo-sr">
            <caption>หลักสูตรที่มีผู้เข้าร่วมสูงสุด</caption>
            <thead>
              <tr>
                <th scope="col">หลักสูตร</th>
                <th scope="col">ผู้เข้าร่วม</th>
                <th scope="col">สัดส่วน</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($people['top_courses'] as $course)
                <tr>
                  <th scope="row">{{ $course['label'] }}</th>
                  <td>{{ $num($course['count']) }} คน</td>
                  <td>{{ $course['pct'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>
  @endif
</section>
