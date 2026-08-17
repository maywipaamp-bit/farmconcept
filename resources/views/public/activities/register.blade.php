@extends('public.activities.layout')

@section('title', 'ลงทะเบียน '.$activity['title'])

{{-- หน้านี้เป็น full-bleed — ระยะขอบมาจาก padding ของ .public-app ไม่ใช่กรอบการ์ด --}}
@section('body-class', 'is-register-page')

{{-- ปุ่มขวาบนของ topbar เป็น "กลับไปหน้ากิจกรรม" แทนช่องค้นหา
     เพราะหน้านี้เข้ามาจากหน้ารายละเอียดกิจกรรมเสมอ ทางออกจึงควรพากลับไปที่เดิม --}}
@section('search-action')
    <a class="round-icon-button" href="{{ $config['urls']['activity'] }}" aria-label="กลับไปหน้ารายละเอียดกิจกรรม">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </a>
@endsection

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- ใช้ Noto Sans Thai ตัวเดียวกับ layout (โหลดไว้แล้ว) — ไม่โหลดฟอนต์ที่สองซ้อน
         ไม่งั้นตอน reload จะเห็นฟอนต์เก่ากระพริบสลับก่อนฟอนต์จริงจะติด --}}
    <link rel="stylesheet" href="@assetv('assets/css/public-register.css')">
@endpush

