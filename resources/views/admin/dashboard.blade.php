@extends('layouts.admin')

@section('title', 'ภาพรวมการดำเนินงาน')

@section('main-class', 'dbo')

@push('styles')
{{-- ฟอนต์ของแดชบอร์ด — โหลดเฉพาะหน้านี้ ไม่แตะฟอนต์กลางของระบบ
     ใช้ <link> ไม่ใช่ @import ใน CSS ด้วยเหตุผลเดียวกับที่ layout อธิบายไว้
     คือกันคำขอต่อแถวสองชั้นที่ทำให้เห็นฟอนต์สำรองแวบหนึ่งตอนเปิดหน้า --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600&display=swap">
@endpush

@section('content')
<div class="dbo-page">

  <div class="dbo-head">
    <div class="dbo-head-text">
      <span class="dbo-eyebrow">THEFARMCONCEPT · ADMIN</span>
      <h1 class="dbo-title">ภาพรวมการดำเนินงาน</h1>
    </div>

    <div class="dbo-head-tools">
      {{-- ตัวกรองเป็นลิงก์จริง ไม่ใช่ปุ่มเปล่า — กดกลางปุ่มเปิดแท็บใหม่ได้ และใช้งานได้แม้ JS ยังไม่ทำงาน
           JS ดักคลิกแล้วเปลี่ยนเฉพาะเนื้อในให้ ไม่โหลดหน้าใหม่ --}}
      <div class="dbo-filters" id="dbo-filters" role="group" aria-label="ช่วงเวลาของข้อมูล">
        @foreach ($data['range_options'] as $option)
          <a class="dbo-pill{{ $option['active'] ? ' is-active' : '' }}"
             href="{{ route('admin.dashboard', ['range' => $option['key']]) }}"
             data-range="{{ $option['key'] }}"
             @if ($option['active']) aria-current="true" @endif>{{ $option['label'] }}</a>
        @endforeach
      </div>
      <span class="dbo-stamp" id="dbo-stamp">ข้อมูล ณ {{ $data['generated_at']->locale('th')->translatedFormat('j M') }} {{ $data['generated_at']->year + 543 }}</span>
    </div>
  </div>

  {{-- ทุกอย่างที่เปลี่ยนตามช่วงเวลาอยู่ในกล่องนี้ก้อนเดียว
       aria-live="polite" ให้โปรแกรมอ่านหน้าจอรู้ว่าข้อมูลถูกเปลี่ยนหลังกดตัวกรอง --}}
  <div class="dbo-body" id="dbo-body" aria-live="polite" aria-busy="false">
    @include('admin.dashboard.body', ['data' => $data])
  </div>
</div>

{{-- กล่อง tooltip กล่องเดียวของทั้งหน้า — JS ย้ายตำแหน่งด้วย style.left/top ตรง ๆ
     ไม่ผูกกับ state ของหน้า จึงไม่ทำให้กราฟทั้งหน้าวาดใหม่ทุกครั้งที่เมาส์ขยับ --}}
<div class="dbo-tip" id="dbo-tip" role="tooltip" aria-hidden="true">
  <div class="dbo-tip-head">
    <span class="dbo-tip-dot" data-tip-dot></span>
    <span class="dbo-tip-title" data-tip-title></span>
  </div>
  <div data-tip-lines></div>
</div>
@endsection

@push('page-script')
<script src="{{ asset('assets/js/dashboard-insight.js') }}"></script>
@endpush
