@extends('public.activities.layout')

@section('title', 'ลงทะเบียน '.$activity['title'])

{{-- ปุ่มขวาบนของ topbar เป็น "กลับไปหน้ากิจกรรม" แทนช่องค้นหา
     เพราะหน้านี้เข้ามาจากหน้ารายละเอียดกิจกรรมเสมอ ทางออกจึงควรพากลับไปที่เดิม --}}
@section('search-action')
    <a class="round-icon-button" href="{{ $config['urls']['activity'] }}" aria-label="กลับไปหน้ารายละเอียดกิจกรรม">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </a>
@endsection

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="@assetv('assets/css/public-register.css')">
@endpush

@section('content')
<div class="reg-page">
    <div class="reg-card">
        {{-- แถบบอกว่ากำลังลงทะเบียนกิจกรรมไหน + ทางกลับไปหน้ารายละเอียด
             ต่อเนื่องจากหน้าก่อนหน้า ไม่ใช่ขึ้นหน้าใหม่ที่ไม่บอกว่ามาจากไหน --}}
        <a class="reg-back-link" href="{{ $config['urls']['activity'] }}">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            <span>กลับไปรายละเอียดกิจกรรม</span>
        </a>

        <div class="reg-pane is-center" id="reg-pane">

            {{-- หน้าจอ 1 — ตรวจสอบสิทธิ์ --}}
            <section class="reg-screen-check" data-screen="check">
                {{-- ชื่อกิจกรรมอยู่บนสุดของทุกทางเข้า เพื่อให้รู้ว่ากำลังลงทะเบียนอะไรตั้งแต่วินาทีแรก --}}
                <div class="reg-activity-card is-lead">
                    <div class="reg-activity-thumb">
                        @if($config['activity']['image'])
                            <img src="{{ $config['activity']['image'] }}" alt="">
                        @endif
                    </div>
                    <div class="reg-activity-info">
                        <span class="reg-activity-title">{{ $config['activity']['title'] }}</span>
                        <span>{{ $config['activity']['scheduleLabel'] ?: '-' }}</span>
                        <span>{{ $config['activity']['isFree'] ? 'เข้าร่วมฟรี ไม่มีค่าใช้จ่าย' : 'ค่าลงทะเบียน '.number_format($config['activity']['fee']).' บาท / ท่าน' }}</span>
                    </div>
                </div>

                <div class="reg-heading">
                    <span class="reg-title">ลงทะเบียนเข้าร่วมกิจกรรม</span>
                    <span class="reg-subtitle">กรุณากรอกเบอร์โทรศัพท์หรืออีเมล เพื่อตรวจสอบสิทธิ์การลงทะเบียน</span>
                </div>
                <div class="reg-check-body">
                    @if($lineError)
                        <p class="reg-error-text is-block">{{ $lineError }}</p>
                    @endif

                    <div class="reg-field">
                        <label for="reg-contact">เบอร์โทรศัพท์ หรือ อีเมล</label>
                        <input id="reg-contact" class="reg-input is-bright reg-check-input" type="text"
                               inputmode="email" autocomplete="tel email"
                               placeholder="081-234-5678 หรือ you@example.com">
                        <span class="reg-error-text" id="reg-contact-error" hidden>กรุณากรอกเบอร์โทรศัพท์หรืออีเมลให้ถูกต้อง</span>
                    </div>
                    <button type="button" class="reg-btn-primary" id="reg-check-btn" disabled>ตรวจสอบและไปต่อ</button>

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
                                    <button type="submit" class="reg-line-switch">เปลี่ยนบัญชี</button>
                                </form>
                            </div>
                            <button type="button" class="reg-btn-outline" id="reg-line-continue">ใช้บัญชีนี้ลงทะเบียนต่อ</button>
                        @else
                            <a class="reg-btn-line" href="{{ $config['line']['loginUrl'] }}">
                                <span class="reg-line-badge">L</span>
                                <span>เข้าสู่ระบบด้วย LINE</span>
                            </a>
                            <span class="reg-line-hint">เชื่อมบัญชี LINE แล้วระบบจะกรอกชื่อให้อัตโนมัติ และแจ้งผลการลงทะเบียนกลับทาง LINE</span>
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
                <div class="reg-booking-rows">
                    <span class="reg-group-label">รายละเอียดการจอง</span>
                    <dl class="reg-booking-rows" id="reg-found-rows" style="margin:0"></dl>
                </div>
            </section>

            {{-- หน้าจอ 3 — กรอกข้อมูลผู้ลงทะเบียนหลัก --}}
            <section class="reg-screen-form" data-screen="form" hidden>
                <div class="reg-activity-card">
                    <div class="reg-activity-thumb">
                        @if($config['activity']['image'])
                            <img src="{{ $config['activity']['image'] }}" alt="">
                        @endif
                    </div>
                    <div class="reg-activity-info">
                        <span class="reg-activity-title">{{ $config['activity']['title'] }}</span>
                        <span>{{ $config['activity']['scheduleLabel'] ?: '-' }}</span>
                        <span>{{ $config['activity']['isFree'] ? 'เข้าร่วมฟรี ไม่มีค่าใช้จ่าย' : 'ค่าลงทะเบียน '.number_format($config['activity']['fee']).' บาท / ท่าน' }}</span>
                    </div>
                </div>

                <span class="reg-section-title">ข้อมูลผู้ลงทะเบียนหลัก</span>

                <div class="reg-form-fields">
                    <div class="reg-field">
                        <label for="reg-name"><span>ชื่อ - นามสกุล</span><span class="reg-star">*</span></label>
                        <input id="reg-name" class="reg-input" type="text" maxlength="160" autocomplete="name" placeholder="เช่น สมหญิง รักธรรมชาติ">
                    </div>
                    <div class="reg-field">
                        <label for="reg-phone"><span>เบอร์โทรศัพท์</span><span class="reg-star">*</span></label>
                        <input id="reg-phone" class="reg-input" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" placeholder="081-234-5678">
                        <span class="reg-error-text" id="reg-phone-error" hidden>กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก</span>
                    </div>
                    @if($config['fields']['email']['enabled'])
                        <div class="reg-field">
                            <label for="reg-email"><span>อีเมล</span>@if($config['fields']['email']['required'])<span class="reg-star">*</span>@endif</label>
                            <input id="reg-email" class="reg-input" type="email" maxlength="160" autocomplete="email" placeholder="you@example.com">
                        </div>
                    @endif

                    @if($config['fields']['age_range']['enabled'] || $config['fields']['occupation']['enabled'])
                        <div class="reg-field-grid{{ ($config['fields']['age_range']['enabled'] && $config['fields']['occupation']['enabled']) ? '' : ' is-single' }}">
                            @if($config['fields']['age_range']['enabled'])
                                <div class="reg-field">
                                    <label for="reg-age"><span>ช่วงอายุ</span>@if($config['fields']['age_range']['required'])<span class="reg-star">*</span>@endif</label>
                                    <select id="reg-age" class="reg-select">
                                        <option value="">เลือกช่วงอายุ</option>
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
                                        <option value="">เลือกอาชีพ</option>
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
                                <option value="">เลือกช่องทาง</option>
                                @foreach($config['options']['sources'] as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="reg-field">
                        <label for="reg-note">หมายเหตุ (ถ้ามี)</label>
                        <input id="reg-note" class="reg-input" type="text" maxlength="255" placeholder="เช่น แพ้อาหารบางชนิด">
                    </div>

                    @if(count($config['rounds']) > 0)
                        <div class="reg-field">
                            <label for="reg-round"><span>รอบที่ต้องการสมัคร</span><span class="reg-star">*</span></label>
                            <select id="reg-round" class="reg-select">
                                <option value="">เลือกรอบกิจกรรม</option>
                                @foreach($config['rounds'] as $round)
                                    <option value="{{ $round['id'] }}" @disabled($round['seatsLeft'] === 0)>
                                        {{ $round['label'] }}{{ $round['seatsLeft'] !== null ? ' · เหลือ '.$round['seatsLeft'].' ที่นั่ง' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($config['maxSeats'] > 1)
                        <div class="reg-field">
                            <label id="reg-seat-heading">จำนวนที่นั่งที่ต้องการ</label>
                            <div class="reg-seat-box" role="group" aria-labelledby="reg-seat-heading">
                                <div class="reg-seat-text">
                                    <span class="reg-seat-label" id="reg-seat-label">มาคนเดียว</span>
                                    <span class="reg-seat-note" id="reg-seat-note"></span>
                                </div>
                                <div class="reg-stepper">
                                    <button type="button" id="reg-seat-minus" aria-label="ลดจำนวน">−</button>
                                    <span class="reg-seat-count" id="reg-seat-count">1</span>
                                    <button type="button" id="reg-seat-plus" aria-label="เพิ่มจำนวน">+</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <label class="reg-consent">
                        <input type="checkbox" id="reg-consent">
                        <span>ข้าพเจ้ายอมรับ@if($config['links']['terms'])<a href="{{ $config['links']['terms'] }}" target="_blank" rel="noopener">เงื่อนไขการเข้าร่วม</a>@else<b style="font-weight:500">เงื่อนไขการเข้าร่วม</b>@endif และ@if($config['links']['privacy'])<a href="{{ $config['links']['privacy'] }}" target="_blank" rel="noopener">นโยบายความเป็นส่วนตัว</a>@else<b style="font-weight:500">นโยบายความเป็นส่วนตัว</b>@endif</span>
                    </label>
                </div>
            </section>

            {{-- หน้าจอ 5 — ชำระเงิน --}}
            <section class="reg-screen-pay" data-screen="pay" hidden>
                <div class="reg-heading">
                    <span class="reg-section-title">ชำระค่าลงทะเบียน</span>
                    <span class="reg-subtitle">ชำระภายใน 30 นาที ระบบจะยืนยันที่นั่งเมื่อได้รับเงิน</span>
                </div>

                <dl class="reg-pay-summary" style="margin:0">
                    <div class="reg-pay-row">
                        <dt id="reg-pay-fee-label">ค่าลงทะเบียน</dt>
                        <dd id="reg-pay-fee-value"></dd>
                    </div>
                    <div class="reg-pay-row">
                        <dt>ค่าธรรมเนียมระบบ</dt>
                        <dd>0 ฿</dd>
                    </div>
                    <div class="reg-pay-row">
                        <dt>ส่วนลด</dt>
                        <dd>0 ฿</dd>
                    </div>
                    <hr>
                    <div class="reg-pay-total">
                        <dt>ยอดชำระทั้งสิ้น</dt>
                        <dd id="reg-pay-total"></dd>
                    </div>
                </dl>

                @if($config['payment']['qrUrl'])
                    <div class="reg-method-tabs" role="tablist" aria-label="วิธีชำระเงิน">
                        <button type="button" class="reg-method-tab is-active" id="reg-tab-qr" role="tab" aria-selected="true">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z"/></svg>
                            <span>สแกน QR</span>
                        </button>
                        <button type="button" class="reg-method-tab" id="reg-tab-bank" role="tab" aria-selected="false">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18M5 10V7l7-4 7 4v3M4 10v9h16v-9M9 14v5m6-5v5"/></svg>
                            <span>โอนเข้าบัญชี</span>
                        </button>
                    </div>

                    <div class="reg-qr-box" id="reg-qr-panel">
                        <span class="reg-qr-title">สแกนคิวอาร์เพื่อชำระเงิน</span>
                        <div class="reg-qr-image">
                            <img src="{{ $config['payment']['qrUrl'] }}" alt="QR Code สำหรับชำระเงิน">
                        </div>
                        <span class="reg-qr-expire" id="reg-qr-expire">คิวอาร์หมดอายุใน 30:00 นาที</span>
                        <button type="button" class="reg-btn-outline reg-qr-save" id="reg-qr-save">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                            <span>บันทึกคิวอาร์</span>
                        </button>
                    </div>
                @endif

                @if($config['payment']['accountNumber'])
                    <dl class="reg-bank-box" id="reg-bank-panel" style="margin:0" @if($config['payment']['qrUrl']) hidden @endif>
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
                        <div class="reg-bank-row">
                            <dt>ยอดที่ต้องโอน</dt>
                            <div class="reg-bank-value"><dd class="num" id="reg-bank-amount"></dd></div>
                        </div>
                        <span class="reg-copied-pill" id="reg-copied-pill" hidden>คัดลอกแล้ว วางในแอปธนาคารได้เลย</span>
                    </dl>
                @endif

                <div class="reg-slip">
                    <div class="reg-slip-heading">
                        <span class="reg-group-label">แนบสลิปการโอน</span>
                        <span class="reg-slip-hint">ช่วยให้ยืนยันเร็วขึ้น</span>
                    </div>
                    <input type="file" id="reg-slip-input" accept="image/jpeg,image/png,image/webp" hidden>
                    <button type="button" class="reg-dropzone" id="reg-dropzone">
                        <span class="reg-dropzone-icon">
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                        </span>
                        <span class="reg-dropzone-label">แตะเพื่อเลือกรูปสลิป</span>
                        <span class="reg-dropzone-hint">ถ่ายจากมือถือได้เลย · JPG หรือ PNG</span>
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
                    <span class="reg-slip-note">โอนแล้วกดยืนยันด้านล่าง เจ้าหน้าที่จะตรวจสอบให้ภายใน 1 วันทำการ แล้วแจ้งกลับทาง LINE</span>
                </div>
            </section>

            {{-- หน้าจอ 6 — สำเร็จ --}}
            <section class="reg-screen-done" data-screen="done" hidden>
                <div class="reg-done-hero">
                    <span class="reg-check-circle">
                        <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5 9 17.5 20 6.5"/></svg>
                    </span>
                    <h2>ลงทะเบียนสำเร็จแล้ว!</h2>
                    <p>ยินดีที่จะได้พบกันนะคะ เก็บภาพรายละเอียดการจองนี้ไว้ แล้วนำมาแสดงที่หน้างานได้เลย</p>
                    <span class="reg-code-pill" id="reg-done-code"></span>
                </div>

                <div class="reg-done-box" id="reg-done-rows"></div>

                <button type="button" class="reg-btn-outline reg-download-btn" id="reg-download">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                    <span>ดาวน์โหลดรายละเอียดการจอง</span>
                </button>
            </section>
        </div>

        <footer class="reg-footer" id="reg-footer" hidden>
            <div class="reg-footer-note" id="reg-footer-note" hidden>
                <span class="reg-footer-note-label" id="reg-footer-note-label"></span>
                <span class="reg-footer-note-value" id="reg-footer-note-value"></span>
            </div>
            <p class="reg-footer-error" id="reg-footer-error" aria-live="polite"></p>
            <button type="button" class="reg-btn-primary" id="reg-primary-btn"></button>
            <button type="button" class="reg-btn-secondary" id="reg-secondary-btn" hidden></button>
        </footer>
    </div>

    {{-- หน้าจอ 4 — popup ผู้ร่วมเพิ่ม --}}
    <div class="reg-modal" id="reg-guest-modal" hidden role="dialog" aria-modal="true" aria-labelledby="reg-guest-title">
        <div class="reg-modal-card">
            <div class="reg-modal-head">
                <div class="reg-modal-titles">
                    <h2 id="reg-guest-title">ข้อมูลผู้ร่วมเพิ่ม</h2>
                    <p id="reg-guest-subtitle"></p>
                </div>
                <button type="button" class="reg-modal-close" id="reg-guest-close" aria-label="ปิด">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="reg-modal-body">
                <div class="reg-guest-progress" id="reg-guest-progress"></div>
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
                <div class="reg-guest-saved" id="reg-guest-saved" hidden>
                    <span class="reg-guest-saved-title">ผู้ร่วมที่ระบุแล้ว</span>
                    <div id="reg-guest-saved-list"></div>
                </div>
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
