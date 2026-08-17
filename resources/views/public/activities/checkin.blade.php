@extends('public.activities.layout')

@section('title', 'Check-in · '.$activity->name)

@section('content')
    @php
        /* บรรทัดวันเวลาแบบย่อ "พ. 19 ส.ค. 69 · 13:00–16:00 น." — รูปแบบเดียวกับหน้าแบบประเมิน */
        $thDaysShort = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
        $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $firstRound = $activity->rounds->first();

        $dateLine = '';
        if ($activity->start_date) {
            $d = $activity->start_date;
            $dateLine = $thDaysShort[$d->dayOfWeek].' '.$d->day.' '.$thMonths[$d->month - 1].' '.(($d->year + 543) % 100);
            if ($firstRound) {
                $dateLine .= ' · '.substr((string) $firstRound->time_start, 0, 5).'–'.substr((string) $firstRound->time_end, 0, 5).' น.';
            }
        }
    @endphp

    {{-- หัวกิจกรรม — ให้รู้ว่ากำลัง Check-in กิจกรรมไหน ชุดเดียวกับหน้าแบบประเมิน --}}
    <section class="sv-head">
        <div class="sv-head-row">
            <h1 class="sv-head-name">{{ $activity->name }}</h1>
            @if($activity->has_fee && (float) $activity->fee > 0)
                <span class="sv-head-fee">{{ number_format((float) $activity->fee) }} ฿/ท่าน</span>
            @endif
        </div>
        @if($dateLine)
            <p class="sv-head-sub">{{ $dateLine }}</p>
        @endif
    </section>

    @if(! $enabled)
        <section class="registration-card is-closed">
            <h2>ยังไม่เปิด Check-in</h2>
            <p>กิจกรรมนี้ยังไม่อยู่ในช่วง Check-in หรือสิ้นสุดช่วง Check-in แล้ว</p>
            <a class="tr-primary-button" href="{{ route('public.activities.show', $activity->code) }}">กลับไปหน้ากิจกรรม</a>
        </section>
    @else
        <section class="registration-card checkin-card" id="checkin-form">
            <div class="registration-heading">
                <span class="registration-step">1</span>
                <div>
                    <h2>ยืนยันเบอร์โทรศัพท์เพื่อ Check-in</h2>
                    <p>ใช้เบอร์เดียวกับที่ลงทะเบียน แล้วเลือกรายชื่อผู้เข้าร่วม</p>
                </div>
            </div>

            <form id="public-checkin-form" novalidate>
                @csrf
                <div class="registration-field">
                    <label for="checkin-phone">เบอร์โทรศัพท์ <span>*</span></label>
                    <div class="registration-phone-row">
                        <input id="checkin-phone" name="phone" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" placeholder="08X-XXX-XXXX" required>
                        <button type="button" id="checkin-lookup">ค้นหารายชื่อ</button>
                    </div>
                    <p class="registration-message" id="checkin-message" aria-live="polite"></p>
                </div>

                <div class="checkin-results" id="checkin-results" hidden>
                    <div class="registration-heading is-sub">
                        <span class="registration-step">2</span>
                        <div>
                            <h2>เลือกรายชื่อเพื่อ Check-in</h2>
                            <p>กรณีจองหลายที่นั่ง ระบบจะแสดงรายชื่อทั้งหมดของเบอร์นี้</p>
                        </div>
                    </div>
                    <div class="checkin-name-list" id="checkin-name-list"></div>
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
