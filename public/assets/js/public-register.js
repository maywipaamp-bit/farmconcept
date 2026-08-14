(function () {
    'use strict';

    const config = window.TFC_REGISTER;
    if (!config) return;

    /* ---------- อ้างอิง element ---------- */

    const pane = document.getElementById('reg-pane');
    const screens = {
        check: document.querySelector('[data-screen="check"]'),
        found: document.querySelector('[data-screen="found"]'),
        form: document.querySelector('[data-screen="form"]'),
        pay: document.querySelector('[data-screen="pay"]'),
        done: document.querySelector('[data-screen="done"]')
    };

    const footer = document.getElementById('reg-footer');
    const footerNote = document.getElementById('reg-footer-note');
    const footerNoteLabel = document.getElementById('reg-footer-note-label');
    const footerNoteValue = document.getElementById('reg-footer-note-value');
    const footerError = document.getElementById('reg-footer-error');
    const primaryBtn = document.getElementById('reg-primary-btn');
    const secondaryBtn = document.getElementById('reg-secondary-btn');

    const contactInput = document.getElementById('reg-contact');
    const contactError = document.getElementById('reg-contact-error');
    const checkBtn = document.getElementById('reg-check-btn');

    const nameInput = document.getElementById('reg-name');
    const phoneInput = document.getElementById('reg-phone');
    const phoneError = document.getElementById('reg-phone-error');
    const emailInput = document.getElementById('reg-email');
    const ageSelect = document.getElementById('reg-age');
    const jobSelect = document.getElementById('reg-job');
    const sourceSelect = document.getElementById('reg-source');
    const noteInput = document.getElementById('reg-note');
    const roundSelect = document.getElementById('reg-round');
    const consentInput = document.getElementById('reg-consent');
    const seatLabel = document.getElementById('reg-seat-label');
    const seatNote = document.getElementById('reg-seat-note');
    const seatCount = document.getElementById('reg-seat-count');
    const seatMinus = document.getElementById('reg-seat-minus');
    const seatPlus = document.getElementById('reg-seat-plus');

    const guestModal = document.getElementById('reg-guest-modal');
    const guestSubtitle = document.getElementById('reg-guest-subtitle');
    const guestProgress = document.getElementById('reg-guest-progress');
    const guestName = document.getElementById('reg-guest-name');
    const guestAge = document.getElementById('reg-guest-age');
    const guestJob = document.getElementById('reg-guest-job');
    const guestSaved = document.getElementById('reg-guest-saved');
    const guestSavedList = document.getElementById('reg-guest-saved-list');
    const guestClose = document.getElementById('reg-guest-close');
    const guestBack = document.getElementById('reg-guest-back');
    const guestNext = document.getElementById('reg-guest-next');

    const tabQr = document.getElementById('reg-tab-qr');
    const tabBank = document.getElementById('reg-tab-bank');
    const qrPanel = document.getElementById('reg-qr-panel');
    const bankPanel = document.getElementById('reg-bank-panel');
    const qrExpire = document.getElementById('reg-qr-expire');
    const qrSave = document.getElementById('reg-qr-save');
    const copyBtn = document.getElementById('reg-copy-account');
    const copyIcon = document.getElementById('reg-copy-icon');
    const copiedPill = document.getElementById('reg-copied-pill');
    const bankAmount = document.getElementById('reg-bank-amount');
    const payFeeLabel = document.getElementById('reg-pay-fee-label');
    const payFeeValue = document.getElementById('reg-pay-fee-value');
    const payTotal = document.getElementById('reg-pay-total');
    const slipInput = document.getElementById('reg-slip-input');
    const dropzone = document.getElementById('reg-dropzone');
    const slipCard = document.getElementById('reg-slip-card');
    const slipName = document.getElementById('reg-slip-name');
    const slipRemove = document.getElementById('reg-slip-remove');

    const foundCode = document.getElementById('reg-found-code');
    const foundRows = document.getElementById('reg-found-rows');
    const doneCode = document.getElementById('reg-done-code');
    const doneRows = document.getElementById('reg-done-rows');
    const downloadBtn = document.getElementById('reg-download');

    /* ---------- สถานะของ flow ---------- */

    const state = {
        screen: 'check',
        seats: 1,
        guests: [],
        guestIndex: 0,
        booking: null,
        codes: [],
        slipFile: null,
        submitted: false
    };

    const ICONS = {
        activity: 'M8 2v4m8-4v4M3 10h18M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2',
        time: 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
        place: 'M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11m0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5',
        people: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8m14 10v-2a4 4 0 0 0-3-3.87',
        payment: 'M2 7h20v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zM2 11h20'
    };

    const COPY_PATH = 'M8 8V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-3M5 8h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2';
    const CHECK_PATH = 'M4 12.5 9 17.5 20 6.5';

    let countdownTimer = null;
    let copiedTimer = null;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function fmtBaht(amount) {
        return amount.toLocaleString('en-US') + ' ฿';
    }

    function totalAmount() {
        return config.payment.amountPerSeat * state.seats;
    }

    function extraSeats() {
        return Math.max(0, state.seats - 1);
    }

    function normalizePhone(value) {
        return String(value || '').replace(/\D/g, '').slice(0, 10);
    }

    function isValidPhone(value) {
        return /^0[689]\d{8}$/.test(value);
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function setBusy(button, busy, label) {
        if (busy) {
            button.dataset.label = button.textContent;
            button.dataset.busyLabel = label;
            button.disabled = true;
            button.textContent = label;
        } else {
            button.disabled = false;
            /* คืนข้อความเดิมเฉพาะตอนที่ยังโชว์ข้อความ "กำลัง…" อยู่
               ถ้าหน้าจอถัดไปเปลี่ยนข้อความปุ่มไปแล้ว ห้ามเขียนทับ */
            if (button.textContent === button.dataset.busyLabel && button.dataset.label) {
                button.textContent = button.dataset.label;
            }
            delete button.dataset.busyLabel;
        }
    }

    async function postJson(url, body) {
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
            const errors = data.errors || {};
            const firstError = Object.values(errors).flat()[0];
            const error = new Error(firstError || data.message || 'ไม่สามารถดำเนินการได้ กรุณาลองใหม่อีกครั้งนะคะ');
            error.status = response.status;
            throw error;
        }
        return data;
    }

    /* ---------- สลับหน้าจอ + footer ---------- */

    function showScreen(screen) {
        state.screen = screen;
        Object.keys(screens).forEach(function (key) {
            if (screens[key]) screens[key].hidden = key !== screen;
        });
        pane.classList.toggle('is-center', screen === 'check');
        footerError.textContent = '';
        renderFooter();
        pane.scrollTop = 0;
        if (screen === 'pay') startCountdown();
        else stopCountdown();
    }

    function renderFooter() {
        const screen = state.screen;

        if (screen === 'check') {
            footer.hidden = true;
            return;
        }

        footer.hidden = false;
        secondaryBtn.hidden = true;
        footerNote.hidden = true;
        primaryBtn.disabled = false;

        if (screen === 'found' || screen === 'done') {
            primaryBtn.textContent = 'ดูรายละเอียดกิจกรรม';
            secondaryBtn.textContent = 'กลับหน้าหลัก';
            secondaryBtn.hidden = false;
            return;
        }

        if (screen === 'form') {
            footerNote.hidden = false;
            footerNoteLabel.textContent = 'ยอดรวม ' + state.seats + ' ที่นั่ง';
            footerNoteValue.textContent = config.activity.isFree ? 'เข้าร่วมฟรี' : fmtBaht(totalAmount());

            if (extraSeats() > 0) {
                primaryBtn.textContent = 'ถัดไป · ระบุผู้ร่วม ' + extraSeats() + ' คน';
            } else {
                primaryBtn.textContent = config.payment.required ? 'ไปหน้าชำระเงิน' : 'ยืนยันการลงทะเบียน';
            }
            primaryBtn.disabled = !formUnlocked();
            return;
        }

        if (screen === 'pay') {
            footerNote.hidden = false;
            footerNoteLabel.textContent = 'ยอดชำระ';
            footerNoteValue.textContent = fmtBaht(totalAmount());
            primaryBtn.textContent = 'แจ้งชำระเงินแล้ว';
        }
    }

    /* ---------- หน้าจอ 1: ตรวจสอบสิทธิ์ ---------- */

    function classifyContact(raw) {
        const value = raw.trim();
        const digits = value.replace(/\D/g, '');
        if (isValidPhone(digits)) return { type: 'phone', value: digits };
        if (isValidEmail(value)) return { type: 'email', value: value.toLowerCase() };
        return null;
    }

    async function checkContact() {
        const contact = classifyContact(contactInput.value);
        if (!contact) {
            contactError.hidden = false;
            contactInput.classList.add('is-error');
            contactInput.focus();
            return;
        }

        setBusy(checkBtn, true, 'กำลังตรวจสอบ…');

        try {
            const result = await postJson(config.urls.check, { contact: contact.value });

            if (result.registered) {
                state.booking = result.booking;
                renderFound();
                showScreen('found');
            } else {
                if (contact.type === 'phone') phoneInput.value = contact.value;
                else if (emailInput) emailInput.value = contact.value;
                showScreen('form');
                syncSeatUi();
                nameInput.focus({ preventScroll: true });
            }
        } catch (error) {
            contactError.textContent = error.message;
            contactError.hidden = false;
            contactInput.classList.add('is-error');
        } finally {
            setBusy(checkBtn, false);
        }
    }

    /* ---------- เข้าสู่ระบบด้วย LINE ----------
       โปรไฟล์และการจองมาพร้อมหน้าตั้งแต่ฝั่งเซิร์ฟเวอร์แล้ว (อ่านจาก session)
       ตรงนี้จึงเหลือแค่พาไปหน้าจอที่ถูกต้องและเติมค่าที่รู้อยู่แล้วลงฟอร์ม */

    const line = config.line || {};
    const lineContinueBtn = document.getElementById('reg-line-continue');

    function fillFromLine() {
        const prefill = line.prefill || {};
        if (nameInput && !nameInput.value) nameInput.value = prefill.name || '';
        if (phoneInput && !phoneInput.value && prefill.phone) phoneInput.value = prefill.phone;
        if (emailInput && !emailInput.value && prefill.email) emailInput.value = prefill.email;
    }

    function goToFormWithLine() {
        fillFromLine();
        showScreen('form');
        syncSeatUi();
        /* เบอร์โทรคือช่องเดียวที่ LINE ให้ไม่ได้ ถ้ายังว่างให้เคอร์เซอร์รออยู่ตรงนั้นเลย */
        const target = phoneInput && !phoneInput.value ? phoneInput : nameInput;
        target.focus({ preventScroll: true });
    }

    if (lineContinueBtn) {
        lineContinueBtn.addEventListener('click', goToFormWithLine);
    }

    contactInput.addEventListener('input', function () {
        checkBtn.disabled = contactInput.value.trim() === '';
        contactError.hidden = true;
        contactError.textContent = 'กรุณากรอกเบอร์โทรศัพท์หรืออีเมลให้ถูกต้อง';
        contactInput.classList.remove('is-error');
    });

    contactInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !checkBtn.disabled) {
            event.preventDefault();
            checkContact();
        }
    });

    checkBtn.addEventListener('click', checkContact);

    /* ---------- หน้าจอ 2: ลงทะเบียนแล้ว ---------- */

    function bookingRows(booking) {
        return [
            ['กิจกรรม', booking.activityTitle],
            ['วันและเวลา', booking.scheduleLabel],
            ['สถานที่', booking.location],
            ['จำนวนที่นั่ง', booking.seatsLabel],
            ['การชำระเงิน', booking.paymentLabel]
        ];
    }

    function renderFound() {
        foundCode.textContent = state.booking.code;
        foundRows.innerHTML = bookingRows(state.booking).map(function (row) {
            return '<div class="reg-booking-row"><dt>' + escapeHtml(row[0]) + '</dt><dd>' + escapeHtml(row[1]) + '</dd></div>';
        }).join('');
    }

    /* ---------- หน้าจอ 3: กรอกข้อมูล ---------- */

    function formUnlocked() {
        return nameInput.value.trim() !== '' && consentInput.checked;
    }

    function syncSeatUi() {
        const extra = extraSeats();
        if (seatCount) {
            seatCount.textContent = state.seats;
            seatLabel.textContent = state.seats === 1 ? 'มาคนเดียว' : 'มาด้วยกัน ' + state.seats + ' คน';
            seatNote.textContent = extra > 0
                ? 'อีกขั้นตอนเดียว ขอชื่อเพื่อนอีก ' + extra + ' คน'
                : 'ชวนเพื่อนมาด้วยได้ถึง ' + config.maxSeats + ' คน';
            seatMinus.disabled = state.seats <= 1;
            seatPlus.disabled = state.seats >= config.maxSeats;
        }
        renderFooter();
    }

    if (seatMinus) {
        seatMinus.addEventListener('click', function () {
            state.seats = Math.max(1, state.seats - 1);
            state.guests.length = extraSeats();
            syncSeatUi();
        });
        seatPlus.addEventListener('click', function () {
            state.seats = Math.min(config.maxSeats, state.seats + 1);
            syncSeatUi();
        });
    }

    nameInput.addEventListener('input', renderFooter);
    consentInput.addEventListener('change', renderFooter);
    phoneInput.addEventListener('input', function () {
        phoneInput.value = normalizePhone(phoneInput.value);
        phoneError.hidden = true;
        phoneInput.classList.remove('is-error');
    });

    function validateForm() {
        const phone = normalizePhone(phoneInput.value);
        if (!isValidPhone(phone)) {
            phoneError.hidden = false;
            phoneInput.classList.add('is-error');
            phoneInput.focus();
            return false;
        }

        if (roundSelect && roundSelect.value === '') {
            footerError.textContent = 'กรุณาเลือกรอบกิจกรรมที่ต้องการสมัครก่อนนะคะ';
            roundSelect.focus();
            return false;
        }

        const requiredSelects = [
            [config.fields.age_range, ageSelect, 'กรุณาเลือกช่วงอายุก่อนนะคะ'],
            [config.fields.occupation, jobSelect, 'กรุณาเลือกอาชีพก่อนนะคะ'],
            [config.fields.source_channel, sourceSelect, 'กรุณาเลือกช่องทางที่ทราบข่าวก่อนนะคะ']
        ];
        for (const [field, element, message] of requiredSelects) {
            if (field.required && element && element.value === '') {
                footerError.textContent = message;
                element.focus();
                return false;
            }
        }

        if (config.fields.email.required && emailInput && !isValidEmail(emailInput.value.trim())) {
            footerError.textContent = 'กรุณากรอกอีเมลให้ถูกต้องนะคะ';
            emailInput.focus();
            return false;
        }

        return true;
    }

    /* ---------- หน้าจอ 4: popup ผู้ร่วมเพิ่ม ---------- */

    function openGuestModal(index) {
        state.guestIndex = index;
        const guest = state.guests[index];
        guestName.value = guest ? guest.name : '';
        if (guestAge) guestAge.value = guest ? guest.ageId : '';
        if (guestJob) guestJob.value = guest ? guest.jobId : '';
        renderGuestModal();
        guestModal.hidden = false;
        guestName.focus({ preventScroll: true });
    }

    function renderGuestModal() {
        const extra = extraSeats();
        guestSubtitle.textContent = 'เพื่อนคนที่ ' + (state.guestIndex + 1) + ' จาก ' + extra + ' คน · กรอกไม่นานเลยค่ะ';

        guestProgress.innerHTML = Array.from({ length: Math.max(1, extra) }, function (_, i) {
            return '<span' + (i <= state.guestIndex ? ' class="is-done"' : '') + '></span>';
        }).join('');

        const saved = state.guests.filter(Boolean);
        guestSaved.hidden = saved.length === 0;
        guestSavedList.innerHTML = saved.map(function (guest, i) {
            const meta = [guest.jobLabel, guest.ageLabel].filter(Boolean).join(' · ');
            return '<div class="reg-guest-row">' +
                '<span class="reg-guest-no">' + (i + 2) + '</span>' +
                '<div class="reg-guest-info">' +
                '<span class="reg-guest-name">' + escapeHtml(guest.name) + '</span>' +
                (meta ? '<span class="reg-guest-meta">' + escapeHtml(meta) + '</span>' : '') +
                '</div></div>';
        }).join('');

        const last = state.guestIndex >= extra - 1;
        guestNext.textContent = last
            ? (config.payment.required ? 'ไปหน้าชำระเงิน' : 'ยืนยันการลงทะเบียน')
            : 'บันทึก · คนต่อไป';
        guestNext.disabled = guestName.value.trim() === '';
    }

    function closeGuestModal() {
        guestModal.hidden = true;
    }

    function saveGuest() {
        const name = guestName.value.trim();
        if (!name) return;

        state.guests[state.guestIndex] = {
            name: name,
            ageId: guestAge ? guestAge.value : '',
            ageLabel: guestAge && guestAge.value ? guestAge.options[guestAge.selectedIndex].text : '',
            jobId: guestJob ? guestJob.value : '',
            jobLabel: guestJob && guestJob.value ? guestJob.options[guestJob.selectedIndex].text : ''
        };

        if (state.guestIndex >= extraSeats() - 1) {
            renderGuestModal();
            submitRegistration(guestNext);
        } else {
            openGuestModal(state.guestIndex + 1);
        }
    }

    guestName.addEventListener('input', function () {
        guestNext.disabled = guestName.value.trim() === '';
    });
    guestName.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !guestNext.disabled) {
            event.preventDefault();
            saveGuest();
        }
    });
    guestNext.addEventListener('click', saveGuest);
    guestBack.addEventListener('click', closeGuestModal);
    guestClose.addEventListener('click', closeGuestModal);
    guestModal.addEventListener('click', function (event) {
        if (event.target === guestModal) closeGuestModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !guestModal.hidden) closeGuestModal();
    });

    /* ---------- บันทึกการลงทะเบียน ---------- */

    function buildParticipants() {
        const lead = {
            name: nameInput.value.trim(),
            age_range_id: ageSelect && ageSelect.value ? Number(ageSelect.value) : null,
            occupation_id: jobSelect && jobSelect.value ? Number(jobSelect.value) : null
        };
        const guests = state.guests.slice(0, extraSeats()).map(function (guest) {
            return {
                name: guest.name,
                age_range_id: guest.ageId ? Number(guest.ageId) : null,
                occupation_id: guest.jobId ? Number(guest.jobId) : null
            };
        });
        return [lead].concat(guests);
    }

    async function submitRegistration(button) {
        if (state.submitted) return;

        const payload = {
            phone: normalizePhone(phoneInput.value),
            email: emailInput && emailInput.value.trim() !== '' ? emailInput.value.trim() : null,
            seat_count: state.seats,
            participants: buildParticipants(),
            source_channel_id: sourceSelect && sourceSelect.value ? Number(sourceSelect.value) : null,
            note: noteInput.value.trim() || null,
            activity_round_id: roundSelect && roundSelect.value ? Number(roundSelect.value) : null,
            pdpa: consentInput.checked ? 1 : 0
        };

        setBusy(button, true, 'กำลังบันทึก…');

        try {
            const result = await postJson(config.urls.store, payload);
            state.submitted = true;
            state.booking = result.booking;
            state.codes = result.registrationCodes;
            closeGuestModal();

            if (config.payment.required) {
                renderPay();
                showScreen('pay');
            } else {
                renderDone();
                showScreen('done');
            }
        } catch (error) {
            closeGuestModal();
            footerError.textContent = error.message;
        } finally {
            setBusy(button, false);
        }
    }

    /* ---------- หน้าจอ 5: ชำระเงิน ---------- */

    function renderPay() {
        payFeeLabel.textContent = 'ค่าลงทะเบียน ' + fmtBaht(config.payment.amountPerSeat) + ' × ' + state.seats + ' ที่นั่ง';
        payFeeValue.textContent = fmtBaht(totalAmount());
        payTotal.textContent = fmtBaht(totalAmount());
        if (bankAmount) bankAmount.textContent = fmtBaht(totalAmount());
    }

    function startCountdown() {
        if (!qrExpire || countdownTimer) return;
        let remaining = 30 * 60;
        countdownTimer = window.setInterval(function () {
            remaining = Math.max(0, remaining - 1);
            const minutes = Math.floor(remaining / 60);
            const seconds = String(remaining % 60).padStart(2, '0');
            qrExpire.textContent = 'คิวอาร์หมดอายุใน ' + minutes + ':' + seconds + ' นาที';
            if (remaining === 0) window.clearInterval(countdownTimer);
        }, 1000);
    }

    function stopCountdown() {
        if (countdownTimer) {
            window.clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    if (tabQr && tabBank) {
        tabQr.addEventListener('click', function () {
            tabQr.classList.add('is-active');
            tabBank.classList.remove('is-active');
            tabQr.setAttribute('aria-selected', 'true');
            tabBank.setAttribute('aria-selected', 'false');
            qrPanel.hidden = false;
            if (bankPanel) bankPanel.hidden = true;
        });
        tabBank.addEventListener('click', function () {
            tabBank.classList.add('is-active');
            tabQr.classList.remove('is-active');
            tabBank.setAttribute('aria-selected', 'true');
            tabQr.setAttribute('aria-selected', 'false');
            qrPanel.hidden = true;
            if (bankPanel) bankPanel.hidden = false;
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            const number = config.payment.accountNumber.replace(/\s+/g, '');
            if (navigator.clipboard) navigator.clipboard.writeText(number).catch(function () {});
            copyBtn.classList.add('is-copied');
            copyIcon.setAttribute('d', CHECK_PATH);
            copiedPill.hidden = false;
            window.clearTimeout(copiedTimer);
            copiedTimer = window.setTimeout(function () {
                copiedPill.hidden = true;
                copyBtn.classList.remove('is-copied');
                copyIcon.setAttribute('d', COPY_PATH);
            }, 2200);
        });
    }

    if (qrSave) {
        qrSave.addEventListener('click', async function () {
            try {
                const response = await fetch(config.payment.qrUrl);
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'qr-payment.png';
                link.click();
                window.setTimeout(function () { URL.revokeObjectURL(url); }, 5000);
            } catch (error) {
                window.open(config.payment.qrUrl, '_blank');
            }
        });
    }

    dropzone.addEventListener('click', function () {
        slipInput.click();
    });

    slipInput.addEventListener('change', function () {
        const file = slipInput.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            footerError.textContent = 'ไฟล์สลิปต้องไม่เกิน 5MB นะคะ';
            slipInput.value = '';
            return;
        }
        state.slipFile = file;
        slipName.textContent = file.name;
        dropzone.hidden = true;
        slipCard.hidden = false;
        footerError.textContent = '';
    });

    slipRemove.addEventListener('click', function () {
        state.slipFile = null;
        slipInput.value = '';
        slipCard.hidden = true;
        dropzone.hidden = false;
    });

    async function submitPayment() {
        const formData = new FormData();
        state.codes.forEach(function (code) { formData.append('codes[]', code); });
        if (state.slipFile) formData.append('slip', state.slipFile);

        setBusy(primaryBtn, true, 'กำลังส่งข้อมูล…');

        try {
            const response = await fetch(config.urls.payment, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: formData
            });
            const data = await response.json().catch(function () { return {}; });
            if (!response.ok) {
                const errors = data.errors || {};
                throw new Error(Object.values(errors).flat()[0] || data.message || 'ไม่สามารถส่งข้อมูลได้ กรุณาลองใหม่อีกครั้งนะคะ');
            }
            state.booking = data.booking;
            renderDone();
            showScreen('done');
        } catch (error) {
            footerError.textContent = error.message;
        } finally {
            setBusy(primaryBtn, false);
        }
    }

    /* ---------- หน้าจอ 6: สำเร็จ ---------- */

    function doneRowsData() {
        const booking = state.booking;
        const extra = booking.seats - 1;
        return [
            ['กิจกรรม', booking.activityTitle, ICONS.activity],
            ['วันและเวลา', booking.scheduleLabel, ICONS.time],
            ['สถานที่', booking.location, ICONS.place],
            ['ผู้เข้าร่วม', booking.seats + ' ที่นั่ง · ' + booking.names[0] + (extra > 0 ? ' และอีก ' + extra + ' คน' : ''), ICONS.people],
            ['การชำระเงิน', booking.paymentLabel, ICONS.payment]
        ];
    }

    function renderDone() {
        doneCode.textContent = state.booking.code;
        doneRows.innerHTML = doneRowsData().map(function (row) {
            return '<div class="reg-done-row">' +
                '<span class="reg-done-icon"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' + row[2] + '"/></svg></span>' +
                '<div class="reg-done-text">' +
                '<span class="reg-done-label">' + escapeHtml(row[0]) + '</span>' +
                '<span class="reg-done-value">' + escapeHtml(row[1]) + '</span>' +
                '</div></div>';
        }).join('');
    }

    /* วาดรายละเอียดการจองเป็นภาพให้เก็บไว้แสดงหน้างาน */
    function downloadBookingImage() {
        const booking = state.booking;
        const scale = 2;
        const width = 640;
        const pad = 44;
        const rows = doneRowsData();

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const thai = '"IBM Plex Sans Thai", sans-serif';

        /* คำนวณความสูงจากจำนวนบรรทัดของค่าแต่ละแถวก่อนวาดจริง */
        ctx.font = '500 15px ' + thai;
        const lines = rows.map(function (row) {
            return wrapText(ctx, row[1], width - pad * 2 - 46);
        });
        const rowsHeight = lines.reduce(function (sum, list) { return sum + 26 + list.length * 22 + 16; }, 0);
        const height = 235 + rowsHeight + pad;

        canvas.width = width * scale;
        canvas.height = height * scale;
        ctx.scale(scale, scale);

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);

        ctx.fillStyle = '#5ba554';
        ctx.beginPath();
        ctx.arc(width / 2, pad + 30, 30, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(width / 2 - 12, pad + 31);
        ctx.lineTo(width / 2 - 3, pad + 40);
        ctx.lineTo(width / 2 + 13, pad + 21);
        ctx.stroke();

        ctx.textAlign = 'center';
        ctx.fillStyle = '#1f2c20';
        ctx.font = '600 22px ' + thai;
        ctx.fillText('ลงทะเบียนสำเร็จแล้ว!', width / 2, pad + 95);
        ctx.fillStyle = '#5ba554';
        ctx.font = '600 16px "IBM Plex Sans", sans-serif';
        ctx.fillText(booking.code, width / 2, pad + 125);

        ctx.textAlign = 'left';
        let y = pad + 165;
        rows.forEach(function (row, index) {
            ctx.fillStyle = '#5c6b58';
            ctx.font = '400 12.5px ' + thai;
            ctx.fillText(row[0], pad, y);
            y += 22;
            ctx.fillStyle = '#1f2c20';
            ctx.font = '500 15px ' + thai;
            lines[index].forEach(function (line) {
                ctx.fillText(line, pad, y);
                y += 22;
            });
            y += 16;
        });

        ctx.strokeStyle = '#eef1eb';
        ctx.lineWidth = 1;
        ctx.strokeRect(.5, .5, width - 1, height - 1);

        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = 'booking-' + booking.code + '.png';
        link.click();
    }

    function wrapText(ctx, text, maxWidth) {
        const words = String(text).split(' ');
        const lines = [];
        let current = '';
        words.forEach(function (word) {
            const candidate = current === '' ? word : current + ' ' + word;
            if (ctx.measureText(candidate).width > maxWidth && current !== '') {
                lines.push(current);
                current = word;
            } else {
                current = candidate;
            }
        });
        if (current !== '') lines.push(current);
        return lines;
    }

    downloadBtn.addEventListener('click', downloadBookingImage);

    /* ---------- ปุ่มหลักของ footer ---------- */

    primaryBtn.addEventListener('click', function () {
        footerError.textContent = '';

        if (state.screen === 'found' || state.screen === 'done') {
            window.location.href = config.urls.activity;
            return;
        }

        if (state.screen === 'form') {
            if (!formUnlocked() || !validateForm()) return;
            if (extraSeats() > 0) {
                openGuestModal(0);
            } else {
                submitRegistration(primaryBtn);
            }
            return;
        }

        if (state.screen === 'pay') {
            submitPayment();
        }
    });

    secondaryBtn.addEventListener('click', function () {
        window.location.href = config.urls.home;
    });

    /* ---------- เริ่มต้น ---------- */

    syncSeatUi();

    /* กลับมาจาก LINE แล้วเคยลงทะเบียนกิจกรรมนี้ด้วยบัญชีนี้ — พาไปหน้า "ลงทะเบียนแล้ว" ทันที
       ไม่ต้องให้กรอกเบอร์เพื่อพิสูจน์ตัวตนซ้ำ เพราะเพิ่งยืนยันตัวตนผ่าน LINE มาแล้ว */
    if (line.booking) {
        state.booking = line.booking;
        renderFound();
        showScreen('found');
    } else {
        showScreen('check');
    }
})();
