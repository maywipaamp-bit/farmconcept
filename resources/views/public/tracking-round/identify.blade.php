@extends('public.activities.layout')

@section('title', 'เข้าสู่ระบบประเมินสุขภาวะ')

@section('content')
    <section class="detail-card tr-card">
        <h1 class="tr-login-title">เข้าสู่ระบบประเมินสุขภาวะ</h1>

        {{-- กลับมาจาก LINE แต่ยังไม่มีใครผูกบัญชีนี้ — เด้ง popup ให้กรอกเบอร์ทันที
             การผูก LINE คือการให้สิทธิ์เข้าถึงข้อมูลสุขภาพของคนนั้นตลอดไป จะข้ามขั้นยืนยันไม่ได้ --}}
        {{-- เชื่อม LINE ไม่สำเร็จแล้วถูกพากลับมาหน้านี้ ต้องบอกเหตุผล
             ไม่งั้นผู้ใช้เห็นแค่หน้าเดิมกลับมาเฉย ๆ แล้วสรุปว่า "กดปุ่มแล้วไม่มีอะไรเกิดขึ้น"
             รายละเอียดเชิงเทคนิคอยู่ใน storage/logs (LineLoginService เขียน Log::warning ไว้ทุกขั้น) --}}
        @if(session('lineError'))
            <div class="tr-error" role="alert">{{ session('lineError') }}</div>
        @endif

        @if(session('linkLine'))
            <dialog class="tr-dialog" id="tr-link-dialog">
                <form method="POST" action="{{ route('public.tracking-round-qr.verify') }}" class="tr-dialog-body">
                    @csrf

                    <h2 class="tr-dialog-title">เชื่อม LINE กับบัญชีของคุณ</h2>
                    <p class="tr-dialog-text">
                        กรอกเบอร์โทรที่ลงทะเบียนไว้
                    </p>

                    <div class="registration-field">
                        <label for="tr-link-phone">เบอร์โทรศัพท์ <span>*</span></label>
                        <input type="tel" id="tr-link-phone" name="phone" inputmode="tel"
                               autocomplete="tel" placeholder="08x-xxx-xxxx" required>
                    </div>

                    <div class="tr-dialog-actions">
                        <button type="submit" class="tr-primary-button">เชื่อมบัญชี</button>
                        <button type="button" class="tr-ghost-button" onclick="this.closest('dialog').close()">ไว้ทีหลัง</button>
                    </div>
                </form>
            </dialog>

            @push('page-script')
                <script>document.getElementById('tr-link-dialog')?.showModal();</script>
            @endpush
        @endif

        {{-- รายละเอียดทั้งหมดซ่อนไว้หลังตัวกดสีเทา ไม่แย่งความสนใจจากปุ่มเข้าสู่ระบบ
             แต่ต้องอยู่ในหน้านี้เสมอ เพราะเป็นข้อมูลที่ต้องบอกก่อนขอข้อมูลส่วนบุคคล --}}
        <details class="tr-about">
            <summary>เกี่ยวกับโครงการและการใช้ข้อมูล</summary>

            <p class="tr-about-project">{{ $projectName }}</p>

            {{-- รายการติ๊กถูกสามข้อถูกตัดออก — เนื้อความซ้ำกับคำชี้แจงด้านล่างอยู่แล้ว
                 พูดเรื่องเดียวกันสองรอบทำให้คนอ่านข้ามทั้งสองอัน --}}
            <ol class="tr-disclosure-list">
                @foreach($disclosures as $paragraph)
                    <li>{{ $paragraph }}</li>
                @endforeach
            </ol>
        </details>

        {{-- เบอร์โทรมาก่อน LINE โดยตั้งใจ

             คนที่ยังไม่เคยเชื่อม LINE (ซึ่งคือทุกคนในครั้งแรก) กดปุ่ม LINE แล้วจะถูกพากลับมา
             กรอกเบอร์อยู่ดี — LINE จึงเป็นทางอ้อมที่เพิ่มสองขั้นสำหรับผู้ใช้ใหม่ทุกคน
             ประโยชน์ของ LINE เริ่มตั้งแต่ครั้งที่สองเป็นต้นไป จึงวางเป็นทางเลือกรอง --}}
        <form method="POST" action="{{ route('public.tracking-round-qr.verify') }}" novalidate>
            @csrf

            {{-- ช่องเดียวรับได้ทั้งเบอร์และอีเมล — อีเมลเป็นทางเลือกสำหรับคนที่ไม่สะดวกใช้เบอร์
                 ห้ามใส่ inputmode="numeric" / maxlength="10" / pattern ตัวเลขล้วนกลับมา
                 สามอย่างนั้นบล็อกการพิมพ์อีเมลตั้งแต่แป้นพิมพ์ ทำให้ทางเลือกนี้ใช้ไม่ได้จริง --}}
            <div class="registration-field">
                <label for="tr-phone">เบอร์โทรศัพท์หรืออีเมล <span>*</span></label>
                <input type="text" id="tr-phone" name="phone" inputmode="email" autocomplete="username"
                       placeholder="08x-xxx-xxxx หรือ name@email.com" value="{{ old('phone') }}"
                       maxlength="160" required>
                @error('phone')<span class="registration-message is-error">{{ $message }}</span>@enderror
            </div>

            {{-- รหัสบุคคลอยู่หน้าเดียวกับเบอร์ ไม่ต้องกดผ่านสองจอ
                 เบอร์เดียวใช้กันทั้งบ้านเป็นเรื่องปกติ รหัสจึงเป็นตัวชี้ว่ากำลังตอบในนามใคร
                 เว้นว่างได้ ระบบจะพาไปถามที่หน้าถัดไปแทน สำหรับคนที่จำรหัสไม่ได้ตอนนั้น --}}
            <div class="registration-field">
                <label for="tr-person-code">รหัสบุคคล <span>*</span></label>
                <input type="text" id="tr-person-code" name="person_code" autocomplete="off"
                       placeholder="เช่น P0001" value="{{ old('person_code') }}" maxlength="6">
                @error('person_code')<span class="registration-message is-error">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="tr-primary-button">เข้าสู่ระบบ</button>
        </form>

        @if($lineEnabled)
            <p class="tr-or"><span>หรือ</span></p>

            {{-- ผูกครั้งเดียวแล้วครั้งต่อไปกดปุ่มเดียวเข้าได้เลย ไม่ต้องกรอกเบอร์อีก
                 และยังเป็นช่องทางรับแจ้งเตือนรอบถัดไปด้วย --}}
            <a class="tr-line-outline" href="{{ route('public.tracking-round-qr.line') }}">
                <svg class="tr-line-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2.5c5.24 0 9.5 3.46 9.5 7.72 0 1.54-.6 2.94-1.7 4.2-1.6 1.85-4.3 4.1-5.9 5.06-1.05.63-.94-.28-.9-.55l.15-.9c.04-.28.07-.7-.04-.97-.12-.3-.6-.46-.95-.53-4.7-.62-8.16-3.9-8.16-7.31C4 5.96 8.26 2.5 12 2.5Zm-2.9 5.6a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h.62c.16 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3H9.1Zm-2.6 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h2.02c.17 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3H7.42V8.4a.3.3 0 0 0-.3-.3H6.5Zm4.72 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h.62c.17 0 .3-.13.3-.3v-2.1l1.72 2.33.05.05h.02l.03.02h.66c.17 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3h-.62a.3.3 0 0 0-.3.3v2.1L13.3 8.24l-.04-.05h-.03l-.02-.02h-.66l-.02-.01h-.03l-.02-.01h-.62Zm5.3 0a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h2.03c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.14.3-.3V8.4a.3.3 0 0 0-.3-.3h-2.03Z"/>
                </svg>
                เข้าสู่ระบบด้วย LINE
            </a>
            {{-- เปิดจากกล้องของ iPhone จะได้เบราว์เซอร์ในแอปกล้อง ซึ่ง LINE สลับไปแอป LINE ไม่ได้
                 แล้วขึ้น "ไม่สามารถเข้าสู่ระบบได้" ที่หน้าของ LINE เอง เราแก้ฝั่งนั้นไม่ได้
                 ทางออกคือย้ายไปเปิดใน Safari ก่อน แล้วค่อยกดเชื่อม LINE

                 popup นี้เปิดตอนกดปุ่ม LINE ไม่ใช่ตอนเข้าหน้า — คนที่เข้ามาแล้วจะกรอกเบอร์อยู่แล้ว
                 ไม่ควรโดนกล่องมาบังตั้งแต่ยังไม่ได้ทำอะไร เตือนตอนที่กำลังจะเจอปัญหาจริงพอ --}}
            <dialog class="tr-dialog" id="tr-inapp">
                <div class="tr-dialog-body">
                    <h2 class="tr-dialog-title">เปิดใน Safari</h2>

                    <div class="tr-dialog-actions">
                        <button type="button" class="tr-ghost-button"
                                onclick="this.closest('dialog').close()">ปิด</button>
                        <a class="tr-primary-button" id="tr-open-safari" href="#">เปิดใน Safari</a>
                    </div>
                </div>
            </dialog>
        @endif

        <p class="tr-note">
            ยังไม่มีบัญชี
            <a href="{{ route('public.tracking-round-qr.register') }}">ลงทะเบียน</a>
        </p>
    </section>
