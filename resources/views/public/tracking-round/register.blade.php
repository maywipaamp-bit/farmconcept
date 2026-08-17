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
                 ระเบียนความยินยอมถูกบันทึกแยกไว้ที่ ptp_consents ตรวจย้อนหลังได้

                 ข้อความในลิงก์เปิดอ่านฉบับเต็มได้ — ยินยอมโดยไม่มีทางอ่านว่ายินยอมอะไร
                 ไม่นับเป็นความยินยอมที่ได้แจ้งแล้ว ฉบับที่ไม่มีใน master จะแสดงเป็นข้อความเฉย ๆ --}}
            <label class="registration-consent">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                <span>ยินยอมให้เก็บและใช้@if($consentDocs['cohort'])<a href="#" data-consent-doc="cohort">ข้อมูลเพื่อการวิจัยของโครงการ</a>@else<b style="font-weight:500">ข้อมูลเพื่อการวิจัยของโครงการ</b>@endif ตาม@if($consentDocs['privacy'])<a href="#" data-consent-doc="privacy">นโยบายคุ้มครองข้อมูลส่วนบุคคล</a>@else<b style="font-weight:500">นโยบายคุ้มครองข้อมูลส่วนบุคคล</b>@endif <b>*</b></span>
            </label>

            <button type="submit" class="tr-primary-button tr-form-submit">ลงทะเบียน</button>
        </form>

        {{-- popup ใบเดียวใช้ทั้งสองฉบับ JS เปลี่ยนหัวและเนื้อหาตามลิงก์ที่กด
             ใช้ <dialog> ชุดเดียวกับ popup เชื่อม LINE ในหน้าเข้าสู่ระบบ --}}
        @if($consentDocs['cohort'] || $consentDocs['privacy'])
            <dialog class="tr-dialog" id="tr-consent-dialog">
                <div class="tr-dialog-body">
                    <h2 class="tr-dialog-title" id="tr-consent-title"></h2>
                    <p class="tr-dialog-text" id="tr-consent-version"></p>

                    <div class="tr-consent-doc" id="tr-consent-content"></div>

                    <div class="tr-dialog-actions">
                        <button type="button" class="tr-primary-button" id="tr-consent-close">ปิด</button>
                    </div>
                </div>
            </dialog>
        @endif

        <p class="tr-note">
            เคยลงทะเบียนแล้ว?
            <a href="{{ route('public.tracking-round-qr') }}">เข้าสู่ระบบด้วยเบอร์โทร</a>
        </p>
    </section>
@endsection

@push('page-script')
    @if($consentDocs['cohort'] || $consentDocs['privacy'])
        <script>
        (function () {
            /* เนื้อหามาจาก master data ฝั่งแอดมิน — ใส่เป็น textContent ไม่ใช่ innerHTML
               เพราะเป็นข้อความที่คนพิมพ์เข้ามา ไม่ใช่มาร์กอัปที่ระบบสร้าง */
            var docs = @json($consentDocs);
            var dialog = document.getElementById('tr-consent-dialog');
            var title = document.getElementById('tr-consent-title');
            var version = document.getElementById('tr-consent-version');
            var content = document.getElementById('tr-consent-content');

            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-consent-doc]');
                if (!link) return;

                event.preventDefault();

                var doc = docs[link.dataset.consentDoc];
                if (!doc) return;

                title.textContent = doc.title;
                version.textContent = doc.version ? 'ฉบับ ' + doc.version : '';
                version.hidden = !doc.version;
                content.textContent = doc.content || 'ยังไม่มีเนื้อหาในเอกสารฉบับนี้';
                dialog.showModal();
                content.scrollTop = 0;
            });

            document.getElementById('tr-consent-close').addEventListener('click', function () {
                dialog.close();
            });

            /* กดพื้นหลังนอกกล่องแล้วปิด — ปุ่มปิดอยู่ล่างสุดของเนื้อหาที่ยาว ต้องมีทางออกที่มือถึงเสมอ */
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) dialog.close();
            });
        })();
        </script>
    @endif
@endpush