@section('content')
<div class="reg-page">
    <div class="reg-card">
        {{-- หัวหน้าจอบนพื้นไล่สี — บอกว่ากำลังลงทะเบียนกิจกรรมไหนและมีทางกลับ
             ชื่อเรื่องกับคำอธิบายจะซ่อนเองเมื่อไม่ได้อยู่หน้าจอแรก (คุมด้วยคลาสที่ JS สลับให้) --}}
        <header class="reg-hero">
            {{-- แถวบน: ปุ่มกลับชิดซ้าย ชื่อหน้าจออยู่กึ่งกลางจริง (grid 3 คอลัมน์ ช่องขวาว่างไว้ถ่วงให้สมดุล) --}}
            <div class="reg-hero-top">
                {{-- ยังเป็นลิงก์จริงไปหน้ากิจกรรม เพื่อให้ใช้ได้แม้ JS ไม่ทำงาน
                     ส่วน JS จะดักไว้ให้ย้อนกลับทีละขั้นตอนก่อน แล้วค่อยออกจากหน้านี้เมื่อไม่มีขั้นก่อนหน้า --}}
                <a class="reg-hero-back" id="reg-back" href="{{ $config['urls']['activity'] }}">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>กลับ</span>
                </a>
                {{-- ชื่อหน้ากลางเปลี่ยนตามหน้าจอ — หน้าชำระเงินขึ้น "ชำระเงิน" หน้าอื่นเป็น "ลงทะเบียนเข้าร่วม" --}}
                <h1 class="reg-hero-screen-title">
                    <span data-title-for="pay">ชำระเงิน</span>
                    <span data-title-for="default">ลงทะเบียนเข้าร่วม</span>
                </h1>
                <span class="reg-hero-top-spacer" aria-hidden="true"></span>
            </div>

            {{-- หัวเรื่องเปลี่ยนตามหน้าจอที่กำลังอยู่ — JS ตั้ง data-screen ให้ที่ body --}}
            <h1 class="reg-hero-title" data-for="check">ลงทะเบียนเข้าร่วมกิจกรรม</h1>
            <p class="reg-hero-desc" data-for="check">แค่กรอกเบอร์โทรหรืออีเมล เราจะเช็คให้ว่าคุณเคยลงทะเบียนไว้แล้วหรือยัง</p>
        </header>

        <div class="reg-pane is-center" id="reg-pane">

            {{-- หน้าจอ 1 — ตรวจสอบสิทธิ์ --}}
            <section class="reg-screen-check" data-screen="check">
                <div class="reg-check-body">
                    @if($lineError)
                        <p class="reg-error-text is-block">{{ $lineError }}</p>
                    @endif

                    <div class="reg-field">
                        <label for="reg-contact">เบอร์โทรศัพท์ หรือ อีเมล</label>
                        {{-- ไม่ใส่ placeholder ตามสเปก — ป้ายบนช่องบอกอยู่แล้วว่ากรอกอะไร --}}
                        {{-- ตัวอย่างรูปแบบอยู่ในช่องเป็นตัวสีเทา ไม่ใช่บรรทัดอธิบายใต้ช่อง
                             จะได้ไม่กินความสูงเพิ่ม และหายไปเองทันทีที่เริ่มพิมพ์ --}}
                        <input id="reg-contact" class="reg-input reg-check-input" type="text"
                               inputmode="email" autocomplete="tel email"
                               placeholder="081-234-5678 หรือ you@example.com">
                        <span class="reg-error-text" id="reg-contact-error" hidden>ขอเป็นเบอร์มือถือ 10 หลัก หรืออีเมลที่ใช้งานได้นะคะ</span>
                    </div>
                    <button type="button" class="reg-btn-soft" id="reg-check-btn" disabled>ถัดไป</button>

                    @if($config['line']['enabled'])
                        <div class="reg-divider"><span>หรือ</span></div>

                        @if($config['line']['profile'])
                            {{-- ล็อกอินแล้ว — บอกว่ากำลังใช้บัญชีไหน และให้ทางออกถ้าเป็นบัญชีของคนอื่น --}}
                            <div class="reg-line-account">
                                <span class="reg-line-avatar">
                                    @if($config['line']['profile']['pictureUrl'])
                                        <img src="{{ $config['line']['profile']['pictureUrl'] }}" alt="">
                                    @else
                                        <span class="reg-line-badge">L</span>
                                    @endif
                                </span>
                                <span class="reg-line-account-text">
                                    <span class="reg-line-account-name">{{ $config['line']['profile']['displayName'] }}</span>
                                    <span class="reg-line-account-note">เชื่อมบัญชี LINE แล้ว</span>
                                </span>
                                <form method="POST" action="{{ $config['line']['logoutUrl'] }}">
                                    @csrf
                                    <button type="submit" class="reg-line-switch">ออกจากระบบ</button>
                                </form>
                            </div>
                            <button type="button" class="reg-btn-outline" id="reg-line-continue">ใช้บัญชีนี้ลงทะเบียนต่อ</button>
                            {{-- บอกทางเลือกให้ชัด — ล็อกอินค้างอยู่ไม่ได้แปลว่าต้องลงทะเบียนด้วยบัญชีนี้เท่านั้น --}}
                            <span class="reg-line-hint">ลงทะเบียนให้คนอื่นได้ โดยกรอกเบอร์โทรของคนนั้นในช่องด้านบน หรือกด "ออกจากระบบ" เพื่อเริ่มใหม่</span>
                        @else
                            <a class="reg-btn-line" href="{{ $config['line']['loginUrl'] }}">เข้าสู่ระบบด้วย LINE</a>
                            {{-- บอกเฉพาะสิ่งที่ระบบทำได้จริงตอนนี้ — การส่งข้อความอัตโนมัติผ่าน LINE
                                 ต้องใช้ Messaging API ซึ่งยังไม่ได้ทำ อย่าสัญญาไว้ในหน้าจอ --}}
                            <span class="reg-line-hint">เข้าสู่ระบบด้วย LINE ระบบจะจำข้อมูลของคุณไว้ให้ ครั้งถัดไปไม่ต้องกรอกใหม่</span>
                        @endif
                    @elseif($config['links']['line'])
                        <div class="reg-divider"><span>หรือ</span></div>
                        <a class="reg-btn-line" href="{{ $config['links']['line'] }}" target="_blank" rel="noopener">
                            <span class="reg-line-badge">L</span>
                            <span>สอบถามทาง LINE</span>
                        </a>
                    @endif
                </div>
            </section>

            {{-- หน้าจอ 2 — ลงทะเบียนแล้ว --}}
            <section class="reg-screen-found" data-screen="found" hidden>
                <div class="reg-confirm-card">
                    <span class="reg-check-circle">
                        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5 9 17.5 20 6.5"/></svg>
                    </span>
                    <h2>คุณลงทะเบียนแล้ว</h2>
                    <p>รอเข้างานเพื่อ check-in ที่จุดลงทะเบียนหน้างาน</p>
                    <span class="reg-code-pill" id="reg-found-code"></span>
                </div>
                {{-- บล็อกประวัติการลงทะเบียนเดิม — จุดเดียวในหน้าที่มีกรอบได้
                     เพราะเป็นข้อมูลคนละก้อนกับขั้นตอนที่กำลังทำอยู่ ต้องแยกบริบทให้เห็น
                     ข้างในแยกรายแถวด้วยเส้นบางเท่านั้น ห้ามมีกรอบซ้อนอีกชั้น --}}
                <div class="reg-booking-block">
                    <span class="reg-group-label">รายละเอียดการจอง</span>
                    <dl class="reg-booking-rows" id="reg-found-rows"></dl>
                </div>

                @if($config['line']['profile'] && count($config['line']['history']))
                    {{-- ประวัติกิจกรรมอื่นที่บัญชี LINE นี้เคยลงทะเบียน
                         แสดงเฉพาะตอนเข้าผ่าน LINE เพราะยืนยันตัวตนมาแล้ว — เข้าด้วยเบอร์โทรเฉย ๆ
                         ไม่ควรเห็นประวัติของคนอื่นที่บังเอิญใช้เบอร์เดียวกัน
                         ไม่ใส่กรอบ ใช้เส้นคั่นรายแถว เพราะกรอบชั้นเดียวของหน้าถูกใช้ไปกับการจองปัจจุบันแล้ว --}}
                    <div class="reg-history">
                        <span class="reg-group-label">กิจกรรมอื่นที่คุณเคยลงทะเบียน</span>
                        <div class="reg-history-list">
                            @foreach($config['line']['history'] as $past)
                                <a class="reg-history-row" href="{{ $past['url'] }}">
                                    <span class="reg-history-text">
                                        <span class="reg-history-title">{{ $past['title'] }}</span>
                                        <span class="reg-history-meta">{{ $past['date'] }} · {{ $past['paymentLabel'] }}</span>
                                    </span>
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($config['line']['profile'])
                    {{-- ทางออกจากหน้าจอนี้ — คนที่เคยลงทะเบียนด้วย LINE แล้วจะถูกพามาหน้านี้ทุกครั้ง
                         ถ้าไม่มีปุ่มนี้จะกลับไปช่องกรอกเบอร์ไม่ได้เลย และลงทะเบียนให้คนอื่นไม่ได้ --}}
                    <div class="reg-line-exit">
                        <span>ต้องการลงทะเบียนให้คนอื่น หรือใช้เบอร์โทรแทน?</span>
                        <form method="POST" action="{{ $config['line']['logoutUrl'] }}">
                            @csrf
                            <button type="submit" class="reg-btn-secondary">ออกจากระบบ LINE แล้วกรอกเบอร์โทร</button>
                        </form>
                    </div>
                @endif
            </section>

            {{-- หน้าจอ 3 — กรอกข้อมูลผู้ลงทะเบียนหลัก --}}
            <section class="reg-screen-form" data-screen="form" hidden>
                {{-- สรุปกิจกรรมที่กำลังลงทะเบียน — ชื่อ/วันเวลาซ้าย ราคาขวา คั่นจากฟอร์มด้วยเส้นบางด้านล่าง --}}
                <div class="reg-activity-summary">
                    <div class="reg-activity-summary-text">
                        <span class="reg-activity-name">{{ $config['activity']['title'] }}</span>
                        {{-- เฉพาะวันเวลา ไม่แสดงสถานที่ — ดูได้จากหน้ารายละเอียดกิจกรรมอยู่แล้ว
                             กิจกรรมหลายรอบไม่แสดงวันเวลาตรงนี้เลย — วันเวลาของแต่ละรอบอยู่ใน dropdown เลือกรอบแล้ว --}}
                        @if(count($config['rounds']) <= 1)
                            <span class="reg-activity-meta">{{ $config['activity']['scheduleLabel'] ?: '-' }}</span>
                        @endif
                    </div>
                    <span class="reg-activity-fee">{{ $config['activity']['isFree'] ? 'ฟรี' : number_format($config['activity']['fee']).' ฿/ท่าน' }}</span>
                </div>

                <div class="reg-form-fields">
                    {{-- เลือกรอบอยู่บนสุด ใต้ข้อมูลกิจกรรม — ต้องรู้ก่อนว่าสมัครรอบไหน ค่อยกรอกข้อมูลตัวเอง --}}
                    @if(count($config['rounds']) > 0)
                        <p class="reg-fieldset-title">รอบที่ต้องการสมัคร</p>
                        <div class="reg-field">
                            <label for="reg-round"><span>รอบที่ต้องการสมัคร</span><span class="reg-star">*</span></label>
                            <select id="reg-round" class="reg-select">
                                <option value="">เลือกรอบ *</option>
                                {{-- ไม่โชว์จำนวนที่นั่งเหลือ — รอบที่เต็มถูก disable ให้เลือกไม่ได้อยู่แล้ว --}}
                                @foreach($config['rounds'] as $round)
                                    <option value="{{ $round['id'] }}" @disabled($round['seatsLeft'] === 0)>{{ $round['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <p class="reg-fieldset-title">ข้อมูลผู้ลงทะเบียน</p>
                    <div class="reg-field">
                        {{-- label ยังอยู่ใน DOM เพื่อ screen reader แต่ซ่อนไว้ทางสายตา — ใช้ placeholder
                             สีเทาในช่องแทนตามที่ขอ ไม่ต้องมีลาเบลลอยเหนือช่องแล้ว --}}
                        <label for="reg-name"><span>ชื่อ - นามสกุล</span><span class="reg-star">*</span></label>
                        <input id="reg-name" class="reg-input" type="text" maxlength="160" autocomplete="name" placeholder="ชื่อ - นามสกุล *">
                    </div>
                    <div class="reg-field">
                        <label for="reg-phone"><span>เบอร์โทรศัพท์</span><span class="reg-star">*</span></label>
                        <input id="reg-phone" class="reg-input" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" placeholder="เบอร์โทรศัพท์ *">
                        <span class="reg-error-text" id="reg-phone-error" hidden>กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก</span>
                    </div>
                    {{-- แสดงเสมอ ไม่ผูกกับสวิตช์ "เปิดใช้อีเมล" ของแบบลงทะเบียน
                         เพราะหน้าจอแรกให้กรอกเบอร์โทร "หรืออีเมล" ได้ ถ้าคนกรอกอีเมลมาแล้วที่นี่ไม่มีช่องรับ
                         อีเมลที่เพิ่งกรอกจะหายไปเงียบ ๆ · สวิตช์ของแบบฟอร์มยังคุมว่าบังคับกรอกหรือไม่ --}}
                    <div class="reg-field">
                        <label for="reg-email"><span>อีเมล</span>@if($config['fields']['email']['required'])<span class="reg-star">*</span>@endif</label>
                        <input id="reg-email" class="reg-input" type="email" maxlength="160" autocomplete="email" placeholder="อีเมล{{ $config['fields']['email']['required'] ? ' *' : '' }}">
                    </div>

                    @if($config['fields']['age_range']['enabled'] || $config['fields']['occupation']['enabled'])
                        <div class="reg-field-grid{{ ($config['fields']['age_range']['enabled'] && $config['fields']['occupation']['enabled']) ? '' : ' is-single' }}">
                            @if($config['fields']['age_range']['enabled'])
                                <div class="reg-field">
                                    <label for="reg-age"><span>ช่วงอายุ</span>@if($config['fields']['age_range']['required'])<span class="reg-star">*</span>@endif</label>
                                    <select id="reg-age" class="reg-select">
                                        <option value="">เลือกช่วงอายุ{{ $config['fields']['age_range']['required'] ? ' *' : '' }}</option>
                                        @foreach($config['options']['ageRanges'] as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if($config['fields']['occupation']['enabled'])
                                <div class="reg-field">
                                    <label for="reg-job"><span>อาชีพ</span>@if($config['fields']['occupation']['required'])<span class="reg-star">*</span>@endif</label>
                                    <select id="reg-job" class="reg-select">
                                        <option value="">เลือกอาชีพ{{ $config['fields']['occupation']['required'] ? ' *' : '' }}</option>
                                        @foreach($config['options']['occupations'] as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($config['fields']['source_channel']['enabled'])
                        <div class="reg-field">
                            <label for="reg-source"><span>ช่องทางที่ทราบข่าวกิจกรรม</span>@if($config['fields']['source_channel']['required'])<span class="reg-star">*</span>@endif</label>
                            <select id="reg-source" class="reg-select">
                                <option value="">ช่องทางที่ทราบข่าวกิจกรรม{{ $config['fields']['source_channel']['required'] ? ' *' : '' }}</option>
                                @foreach($config['options']['sources'] as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- มาหลายคน — เลือกจาก dropdown แทนปุ่ม +/− เดิม ให้เข้าชุดกับฟิลด์เลือกค่าอื่น ๆ ในหน้านี้
                         ตัวเลือกสุดท้ายเป็น "ขึ้นไป" เพราะเกินจำนวนนี้ต้องติดต่อผู้จัดเป็นกรณีไป --}}
                    @if($config['maxSeats'] > 1)
                        <p class="reg-fieldset-title">จำนวนที่นั่ง</p>
                        <div class="reg-field">
                            <label for="reg-seat-select">จำนวนที่นั่ง</label>
                            <select id="reg-seat-select" class="reg-select">
                                @for ($i = 1; $i <= $config['maxSeats']; $i++)
                                    <option value="{{ $i }}">{{ $i }} ที่นั่ง{{ $i === $config['maxSeats'] ? 'ขึ้นไป' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                    @endif

                    <p class="reg-fieldset-title">ข้อมูลเพิ่มเติม</p>
                    <div class="reg-field">
                        <label for="reg-note">ข้อมูลเพิ่มเติม (ถ้ามี)</label>
                        <textarea id="reg-note" class="reg-textarea" maxlength="255" rows="2" placeholder="เช่น มีผู้ติดตาม 2 คน / คาดหวังอะไรในกิจกรรมนี้"></textarea>
                    </div>

                    {{-- ลิงก์เงื่อนไข/นโยบาย: มีเอกสาร active ในระบบ (master/consent-documents) → เปิดอ่านเป็น popup
                         ไม่มีเอกสารแต่ตั้ง URL ไว้ → เปิดลิงก์ภายนอกแบบเดิม · ไม่มีทั้งคู่ → ตัวหนาเฉย ๆ --}}
                    <label class="reg-consent">
                        <input type="checkbox" id="reg-consent">
                        <span>ยอมรับ@if($config['consentDocs']['terms'])<a href="#" data-consent-doc="terms">เงื่อนไขการเข้าร่วม</a>@elseif($config['links']['terms'])<a href="{{ $config['links']['terms'] }}" target="_blank" rel="noopener">เงื่อนไขการเข้าร่วม</a>@else<b style="font-weight:500">เงื่อนไขการเข้าร่วม</b>@endif และ@if($config['consentDocs']['privacy'])<a href="#" data-consent-doc="privacy">นโยบายความเป็นส่วนตัว</a>@elseif($config['links']['privacy'])<a href="{{ $config['links']['privacy'] }}" target="_blank" rel="noopener">นโยบายความเป็นส่วนตัว</a>@else<b style="font-weight:500">นโยบายความเป็นส่วนตัว</b>@endif</span>
                    </label>
                </div>
            </section>

            {{-- หน้าจอ 5 — ชำระเงิน --}}
            <section class="reg-screen-pay" data-screen="pay" hidden>
                {{-- ขึ้นเฉพาะตอนกลับเข้ามาใหม่หลังออกไปโอนเงิน — บอกว่าที่นั่งยังอยู่ ไม่ต้องลงทะเบียนซ้ำ
                     ถ้าไม่บอก คนจะนึกว่าระบบพากลับมาที่เดิมเพราะลงทะเบียนไม่สำเร็จ แล้วกรอกใหม่ทั้งชุด --}}
                <div class="reg-resume-note" id="reg-resume-note" hidden>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>
                        จองที่นั่งไว้ให้แล้ว — แนบสลิปต่อได้เลย ไม่ต้องกรอกข้อมูลใหม่
                        {{-- เครื่องที่ใช้ร่วมกัน (แท็บเล็ตหน้างาน) อาจค้างรายการของคนก่อนหน้าไว้
                             ต้องมีทางออกให้คนถัดไปเริ่มใหม่ได้เอง ไม่ต้องรอ 1 วันให้หมดอายุ --}}
                        <button type="button" class="reg-resume-reset" id="reg-resume-reset">ไม่ใช่รายการของฉัน</button>
                    </span>
                </div>

                {{-- แถบสรุปยอด — ชื่อกิจกรรม + "n ที่นั่ง × ราคา" ซ้าย ยอดรวมขวา --}}
                <div class="reg-pay-summary-bar">
                    <div class="reg-pay-summary-text">
                        <span class="reg-pay-summary-name">{{ $config['activity']['title'] }}</span>
                        <span class="reg-pay-summary-calc" id="reg-pay-fee-label"></span>
                    </div>
                    <span class="reg-pay-summary-total num" id="reg-pay-total"></span>
                </div>

                {{-- โอนเข้าบัญชีเป็นค่าเริ่มต้น — คนส่วนใหญ่โอนจากแอปธนาคารโดยตรง
                     QR เป็นทางเลือกที่กดสลับได้ หน้าจึงสั้นลงเพราะไม่ต้องโชว์รูป QR ตั้งแต่แรก --}}
                @if($config['payment']['qrUrl'])
                    <div class="reg-method-tabs" role="tablist" aria-label="วิธีชำระเงิน">
                        <button type="button" class="reg-method-tab is-active" id="reg-tab-bank" role="tab" aria-selected="true">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18M5 10V7l7-4 7 4v3M4 10v9h16v-9M9 14v5m6-5v5"/></svg>
                            <span>โอนเข้าบัญชี</span>
                        </button>
                        <button type="button" class="reg-method-tab" id="reg-tab-qr" role="tab" aria-selected="false">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z"/></svg>
                            <span>สแกน QR</span>
                        </button>
                    </div>

                    {{-- QR ใหญ่เต็มกว้างในกรอบมน — หัวบอกว่าเป็น PromptPay ของใคร ไม่มีข้อความอื่นรก --}}
                    <div class="reg-qr-box" id="reg-qr-panel" hidden>
                        <span class="reg-qr-title">PromptPay · {{ $config['payment']['accountName'] ?: 'The Farm Concept' }}</span>
                        <div class="reg-qr-image">
                            <img src="{{ $config['payment']['qrUrl'] }}" alt="QR Code สำหรับชำระเงิน">
                        </div>
                        <button type="button" class="reg-btn-outline reg-qr-save" id="reg-qr-save">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                            <span>บันทึกคิวอาร์</span>
                        </button>
                    </div>
                @endif

                @if($config['payment']['accountNumber'])
                    {{-- แสดงตั้งแต่แรกเสมอ — โอนเข้าบัญชีเป็นวิธีชำระเริ่มต้นแล้ว --}}
                    <dl class="reg-bank-box" id="reg-bank-panel" style="margin:0">
                        <div class="reg-bank-row">
                            <dt>ธนาคาร</dt>
                            <div class="reg-bank-value"><dd>{{ $config['payment']['bankName'] }}</dd></div>
                        </div>
                        <div class="reg-bank-row">
                            <dt>ชื่อบัญชี</dt>
                            <div class="reg-bank-value"><dd>{{ $config['payment']['accountName'] }}</dd></div>
                        </div>
                        <div class="reg-bank-row">
                            <dt>เลขที่บัญชี</dt>
                            <div class="reg-bank-value">
                                <dd class="num">{{ $config['payment']['accountNumber'] }}</dd>
                                <button type="button" class="reg-copy-btn" id="reg-copy-account" aria-label="คัดลอกเลขที่บัญชี">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path id="reg-copy-icon" d="M8 8V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-3M5 8h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2"/></svg>
                                </button>
                            </div>
                        </div>
                        <span class="reg-copied-pill" id="reg-copied-pill" hidden>คัดลอกแล้ว วางในแอปธนาคารได้เลย</span>
                    </dl>
                @endif

                <div class="reg-slip">
                    <div class="reg-slip-heading">
                        <span class="reg-group-label">แนบสลิปการโอน</span>
                    </div>
                    <input type="file" id="reg-slip-input" accept="image/jpeg,image/png,image/webp" hidden>
                    <button type="button" class="reg-dropzone" id="reg-dropzone">
                        <span class="reg-dropzone-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </span>
                        <span class="reg-dropzone-text">
                            <span class="reg-dropzone-label">แนบสลิปการโอนเงิน</span>
                            <span class="reg-dropzone-hint">ถ่ายจากมือถือได้เลย · JPG หรือ PNG</span>
                        </span>
                    </button>
                    <div class="reg-slip-card" id="reg-slip-card" hidden>
                        <span class="reg-slip-icon">
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5 9 17.5 20 6.5"/></svg>
                        </span>
                        <div class="reg-slip-info">
                            <span class="reg-slip-name" id="reg-slip-name"></span>
                            <span class="reg-slip-status">แนบเรียบร้อยแล้ว ขอบคุณค่ะ</span>
                        </div>
                        <button type="button" class="reg-slip-remove" id="reg-slip-remove" aria-label="ลบสลิป">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </section>

            {{-- หน้าจอ 6 — สำเร็จ --}}
            <section class="reg-screen-done" data-screen="done" hidden>
                <div class="reg-done-hero">
                    <span class="reg-check-circle">
                        <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5 9 17.5 20 6.5"/></svg>
                    </span>
                    <h2>ลงทะเบียนสำเร็จแล้ว!</h2>
                    <span class="reg-code-pill" id="reg-done-code"></span>
                </div>

                <div class="reg-done-box" id="reg-done-rows"></div>

                <button type="button" class="reg-btn-outline reg-download-btn" id="reg-download">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                    <span>ดาวน์โหลดรายละเอียด</span>
                </button>
            </section>
        </div>

        <footer class="reg-footer" id="reg-footer" hidden>
            <p class="reg-footer-error" id="reg-footer-error" aria-live="polite"></p>
            <button type="button" class="reg-btn-primary" id="reg-primary-btn"></button>
            <button type="button" class="reg-btn-secondary" id="reg-secondary-btn" hidden></button>
        </footer>
    </div>

    {{-- popup แจ้งว่าเบอร์/อีเมลนี้ลงทะเบียนกิจกรรมนี้ไว้แล้ว — โผล่ทันทีที่กรอกเสร็จ
         ไม่ต้องรอกดส่งแล้วค่อยเจอ error ตอนท้าย --}}
    <div class="reg-modal" id="reg-dup-modal" hidden role="dialog" aria-modal="true" aria-labelledby="reg-dup-title">
        <div class="reg-modal-card reg-dup-card">
            <div class="reg-modal-head">
                <div class="reg-modal-titles">
                    <h2 id="reg-dup-title">เคยลงทะเบียนแล้ว</h2>
                    <p id="reg-dup-text"></p>
                </div>
                <button type="button" class="reg-modal-close" id="reg-dup-close" aria-label="ปิด">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="reg-modal-foot">
                <button type="button" class="reg-btn-secondary" id="reg-dup-edit">แก้ไขข้อมูล</button>
                <button type="button" class="reg-btn-primary" id="reg-dup-view">ดูประวัติการลงทะเบียน</button>
            </div>
        </div>
    </div>

    {{-- popup อ่านเอกสารความยินยอม — ใช้ใบเดียวทั้งเงื่อนไขและนโยบาย JS เปลี่ยนหัว/เนื้อหาตามลิงก์ที่กด --}}
    <div class="reg-modal" id="reg-consent-modal" hidden role="dialog" aria-modal="true" aria-labelledby="reg-consent-modal-title">
        <div class="reg-modal-card reg-consent-doc-card">
            <div class="reg-modal-head">
                <div class="reg-modal-titles">
                    <h2 id="reg-consent-modal-title"></h2>
                    <p id="reg-consent-modal-version"></p>
                </div>
                <button type="button" class="reg-modal-close" id="reg-consent-modal-close" aria-label="ปิด">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="reg-modal-body reg-consent-doc-body" id="reg-consent-modal-content"></div>
        </div>
    </div>

    {{-- หน้าจอ 4 — popup ผู้ร่วมเพิ่ม --}}
    <div class="reg-modal" id="reg-guest-modal" hidden role="dialog" aria-modal="true" aria-labelledby="reg-guest-title">
        <div class="reg-modal-card">
            <div class="reg-modal-head">
                <div class="reg-modal-titles">
                    <h2 id="reg-guest-title">ข้อมูลผู้ร่วมเพิ่ม</h2>
                    {{-- บอกว่ากรอกถึงคนที่เท่าไหร่ — แถบ progress กับคำชวนถูกตัดออกให้ป๊อปอัปโล่งขึ้น --}}
                    <p id="reg-guest-subtitle"></p>
                </div>
                <button type="button" class="reg-modal-close" id="reg-guest-close" aria-label="ปิด">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="reg-modal-body">
                <div class="reg-field">
                    <label for="reg-guest-name"><span>ชื่อ - นามสกุล</span><span class="reg-star is-strong">*</span></label>
                    <input id="reg-guest-name" class="reg-input is-bright" type="text" maxlength="160" placeholder="เช่น สมชาย ใจดี">
                </div>
                @if($config['fields']['age_range']['enabled'] || $config['fields']['occupation']['enabled'])
                    <div class="reg-field-grid{{ ($config['fields']['age_range']['enabled'] && $config['fields']['occupation']['enabled']) ? '' : ' is-single' }}">
                        @if($config['fields']['age_range']['enabled'])
                            <div class="reg-field">
                                <label for="reg-guest-age">ช่วงอายุ</label>
                                <select id="reg-guest-age" class="reg-select is-bright">
                                    <option value="">เลือกช่วงอายุ</option>
                                    @foreach($config['options']['ageRanges'] as $option)
                                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if($config['fields']['occupation']['enabled'])
                            <div class="reg-field">
                                <label for="reg-guest-job">อาชีพ</label>
                                <select id="reg-guest-job" class="reg-select is-bright">
                                    <option value="">เลือกอาชีพ</option>
                                    @foreach($config['options']['occupations'] as $option)
                                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="reg-modal-foot">
                <button type="button" class="reg-btn-secondary" id="reg-guest-back">ย้อนกลับ</button>
                <button type="button" class="reg-btn-primary" id="reg-guest-next" disabled></button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('page-data')
<script>
    window.TFC_REGISTER = @json($config);
</script>
@endpush

@push('page-script')
<script src="@assetv('assets/js/public-register.js')" defer></script>
@endpush
