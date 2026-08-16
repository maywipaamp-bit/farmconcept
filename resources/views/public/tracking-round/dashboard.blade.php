@extends('public.activities.layout')

@section('title', 'หน้าหลักผู้เข้าร่วม')

@section('content')
    <section class="detail-card tr-card">
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

        @if(session('lineLinked'))
            <div class="tr-notice is-success" role="status">เชื่อม LINE เรียบร้อยแล้ว · จะได้รับแจ้งเตือนรอบถัดไปทาง LINE</div>
        @endif

        @if(session('lineConflict'))
            <div class="tr-notice" role="status">บัญชี LINE นี้ถูกผูกกับผู้ใช้อื่นไว้แล้ว จึงเชื่อมให้ไม่ได้ — กรุณาติดต่อเจ้าหน้าที่</div>
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
