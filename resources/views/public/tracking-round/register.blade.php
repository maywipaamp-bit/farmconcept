@extends('public.activities.layout')

@section('title', 'ลงทะเบียนกลุ่มตัวอย่าง')

@section('content')
    <section class="detail-card tr-card">
        <h1 class="tr-login-title">ลงทะเบียนกลุ่มตัวอย่าง</h1>

        @if(session('phoneNotFound'))
            <div class="tr-notice" role="status">ไม่พบเบอร์นี้ในระบบ กรุณาลงทะเบียนก่อน</div>
        @endif

        {{-- error กล่องเดียว ไล่ตามลำดับฟิลด์บนลงล่าง ผู้ใช้จะได้แก้ทีละอย่างไม่ต้องกวาดหาทั้งหน้า --}}
        @if($errors->any())
            <div class="tr-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('public.tracking-round-qr.register.submit') }}" novalidate>
            @csrf

            {{-- ไม่เก็บชื่อเลย — ระบบออกรหัสบุคคล (เช่น P0001) ให้เป็นชื่อในระบบแทน
                 ผู้ตอบใช้รหัสนี้คู่กับเบอร์โทรตอนเข้าระบบครั้งถัดไป --}}

            {{-- เบอร์ที่กรอกไว้ตอนยืนยันตัวตนถูกเติมมาให้แล้ว ไม่ต้องพิมพ์ซ้ำ --}}
            <div class="registration-field">
                <label for="tr-phone">เบอร์โทรศัพท์ <span>*</span></label>
                <input type="tel" id="tr-phone" name="phone" inputmode="numeric" autocomplete="tel"
                       placeholder="08x-xxx-xxxx" value="{{ old('phone', $phone) }}"
                       maxlength="10" pattern="[0-9]{10}" required>
            </div>

            <div class="registration-field">
                <label for="tr-gender">เพศ <span>*</span></label>
                <select id="tr-gender" name="gender" required>
                    <option value="" disabled @selected(! old('gender'))>เลือกเพศ</option>
                    @foreach($genders as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ตัวเลือกช่วงอายุมาจาก master data (mst_options กลุ่ม age_range) ชุดเดียวกับแบบลงทะเบียนกิจกรรม
                 ไม่ถามปีเกิดเพราะระบุตัวได้ง่ายและทำให้คนอึดอัด — ช่วงอายุพอสำหรับการวิเคราะห์ --}}
            <div class="registration-field">
                <label for="tr-age-range">ช่วงอายุ <span>*</span></label>
                <select id="tr-age-range" name="age_range_id" required>
                    <option value="" disabled @selected(! old('age_range_id'))>เลือกช่วงอายุ</option>
                    @foreach($ageRanges as $range)
                        <option value="{{ $range->id }}" @selected(old('age_range_id') == $range->id)>{{ $range->label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ต้องมีความยินยอมก่อนเก็บข้อมูลสุขภาพเสมอ ไม่ว่าจะเก็บทางไหน
                 ระเบียนความยินยอมถูกบันทึกแยกไว้ที่ ptp_consents ตรวจย้อนหลังได้ --}}
            <label class="registration-consent">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                <span>ยินยอมให้เก็บและใช้ข้อมูลเพื่อการวิจัยของโครงการ ตามนโยบายคุ้มครองข้อมูลส่วนบุคคล <b>*</b></span>
            </label>

            <button type="submit" class="tr-primary-button tr-form-submit">ลงทะเบียน</button>
        </form>

        <p class="tr-note">
            เคยลงทะเบียนแล้ว?
            <a href="{{ route('public.tracking-round-qr') }}">เข้าสู่ระบบด้วยเบอร์โทร</a>
        </p>
    </section>
@endsection
