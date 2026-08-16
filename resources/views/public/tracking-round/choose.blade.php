@extends('public.activities.layout')

@section('title', 'เลือกชื่อของคุณ')

@section('content')
    <section class="detail-card tr-card">
        {{-- เบอร์เดียวเจอคนเดียวก็ยังต้องยืนยันชื่อ แต่ไม่ต้องพูดว่า "เลือก" ในเมื่อไม่มีอะไรให้เลือก --}}
        @if($people->count() > 1)
            <h1 class="tr-heading">พบ {{ $people->count() }} รายชื่อในเบอร์นี้</h1>
            <p class="tr-subheading">เบอร์ {{ $phone }} · เลือกชื่อของคุณ แล้วยืนยันด้วยชื่อจริง 5 ตัวอักษรแรก</p>
        @else
            <h1 class="tr-heading">ยืนยันตัวตน</h1>
            <p class="tr-subheading">เบอร์ {{ $phone }} · กรอกชื่อจริง 5 ตัวอักษรแรกเพื่อเข้าใช้งาน</p>
        @endif

        @error('name_prefix')<div class="tr-error" role="alert">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('public.tracking-round-qr.choose.submit') }}">
            @csrf

            <div class="tr-people">
                @foreach($people as $person)
                    {{-- ชื่อถูกปิดบังไว้ (สมห••• ใ•••) — ห้ามแสดงชื่อเต็มก่อนยืนยันตัวตน
                         ไม่งั้นแค่รู้เบอร์ของบ้านหนึ่งก็อ่านได้ว่ามีใครอยู่ในโครงการบ้าง --}}
                    <details class="tr-person" @if($selected === $person->id || ($loop->first && ! $selected)) open @endif>
                        <summary>
                            <span class="tr-person-avatar" aria-hidden="true">{{ mb_substr($person->name, 0, 1) }}</span>
                            <span class="tr-person-text">
                                <span class="tr-person-name">{{ $person->maskedName() }}</span>
                                <span class="tr-person-meta">
                                    {{ collect([$person->targetGroup?->name, $person->area?->name])->filter()->join(' · ') ?: 'กลุ่มตัวอย่าง' }}
                                </span>
                            </span>
                            <span class="tr-person-chevron" aria-hidden="true"></span>
                        </summary>

                        <div class="tr-person-confirm">
                            <label for="tr-prefix-{{ $person->id }}">ชื่อจริง 5 ตัวอักษรแรก</label>
                            <input type="text" id="tr-prefix-{{ $person->id }}" name="name_prefix"
                                   class="tr-prefix-input" maxlength="5" autocomplete="off" inputmode="text">
                            <button type="submit" name="participant_id" value="{{ $person->id }}"
                                    class="tr-primary-button">ยืนยันตัวตน</button>
                        </div>
                    </details>
                @endforeach
            </div>
        </form>

        <p class="tr-note">
            ไม่มีชื่อของคุณ
            <a href="{{ route('public.tracking-round-qr.register') }}">ลงทะเบียนเพิ่ม</a>
        </p>

        <p class="tr-note">
            <a href="{{ route('public.tracking-round-qr') }}">ใช้เบอร์อื่น</a>
        </p>
    </section>
@endsection
