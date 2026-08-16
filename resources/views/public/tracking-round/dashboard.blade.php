@extends('public.activities.layout')

@section('title', 'หน้าหลักผู้เข้าร่วม')

@section('content')
    <section class="detail-card tr-card">
        {{-- แถวผู้ใช้ + เมนู — ใช้ details/summary จะได้ไม่ต้องมี JS สำหรับ dropdown --}}
        <div class="tr-user-row">
            <details class="tr-user-menu">
                <summary>
                    <span class="tr-user-text">
                        <span class="tr-user-hello">สวัสดี</span>
                        <span class="tr-user-name">คุณ{{ $participant->name }}</span>
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

            <span class="tr-user-chip">กลุ่มตัวอย่าง · {{ $participant->area?->name ?? 'ยังไม่ระบุพื้นที่' }}</span>
        </div>

        @if(session('lineLinked'))
            <div class="tr-notice is-success" role="status">เชื่อม LINE เรียบร้อยแล้ว · จะได้รับแจ้งเตือนรอบถัดไปทาง LINE</div>
        @endif

        {{-- การ์ดเดียวในหน้าที่ใช้สีเขียว — สิ่งที่ต้องทำตอนนี้ ที่เหลือเป็นข้อมูลประกอบ --}}
        <div class="tr-due-card{{ $dueRound ? '' : ' is-empty' }}">
            <div class="tr-due-head">
                <span>รอบที่ถึงกำหนด</span>
                <a href="{{ route('public.tracking-round-qr.rounds') }}">ดูทุกรอบ →</a>
            </div>

            @if($dueRound)
                <p class="tr-due-name">รอบที่ {{ $dueOrder }} · {{ $dueRound->name }}</p>
                <p class="tr-due-meta">ใช้เวลา 5 นาที · ถึง @thaidate($dueBefore)</p>
                <a class="tr-primary-button"
                   href="{{ route('public.tracking-round-qr.survey', $dueRound->id) }}">ทำแบบประเมิน</a>
            @else
                <p class="tr-due-name">ยังไม่มีรอบที่ต้องทำตอนนี้</p>
                <p class="tr-due-meta">คุณทำครบทุกรอบแล้ว ขอบคุณครับ</p>
            @endif
        </div>

        <div class="tr-stats">
            <div>
                <span class="tr-stat-number">{{ $answeredRounds }}/{{ $totalRounds }}</span>
                <span class="tr-stat-label">รอบที่ทำแล้ว</span>
            </div>
            <div>
                {{-- เต็มวัน–เดือน–ปี ไม่ตัดปีทิ้ง — รอบติดตามกินเวลาข้ามปี ปีจึงไม่ใช่ส่วนที่เดาเอาได้ --}}
                <span class="tr-stat-number is-date">
                    @if($nextRound)@thaidate($nextRound->due_date)@else—@endif
                </span>
                <span class="tr-stat-label">ครบกำหนดรอบถัดไป</span>
            </div>
        </div>

        {{-- สวิตช์แจ้งเตือน — เป็นค่าของแต่ละคน เพราะเป็นการยินยอมรับข้อความส่วนบุคคล --}}
        <form method="POST" action="{{ route('public.tracking-round-qr.notify') }}" class="tr-row-form">
            @csrf
            <button type="submit" class="tr-toggle-row" aria-pressed="{{ $participant->line_notify ? 'true' : 'false' }}">
                <span class="tr-info-text">แจ้งเตือนรอบถัดไปผ่าน LINE</span>
                <span class="tr-toggle{{ $participant->line_notify ? ' is-on' : '' }}" aria-hidden="true"><i></i></span>
            </button>
        </form>

        {{-- ยังไม่ได้เชื่อม LINE = เปิดสวิตช์ไปก็ส่งไม่ถึง ต้องมีทางเชื่อมให้กดตรงนี้เลย
             ไม่ใช่บอกว่า "ยังไม่ได้เชื่อม" แล้วปล่อยให้ไปหาทางเอง --}}
        @unless($participant->line_user_id)
            <a class="tr-line-outline" href="{{ route('public.tracking-round-qr.line') }}">
                <svg class="tr-line-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2.5c5.24 0 9.5 3.46 9.5 7.72 0 1.54-.6 2.94-1.7 4.2-1.6 1.85-4.3 4.1-5.9 5.06-1.05.63-.94-.28-.9-.55l.15-.9c.04-.28.07-.7-.04-.97-.12-.3-.6-.46-.95-.53-4.7-.62-8.16-3.9-8.16-7.31C4 5.96 8.26 2.5 12 2.5Zm-2.9 5.6a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h.62c.16 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3H9.1Zm-2.6 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h2.02c.17 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3H7.42V8.4a.3.3 0 0 0-.3-.3H6.5Zm4.72 0a.3.3 0 0 0-.3.3v3.9c0 .17.14.3.3.3h.62c.17 0 .3-.13.3-.3v-2.1l1.72 2.33.05.05h.02l.03.02h.66c.17 0 .3-.13.3-.3v-3.9a.3.3 0 0 0-.3-.3h-.62a.3.3 0 0 0-.3.3v2.1L13.3 8.24l-.04-.05h-.03l-.02-.02h-.66l-.02-.01h-.03l-.02-.01h-.62Zm5.3 0a.3.3 0 0 0-.3.3v3.9c0 .17.13.3.3.3h2.03c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.13.3-.3v-.62a.3.3 0 0 0-.3-.3h-1.11v-.43h1.11c.16 0 .3-.14.3-.3V8.4a.3.3 0 0 0-.3-.3h-2.03Z"/>
                </svg>
                เชื่อมต่อ LINE
            </a>
            <p class="tr-hint-center">เชื่อมแล้วจะได้รับแจ้งเตือนรอบถัดไป และเข้าระบบครั้งหน้าไม่ต้องกรอกเบอร์</p>
        @endunless
    </section>
@endsection
