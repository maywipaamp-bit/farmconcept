@extends('public.activities.layout')

@section('title', 'ยืนยันตัวตน')

@section('content')
    <section class="detail-card tr-card">
        <h1 class="tr-heading">ยืนยันตัวตน</h1>
        {{-- ไม่แสดงรายชื่อในเบอร์นี้เลย แม้แบบปิดบัง — แค่รู้เบอร์ของบ้านหนึ่งไม่ควรอ่านได้ว่ามีใครอยู่ในโครงการ
             ผู้ตอบพิมพ์ชื่อตัวเองจากความจำ ระบบเป็นฝ่ายจับคู่ให้ ไม่ใช่ยื่นตัวเลือกให้เดา --}}
        <p class="tr-subheading">เบอร์ {{ $phone }} · กรอกรหัสบุคคลของคุณเพื่อเข้าใช้งาน</p>

        @error('name_prefix')<div class="tr-error" role="alert">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('public.tracking-round-qr.choose.submit') }}" novalidate>
            @csrf

            <div class="registration-field">
                <label for="tr-prefix">รหัสบุคคล <span>*</span></label>
                <input type="text" id="tr-prefix" name="name_prefix" class="tr-prefix-input"
                       maxlength="6" autocomplete="off" inputmode="text" autofocus
                       value="{{ old('name_prefix') }}" required>
                <span class="tr-field-hint">เช่น P0001</span>
            </div>

            <button type="submit" class="tr-primary-button tr-form-submit">เข้าใช้งาน</button>
        </form>

        <p class="tr-note">
            ยังไม่เคยลงทะเบียน
            <a href="{{ route('public.tracking-round-qr.register') }}">ลงทะเบียนเพิ่ม</a>
        </p>

        <p class="tr-note">
            <a href="{{ route('public.tracking-round-qr') }}">ใช้เบอร์อื่น</a>
        </p>
    </section>
@endsection
