@extends('public.activities.layout')

@section('title', 'เข้าสู่ระบบประเมินสุขภาวะ')

@section('content')
    {{-- เปิดจากในแอป LINE (LIFF) — ระบบรู้ว่าใครเป็นใครเองโดยไม่ต้องกดอะไร
         คลุมทับหน้าไว้ระหว่างตรวจ ไม่งั้นผู้ใช้จะเห็นฟอร์มกรอกเบอร์แวบหนึ่งแล้วหน้าเด้งหนี
         ซ่อนไว้ก่อน สคริปต์เปิดเฉพาะตอนที่อยู่ในแอป LINE จริง --}}
    @if($liffAuto)
        <div class="tr-liff-cover" id="tr-liff-cover" hidden>
            <div class="tr-liff-box">
                <span class="tr-liff-spinner" aria-hidden="true"></span>
                <p class="tr-liff-text">{{ $linking ? 'กำลังเชื่อมบัญชี LINE…' : 'กำลังเข้าสู่ระบบด้วย LINE…' }}</p>
            </div>
        </div>
    @endif

    <section class="detail-card tr-card">
        <h1 class="tr-login-title">{{ $linking ? 'เชื่อมบัญชี LINE' : 'เข้าสู่ระบบประเมินสุขภาวะ' }}</h1>

        @if($linking)
            <p class="tr-subheading">
                เชื่อมแล้วครั้งหน้ากดปุ่มเดียวเข้าได้เลย ไม่ต้องกรอกเบอร์หรือรหัสอีก
            </p>
        @endif

        {{-- กลับมาจาก LINE แต่ยังไม่มีใครผูกบัญชีนี้ — บอกด้วยแถบข้อความ ไม่ใช่ popup ครอบจอ
             การผูก LINE คือการให้สิทธิ์เข้าถึงข้อมูลสุขภาพของคนนั้นตลอดไป จะข้ามขั้นยืนยันไม่ได้
             แต่ตัวฟอร์มด้านล่างถามสิ่งเดียวกันเป๊ะอยู่แล้ว จึงไม่ต้องมีช่องกรอกซ้ำใน popup --}}
        {{-- เชื่อม LINE ไม่สำเร็จแล้วถูกพากลับมาหน้านี้ ต้องบอกเหตุผล
             ไม่งั้นผู้ใช้เห็นแค่หน้าเดิมกลับมาเฉย ๆ แล้วสรุปว่า "กดปุ่มแล้วไม่มีอะไรเกิดขึ้น"
             รายละเอียดเชิงเทคนิคอยู่ใน storage/logs (LineLoginService เขียน Log::warning ไว้ทุกขั้น) --}}
        @if(session('lineError'))
            <div class="tr-error" role="alert">{{ session('lineError') }}</div>
        @endif

        @if(session('linkLine'))
            <div class="tr-notice is-success" role="status">
                เชื่อม LINE ได้แล้ว เหลืออีกขั้นเดียว —
                กรอกเบอร์โทร อีเมล หรือรหัสบุคคลที่ลงทะเบียนไว้ด้านล่าง
                ระบบจะผูก LINE ให้กับบัญชีของคุณอัตโนมัติ
            </div>
        @endif

        {{-- บล็อก "เกี่ยวกับโครงการและการใช้ข้อมูล" ถูกตัดออกตามคำสั่ง
             เนื้อหาความยินยอมฉบับเต็มยังอ่านได้ที่หน้าลงทะเบียนกลุ่มตัวอย่าง (ลิงก์ท้ายหน้านี้)
             ซึ่งเป็นจุดที่ระบบขอความยินยอมจริง หน้านี้เป็นแค่การยืนยันตัวตนของคนที่ลงทะเบียนไว้แล้ว --}}

        {{-- เบอร์โทรมาก่อน LINE โดยตั้งใจ

             คนที่ยังไม่เคยเชื่อม LINE (ซึ่งคือทุกคนในครั้งแรก) กดปุ่ม LINE แล้วจะถูกพากลับมา
             กรอกเบอร์อยู่ดี — LINE จึงเป็นทางอ้อมที่เพิ่มสองขั้นสำหรับผู้ใช้ใหม่ทุกคน
             ประโยชน์ของ LINE เริ่มตั้งแต่ครั้งที่สองเป็นต้นไป จึงวางเป็นทางเลือกรอง --}}
        <form method="POST" action="{{ route('public.tracking-round-qr.verify') }}" novalidate @if($linking) hidden @endif>
            @csrf

            {{-- ช่องเดียวรับได้ทั้งสามอย่าง — เบอร์ · อีเมล · รหัสบุคคล กรอกอย่างใดอย่างหนึ่งพอ

                 เดิมบังคับกรอกเบอร์ + รหัสคู่กัน ซึ่งทำให้ผู้สูงอายุถอดใจตั้งแต่หน้าแรก
                 หน้านี้เข้าถึงได้เฉพาะคนที่ได้รับลิงก์/QR ของโครงการอยู่แล้ว ด่านที่สอง
                 จึงกันคนได้น้อยกว่าที่ทำให้คนที่ควรตอบเลิกตอบ (ทีมตัดสินใจ)

                 ห้ามใส่ inputmode="numeric" / maxlength="10" / pattern ตัวเลขล้วนกลับมา
                 สามอย่างนั้นบล็อกการพิมพ์อีเมลกับรหัสบุคคลตั้งแต่แป้นพิมพ์ --}}
            <div class="registration-field">
                <label for="tr-phone">เบอร์โทรศัพท์ อีเมล หรือรหัสบุคคล <span>*</span></label>
                <input type="text" id="tr-phone" name="phone" inputmode="email" autocomplete="username"
                       placeholder="08x-xxx-xxxx · name@email.com · P0001" value="{{ old('phone') }}"
                       maxlength="160" autofocus required>
                @error('phone')<span class="registration-message is-error">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="tr-primary-button">เข้าสู่ระบบ</button>
        </form>

        @if($lineEnabled)
            @unless($linking)<p class="tr-or"><span>หรือ</span></p>@endunless

            {{-- ผูกครั้งเดียวแล้วครั้งต่อไปกดปุ่มเดียวเข้าได้เลย ไม่ต้องกรอกเบอร์อีก
                 และยังเป็นช่องทางรับแจ้งเตือนรอบถัดไปด้วย --}}
            {{-- ปลายทางเป็นลิงก์ LIFF เมื่อตั้งค่าไว้ ไม่ใช่ OAuth

                 OAuth ต้องสลับแอปไป-กลับ ซึ่งเบราว์เซอร์ในแอปบน iPhone ทำไม่ได้ กดแล้วเงียบ
                 ลิงก์ LIFF เป็น universal link เปิดแอป LINE ตรง ๆ จบในแอปเดียว
                 ไม่ได้ตั้ง LIFF ไว้ค่อยตกไปใช้ OAuth ตามเดิม (ดู lineButtonUrl ฝั่งคอนโทรลเลอร์) --}}
            <a class="tr-line-outline" href="{{ $lineButtonUrl }}">
                <svg class="tr-line-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2.5c5.24 0 9.5 3.46 9.5 7.72 0 1.54-.6 2.94-1.7 4.2-1.6 1.85-4.3 4.1-5.9 5.06-1.05.63-.94-.28-.9-.55l.15-.9c.04-.28.07-.7-.04-.97-.12-.3-.6-.46-.95-.53-4.7-.62-8.16-3.9-8.16-7.31C4 5.96 8.26 2.5 12 2.5Zm-2.9 5.6a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h.62c.16 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3H9.1Zm-2.6 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h2.02c.17 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3H7.42V8.4a.3.3 0 0 0-.3-.3H6.5Zm4.72 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h.62c.17 0 .3-.13.3-.3v-2.1l1.72 2.33.05.05h.02l.03.02h.66c.17 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3h-.62a.3.3 0 0 0-.3.3v2.1L13.3 8.24l-.04-.05h-.03l-.02-.02h-.66l-.02-.01h-.03l-.02-.01h-.62Zm5.3 0a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h2.03c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.14.3-.3V8.4a.3.3 0 0 0-.3-.3h-2.03Z"/>
                </svg>
                {{ $linking ? 'เชื่อมบัญชี LINE' : 'เข้าสู่ระบบด้วย LINE' }}
            </a>
            {{-- ไม่ได้ตั้ง LIFF ไว้ = ปุ่มด้านบนยังเป็น OAuth ซึ่งเบราว์เซอร์ในแอปบน iPhone ใช้ไม่ได้
                 เฉพาะกรณีนั้นถึงจะมีกล่องชวนให้ย้ายไปเปิดใน Safari ก่อน
                 ตั้ง LIFF ไว้แล้วห้ามมีกล่องนี้ — มันดักการกดแล้วไม่ยอมให้ลิงก์ LIFF ทำงาน
                 กลายเป็นตัวขวางเสียเอง (อาการ "กดแล้วไม่วิ่งไป LINE") --}}
            @unless($lineIsLiff)
                <dialog class="tr-dialog" id="tr-inapp">
                    <div class="tr-dialog-body">
                        <h2 class="tr-dialog-title">เปิดใน Safari ก่อน</h2>
                        <p class="tr-dialog-text">
                            หน้านี้ถูกเปิดอยู่ในเบราว์เซอร์ของแอปอื่น ซึ่งสลับไปแอป LINE ไม่ได้
                            กดปุ่มด้านล่างเพื่อเปิดใน Safari แล้วค่อยกดปุ่ม LINE อีกครั้ง
                            ถ้าปุ่มไม่ทำงาน ให้กดปุ่มแชร์แล้วเลือก "เปิดใน Safari"
                        </p>

                        <div class="tr-dialog-actions">
                            <button type="button" class="tr-ghost-button"
                                    onclick="this.closest('dialog').close()">ปิด</button>
                            <a class="tr-primary-button" id="tr-open-safari" href="#">เปิดใน Safari</a>
                        </div>
                    </div>
                </dialog>
            @endunless
        @endif

        @if($linking)
            <p class="tr-note">
                <a href="{{ route('public.tracking-round-qr.dashboard') }}">ไว้ทีหลัง กลับหน้าหลัก</a>
            </p>
        @else
            <p class="tr-note">
                ยังไม่มีบัญชี
                <a href="{{ route('public.tracking-round-qr.register') }}">ลงทะเบียน</a>
            </p>
        @endif
    </section>
