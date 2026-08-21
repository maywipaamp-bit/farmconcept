@extends('public.activities.layout')

@section('title', 'หน้าหลักผู้เข้าร่วม')

@section('content')
    <section class="detail-card tr-card">
        {{-- ยังกรอกแทนคนอื่นค้างอยู่ ต้องเห็นตั้งแต่หน้าแรก

             หน้านี้แสดงรอบของ "ผู้กรอก" ไม่ใช่ของผู้ถูกประเมิน ถ้าไม่มีป้ายบอก ผู้กรอกจะนึกว่า
             เลิกกรอกแทนไปแล้ว กด "เริ่มทำ" ที่ไทม์ไลน์ด้านล่างแล้วเจอ 404 โดยไม่รู้สาเหตุ --}}
        @includeWhen($proxyFor, 'public.tracking-round.partials.proxy-banner', ['proxyFor' => $proxyFor])

        @if($proxyFor)
            <p class="tr-notice" role="status">
                ไทม์ไลน์ด้านล่างเป็นรอบของคุณเอง ไม่ใช่ของ {{ $proxyFor->name }} —
                <a href="{{ route('public.tracking-round-qr.rounds') }}">ไปที่รอบของ {{ $proxyFor->name }}</a>
            </p>
        @endif

        {{-- แถวผู้ใช้ + เมนู — ใช้ details/summary จะได้ไม่ต้องมี JS สำหรับ dropdown --}}
        <div class="tr-user-row">
            <details class="tr-user-menu">
                <summary>
                    {{-- ไม่แสดงชื่อ — หน้านี้เปิดในที่สาธารณะได้ (สแกน QR หน้างาน)
                         แสดงรหัสบุคคลเพราะเป็นรหัสเดียวกับที่ใช้เข้าระบบ เห็นทุกครั้งจะได้จำได้ --}}
                    <span class="tr-user-text">
                        <span class="tr-user-hello">สวัสดี</span>
                        <span class="tr-user-name">{{ $participant->person_code }}</span>
                    </span>
                    <span class="tr-user-chevron" aria-hidden="true"></span>
                </summary>

                <div class="tr-user-dropdown">
                    <a class="tr-user-item" href="{{ route('public.tracking-round-qr.proxy') }}">
                        ทำแทนคนอื่น
                        <small>ยืนยันตัวตนผู้ถูกประเมินก่อน</small>
                    </a>
                    <form method="POST" action="{{ route('public.tracking-round-qr.sign-out') }}">
                        @csrf
                        <button type="submit" class="tr-user-item is-danger">ออกจากระบบ</button>
                    </form>
                </div>
            </details>

            {{-- รหัสอยู่ในคำทักทายด้านบนแล้ว ป้ายนี้เหลือแค่บอกบทบาท --}}
            <span class="tr-user-chip">กลุ่มตัวอย่าง</span>
        </div>

        {{-- บอกรหัสครั้งแรกครั้งเดียวหลังลงทะเบียน — รหัสนี้คือกุญแจเข้าระบบ ลืมแล้วต้องติดต่อเจ้าหน้าที่ --}}
        @if(session('justRegistered'))
            <div class="tr-notice is-success" role="status">
                ลงทะเบียนเรียบร้อย · รหัสบุคคลของคุณคือ <b>{{ session('justRegistered') }}</b>
                — ใช้รหัสนี้คู่กับเบอร์โทรตอนเข้าระบบครั้งถัดไป
            </div>
        @endif

        {{-- เชื่อม LINE ไม่สำเร็จแล้วถูกพากลับมาที่แดชบอร์ด (คนที่ยืนยันตัวตนไว้แล้ว)
             ต้องบอกเหตุผลเหมือนกัน ไม่งั้นสวิตช์แจ้งเตือนก็ยังปิดอยู่โดยไม่รู้ว่าทำไม --}}
        @if(session('lineError'))
            <div class="tr-notice" role="alert">{{ session('lineError') }}</div>
        @endif

        {{-- บัญชี LINE ที่เพิ่งล็อกอินเป็นของกลุ่มตัวอย่างอีกคน — ต้องถามก่อนสลับ
             สลับให้เงียบ ๆ แปลว่าคำตอบรอบถัดไปจะไปลงระเบียนผิดคนโดยไม่มีใครรู้ --}}
        @if($switchTo)
            <div class="tr-notice tr-switch" role="alert">
                <p class="tr-switch-text">
                    บัญชี LINE ที่คุณเพิ่งเข้าสู่ระบบ ผูกอยู่กับรหัสบุคคล <b>{{ $switchTo }}</b>
                    ซึ่งไม่ใช่บัญชีที่คุณใช้อยู่ตอนนี้ ({{ $participant->person_code }})
                </p>
                <form method="POST" action="{{ route('public.tracking-round-qr.switch') }}" class="tr-switch-actions">
                    @csrf
                    <button type="submit" name="confirm" value="0" class="tr-ghost-button">ใช้บัญชีเดิมต่อ</button>
                    <button type="submit" name="confirm" value="1" class="tr-primary-button">สลับไปใช้ {{ $switchTo }}</button>
                </form>
            </div>
        @endif

        @if(session('lineLinked'))
            <div class="tr-notice is-success" role="status">เชื่อม LINE เรียบร้อยแล้ว · จะได้รับแจ้งเตือนรอบถัดไปทาง LINE</div>
        @endif

        @if(session('lineConflict'))
            <div class="tr-notice" role="status">บัญชี LINE นี้ถูกผูกกับผู้ใช้อื่นไว้แล้ว จึงเชื่อมให้ไม่ได้ — กรุณาติดต่อเจ้าหน้าที่</div>
        @endif

        {{-- ยังไม่ได้เชื่อม LINE — ชวนตรงนี้ ก่อนไทม์ไลน์

             เดิมมีแต่สวิตช์ล่างสุดที่เขียนว่า "แจ้งเตือนรอบถัดไปผ่าน LINE" ซึ่งไม่ได้บอกประโยชน์
             ข้อที่คนสนใจจริง คือเชื่อมแล้วไม่ต้องจำอะไรตอนเข้าครั้งหน้า
             คนที่เพิ่งลงทะเบียนเสร็จจึงไม่มีเหตุผลให้กด แล้วรอบหน้าก็กลับมาติดที่หน้าเข้าสู่ระบบอีก --}}
        @if(blank($participant->line_user_id) && $lineEnabled)
            <div class="tr-line-invite">
                <span class="tr-line-invite-title">เชื่อม LINE ครั้งเดียว ครั้งหน้าเข้าได้เลย</span>
                <span class="tr-line-invite-text">
                    ไม่ต้องกรอกเบอร์หรือรหัสอีก และได้รับแจ้งเตือนเมื่อถึงรอบถัดไป
                </span>
                <a class="tr-line-outline" href="{{ route('public.tracking-round-qr.line') }}">
                    <svg class="tr-line-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2.5c5.24 0 9.5 3.46 9.5 7.72 0 1.54-.6 2.94-1.7 4.2-1.6 1.85-4.3 4.1-5.9 5.06-1.05.63-.94-.28-.9-.55l.15-.9c.04-.28.07-.7-.04-.97-.12-.3-.6-.46-.95-.53-4.7-.62-8.16-3.9-8.16-7.31C4 5.96 8.26 2.5 12 2.5Zm-2.9 5.6a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h.62c.16 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3H9.1Zm-2.6 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h2.02c.17 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3H7.42V8.4a.3.3 0 0 0-.3-.3H6.5Zm4.72 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h.62c.17 0 .3-.13.3-.3v-2.1l1.72 2.33.05.05h.02l.03.02h.66c.17 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3h-.62a.3.3 0 0 0-.3.3v2.1L13.3 8.24l-.04-.05h-.03l-.02-.02h-.66l-.02-.01h-.03l-.02-.01h-.62Zm5.3 0a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h2.03c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.14.3-.3V8.4a.3.3 0 0 0-.3-.3h-2.03Z"/>
                    </svg>
                    เชื่อมบัญชี LINE
                </a>
            </div>
        @endif

        {{-- ไทม์ไลน์เต็มชุดมาอยู่บนหน้าหลักเลย แทนการ์ด "รอบที่ถึงกำหนด" กับกล่องตัวเลข
             การ์ดเดิมบอกแค่รอบเดียวแล้วต้องกดไปอีกหน้าเพื่อดูภาพรวม ทั้งที่เนื้อหาทั้งหมดสั้นพอ
             จะแสดงในที่เดียว — เห็นครบว่าอยู่ตรงไหนของโครงการ และกดทำรอบที่ถึงคิวได้จากที่เดียวกัน --}}
        <p class="tr-subheading">
            รอบแบบประเมิน · ทำได้ทีละรอบตามลำดับ ·
            {{ $rounds->whereNotNull('answered_at')->count() }}/{{ $rounds->count() }} รอบ
        </p>

        @include('public.tracking-round.partials.timeline')

        {{-- สวิตช์แจ้งเตือน — เป็นค่าของแต่ละคน เพราะเป็นการยินยอมรับข้อความส่วนบุคคล
             ยังไม่เชื่อม LINE: สวิตช์แสดงเป็นปิด กดแล้วพาไปเชื่อม LINE เลย (จัดการที่ toggleNotify)
             ไม่มีปุ่มเชื่อมแยก — สวิตช์คือทางเข้าเดียว --}}
        @php($linked = filled($participant->line_user_id))
        <form method="POST" action="{{ route('public.tracking-round-qr.notify') }}" class="tr-row-form">
            @csrf
            <button type="submit" class="tr-toggle-row"
                    aria-pressed="{{ $linked && $participant->line_notify ? 'true' : 'false' }}">
                <span class="tr-info-text">แจ้งเตือนรอบถัดไปผ่าน LINE</span>
                <span class="tr-toggle{{ $linked && $participant->line_notify ? ' is-on' : '' }}" aria-hidden="true"><i></i></span>
            </button>
        </form>
    </section>
@endsection
