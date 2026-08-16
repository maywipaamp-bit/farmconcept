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
    const seatSelect = document.getElementById('reg-seat-select');

    const guestModal = document.getElementById('reg-guest-modal');
    const guestSubtitle = document.getElementById('reg-guest-subtitle');
    const guestName = document.getElementById('reg-guest-name');
    const guestAge = document.getElementById('reg-guest-age');
    const guestJob = document.getElementById('reg-guest-job');
    const guestClose = document.getElementById('reg-guest-close');
    const guestBack = document.getElementById('reg-guest-back');
    const guestNext = document.getElementById('reg-guest-next');

    const tabQr = document.getElementById('reg-tab-qr');
    const tabBank = document.getElementById('reg-tab-bank');
    const qrPanel = document.getElementById('reg-qr-panel');
    const bankPanel = document.getElementById('reg-bank-panel');
    const qrSave = document.getElementById('reg-qr-save');
    const copyBtn = document.getElementById('reg-copy-account');
    const copyIcon = document.getElementById('reg-copy-icon');
    const copiedPill = document.getElementById('reg-copied-pill');
    const bankAmount = document.getElementById('reg-bank-amount');
    const payFeeLabel = document.getElementById('reg-pay-fee-label');
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
        /* หัวหน้าจอเลือกชื่อเรื่อง/คำอธิบายตามหน้าจอที่กำลังอยู่ (ดู [data-for] ใน CSS) */
        document.body.dataset.screen = screen;
        footerError.textContent = '';
        renderFooter();
        pane.scrollTop = 0;
    }

    function renderFooter() {
        const screen = state.screen;

        if (screen === 'check') {
            footer.hidden = true;
            return;
        }

        footer.hidden = false;
        secondaryBtn.hidden = true;
        primaryBtn.disabled = false;

        if (screen === 'found' || screen === 'done') {
            primaryBtn.textContent = 'ดูรายละเอียดกิจกรรม';
            secondaryBtn.textContent = 'กลับหน้าหลัก';
            secondaryBtn.hidden = false;
            return;
        }

        if (screen === 'form') {
            /* ไม่มีแถว "ยอดรวม" เหนือปุ่มแล้ว — ยอดอยู่บนตัวปุ่มที่เดียว ผู้ใช้เห็นตรงจุดที่กำลังจะกด */
            const total = config.activity.isFree ? '' : ' · ' + totalAmount().toLocaleString('th-TH') + ' บาท';
            if (extraSeats() > 0) {
                primaryBtn.textContent = 'ถัดไป · ระบุผู้ร่วม ' + extraSeats() + ' คน' + total;
            } else {
                primaryBtn.textContent = (config.payment.required ? 'ไปหน้าชำระเงิน' : 'ยืนยันการลงทะเบียน') + total;
            }
            primaryBtn.disabled = !formUnlocked();
            return;
        }

        if (screen === 'pay') {
            primaryBtn.textContent = 'แจ้งชำระเงินแล้ว';
        }
    }

    /* ---------- หน้าจอ 1: ตรวจสอบสิทธิ์ ---------- */

    /* ข้อความเดียวใช้ทั้งตอนตั้งค่าเริ่มต้นและตอนรีเซ็ต จะได้ไม่หลุดไปคนละแบบ
       เขียนแบบชวนกรอกใหม่ ไม่ใช่ตำหนิว่ากรอกผิด */
    const CONTACT_HINT = 'ขอเป็นเบอร์มือถือ 10 หลัก หรืออีเมลที่ใช้งานได้นะคะ';

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

    /* ---------- ปุ่มย้อนกลับมุมซ้ายบน ----------
       ย้อนทีละขั้นตามเส้นทางที่ผู้ใช้เดินมา ไม่ใช่กระเด็นออกจากหน้าลงทะเบียนทันที
       หน้าจอกรอกข้อมูลเป็นจุดเริ่มแล้ว (หน้าจอตรวจสอบสิทธิ์ถูกปิดไว้) กดกลับ = ออกไปหน้ากิจกรรม */
    const PREVIOUS_SCREEN = { pay: 'form' };

    const backLink = document.getElementById('reg-back');

    if (backLink) {
        backLink.addEventListener('click', function (event) {
            const previous = PREVIOUS_SCREEN[state.screen];
            if (!previous) return;

            event.preventDefault();
            showScreen(previous);
        });
    }

    /* ---------- ตรวจการลงทะเบียนซ้ำระหว่างกรอกฟอร์ม ----------
       หน้าจอตรวจสอบสิทธิ์ถูกปิดไปแล้ว จึงย้ายการตรวจมาไว้ตอนกรอกเบอร์/อีเมลเสร็จ (blur)
       เจอว่าเคยลงทะเบียนแล้วขึ้น popup ทันที ดีกว่าปล่อยให้กรอกจนจบแล้วค่อยโดนปฏิเสธตอนส่ง */

    const dupModal = document.getElementById('reg-dup-modal');
    const dupText = document.getElementById('reg-dup-text');
    const dupView = document.getElementById('reg-dup-view');
    const dupEdit = document.getElementById('reg-dup-edit');
    const dupClose = document.getElementById('reg-dup-close');

    /* จำค่าที่ตรวจแล้ว — จะได้ไม่เด้ง popup ซ้ำทุกครั้งที่โฟกัสออกจากช่องเดิมโดยไม่ได้แก้อะไร */
    const dupChecked = {};

    function openDupModal(label) {
        if (!dupModal) return;
        dupText.textContent = label + 'นี้ลงทะเบียนกิจกรรมนี้ไว้แล้ว';
        dupModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeDupModal() {
        if (!dupModal) return;
        dupModal.hidden = true;
        document.body.style.overflow = '';
    }

    dupClose?.addEventListener('click', closeDupModal);
    dupEdit?.addEventListener('click', closeDupModal);

    dupView?.addEventListener('click', function () {
        closeDupModal();
        renderFound();
        showScreen('found');
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && dupModal && !dupModal.hidden) closeDupModal();
    });

    /* ---------- popup อ่านเอกสารความยินยอม (เงื่อนไขการเข้าร่วม · นโยบายความเป็นส่วนตัว) ---------- */

    const consentDocModal = document.getElementById('reg-consent-modal');
    const consentDocTitle = document.getElementById('reg-consent-modal-title');
    const consentDocVersion = document.getElementById('reg-consent-modal-version');
    const consentDocContent = document.getElementById('reg-consent-modal-content');

    function openConsentDoc(kind) {
        const doc = config.consentDocs && config.consentDocs[kind];
        if (!doc || !consentDocModal) return;
        consentDocTitle.textContent = doc.title;
        consentDocVersion.textContent = doc.version ? 'เวอร์ชัน ' + doc.version : '';
        consentDocContent.textContent = doc.content;
        consentDocContent.scrollTop = 0;
        consentDocModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeConsentDoc() {
        if (!consentDocModal) return;
        consentDocModal.hidden = true;
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-consent-doc]');
        if (link) {
            /* กันไม่ให้ label ของ checkbox สลับติ๊กตอนกดลิงก์อ่านเอกสาร */
            event.preventDefault();
            openConsentDoc(link.dataset.consentDoc);
            return;
        }
        if (event.target.closest('#reg-consent-modal-close')) closeConsentDoc();
        /* คลิกฉากหลังนอกการ์ดเพื่อปิด */
        if (consentDocModal && !consentDocModal.hidden && event.target === consentDocModal) closeConsentDoc();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && consentDocModal && !consentDocModal.hidden) closeConsentDoc();
    });

    function bindDuplicateCheck(input, label, toContact) {
        if (!input) return;

        input.addEventListener('blur', function () {
            const contact = toContact(input.value);
            if (!contact || dupChecked[contact] !== undefined) return;

            postJson(config.urls.check, { contact: contact })
                .then(function (result) {
                    dupChecked[contact] = Boolean(result.registered);
                    if (!result.registered) return;
                    state.booking = result.booking;
                    openDupModal(label);
                })
                /* ตรวจไม่ได้ (เน็ตล่ม ฯลฯ) ไม่ต้องขวางการกรอก — ฝั่งเซิร์ฟเวอร์กันซ้ำตอนบันทึกอีกชั้นอยู่แล้ว */
                .catch(function () {});
        });
    }

    bindDuplicateCheck(phoneInput, 'เบอร์โทรศัพท์', function (value) {
        const digits = normalizePhone(value);
        return isValidPhone(digits) ? digits : null;
    });

    bindDuplicateCheck(emailInput, 'อีเมล', function (value) {
        const email = String(value || '').trim().toLowerCase();
        return isValidEmail(email) ? email : null;
    });

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
        contactError.textContent = CONTACT_HINT;
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

    /* ปุ่มปลดล็อกเมื่อครบ ชื่อ + เบอร์โทร 10 หลัก + ติ๊กยอมรับเงื่อนไข */
    function formUnlocked() {
        return nameInput.value.trim() !== ''
            && isValidPhone(phoneInput.value.replace(/\D/g, ''))
            && consentInput.checked;
    }

    function syncSeatUi() {
        if (seatSelect) seatSelect.value = state.seats;
        renderFooter();
    }

    if (seatSelect) {
        seatSelect.addEventListener('change', function () {
            state.seats = Math.min(config.maxSeats, Math.max(1, Number(seatSelect.value) || 1));
            state.guests.length = extraSeats();
            syncSeatUi();
        });
    }

    nameInput.addEventListener('input', renderFooter);
    consentInput.addEventListener('change', renderFooter);
    phoneInput.addEventListener('input', function () {
        phoneInput.value = normalizePhone(phoneInput.value);
        phoneError.hidden = true;
        phoneInput.classList.remove('is-error');
        renderFooter();
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
        /* บอกตำแหน่งเฉพาะตอนมีผู้ร่วมหลายคน — คนเดียวไม่ต้องบอกว่า "คนที่ 1 จาก 1" */
        guestSubtitle.textContent = extra > 1
            ? 'เพื่อนคนที่ ' + (state.guestIndex + 1) + ' จาก ' + extra + ' คน'
            : '';

        /* ไม่สรุปรายชื่อที่กรอกไปแล้วและไม่มีแถบ progress — กดปุ่มไปคนถัดไปได้เลย
           ตำแหน่งบอกในบรรทัดใต้หัวข้อ (เฉพาะตอนมีหลายคน) */
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
        payFeeLabel.textContent = state.seats + ' ที่นั่ง × ' + config.payment.amountPerSeat.toLocaleString('th-TH') + ' บาท';
        payTotal.textContent = totalAmount().toLocaleString('th-TH') + ' บาท';
        if (bankAmount) bankAmount.textContent = fmtBaht(totalAmount());
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

    /* อ่านค่าตัวแปรสีจาก CSS ของหน้า — ภาพที่วาดจะได้สีชุดเดียวกับหน้าเว็บเสมอ
       แม้ต่อไปโทนสีเปลี่ยน ก็ไม่ต้องมาไล่แก้ค่าที่ hardcode ไว้ในนี้อีก */
    function cssVar(name, fallback) {
        const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return value || fallback;
    }

    /* วาดไอคอนเส้นชุดเดียวกับบนหน้า (path 24×24) ลง canvas ที่ตำแหน่ง/ขนาดที่ต้องการ */
    function drawIcon(ctx, path, x, y, size, color) {
        ctx.save();
        ctx.translate(x, y);
        ctx.scale(size / 24, size / 24);
        ctx.strokeStyle = color;
        ctx.lineWidth = 1.8;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.stroke(new Path2D(path));
        ctx.restore();
    }

    /* วาดรายละเอียดการจองเป็นภาพให้เก็บไว้แสดงหน้างาน
       เลย์เอาต์/สี/ฟอนต์จำลองหน้าจอ "ลงทะเบียนสำเร็จ" ของเว็บแบบหนึ่งต่อหนึ่ง */
    function downloadBookingImage() {
        const booking = state.booking;
        const rows = doneRowsData();
        const scale = 2;
        const width = 520;
        const pad = 24;
        const contentWidth = width - pad * 2;
        const thai = '"Noto Sans Thai", sans-serif';

        const colors = {
            title: cssVar('--reg-title', '#123c1c'),
            text: cssVar('--reg-text', '#14421f'),
            faint: cssVar('--reg-text-faint', '#9aa89c'),
            footer: cssVar('--reg-footer-text', '#9AA694'),
            icon: cssVar('--reg-text-icon', '#2c7a44'),
            accent: cssVar('--reg-accent', '#1b7a3d'),
            primary: cssVar('--reg-primary', '#34c759'),
            border: cssVar('--reg-border', '#e4eee6'),
            borderSoft: cssVar('--reg-border-soft', '#eef4ef'),
            pillBg: cssVar('--reg-tint-strong', '#f2f8f3'),
            pillBorder: cssVar('--reg-tint-line', '#dcebe0')
        };

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        /* การ์ดสรุป: icon 17 + gap 12 → ข้อความกว้าง content - padding การ์ด 20×2 - 29 */
        const cardTextWidth = contentWidth - 40 - 29;
        ctx.font = '500 13.5px ' + thai;
        const lines = rows.map(function (row) {
            return wrapText(ctx, row[1], cardTextWidth);
        });
        /* แถวการ์ด: padding 12 + label 18 + gap 2 + ค่า 20/บรรทัด + padding 12 */
        const rowHeights = lines.map(function (list) { return 12 + 18 + 2 + list.length * 20 + 12; });
        const cardHeight = 8 + rowHeights.reduce(function (sum, h) { return sum + h; }, 0);

        const heroTop = 36;
        const circleSize = 72;
        const titleY = heroTop + circleSize + 14 + 18;
        const pillTop = titleY + 12;
        const pillHeight = 33;
        const dividerY = pillTop + pillHeight + 16;
        const cardTop = dividerY + 20;
        const footerY = cardTop + cardHeight + 26;
        const height = footerY + 24;

        canvas.width = width * scale;
        canvas.height = height * scale;
        ctx.scale(scale, scale);

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);

        /* วงกลมเครื่องหมายถูก + เงาเขียวจางแบบเดียวกับ .reg-check-circle */
        const cx = width / 2;
        const cy = heroTop + circleSize / 2;
        ctx.save();
        ctx.shadowColor = 'rgba(52, 199, 89, .28)';
        ctx.shadowBlur = 16;
        ctx.shadowOffsetY = 6;
        ctx.fillStyle = colors.primary;
        ctx.beginPath();
        ctx.arc(cx, cy, circleSize / 2, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 4.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(cx - 13, cy + 1);
        ctx.lineTo(cx - 4, cy + 10);
        ctx.lineTo(cx + 14, cy - 9);
        ctx.stroke();

        ctx.textAlign = 'center';
        ctx.fillStyle = colors.title;
        ctx.font = '600 18px ' + thai;
        ctx.fillText('ลงทะเบียนสำเร็จแล้ว!', cx, titleY);

        /* ป้ายรหัสจอง — ทรง/สีเดียวกับ .reg-code-pill บนหน้า */
        ctx.font = '600 14px ' + thai;
        const codeWidth = ctx.measureText(booking.code).width;
        const pillWidth = codeWidth + 32;
        ctx.fillStyle = colors.pillBg;
        ctx.strokeStyle = colors.pillBorder;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.roundRect(cx - pillWidth / 2, pillTop, pillWidth, pillHeight, 12);
        ctx.fill();
        ctx.stroke();
        ctx.fillStyle = colors.accent;
        ctx.fillText(booking.code, cx, pillTop + 22);

        /* เส้นคั่นใต้ hero แบบเดียวกับหน้า */
        ctx.strokeStyle = colors.border;
        ctx.beginPath();
        ctx.moveTo(pad, dividerY + .5);
        ctx.lineTo(width - pad, dividerY + .5);
        ctx.stroke();

        /* การ์ดสรุป — กรอบมน 16 + เงาอ่อน ตาม .reg-done-box */
        ctx.save();
        ctx.shadowColor = 'rgba(20, 66, 31, .05)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetY = 2;
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(pad, cardTop, contentWidth, cardHeight, 16);
        ctx.fill();
        ctx.restore();
        ctx.strokeStyle = colors.border;
        ctx.beginPath();
        ctx.roundRect(pad + .5, cardTop + .5, contentWidth - 1, cardHeight - 1, 16);
        ctx.stroke();

        ctx.textAlign = 'left';
        let y = cardTop + 4;
        const cardX = pad + 20;
        rows.forEach(function (row, index) {
            const rowTop = y;
            drawIcon(ctx, row[2], cardX, rowTop + 14, 17, colors.icon);
            ctx.fillStyle = colors.faint;
            ctx.font = '400 12px ' + thai;
            ctx.fillText(row[0], cardX + 29, rowTop + 24);
            ctx.fillStyle = colors.text;
            ctx.font = '500 13.5px ' + thai;
            lines[index].forEach(function (line, lineIndex) {
                ctx.fillText(line, cardX + 29, rowTop + 44 + lineIndex * 20);
            });
            y += rowHeights[index];
            if (index < rows.length - 1) {
                ctx.strokeStyle = colors.borderSoft;
                ctx.beginPath();
                ctx.moveTo(cardX, y + .5);
                ctx.lineTo(pad + contentWidth - 20, y + .5);
                ctx.stroke();
            }
        });

        ctx.textAlign = 'center';
        ctx.fillStyle = colors.footer;
        ctx.font = '400 12px ' + thai;
        ctx.fillText('TheFarmConcept © 2026', cx, footerY);

        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = 'booking-' + booking.code + '.png';
        link.click();
    }

    /* ตัดบรรทัด — แยกด้วยช่องว่างก่อน ถ้าท่อนเดียวยังยาวเกิน (ข้อความไทยไม่มีช่องว่าง)
       ค่อยตัดทีละตัวอักษร ไม่ให้ข้อความทะลุขอบการ์ด */
    function wrapText(ctx, text, maxWidth) {
        const lines = [];
        let current = '';

        function pushWord(word) {
            const candidate = current === '' ? word : current + ' ' + word;
            if (ctx.measureText(candidate).width <= maxWidth) {
                current = candidate;
                return;
            }
            if (current !== '') {
                lines.push(current);
                current = '';
            }
            if (ctx.measureText(word).width <= maxWidth) {
                current = word;
                return;
            }
            let chunk = '';
            for (const ch of word) {
                if (ctx.measureText(chunk + ch).width > maxWidth && chunk !== '') {
                    lines.push(chunk);
                    chunk = '';
                }
                chunk += ch;
            }
            current = chunk;
        }

        String(text).split(' ').forEach(pushWord);
        if (current !== '') lines.push(current);
        return lines;
    }

    /* ให้ฟอนต์ไทยโหลดเสร็จก่อนวาด — วาดตอนฟอนต์ยังไม่มา ภาพจะออกเป็นฟอนต์ระบบไม่ตรงหน้าเว็บ */
    downloadBtn.addEventListener('click', function () {
        const draw = function () { downloadBookingImage(); };
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(draw);
        } else {
            draw();
        }
    });

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
        /* เข้าหน้ากรอกข้อมูลทันที — หน้าจอตรวจสอบสิทธิ์ถูกปิดไว้
           การกันลงทะเบียนซ้ำย้ายไปตรวจตอนกรอกเบอร์/อีเมลในฟอร์มแทน (ดู bindDuplicateCheck) */
        fillFromLine();
        showScreen('form');
    }
})();