@endsection

@push('page-script')
<script>
/* เบราว์เซอร์ในแอป (กล้อง iPhone, Facebook, IG ฯลฯ) เปิด LINE Login ไม่ผ่าน
   เพราะสลับไปแอป LINE ไม่ได้ ทางออกเดียวคือย้ายไปเปิดใน Safari ก่อน

   ตรวจแบบ "ไม่ใช่ Safari เต็มตัวบน iOS" แทนการไล่รายชื่อแอป
   เพราะรายชื่อแอปที่มีเบราว์เซอร์ในตัวเพิ่มขึ้นเรื่อย ๆ ตามไม่ไหว */
(function () {
    var dialog = document.getElementById('tr-inapp');
    var lineButton = document.querySelector('.tr-line-outline');

    if (!dialog || !lineButton) return;

    var ua = navigator.userAgent || '';
    var isIos = /iPad|iPhone|iPod/.test(ua);
    /* Safari เต็มตัวบน iOS มี "Safari/" ใน UA ส่วนเบราว์เซอร์ในแอปไม่มี
       Chrome/Firefox/Edge บน iOS มีคำระบุของตัวเอง และเปิด LINE ได้ปกติ จึงไม่ต้องเตือน */
    var isRealBrowser = /Safari\//.test(ua) || /CriOS|FxiOS|EdgiOS/.test(ua);

    if (!isIos || isRealBrowser) return;

    /* x-safari-https:// เป็นสคีมที่ iOS ใช้เด้งออกไป Safari จากเบราว์เซอร์ในแอป
       ไม่ได้ผลทุกแอป จึงต้องมีวิธีกดปุ่มแชร์บอกไว้เป็นทางสำรองเสมอ */
    document.getElementById('tr-open-safari').href = 'x-safari-' + window.location.href;

    lineButton.addEventListener('click', function (event) {
        event.preventDefault();
        dialog.showModal();
    });
})();
</script>
@endpush
