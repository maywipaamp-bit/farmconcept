{{-- แถว 2 — ผู้เข้าร่วมกิจกรรมทั้งหมด: เพศ · ช่วงอายุ · หลักสูตรยอดนิยม --}}
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
    <div class="dbo-total-side">
      <span class="dbo-total-side-label">เข้าร่วมกลุ่มตัวอย่าง</span>
      <span class="dbo-num">{{ $num($cohortTotal) }}</span>
      <span class="dbo-kpi-unit">คน · {{ $cohortShare }}</span>
    </div>
  </div>

  @if ($people['total'] === 0)
    @include('admin.dashboard.empty', [
      'title' => 'ยังไม่มีผู้ลงทะเบียนในช่วงเวลาที่เลือก',
      'note' => 'เมื่อมีผู้ลงทะเบียนเข้ากิจกรรม แผงเพศ ช่วงอายุ และหลักสูตรจะแสดงที่นี่',
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
           กล่องนี้เป็นตัววัดความสูงของกราฟแท่งในแผง 3 (dbo-baseline-source)
           เพื่อให้เส้นฐานของสองกราฟอยู่ระดับเดียวกัน --}}
      <div class="dbo-panel dbo-panel--age">
        <span class="dbo-panel-title">จำแนกตามอายุ</span>
        <div class="dbo-ages" data-dbo-baseline-source>
          @foreach ($people['age_bands'] as $band)
            <div class="dbo-age dbo-hit"
                 {!! $tip('age-' . $loop->index, $band['label'], [
                   ['จำนวน', $num($band['count']) . ' คน'],
                   ['สัดส่วน', $band['pct']],
                 ]) !!}>
              <span class="dbo-age-label">{{ $band['label'] }}</span>
              <div class="dbo-age-bar">
                <span class="dbo-age-fill dbo-mark dbo-r{{ $band['rank'] }}"
                      data-dbo-key="age-{{ $loop->index }}"
                      style="--dbo-w: {{ $band['bar'] }}%"></span>
                <span class="dbo-age-count dbo-num">{{ $num($band['count']) }}</span>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- แผง 3 — หลักสูตรที่มีผู้เข้าร่วมสูงสุด: กราฟแท่งตั้ง + ตารางอันดับ ใช้คีย์ชุดเดียวกัน --}}
      <div class="dbo-panel dbo-panel--courses">
        @if ($people['top_courses'] === [])
          @include('admin.dashboard.empty', [
            'title' => 'ยังไม่มีกิจกรรมที่ผูกหลักสูตร',
            'note' => 'อันดับหลักสูตรนับจากผู้ลงทะเบียนของกิจกรรมที่ระบุหลักสูตรไว้แล้ว',
          ])
        @else
          <div class="dbo-courses-chart">
            <span class="dbo-panel-title">หลักสูตรที่มีผู้เข้าร่วมสูงสุด · {{ count($people['top_courses']) }} อันดับแรก</span>
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
            <div class="dbo-bar-labels" aria-hidden="true">
              @foreach ($people['top_courses'] as $course)
                <span class="dbo-bar-label">{{ $course['label'] }}</span>
              @endforeach
            </div>
          </div>

          <div class="dbo-courses-table">
            <span class="dbo-panel-title">รายละเอียด {{ count($people['top_courses']) }} อันดับแรก</span>
            <div class="dbo-rank-head">
              <span>#</span>
              <span>หลักสูตร</span>
              <span class="dbo-align-right">จำนวน</span>
              <span class="dbo-align-right">%</span>
            </div>
            <div class="dbo-rank-list">
              @foreach ($people['top_courses'] as $course)
                <div class="dbo-rank-row dbo-hit"
                     {!! $tip('course-' . $course['no'], $course['label'], [
                       ['ผู้เข้าร่วม', $num($course['count']) . ' คน'],
                       ['สัดส่วน', $course['pct']],
                     ]) !!}>
                  <span class="dbo-rank-no dbo-num">{{ $course['no'] }}</span>
                  <span class="dbo-rank-name" title="{{ $course['label'] }}">{{ $course['label'] }}</span>
                  <span class="dbo-rank-count dbo-num">{{ $num($course['count']) }}</span>
                  <span class="dbo-rank-pct dbo-num">{{ $course['pct'] }}</span>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    </div>
  @endif
</section>
