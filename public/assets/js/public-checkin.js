/* เช็กอินหน้างาน — แยกเป็นทีละหน้าจอ: กรอกเบอร์ → (เลือกชื่อถ้ามีหลายคน) → ผลลัพธ์
   เบอร์ที่มีชื่อเดียวไม่ต้องให้กดเลือกซ้ำ ระบบเช็กอินให้เลยแล้วขึ้นผลลัพธ์
   ทุกหน้าจออยู่ใน DOM ตั้งแต่แรก ที่นี่แค่สลับว่าจะให้เห็นอันไหน */
(function () {
    'use strict';

    const config = window.TFC_PUBLIC_CHECKIN;
    const form = document.getElementById('public-checkin-form');
    if (!config || !form) return;

    const steps = {
        phone: document.getElementById('ck-step-phone'),
        people: document.getElementById('ck-step-people'),
        done: document.getElementById('ck-step-done')
    };

    const phone = document.getElementById('checkin-phone');
    const lookupButton = document.getElementById('checkin-lookup');
    const backButton = document.getElementById('checkin-back');
    const message = document.getElementById('checkin-message');
    const peopleMessage = document.getElementById('checkin-people-message');
    const peopleSub = document.getElementById('checkin-people-sub');
    const nameList = document.getElementById('checkin-name-list');
    const doneTitle = document.getElementById('checkin-done-title');
    const doneName = document.getElementById('checkin-done-name');
    const doneTime = document.getElementById('checkin-done-time');
    let verifiedPhone = '';

    function csrfToken() {
        return form.querySelector('input[name="_token"]').value;
    }

    function normalizedPhone() {
        return phone.value.replace(/\D/g, '').slice(0, 10);
    }

    function showStep(name) {
        Object.keys(steps).forEach(function (key) { steps[key].hidden = key !== name; });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function setMessage(target, text, type) {
        target.textContent = text || '';
        target.className = 'registration-message' + (type ? ' is-' + type : '');
    }

    /* จำข้อความเดิมไว้ตั้งแต่ครั้งแรกที่กด แล้วคืนค่านั้นตอนเลิกรอ — ไม่ต้องส่งข้อความเดิมกลับมาทุกที่ */
    function setBusy(button, busy, busyLabel) {
        if (busy && !button.dataset.label) button.dataset.label = button.textContent;
        button.disabled = busy;
        button.textContent = busy ? busyLabel : button.dataset.label;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function jsonRequest(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify(body)
        });
        const data = await response.json().catch(function () { return {}; });

        if (!response.ok) {
            const firstError = Object.values(data.errors || {}).flat()[0];
            throw new Error(firstError || data.message || 'ไม่สามารถดำเนินการได้ กรุณาลองใหม่');
        }

        return data;
    }

    /* moment มาจากเซิร์ฟเวอร์แล้วในรูป "16:42 น. · 17 ส.ค. 2569" — ไม่จัดรูปแบบเองที่นี่
       เพราะเครื่องที่ตั้งเวลาผิดจะแสดงคนละเวลากับที่บันทึกไว้จริง */
    function showDone(title, name, moment) {
        doneTitle.textContent = title;
        doneName.textContent = name;
        doneTime.textContent = moment || '';
        showStep('done');
    }

    function renderRegistrations(registrations) {
        nameList.innerHTML = registrations.map(function (registration) {
            const checkedIn = Boolean(registration.checkedIn);
            return '<div class="checkin-name-row' + (checkedIn ? ' is-done' : '') + '" data-registration-row="' + escapeHtml(registration.code) + '">' +
                '<span class="checkin-person-icon" aria-hidden="true">' + (checkedIn ? '✓' : '•') + '</span>' +
                '<strong>' + escapeHtml(registration.name) + '</strong>' +
                '<button type="button" data-checkin-code="' + escapeHtml(registration.code) + '"' + (checkedIn ? ' disabled' : '') + '>' +
                    (checkedIn ? 'เช็กอินแล้ว' : 'เช็กอิน') +
                '</button>' +
                '</div>';
        }).join('');
    }

    /* เบอร์ที่มีชื่อเดียว — เช็กอินให้เลย ไม่ต้องให้กดยืนยันชื่อตัวเองซ้ำอีกครั้ง */
    async function checkInOnly(registration) {
        if (registration.checkedIn) {
            /* เคยเช็กอินไว้แล้ว — ไม่ใช่ข้อผิดพลาด บอกให้รู้ว่าเรียบร้อยอยู่แล้ว
               พร้อมเวลาที่เช็กอินไว้เดิม ไม่ใช่เวลาที่เพิ่งกด */
            showDone('คุณเช็กอินเรียบร้อยแล้ว', registration.name, registration.checkedInAt);

            return;
        }

        const data = await jsonRequest(config.storeUrl, {
            phone: verifiedPhone,
            registration_code: registration.code
        });

        showDone('เช็กอินเรียบร้อยแล้ว', registration.name, (data.registration || {}).checkedInAt);
    }

    async function lookup() {
        const value = normalizedPhone();
        phone.value = value;
        verifiedPhone = '';

        if (!/^0[689]\d{8}$/.test(value)) {
            setMessage(message, 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก', 'error');
            phone.focus();

            return;
        }

        setBusy(lookupButton, true, 'กำลังตรวจสอบ…');
        setMessage(message, '', '');

        try {
            const data = await jsonRequest(config.lookupUrl, { phone: value });
            const registrations = data.registrations || [];

            /* ไม่พบ = อยู่หน้าเดิม บอกตรงนั้นเลย ดีกว่าพาไปหน้าว่าง ๆ แล้วให้ย้อนกลับเอง */
            if (registrations.length === 0) {
                setMessage(message, 'ไม่พบรายชื่อที่ลงทะเบียนด้วยเบอร์นี้ กรุณาตรวจสอบเบอร์อีกครั้ง', 'error');

                return;
            }

            verifiedPhone = value;

            if (registrations.length === 1) {
                await checkInOnly(registrations[0]);

                return;
            }

            peopleSub.textContent = 'เบอร์ ' + value + ' มีผู้ลงทะเบียน ' + registrations.length + ' คน — กดเช็กอินทีละคน';
            renderRegistrations(registrations);
            setMessage(peopleMessage, '', '');
            showStep('people');
        } catch (error) {
            setMessage(message, error.message, 'error');
        } finally {
            setBusy(lookupButton, false, '');
        }
    }

    phone.addEventListener('input', function () {
        if (normalizedPhone() !== verifiedPhone) {
            verifiedPhone = '';
            setMessage(message, '', '');
        }
    });

    phone.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookup();
        }
    });

    lookupButton.addEventListener('click', lookup);

    backButton.addEventListener('click', function () {
        verifiedPhone = '';
        setMessage(message, '', '');
        showStep('phone');
        phone.focus({ preventScroll: true });
    });

    /* หน้าเลือกชื่อ — เช็กอินได้ทีละคนโดยไม่ต้องออกจากหน้า เพราะคนจองหลายที่นั่ง
       มักเช็กอินให้ทุกคนในคราวเดียว การเด้งไปหน้าผลลัพธ์ทุกครั้งจะต้องเดินกลับมาใหม่ */
    nameList.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-checkin-code]');
        if (!button || button.disabled || !verifiedPhone) return;

        setBusy(button, true, 'กำลังบันทึก…');
        setMessage(peopleMessage, '', '');

        try {
            const data = await jsonRequest(config.storeUrl, {
                phone: verifiedPhone,
                registration_code: button.dataset.checkinCode
            });
            const row = button.closest('[data-registration-row]');
            row.classList.add('is-done');
            row.querySelector('.checkin-person-icon').textContent = '✓';
            button.dataset.label = 'เช็กอินแล้ว';
            setBusy(button, false, '');
            button.disabled = true;
            setMessage(peopleMessage, data.message, 'success');
        } catch (error) {
            setBusy(button, false, '');
            setMessage(peopleMessage, error.message, 'error');
        }
    });

    phone.focus({ preventScroll: true });
})();
