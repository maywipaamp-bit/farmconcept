@extends('public.activities.layout')

@section('title', 'ลงทะเบียนกลุ่มตัวอย่าง')

@section('content')
    <section class="detail-card tr-card">
        <h1 class="tr-login-title">ลงทะเบียนกลุ่มตัวอย่าง</h1>

        {{-- error กล่องเดียว ไล่ตามลำดับฟิลด์บนลงล่าง ผู้ใช้จะได้แก้ทีละอย่างไม่ต้องกวาดหาทั้งหน้า --}}
        @if($errors->any())
            <div class="tr-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('public.tracking-round-qr.register.submit') }}" novalidate>
            @csrf

            <div class="registration-field">
                <label for="tr-name">ชื่อ-นามสกุล <span>*</span></label>
                <input type="text" id="tr-name" name="name" autocomplete="name"
                       placeholder="เช่น สมหญิง ใจดี" value="{{ old('name', $lineName) }}" required>
            </div>

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

            {{-- รายชื่อพื้นที่มาจาก master data ไม่ได้เขียนรายชื่อชุมชนตายไว้ในหน้าจอ --}}
            <div class="registration-field">
                <label for="tr-area">พื้นที่ / ชุมชน <span>*</span></label>
                <select id="tr-area" name="area_id" required>
                    <option value="" disabled @selected(! old('area_id'))>เลือกพื้นที่ / ชุมชน</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" @selected(old('area_id') == $area->id)>{{ $area->name }}</option>
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
