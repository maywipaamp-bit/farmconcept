@extends('layouts.admin')

@section('title', 'ผลตอบรายคน · '.$participant->person_code)

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <a href="{{ route('admin.evaluations.person-results.index') }}">ผลตอบรายคน</a> <span>/</span>
    <span class="is-current">{{ $participant->person_code }}</span>
  </nav>

  <div class="rl-head">
    <div>
      {{-- แสดงเฉพาะรหัสบุคคล ไม่แสดงชื่อ (คำสั่งทีม) — กลุ่มตัวอย่างเป็นข้อมูลนิรนาม --}}
      <h1 class="rl-title">{{ $participant->person_code }}</h1>
      <p class="pr-subtitle">
        ตอบแล้ว <span class="num">{{ $totalRounds }}</span> รอบ ·
        อ่านแนวโน้มจากลูกศรในช่อง: เทียบกับรอบก่อนหน้าที่มีคำตอบของข้อเดียวกัน
      </p>
    </div>
    <a class="btn btn-outline" href="{{ route('admin.evaluations.person-results.index') }}">← กลับรายชื่อ</a>
  </div>

  @forelse($matrices as $matrix)
    <div class="card pr-card">
      <div class="pr-card-head">
        <h2 class="pr-form-name">{{ $matrix['form'] }}</h2>
        <span class="pr-legend">
          <span class="pr-trend is-up">▲ ดีขึ้น</span>
          <span class="pr-trend is-down">▼ ลดลง</span>
          <span class="pr-trend is-changed">◆ เปลี่ยน (บอกทิศไม่ได้)</span>
        </span>
      </div>

      {{-- ภาพรวมเทียบรอบแรกกับรอบล่าสุด นับเป็นจำนวนข้อ ไม่ใช่คะแนนรวม
           เพราะตัวเลือกของแบบประเมินไม่มีคะแนนตัวเลขกำกับ การตีคะแนนเองคือการแต่งตัวเลข --}}
      @php($s = $matrix['summary'])
      @if(array_sum($s) > 0)
        <div class="pr-overall">
          <span class="pr-overall-label">ภาพรวมรอบแรก → รอบล่าสุด</span>
          <span class="pr-chip is-up">ดีขึ้น <b class="num">{{ $s['up'] }}</b> ข้อ</span>
          <span class="pr-chip">คงเดิม <b class="num">{{ $s['same'] }}</b> ข้อ</span>
          <span class="pr-chip is-down">ลดลง <b class="num">{{ $s['down'] }}</b> ข้อ</span>
          @if($s['changed'] > 0)
            <span class="pr-chip is-changed">เปลี่ยนแต่บอกทิศไม่ได้ <b class="num">{{ $s['changed'] }}</b> ข้อ</span>
          @endif
        </div>
      @endif

      <div class="pr-scroll">
        <table class="pr-matrix">
          <colgroup>
            <col class="pr-col-q">
            @foreach($matrix['columns'] as $column)
              <col>
            @endforeach
          </colgroup>
          <thead>
            <tr>
              <th class="pr-q-head">ข้อคำถาม</th>
              @foreach($matrix['columns'] as $column)
                <th>
                  <span class="pr-round">{{ $column['round'] }}</span>
                  @if($column['at'])
                    <span class="pr-round-at">@thaidate($column['at'])</span>
                  @endif
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($matrix['rows'] as $row)
              @if($row['type'] === 'section')
                <tr class="pr-section">
                  <td colspan="{{ count($matrix['columns']) + 1 }}">
                    <span class="pr-section-name">{{ $row['text'] }}</span>
                    @php($ss = $row['summary'] ?? ['up' => 0, 'down' => 0, 'same' => 0, 'changed' => 0])
                    @if(array_sum($ss) > 0)
                      <span class="pr-section-summary">
                        <span class="pr-trend is-up">▲ {{ $ss['up'] }}</span>
                        <span class="pr-trend is-down">▼ {{ $ss['down'] }}</span>
                        <span>คงเดิม {{ $ss['same'] }}</span>
                        @if($ss['changed'] > 0)<span class="pr-trend is-changed">◆ {{ $ss['changed'] }}</span>@endif
                      </span>
                    @endif
                  </td>
                </tr>
              @else
                <tr>
                  <td class="pr-q">{{ $row['text'] }}</td>
                  @foreach($row['cells'] as $cell)
                    <td class="pr-cell">
                      <span class="pr-answer">{{ $cell['label'] }}</span>
                      <span class="pr-cell-meta">
                        {{-- ตำแหน่งบนสเกล (เช่น 3/4) — ตัวเลขทำให้เทียบข้ามช่องได้เร็วกว่าอ่านป้ายซ้ำ --}}
                        @if($cell['pos'] !== null && $cell['max'])
                          <span class="pr-pos num">{{ $cell['pos'] }}/{{ $cell['max'] }}</span>
                        @endif
                        @if($cell['trend'] === 'up')
                          <span class="pr-trend is-up" title="ดีขึ้นจากรอบก่อนหน้า">▲</span>
                        @elseif($cell['trend'] === 'down')
                          <span class="pr-trend is-down" title="ลดลงจากรอบก่อนหน้า">▼</span>
                        @elseif($cell['trend'] === 'changed')
                          <span class="pr-trend is-changed" title="คำตอบเปลี่ยนจากรอบก่อนหน้า">◆</span>
                        @endif
                      </span>
                    </td>
                  @endforeach
                </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @empty
    <div class="card pr-card">
      <div class="fb-empty"><span class="fb-empty-title">คนนี้ยังไม่มีคำตอบที่ส่งเข้ามา</span></div>
    </div>
  @endforelse
@endsection
