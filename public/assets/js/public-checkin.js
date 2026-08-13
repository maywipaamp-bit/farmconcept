(function () {
    'use strict';

    const config = window.TFC_PUBLIC_CHECKIN;
    const form = document.getElementById('public-checkin-form');
    if (!config || !form) return;

    const phone = document.getElementById('checkin-phone');
    const lookupButton = document.getElementById('checkin-lookup');
    const message = document.getElementById('checkin-message');
    const results = document.getElementById('checkin-results');
    const nameList = document.getElementById('checkin-name-list');
    let verifiedPhone = '';

    function csrfToken() {
        return form.querySelector('input[name="_token"]').value;
    }

    function normalizedPhone() {
        return phone.value.replace(/\D/g, '').slice(0, 10);
    }

    function setMessage(text, type) {
        message.textContent = text || '';
        message.className = 'registration-message' + (type ? ' is-' + type : '');
    }

    function setBusy(button, busy, label) {
        if (busy) button.dataset.label = button.textContent;
        button.disabled = busy;
        button.textContent = busy ? label : (button.dataset.label || button.textContent);
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

    function renderRegistrations(registrations) {
        nameList.innerHTML = registrations.map(function (registration) {
            const checkedIn = Boolean(registration.checkedIn);
            return '<div class="checkin-name-row' + (checkedIn ? ' is-done' : '') + '" data-registration-row="' + escapeHtml(registration.code) + '">' +
                '<span class="checkin-person-icon" aria-hidden="true">' + (checkedIn ? '✓' : '•') + '</span>' +
                '<strong>' + escapeHtml(registration.name) + '</strong>' +
                '<button type="button" data-checkin-code="' + escapeHtml(registration.code) + '"' + (checkedIn ? ' disabled' : '') + '>' +
                    (checkedIn ? 'Check-in แล้ว' : 'Check-in') +
                '</button>' +
                '</div>';
        }).join('');
        results.hidden = false;
    }

    async function lookup() {
        const value = normalizedPhone();
        phone.value = value;
        verifiedPhone = '';
        results.hidden = true;

        if (!/^0[689]\d{8}$/.test(value)) {
            setMessage('กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก', 'error');
            phone.focus();
            return;
        }

        setBusy(lookupButton, true, 'กำลังค้นหา…');
        setMessage('', '');

        try {
            const data = await jsonRequest(config.lookupUrl, { phone: value });
            verifiedPhone = value;
            renderRegistrations(data.registrations || []);
            setMessage(data.message, 'success');
            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            setMessage(error.message, 'error');
        } finally {
            setBusy(lookupButton, false, 'ค้นหารายชื่อ');
        }
    }

    phone.addEventListener('input', function () {
        if (normalizedPhone() !== verifiedPhone) {
            verifiedPhone = '';
            results.hidden = true;
            setMessage('', '');
        }
    });

    phone.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookup();
        }
    });

    lookupButton.addEventListener('click', lookup);

    nameList.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-checkin-code]');
        if (!button || button.disabled || !verifiedPhone) return;

        setBusy(button, true, 'กำลังบันทึก…');
        setMessage('', '');

        try {
            const data = await jsonRequest(config.storeUrl, {
                phone: verifiedPhone,
                registration_code: button.dataset.checkinCode
            });
            const row = button.closest('[data-registration-row]');
            row.classList.add('is-done');
            row.querySelector('.checkin-person-icon').textContent = '✓';
            button.dataset.label = 'Check-in แล้ว';
            button.textContent = 'Check-in แล้ว';
            button.disabled = true;
            setMessage(data.message, 'success');
        } catch (error) {
            setBusy(button, false, 'Check-in');
            setMessage(error.message, 'error');
        }
    });

    window.setTimeout(function () {
        document.getElementById('checkin-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        phone.focus({ preventScroll: true });
    }, 120);
})();
