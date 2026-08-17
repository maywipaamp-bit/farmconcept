@extends('layouts.admin')

@section('title', 'ผลการวิเคราะห์')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <span class="is-current">ผลการวิเคราะห์</span>
  </nav>

  <div class="rl-head">
    <div>
      <h1 class="rl-title">ผลการวิเคราะห์</h1>
      <p class="pr-subtitle">
        ภาพรวมสุขภาวะระดับโครงการ — สัดส่วนกลุ่มตัวอย่าง · การตอบรายรอบ · ผลก่อนและหลังเข้าร่วม
        · รายงานเจาะรายแบบประเมินอยู่ที่เมนู สรุปผลแบบประเมิน
      </p>
    </div>
  </div>

  {{-- การ์ดชุดเดียวกับแดชบอร์ด (partial เดิม ตัวคำนวณเดิม)
       ครอบด้วย .dbo เพราะตัวแปรสี/ฟอนต์ของการ์ดพวกนี้ประกาศไว้ใต้คลาสนั้น --}}
  @php
      $data = $overview;

      /* สองตัวช่วยที่ partial ของแดชบอร์ดคาดว่าจะมี — นิยามเดียวกับ admin.dashboard.body */
      $tip = function (string $key, string $title, array $lines): string {
          $payload = json_encode(['title' => $title, 'lines' => $lines], JSON_UNESCAPED_UNICODE);

          return 'data-dbo-key="' . e($key) . '" data-dbo-tip="' . e($payload) . '"';
      };
      $num = fn (int $value): string => number_format($value);
  @endphp
  {{-- id=dbo-body ให้สคริปต์ tooltip ของแดชบอร์ดจับได้ — ชี้ชิ้นกราฟแล้วเห็นตัวเลขจริง --}}
  <div class="dbo an-overview" id="dbo-body">
    {{-- แถวที่ 1: กลุ่มเป้าหมาย · เพศ · ช่วงอายุ — สามวงฐานเดียวกัน ป้ายอยู่ข้างวงเสมอ --}}
    @php($cohort = $data['cohort'])
    @php($demoDonuts = collect($overview['demographic_donuts'])->keyBy('name'))
    <div class="dbo-row an-donut-row">
      @include('admin.dashboard.cards.cohort-donut')
      @include('admin.evaluations.partials.cohort-donut-card', ['donut' => $demoDonuts['เพศ']])
      @include('admin.evaluations.partials.cohort-donut-card', ['donut' => $demoDonuts['ช่วงอายุ']])
    </div>

    {{-- แถวที่ 2: พื้นที่ (รายการยาวได้ เลยได้พื้นที่กว้างกว่า) คู่กับการตอบรายรอบ --}}
    @php($survey = $data['survey_rounds'])
    <div class="dbo-row an-area-row">
      @include('admin.evaluations.partials.cohort-donut-card', ['donut' => $demoDonuts['พื้นที่']])
      @include('admin.dashboard.cards.survey-rounds')
    </div>

    {{-- แถวที่ 3: ก่อน–หลัง + ผลวิเคราะห์แนวโน้ม --}}
    @include('admin.dashboard.rows.assessment')

    {{-- ที่มาของตัวเลข — ให้ผู้อ่านตรวจย้อนได้ว่าผลวิเคราะห์มาจากข้อมูลจริง ไม่ใช่เลขลอย
         ชี้จุดบนกราฟเส้นข้างบนจะเห็นค่าของหัวข้อนั้นเป็นราย จุด (tooltip) --}}
    <p class="an-sources">
      ที่มาของตัวเลข: คำนวณจากคำตอบจริงในระบบ —
      แบบประเมิน <b class="num">{{ $num($overview['sources']['responses']) }}</b> ใบ ·
      คำตอบรายข้อ <b class="num">{{ $num($overview['sources']['answers']) }}</b> ข้อ ·
      ผู้ตอบ <b class="num">{{ $num($overview['sources']['people']) }}</b> คน
      (ชี้จุดบนกราฟเพื่อดูค่าของแต่ละหัวข้อ) ·
      ตรวจรายข้อได้ที่ <a href="{{ route('admin.evaluations.summary') }}">สรุปผลแบบประเมิน</a> ·
      ตรวจรายใบตอบได้ที่ <a href="{{ route('admin.evaluations.responses.index') }}">ตอบแบบประเมิน</a> ·
      ตรวจรายคนได้ที่ <a href="{{ route('admin.evaluations.person-results.index') }}">ผลตอบรายคน</a>
    </p>
  </div>

  {{-- กล่อง tooltip ที่สคริปต์ของแดชบอร์ดใช้ — โครงเดียวกับ admin/dashboard.blade.php --}}
  <div class="dbo-tip" id="dbo-tip" role="tooltip" aria-hidden="true">
    <div class="dbo-tip-head">
      <span class="dbo-tip-dot" data-tip-dot></span>
      <span class="dbo-tip-title" data-tip-title></span>
    </div>
    <div class="dbo-tip-lines" data-tip-lines></div>
  </div>
@endsection

@push('page-script')
{{-- สคริปต์ tooltip/ไฮไลต์ตัวเดียวกับแดชบอร์ด — ชี้กราฟแล้วเห็นตัวเลขจริงของชิ้นนั้น --}}
<script src="{{ asset('assets/js/dashboard-insight.js') }}"></script>
@endpush