@endsection

@push('page-script')
<script>
/* เบราว์เซอร์ในแอป (กล้อง iPhone, Facebook, IG ฯลฯ) เปิด LINE Login ไม่ผ่าน
   เพราะสลับไปแอป LINE ไม่ได้ ทางออกเดียวคือย้ายไปเปิดใน Safari ก่อน

   ทั้งบล็อกนี้ทำงานเฉพาะตอน "ไม่ได้ตั้ง LIFF" — ตัวกล่องถูกวางไว้ในเงื่อนไข lineIsLiff ด้านบน
   พอไม่มีกล่อง getElementById คืน null แล้วฟังก์ชันนี้ออกตั้งแต่บรรทัดแรก

   (ห้ามพิมพ์ชื่อ directive ของ Blade ลงในคอมเมนต์นี้ — Blade คอมไพล์ directive
    แม้อยู่ในคอมเมนต์ของ JS จะกลายเป็นเงื่อนไขจริงที่ไม่มีตัวปิด แล้วทั้งไฟล์พัง)
   ห้ามเอาเงื่อนไขนั้นออก: ตั้ง LIFF ไว้แล้วปุ่มเป็น universal link ที่เปิดแอป LINE ได้เอง
   การดักการกดตรงนี้จะกลายเป็นตัวขวาง ทำให้ "กดแล้วไม่วิ่งไป LINE"

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
        /* ห้ามยกเลิกการกดก่อนรู้ว่า popup ขึ้นจริง
           iOS ต่ำกว่า 15.4 ไม่มี <dialog> เลย ถ้า preventDefault ไปก่อนแล้ว showModal พัง
           ผู้ใช้จะกดปุ่มแล้วไม่มีอะไรเกิดขึ้น ซึ่งแย่กว่าปล่อยให้ไป LINE แล้วเจอ error ของ LINE */
        try {
            dialog.showModal();
        } catch (e) {
            return;
        }

        if (!dialog.open) return;

        event.preventDefault();
    });
})();
</script>
@endpush

