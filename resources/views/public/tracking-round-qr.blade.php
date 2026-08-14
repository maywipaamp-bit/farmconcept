@extends('public.activities.layout')

@section('title', 'แบบประเมินติดตามสุขภาพ')

{{-- layout สาธารณะไม่มี meta csrf เพราะหน้าอื่นเป็น GET ล้วน — หน้านี้ต้อง POST ตอนบันทึกคำตอบ --}}
@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <section class="detail-card" style="padding: 20px 16px;">
        <div class="registration-heading">
            <span class="registration-step">1</span>
            <div>
                <h2>ยืนยันตัวตนก่อนทำแบบประเมิน</h2>
                <p>กรอกเบอร์โทรศัพท์และรหัสบุคคลที่อยู่บนใบยินยอมของคุณ ระบบจะแสดงเฉพาะรอบที่ถึงกำหนดของคุณเท่านั้น</p>
            </div>
        </div>

        <form id="tr-verify-form" novalidate>
            <div class="registration-field">
                <label for="tr-phone">เบอร์โทรศัพท์ <span>*</span></label>
                <input type="tel" id="tr-phone" inputmode="tel" autocomplete="tel" placeholder="08x-xxx-xxxx" required>
            </div>

            <div class="registration-field">
                <label for="tr-person-code">รหัสบุคคลบนใบยินยอม <span>*</span></label>
                <input type="text" id="tr-person-code" autocomplete="off" placeholder="เช่น PID-0001" required>
            </div>

            <button type="submit" class="registration-submit" id="tr-verify-submit">ตรวจสอบรอบที่ต้องทำ</button>
            <p class="registration-message" id="tr-message" role="status" aria-live="polite"></p>
        </form>

        <div id="tr-rounds" hidden>
            <div class="registration-heading is-sub">
                <span class="registration-step">2</span>
                <div>
                    <h2>รอบที่ถึงกำหนดของคุณ</h2>
                    <p>เลือกรอบที่ต้องการทำแบบประเมิน</p>
                </div>
            </div>
            <div class="checkin-name-list" id="tr-round-list"></div>
        </div>
    </section>
@endsection

@push('page-script')
<script>
(function () {
    var TOKEN = @json($token);
    var form = document.getElementById('tr-verify-form');
    var submit = document.getElementById('tr-verify-submit');
    var message = document.getElementById('tr-message');
    var panel = document.getElementById('tr-rounds');
    var list = document.getElementById('tr-round-list');

    var verified = null;

    function say(text, tone) {
        message.textContent = text;
        message.className = 'registration-message' + (tone ? ' is-' + tone : '');
    }

    function esc(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function thaiDate(iso) {
        if (!iso) return '';
        var months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        var p = iso.split('-').map(Number);
        return p[2] + ' ' + months[p[1] - 1] + ' ' + (p[0] + 543);
    }

    /* ชื่อรอบมาจากข้อมูลของผู้ตอบล้วน ๆ ไม่มี "3 เดือน / 6 เดือน" เขียนตายในหน้านี้ */
    function renderRounds(rounds) {
        panel.hidden = rounds.length === 0;

        list.innerHTML = rounds.map(function (r) {
            return '<div class="checkin-name-row">' +
                '<span class="checkin-person-icon">✓</span>' +
                '<strong>' + esc(r.name) + '<br><span style="color:var(--text-secondary);font-weight:400">ครบกำหนด ' +
                    esc(thaiDate(r.dueDate)) + ' · ' + esc(r.state) + '</span></strong>' +
                '<button type="button" data-round="' + r.id + '">ทำแบบประเมิน</button>' +
                '</div>';
        }).join('');
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var phone = document.getElementById('tr-phone').value.trim();
        var personCode = document.getElementById('tr-person-code').value.trim();

        if (!phone || !personCode) return say('กรุณากรอกเบอร์โทรศัพท์และรหัสบุคคลให้ครบ', 'error');

        submit.disabled = true;
        say('กำลังตรวจสอบ…');

        var params = new URLSearchParams({ token: TOKEN, phone: phone, person_code: personCode });

        fetch('{{ route('public.tracking-round-qr.verify') }}?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
            .then(function (res) {
                submit.disabled = false;

                if (!res.ok) {
                    panel.hidden = true;
                    return say(res.body.message || 'ยืนยันตัวตนไม่สำเร็จ', 'error');
                }

                verified = { phone: phone, personCode: personCode };
                say('สวัสดีคุณ ' + res.body.participant.name + ' · ' + res.body.message, 'success');
                renderRounds(res.body.rounds);
            })
            .catch(function () {
                submit.disabled = false;
                say('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ กรุณาลองใหม่', 'error');
            });
    });

    list.addEventListener('click', function (event) {
        var button = event.target.closest('[data-round]');
        if (!button || !verified) return;

        button.disabled = true;
        button.textContent = 'กำลังบันทึก…';

        fetch('{{ route('public.tracking-round-qr.submit') }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                token: TOKEN,
                phone: verified.phone,
                person_code: verified.personCode,
                round_id: Number(button.getAttribute('data-round'))
            })
        })
            .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
            .then(function (res) {
                if (!res.ok) {
                    button.disabled = false;
                    button.textContent = 'ทำแบบประเมิน';
                    return say(res.body.message || 'บันทึกไม่สำเร็จ', 'error');
                }

                button.textContent = 'บันทึกแล้ว';
                button.closest('.checkin-name-row').classList.add('is-done');
                say(res.body.message, 'success');
            })
            .catch(function () {
                button.disabled = false;
                button.textContent = 'ทำแบบประเมิน';
                say('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ', 'error');
            });
    });
})();
</script>
@endpush
