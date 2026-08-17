@extends('public.activities.layout')

@section('title', 'เช็กอิน · '.$activity->name)

@section('content')
    {{-- ไม่มีหัวกิจกรรมด้านบน — หน้านี้ทำเรื่องเดียวคือเช็กอิน
         ชื่อกิจกรรมไปอยู่ที่หน้าจอผลลัพธ์ ซึ่งเป็นจุดที่ต้องยืนยันว่าเข้าร่วมงานไหน --}}
    @if(! $enabled)
        <section class="registration-card checkin-card is-closed">
            <h2>ยังไม่เปิดเช็กอิน</h2>
            <p>กิจกรรมนี้ยังไม่อยู่ในช่วงเช็กอิน หรือสิ้นสุดช่วงเช็กอินแล้ว</p>
            <a class="tr-primary-button" href="{{ route('public.activities.show', $activity->code) }}">กลับไปหน้ากิจกรรม</a>
        </section>
    @else
        {{-- สามหน้าจออยู่ใน DOM ตั้งแต่แรก JS แค่สลับว่าจะให้เห็นอันไหน
             ทีละหน้าจอเพราะทำบนมือถือหน้างาน — เห็นสิ่งที่ต้องทำอย่างเดียวไม่มีอะไรให้ลังเล --}}
        <section class="registration-card checkin-card" id="checkin-form">
            <form id="public-checkin-form" novalidate>
                @csrf

                {{-- หน้าที่ 1 — กรอกเบอร์โทรศัพท์หรืออีเมล
                     ช่องเดียวรับทั้งสองแบบ หน้างานคนจำไม่ได้ว่าลงทะเบียนไว้ด้วยอะไร
                     type="text" ไม่ใช่ "tel" เพราะต้องพิมพ์อีเมลได้ด้วย --}}
                <div class="ck-step" id="ck-step-phone">
                    <h2 class="ck-title">กรอกเบอร์โทรศัพท์เพื่อเช็กอิน</h2>
                    <p class="ck-sub">ใช้เบอร์โทรศัพท์หรืออีเมลที่ท่านใช้ลงทะเบียนกิจกรรมไว้</p>

                    <div class="registration-field">
                        <label for="checkin-phone">เบอร์โทรศัพท์ หรือ อีเมล <span>*</span></label>
                        <input id="checkin-phone" name="contact" type="text" inputmode="email" maxlength="160"
                               autocomplete="tel" autocapitalize="off" autocorrect="off" spellcheck="false"
                               placeholder="08X-XXX-XXXX หรือ name@email.com" required>
                        <p class="registration-message" id="checkin-message" aria-live="polite"></p>
                    </div>

                    <button type="button" class="registration-submit" id="checkin-lookup">ตรวจสอบ</button>
                </div>

                {{-- หน้าที่ 2 — มีหลายรายชื่อในเบอร์เดียว (จองหลายที่นั่ง) จึงต้องเลือกเอง
                     ถ้ามีชื่อเดียว JS จะข้ามหน้านี้ไปเช็กอินให้เลย --}}
                <div class="ck-step" id="ck-step-people" hidden>
                    <button type="button" class="ck-back" id="checkin-back">เปลี่ยนเบอร์โทรศัพท์</button>
                    <h2 class="ck-title">เลือกรายชื่อเพื่อเช็กอิน</h2>
                    <p class="ck-sub" id="checkin-people-sub"></p>

                    <div class="checkin-name-list" id="checkin-name-list"></div>
                    <p class="registration-message" id="checkin-people-message" aria-live="polite"></p>
                </div>

                {{-- หน้าที่ 3 — ผลลัพธ์ · เป็นที่เดียวที่บอกชื่อกิจกรรม จึงต้องครบพอให้ยืนยันได้ว่ามาถูกงาน --}}
                <div class="ck-step" id="ck-step-done" hidden>
                    {{-- เรียงจากผลลัพธ์ → คำต้อนรับ → ชื่องาน แล้วปิดท้ายด้วยสลิปยืนยันว่าใคร เวลาไหน
                         สองบรรทัดล่างเป็นข้อมูลอ้างอิง จึงแยกกล่องออกจากข้อความต้อนรับ --}}
                    <div class="registration-success ck-done">
                        <span class="registration-success-icon">✓</span>
                        <h2 id="checkin-done-title">เช็กอินเรียบร้อยแล้ว</h2>
                        <p class="ck-done-welcome">ยินดีต้อนรับเข้าสู่กิจกรรม</p>
                        <p class="ck-done-activity">{{ $activity->name }}</p>

                        <dl class="ck-done-slip">
                            <div class="ck-done-slip-row">
                                <dt>ผู้เข้าร่วม</dt>
                                <dd id="checkin-done-name"></dd>
                            </div>
                            <div class="ck-done-slip-row">
                                <dt>เวลาเช็กอิน</dt>
                                <dd id="checkin-done-time"></dd>
                            </div>
                        </dl>
                    </div>
                    <a class="registration-cta" href="{{ route('public.activities.show', $activity->code) }}">กลับไปหน้ากิจกรรม</a>
                </div>
            </form>
        </section>
    @endif
@endsection

@push('page-script')
    @if($enabled)
        <script>
            window.TFC_PUBLIC_CHECKIN = @json(['lookupUrl' => $lookupUrl, 'storeUrl' => $storeUrl]);
        </script>
        <script src="@assetv('assets/js/public-checkin.js')" defer></script>
    @endif
@endpush
