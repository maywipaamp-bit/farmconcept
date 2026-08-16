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
            </dl>

            {{-- ข้อความแปรตามบริบท: กรอกแทน / ยังมีรอบให้ทำต่อ / ทำครบแล้ว
                 ผู้ตอบต้องรู้ว่าต้องทำอะไรต่อ ไม่ใช่แค่ "ขอบคุณ" แล้วจบ --}}
            <p>
                @if($proxyFor)
                    บันทึกคำตอบ{{ $round->name }}ของ {{ $proxyFor->name }} แล้ว
                    ระบบระบุว่าคุณเป็นผู้กรอกแทน
                @else
                    บันทึกคำตอบ{{ $round->name }}เรียบร้อยแล้ว
                    @if($remaining > 0)
                        · คุณยังมีอีก {{ $remaining }} รอบที่ถึงกำหนด
                    @elseif($lineLinked)
                        · รอบถัดไปจะแจ้งเตือนผ่าน LINE
                    @else
                        · เชื่อม LINE ไว้เพื่อรับแจ้งเตือนรอบถัดไปได้
                    @endif
                @endif
            </p>

            <div class="tr-done-stats">
                <div>
                    <span class="tr-done-number">{{ $answeredQuestions }}</span>
                    <span class="tr-done-label">ข้อที่ตอบ</span>
                </div>
                <div>
                    {{-- ตัวเลขเดียวไม่บอกว่าเหลืออีกเท่าไร ใช้รูปแบบเดียวกับแดชบอร์ดให้เทียบกันได้ --}}
                    <span class="tr-done-number">{{ $answeredRounds }}/{{ $totalRounds }}</span>
                    <span class="tr-done-label">รอบที่ทำแล้ว</span>
                </div>
            </div>

            @if($remaining > 0)
                <a class="tr-primary-button" href="{{ route('public.tracking-round-qr.rounds') }}">ทำรอบถัดไป</a>
            @else
                <a class="tr-primary-button" href="{{ route('public.tracking-round-qr.dashboard') }}">กลับหน้าหลัก</a>
            @endif
        </div>
    </section>
@endsection
