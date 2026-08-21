@extends('public.activities.layout')

@section('title', 'ทำแบบประเมินแทนคนอื่น')

@section('content')
    <section class="detail-card tr-card">
        <h1 class="tr-heading">ทำแบบประเมินแทนคนอื่น</h1>
        <p class="tr-subheading">ยืนยันตัวตนผู้ถูกประเมินด้วยเบอร์โทรและรหัสบุคคล เพื่อป้องกันการกรอกผิดคน</p>

        @if($errors->any())
            <div class="tr-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('public.tracking-round-qr.proxy.submit') }}" novalidate>
            @csrf

            {{-- รับได้ทั้งเบอร์และอีเมลเหมือนหน้าเข้าสู่ระบบ — คนกรอกแทนมักมีข้อมูลติดต่ออย่างใดอย่างหนึ่ง
                 ห้ามใส่ inputmode="numeric" / maxlength="10" / pattern ตัวเลขล้วนกลับมา จะพิมพ์อีเมลไม่ได้ --}}
            <div class="registration-field">
                <label for="tr-proxy-phone">เบอร์โทรหรืออีเมลผู้ถูกประเมิน <span>*</span></label>
                <input type="text" id="tr-proxy-phone" name="phone" inputmode="email"
                       placeholder="08x-xxx-xxxx หรือ name@email.com" value="{{ old('phone') }}"
                       maxlength="160" required>
            </div>

            <div class="registration-field">
                <label for="tr-proxy-prefix">รหัสบุคคลหรือชื่อผู้ถูกประเมิน <span>*</span></label>
                {{-- ตัวอย่างอยู่ใน placeholder ไม่ใช่บรรทัดใต้ช่อง — บรรทัดใต้ช่องดันปุ่มลงไป
                     และหายตั้งแต่เริ่มพิมพ์ก็ไม่เสียอะไร เพราะเป็นแค่ตัวอย่างรูปแบบ --}}
                <input type="text" id="tr-proxy-prefix" name="name_prefix"
                       placeholder="เช่น P0002 หรือ สมหญิง" maxlength="20" autocomplete="off"
                       value="{{ old('name_prefix') }}" required>
            </div>

            <button type="submit" class="tr-primary-button tr-form-submit">ยืนยันตัวตน</button>
        </form>

        <p class="tr-note">
            <a href="{{ route('public.tracking-round-qr.dashboard') }}">ยกเลิก</a>
        </p>
    </section>
@endsection