@if($liffAuto)
@push('page-script')
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<script>
/* LIFF — เข้าสู่ระบบอัตโนมัติเมื่อหน้านี้ถูกเปิด "ในแอป LINE"

   ทำไมต้องมี: การเปิดหน้าเว็บในเบราว์เซอร์ของแอปอื่น (ตัวสแกน QR ของกล้องมือถือ) สลับไปแอป LINE
   เพื่อล็อกอินไม่ได้ ผู้ใช้จึงกดปุ่ม "เข้าสู่ระบบด้วย LINE" แล้วไปเจอหน้า error ของ LINE เอง
   LIFF ตัดขั้นตอนนั้นทิ้ง — หน้าถูกเปิดในแอป LINE อยู่แล้ว SDK จึงบอกตัวตนได้ทันที

   ทำเฉพาะตอนอยู่ในแอป LINE จริง (isInClient) — เปิดจากเบราว์เซอร์ปกติต้องไม่ถูกบังคับล็อกอิน
   เพราะทางเข้าด้วยเบอร์โทร + รหัสบุคคลยังต้องใช้ได้ตามเดิม */
(function () {
    var cover = document.getElementById('tr-liff-cover');
    var csrf = document.querySelector('meta[name="csrf-token"]');

    if (!cover || typeof liff === 'undefined') return;

    var settled = false;

    function fail(message) {
        /* ล้มเหลวแล้วต้องคืนหน้าเดิมให้ใช้งานได้ ไม่ใช่ค้างที่ "กำลังเข้าสู่ระบบ…" ตลอดไป
           ผู้ใช้ยังกรอกเบอร์ อีเมล หรือรหัสบุคคลเข้าได้เสมอ */
        settled = true;
        cover.hidden = true;
        if (message) window.alert(message);
    }

    /* กันหน้าค้างที่จอโหลด — SDK ของ LINE ค้างได้จริงเมื่อเน็ตช้าหรือถูกบล็อก
       แล้ว init() จะไม่ resolve และไม่ reject ทั้ง then และ catch จึงไม่มีวันถูกเรียก
       ผู้ใช้จะเห็นวงกลมหมุนอยู่อย่างนั้นโดยไม่มีทางออก
       ครบเวลาแล้วเปิดหน้าให้ใช้งานเงียบ ๆ ไม่ต้องขึ้น alert เพราะทางกรอกเองยังใช้ได้ปกติ */
    setTimeout(function () {
        if (! settled) fail('');
    }, 8000);

    liff.init({ liffId: @json($liffId) })
        .then(function () {
            if (!liff.isInClient()) { settled = true; return; }

            cover.hidden = false;

            /* ในแอป LINE ปกติจะล็อกอินอยู่แล้ว เผื่อกรณีที่ไม่ใช่ให้เรียก login() แล้วรอโหลดรอบใหม่ */
            if (!liff.isLoggedIn()) {
                liff.login();

                return;
            }

            var token = liff.getIDToken();

            if (!token) {
                fail('ขออนุญาตข้อมูลบัญชี LINE ไม่สำเร็จ กรุณากรอกเบอร์โทร อีเมล หรือรหัสบุคคลแทน');

                return;
            }

            return fetch(@json(route('public.tracking-round-qr.liff')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''
                },
                body: JSON.stringify({ id_token: token })
            })
                .then(function (res) { return res.json(); })
                .then(function (body) {
                    if (body && body.success && body.redirect) {
                        window.location.href = body.redirect;

                        return;
                    }

                    fail(body && body.message ? body.message : 'เข้าสู่ระบบด้วย LINE ไม่สำเร็จ');
                });
        })
        .catch(function () {
            fail('เชื่อมต่อ LINE ไม่สำเร็จ กรุณากรอกเบอร์โทร อีเมล หรือรหัสบุคคลแทน');
        });
})();
</script>
@endpush
@endif
