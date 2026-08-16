@extends('public.activities.layout')

@section('title', 'รอบแบบประเมิน')

@section('content')
    <section class="detail-card tr-card">
        @includeWhen($proxyFor, 'public.tracking-round.partials.proxy-banner', ['proxyFor' => $proxyFor])

        <h1 class="tr-login-title">รอบแบบประเมิน</h1>
        <p class="tr-subheading">
            ทำได้เฉพาะรอบที่ถึงกำหนด · {{ $rounds->whereNotNull('answered_at')->count() }}/{{ $rounds->count() }} รอบ
        </p>

        {{-- ไทม์ไลน์แนวตั้ง — เห็นได้ทันทีว่าอยู่ตรงไหนของโครงการ เหลืออีกกี่รอบ
             รอบที่ยังไม่เปิดต้องเห็นว่ามีอยู่ ไม่ใช่ซ่อนจนคิดว่าตกหล่น --}}
        <ol class="tr-timeline">
            @foreach($rounds as $round)
                @php($answered = $round->answered_at !== null)
                @php($open = in_array($round->id, $openIds, true))

                <li class="tr-tl {{ $answered ? 'is-done' : ($open ? 'is-open' : 'is-locked') }}">
                    <span class="tr-tl-dot" aria-hidden="true">{{ $answered ? '✓' : $loop->iteration }}</span>

                    <div class="tr-tl-body">
                        <p class="tr-tl-head">
                            <span class="tr-tl-order">รอบที่ {{ $loop->iteration }}</span>
                            @if($answered)
                                <span class="tr-tl-badge is-done">ทำแล้ว</span>
                            @elseif($open)
                                <span class="tr-tl-badge is-open">ถึงกำหนด</span>
                            @else
                                <span class="tr-tl-badge">ยังไม่เปิด</span>
                            @endif
                        </p>

                        <p class="tr-tl-name">{{ $round->name }}</p>

                        <p class="tr-tl-date">
                            @if($answered)
                                ตอบเมื่อ @thaidate($round->answered_at)
                            @elseif($open)
                                ถึงกำหนด · ใช้เวลา 5 นาที
                            @else
                                เปิด @thaidate($round->windowStart())
                            @endif
                        </p>

                        @if($open)
                            <a class="tr-tl-action"
                               href="{{ route('public.tracking-round-qr.survey', $round->id) }}">เริ่มทำ</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>

        @if($rounds->isEmpty())
            <p class="tr-subheading">ยังไม่มีรอบติดตามสำหรับคุณ</p>
        @endif

        <p class="tr-note">
            <a href="{{ route('public.tracking-round-qr.dashboard') }}">กลับหน้าหลัก</a>
        </p>
    </section>
@endsection
