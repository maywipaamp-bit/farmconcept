(function () {
    'use strict';

    const config = window.TFC_PUBLIC_REGISTRATION;
    const form = document.getElementById('public-registration-form');
    if (!config || !form) return;

    const phone = document.getElementById('registration-phone');
    const checkButton = document.getElementById('registration-check-phone');
    const phoneMessage = document.getElementById('registration-phone-message');
    const details = document.getElementById('registration-details');
    const seatCount = document.getElementById('registration-seat-count');
    const names = document.getElementById('registration-names');
    const formMessage = document.getElementById('registration-form-message');
    const submitButton = document.getElementById('registration-submit');
    const success = document.getElementById('registration-success');
    const successMessage = document.getElementById('registration-success-message');
    let verifiedPhone = '';

    function csrfToken() {
        return form.querySelector('input[name="_token"]').value;
    }

    function normalizedPhone() {
        return phone.value.replace(/\D/g, '').slice(0, 10);
    }

    function setMessage(element, message, type) {
        element.textContent = message || '';
        element.className = 'registration-message' + (type ? ' is-' + type : '');
    }

    function setBusy(button, busy, label) {
        if (busy) button.dataset.label = button.textContent;
        button.disabled = busy;
        button.textContent = busy ? label : (button.dataset.label || button.textContent);
    }

    function renderNames() {
        const count = Number(seatCount?.value || 1);
        const oldValues = Array.from(names.querySelectorAll('input')).map(function (input) { return input.value; });
        names.innerHTML = Array.from({ length: count }, function (_, index) {
            const label = count > 1 ? 'ชื่อ–นามสกุล คนที่ ' + (index + 1) : 'ชื่อ–นามสกุล';
            const value = oldValues[index] || '';
            return '<div class="registration-field">' +
                '<label for="registration-name-' + index + '">' + label + ' <span>*</span></label>' +
                '<input id="registration-name-' + index + '" name="names[]" type="text" maxlength="160" autocomplete="name" value="' + escapeHtml(value) + '" placeholder="' + label + '" required>' +
                '</div>';
        }).join('');
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function jsonRequest(url, options) {
        const response = await fetch(url, {
            method: options.method || 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify(options.body || {})
        });
        const data = await response.json().catch(function () { return {}; });

        if (!response.ok) {
            const errors = data.errors || {};
            const firstError = Object.values(errors).flat()[0];
            throw new Error(firstError || data.message || 'ไม่สามารถดำเนินการได้ กรุณาลองใหม่');
        }

        return data;
    }

    async function checkPhone() {
        const value = normalizedPhone();
        phone.value = value;
        details.hidden = true;
        verifiedPhone = '';

        if (!/^0[689]\d{8}$/.test(value)) {
            setMessage(phoneMessage, 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก', 'error');
            phone.focus();
            return;
        }

        setBusy(checkButton, true, 'กำลังตรวจสอบ…');
        setMessage(phoneMessage, '', '');

        try {
            const result = await jsonRequest(config.checkPhoneUrl, { body: { phone: value } });
            verifiedPhone = value;
            details.hidden = false;
            setMessage(phoneMessage, result.message, 'success');
            renderNames();
            details.scrollIntoView({ behavior: 'smooth', block: 'start' });
            names.querySelector('input')?.focus({ preventScroll: true });
        } catch (error) {
            setMessage(phoneMessage, error.message, 'error');
        } finally {
            setBusy(checkButton, false, 'ตรวจสอบ');
        }
    }

    phone.addEventListener('input', function () {
        const value = normalizedPhone();
        if (value !== verifiedPhone) {
            verifiedPhone = '';
            details.hidden = true;
            setMessage(phoneMessage, '', '');
        }
    });
    phone.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            checkPhone();
        }
    });
    checkButton.addEventListener('click', checkPhone);
    seatCount?.addEventListener('change', renderNames);

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        setMessage(formMessage, '', '');

        if (!verifiedPhone || verifiedPhone !== normalizedPhone()) {
            setMessage(phoneMessage, 'กรุณาตรวจสอบเบอร์โทรศัพท์ก่อนดำเนินการต่อ', 'error');
            phone.focus();
            return;
        }

        if (!form.reportValidity()) return;

        const data = new FormData(form);
        const payload = {
            phone: verifiedPhone,
            seat_count: Number(data.get('seat_count') || 1),
            activity_round_id: data.get('activity_round_id') || null,
            names: data.getAll('names[]'),
            pdpa: data.get('pdpa') ? 1 : 0
        };

        setBusy(submitButton, true, 'กำลังบันทึก…');

        try {
            const result = await jsonRequest(config.storeUrl, { body: payload });
            details.hidden = true;
            phone.closest('.registration-field').hidden = true;
            success.hidden = false;
            successMessage.textContent = result.message + ' · ' + result.names.join(', ');
            success.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (error) {
            setMessage(formMessage, error.message, 'error');
        } finally {
            setBusy(submitButton, false, 'ยืนยันการลงทะเบียน');
        }
    });

    renderNames();

    if (document.getElementById('registration-form')?.dataset.open === 'true') {
        window.setTimeout(function () {
            document.getElementById('registration-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
            phone.focus({ preventScroll: true });
        }, 120);
    }
})();
