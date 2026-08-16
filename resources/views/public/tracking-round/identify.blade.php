@extends('public.activities.layout')

@section('title', 'เข้าสู่ระบบประเมินสุขภาวะ')

@section('content')
    <section class="detail-card tr-card">
        <h1 class="tr-login-title">เข้าสู่ระบบประเมินสุขภาวะ</h1>

        {{-- กลับมาจาก LINE แต่ยังไม่มีใครผูกบัญชีนี้ — เด้ง popup ให้กรอกเบอร์ทันที
             การผูก LINE คือการให้สิทธิ์เข้าถึงข้อมูลสุขภาพของคนนั้นตลอดไป จะข้ามขั้นยืนยันไม่ได้ --}}
        @if(session('linkLine'))
            <dialog class="tr-dialog" id="tr-link-dialog">
                <form method="POST" action="{{ route('public.tracking-round-qr.verify') }}" class="tr-dialog-body">
                    @csrf

                    <h2 class="tr-dialog-title">เชื่อม LINE กับบัญชีของคุณ</h2>
                    <p class="tr-dialog-text">
                        เข้าด้วย LINE สำเร็จแล้ว · กรอกเบอร์โทรที่ลงทะเบียนไว้เพื่อเชื่อมบัญชี
                        ถ้ายังไม่เคยลงทะเบียน ระบบจะพาไปลงทะเบียนแล้วเชื่อม LINE ให้อัตโนมัติ
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

            <ul class="tr-trust-list">
                @foreach($assurances as $line)
                    <li><span class="tr-trust-check" aria-hidden="true"></span>{{ $line }}</li>
                @endforeach
            </ul>

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

            <div class="registration-field">
                <label for="tr-phone">เบอร์โทรศัพท์ <span>*</span></label>
                <input type="tel" id="tr-phone" name="phone" inputmode="numeric" autocomplete="tel"
                       placeholder="08x-xxx-xxxx" value="{{ old('phone') }}"
                       maxlength="10" pattern="[0-9]{10}" required>
                @error('phone')<span class="registration-message is-error">{{ $message }}</span>@enderror
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
            <p class="tr-hint-center">เคยเชื่อม LINE ไว้แล้ว เข้าได้เลยไม่ต้องกรอกเบอร์</p>
        @endif

        <p class="tr-note">
            ยังไม่มีบัญชี
            <a href="{{ route('public.tracking-round-qr.register') }}">ลงทะเบียน</a>
        </p>
    </section>
@endsection
