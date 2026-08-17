@extends('public.activities.layout')

@section('title', 'ส่งแบบประเมินแล้ว')

@section('content')
    <section class="detail-card tr-card">
        <div class="tr-done">
            <span class="tr-done-mark" aria-hidden="true"><i></i></span>

            <h2>ส่งแบบประเมินแล้ว</h2>

            <p class="tr-done-thanks">ขอบคุณในการร่วมตอบแบบสอบถาม</p>

            {{-- หลักฐานการส่ง — ผู้ตอบต้องอ้างอิงได้ว่าตอบรอบไหน เมื่อไร โดยไม่ต้องถามเจ้าหน้าที่ --}}
            <dl class="tr-done-receipt">
                <div>
                    <dt>รอบ</dt>
                    <dd>{{ $round->name }}</dd>
                </div>
                <div>
                    <dt>วันที่ส่ง</dt>
                    <dd>@if($submittedAt)@thaidate($submittedAt) · {{ $submittedAt->format('H:i') }} น.@else—@endif</dd>
                </div>
                @if($proxyFor)
                    {{-- กรอกแทนต้องเห็นชัดบนใบยืนยันว่าคำตอบลงในนามใคร ไม่งั้นเผลอคิดว่าตอบของตัวเอง --}}
                    <div>
                        <dt>ตอบในนามของ</dt>
                        <dd>{{ $proxyFor->name }}</dd>
                    </div>
                @endif
            </dl>

            {{-- กลับแดชบอร์ดของระบบติดตาม ไม่ใช่หน้าหลักของเว็บ — คนที่ตอบรอบนี้ยังมีรอบอื่น
                 และประวัติของตัวเองให้ดูต่อ ซึ่งอยู่ในแดชบอร์ดเท่านั้น --}}
            <a class="tr-primary-button" href="{{ route('public.tracking-round-qr.dashboard') }}">กลับหน้าหลัก</a>
        </div>
    </section>
@endsection
