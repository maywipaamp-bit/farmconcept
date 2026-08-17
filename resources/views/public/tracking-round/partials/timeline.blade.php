{{-- ไทม์ไลน์รอบประเมิน — ใช้ร่วมกันระหว่างแดชบอร์ดกับหน้ารายการรอบ
     ต้องเป็นชุดเดียวกันทั้งสองที่ ไม่งั้นแก้สถานะรอบทีต้องไล่แก้สองแห่งแล้วหลุดแห่งหนึ่งเสมอ

     ต้องการ: $rounds (ใบติดตามทั้งหมด เรียงตามวันครบกำหนด) · $openIds (id ที่ตอบได้ตอนนี้) --}}
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
                        <span class="tr-tl-badge is-open">ทำได้เลย</span>
                    @else
                        <span class="tr-tl-badge">ยังไม่เปิด</span>
                    @endif
                </p>

                <p class="tr-tl-name">{{ $round->name }}</p>

                <p class="tr-tl-date">
                    @if($answered)
                        ตอบเมื่อ @thaidate($round->answered_at)
                    @elseif($open)
                        ครบกำหนด @thaidate($round->due_date) · ใช้เวลา 5 นาที
                    @else
                        ครบกำหนด @thaidate($round->due_date)
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
