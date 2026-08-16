@extends('public.activities.layout')

@section('title', 'ทำแบบประเมินแทนคนอื่น')

@section('content')
    <section class="detail-card tr-card">
        <h1 class="tr-heading">ทำแบบประเมินแทนคนอื่น</h1>
        <p class="tr-subheading">ยืนยันตัวตนผู้ถูกประเมินด้วยเบอร์โทรและชื่อบางส่วน เพื่อป้องกันการกรอกผิดคน</p>

        @if($errors->any())
            <div class="tr-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('public.tracking-round-qr.proxy.submit') }}" novalidate>
            @csrf

            <div class="registration-field">
                <label for="tr-proxy-phone">เบอร์โทรผู้ถูกประเมิน <span>*</span></label>
                <input type="tel" id="tr-proxy-phone" name="phone" inputmode="numeric"
                       placeholder="08x-xxx-xxxx" value="{{ old('phone') }}"
                       maxlength="10" pattern="[0-9]{10}" required>
            </div>

            <div class="registration-field">
                <label for="tr-proxy-prefix">รหัสบุคคลผู้ถูกประเมิน <span>*</span></label>
                <input type="text" id="tr-proxy-prefix" name="name_prefix" class="tr-prefix-input"
                       maxlength="6" autocomplete="off" value="{{ old('name_prefix') }}" required>
                <span class="tr-field-hint">เช่น P0002</span>
            </div>

            <button type="submit" class="tr-primary-button tr-form-submit">ยืนยันตัวตน</button>
        </form>

        <p class="tr-note">
            <a href="{{ route('public.tracking-round-qr.dashboard') }}">ยกเลิก</a>
        </p>
    </section>
@endsection
