@extends('public.activities.layout')

@section('title', $activity['title'])

{{-- ใช้ส่วนหัวโทนเดียวกับหน้าลงทะเบียน — กดปุ่มลงทะเบียนแล้วหัวหน้าจอจะต่อเนื่องกัน --}}
@section('body-class', 'is-detail-page')

@section('content')
    {{-- แถบ "< กลับ" แบบเดียวกับหัวหน้าจอลงทะเบียน ใช้แทน topbar ของ layout ที่ถูกซ่อนไว้
         ปุ่มย้อนกลับจึงมีปุ่มเดียวและอยู่ตำแหน่งเดิมตลอดทั้งเส้นทาง --}}
    {{-- แถวบน: กลับซ้าย · ชื่อหน้ากลาง · แชร์ขวา (grid 3 คอลัมน์ให้ชื่ออยู่กึ่งกลางจอจริง) --}}
    <header class="detail-head">
        <a class="detail-back" href="{{ route('public.activities') }}">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            <span>กลับ</span>
        </a>
        <span class="detail-head-title">รายละเอียดกิจกรรม</span>

        {{-- แชร์ลิงก์กิจกรรมนี้ — ใช้ Web Share API บนมือถือที่รองรับ (เปิดชีตแชร์ของเครื่อง)
             เบราว์เซอร์ที่ไม่รองรับจะคัดลอกลิงก์ให้แทน แล้วโชว์ป้ายยืนยันสั้น ๆ --}}
        <button type="button" class="round-icon-button detail-share" id="detail-share" aria-label="แชร์กิจกรรมนี้">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4.1M8.6 13.4l6.8 4.1"/></svg>
        </button>
        <span class="detail-share-pill" id="detail-share-pill" hidden>คัดลอกลิงก์แล้ว</span>
    </header>

    <article class="detail-card">
        <div class="detail-hero{{ $activity['image'] ? '' : ' has-fallback' }}" @if($activity['image']) style="background-image:url('{{ $activity['image'] }}')" @endif>
            <span class="activity-badge detail-category-badge">{{ strtoupper($activity['category'] ?: $activity['type']) }}</span>
        </div>
        <div class="detail-body">
            <h1>{{ $activity['title'] }}</h1>

            @if($activity['description'])
                <p class="detail-description">{{ $activity['description'] }}</p>
            @endif

            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                <span>{{ $activity['scheduleLabel'] ?: '-' }}</span>
            </div>
            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $activity['location'] }}</span>
            </div>
            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3-7 8-7s8 3 8 7"/></svg>
                <span>{{ $activity['speaker'] }}</span>
            </div>
            <div class="detail-meta detail-price">
                <span class="baht-icon">฿</span>
                <span>{{ $activity['priceLabel'] }}</span>
            </div>

            @if($activity['registrationDeadlineLabel'])
                <div class="deadline-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <span>{{ $activity['registrationDeadlineLabel'] }}</span>
                </div>
            @endif

            @if($registration['enabled'])
                <a class="registration-cta" href="{{ $registration['registerUrl'] }}">ลงทะเบียนเข้าร่วม</a>
            @endif
        </div>
    </article>

    {{-- Check-in และแบบประเมินหลังกิจกรรมย้ายไปหน้าของตัวเองแล้ว
         (public.activities.checkin · public.activities.survey)
         ลิงก์เดิม ?action=checkin และ ?action=post-survey ถูก redirect จากคอนโทรลเลอร์ --}}
    @if(!$registration['enabled'] && $activity['requiresRegistration'])
        {{-- บอกสาเหตุจริงว่าปิดเพราะอะไร ไม่ใช่ข้อความรวมทุกกรณีเหมือนเดิม --}}
        <section class="registration-card is-closed">
            <h2>{{ $registration['closed']['title'] }}</h2>
            <p>{{ $registration['closed']['message'] }}</p>
        </section>
    @endif
@endsection

@push('page-script')
    <script>
        (function () {
            var shareBtn = document.getElementById('detail-share');
            var pill = document.getElementById('detail-share-pill');
            if (!shareBtn) return;

            var pillTimer = null;
            function showPill(text) {
                pill.textContent = text;
                pill.hidden = false;
                clearTimeout(pillTimer);
                pillTimer = setTimeout(function () { pill.hidden = true; }, 2200);
            }

            shareBtn.addEventListener('click', async function () {
                var shareData = { title: document.title, url: location.href };
                if (navigator.share) {
                    try {
                        await navigator.share(shareData);
                    } catch (error) {
                        /* ผู้ใช้กดยกเลิกชีตแชร์ — ไม่ต้องทำอะไรต่อ */
                    }
                    return;
                }
                try {
                    await navigator.clipboard.writeText(location.href);
                    showPill('คัดลอกลิงก์แล้ว');
                } catch (error) {
                    showPill('คัดลอกลิงก์ไม่สำเร็จ');
                }
            });
        })();
    </script>
@endpush
