/* เช็กอินหน้างาน — ทีละหน้าจอ: กรอกเบอร์ → ยืนยัน/เลือกชื่อ → ผลลัพธ์
   ชื่อเดียว  → หน้ายืนยัน ให้ผู้เข้าร่วมกดปุ่มเช็กอินเอง
   หลายชื่อ  → หน้าเลือกรายชื่อ กดเช็กอินทีละคน
   ไม่เช็กอินให้อัตโนมัติในทั้งสองทาง เพราะเบอร์เดียวใช้กันทั้งบ้านเป็นเรื่องปกติ
   ผู้เข้าร่วมต้องได้เห็นชื่อก่อนว่าระบบจับถูกคน แล้วจึงกดยืนยันด้วยตัวเอง
   ทุกหน้าจออยู่ใน DOM ตั้งแต่แรก ที่นี่แค่สลับว่าจะให้เห็นอันไหน */
(function () {
    'use strict';

    const config = window.TFC_PUBLIC_CHECKIN;
    const form = document.getElementById('public-checkin-form');
    if (!config || !form) return;

    const steps = {
        phone: document.getElementById('ck-step-phone'),
        confirm: document.getElementById('ck-step-confirm'),
        people: document.getElementById('ck-step-people'),
        done: document.getElementById('ck-step-done')
    };

    const phone = document.getElementById('checkin-phone');
    const lookupButton = document.getElementById('checkin-lookup');
    const backButtons = document.querySelectorAll('[data-checkin-back]');
    const confirmName = document.getElementById('checkin-confirm-name');
    const confirmMessage = document.getElementById('checkin-confirm-message');
    const confirmButton = document.getElementById('checkin-confirm-btn');
    const message = document.getElementById('checkin-message');
    const peopleMessage = document.getElementById('checkin-people-message');
    const peopleSub = document.getElementById('checkin-people-sub');
    const nameList = document.getElementById('checkin-name-list');
    const doneTitle = document.getElementById('checkin-done-title');
    const doneName = document.getElementById('checkin-done-name');
    const doneTime = document.getElementById('checkin-done-time');
    let verifiedContact = '';

    function csrfToken() {
        return form.querySelector('input[name="_token"]').value;
    }

    /* ช่องเดียวรับได้ทั้งเบอร์และอีเมล — มี @ ถือเป็นอีเมล นอกนั้นตัดให้เหลือแต่ตัวเลข
       เกณฑ์เดียวกับ PublicCheckinLookupRequest ฝั่งเซิร์ฟเวอร์ */
    function normalizedContact() {
        const raw = phone.value.trim();

        return raw.includes('@') ? raw : raw.replace(/\D/g, '').slice(0, 10);
    }

    function isValidContact(value) {
        return /^0[689]\d{8}$/.test(value) || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
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

    /* คนที่กำลังจะเช็กอินในหน้ายืนยัน — เก็บไว้เพราะปุ่มเช็กอินอยู่คนละที่กับตอนค้นเจอ */
    let pendingRegistration = null;

    /* เบอร์ที่มีชื่อเดียว — พาไปหน้ายืนยันให้ผู้เข้าร่วมกดเช็กอินเอง
       ไม่เช็กอินให้อัตโนมัติ เพราะเบอร์เดียวใช้กันทั้งบ้านเป็นเรื่องปกติ
       ต้องให้เห็นชื่อก่อนว่าระบบจับถูกคน และการกดเองทำให้รู้ตัวว่าเช็กอินแล้วจริง */
    function askToConfirm(registration) {
        /* เช็กอินไปแล้วไม่มีอะไรให้กด ข้ามไปหน้าผลลัพธ์เลย */
        if (registration.checkedIn) {
            showDone('คุณเช็กอินเรียบร้อยแล้ว', registration.name, registration.checkedInAt);

            return;
        }

        pendingRegistration = registration;
        confirmName.textContent = registration.name;
        setMessage(confirmMessage, '', '');
        showStep('confirm');
    }

    async function submitConfirm() {
        if (!pendingRegistration) return;

        setBusy(confirmButton, true, 'กำลังเช็กอิน…');
        setMessage(confirmMessage, '', '');

        try {
            const data = await jsonRequest(config.storeUrl, {
                contact: verifiedContact,
                registration_code: pendingRegistration.code
            });

            showDone('เช็กอินเรียบร้อยแล้ว', pendingRegistration.name, (data.registration || {}).checkedInAt);
            pendingRegistration = null;
        } catch (error) {
            setMessage(confirmMessage, error.message, 'error');
        } finally {
            setBusy(confirmButton, false, '');
        }
    }

    async function lookup() {
        const value = normalizedContact();
        phone.value = value;
        verifiedContact = '';

        if (!isValidContact(value)) {
            setMessage(message, 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก หรืออีเมลที่ใช้ลงทะเบียน', 'error');
            phone.focus();

            return;
        }

        setBusy(lookupButton, true, 'กำลังตรวจสอบ…');
        setMessage(message, '', '');

        try {
            const data = await jsonRequest(config.lookupUrl, { contact: value });
            const registrations = data.registrations || [];

            /* ไม่พบ = อยู่หน้าเดิม บอกตรงนั้นเลย ดีกว่าพาไปหน้าว่าง ๆ แล้วให้ย้อนกลับเอง */
            if (registrations.length === 0) {
                setMessage(message, 'ไม่พบรายชื่อที่ลงทะเบียนด้วยข้อมูลนี้ กรุณาตรวจสอบอีกครั้ง', 'error');

                return;
            }

            verifiedContact = value;

            if (registrations.length === 1) {
                askToConfirm(registrations[0]);

                return;
            }

            peopleSub.textContent = value + ' มีผู้ลงทะเบียน ' + registrations.length + ' คน — กดเช็กอินทีละคน';
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
        if (normalizedContact() !== verifiedContact) {
            verifiedContact = '';
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

    backButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            verifiedContact = '';
            pendingRegistration = null;
            setMessage(message, '', '');
            showStep('phone');
            phone.focus({ preventScroll: true });
        });
    });

    confirmButton.addEventListener('click', submitConfirm);

    /* หน้าเลือกชื่อ — เช็กอินได้ทีละคนโดยไม่ต้องออกจากหน้า เพราะคนจองหลายที่นั่ง
       มักเช็กอินให้ทุกคนในคราวเดียว การเด้งไปหน้าผลลัพธ์ทุกครั้งจะต้องเดินกลับมาใหม่ */
    nameList.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-checkin-code]');
        if (!button || button.disabled || !verifiedContact) return;

        setBusy(button, true, 'กำลังบันทึก…');
        setMessage(peopleMessage, '', '');

        try {
            const data = await jsonRequest(config.storeUrl, {
                contact: verifiedContact,
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
