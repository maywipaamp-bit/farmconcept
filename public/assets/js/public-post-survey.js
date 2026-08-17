/* แบบประเมินหลังกิจกรรม — ทำทีละข้อแบบเดียวกับแบบประเมินติดตาม (tr-*)
   ทุกข้ออยู่ใน DOM อยู่แล้ว ที่นี่แค่ซ่อน/แสดง คุมปุ่ม และส่งคำตอบเป็น JSON ตอนจบ
   ไม่ยิงเซิร์ฟเวอร์รายข้อ — คำตอบครึ่ง ๆ ที่ค้างไว้ใช้วิเคราะห์ไม่ได้ */
(function () {
    'use strict';

    const config = window.TFC_PUBLIC_POST_SURVEY;
    const form = document.getElementById('public-post-survey-form');
    if (!config || !form) return;

    const steps = Array.prototype.slice.call(form.querySelectorAll('.tr-step'));
    const back = document.getElementById('post-survey-back');
    const next = document.getElementById('post-survey-submit');
    const count = document.getElementById('post-survey-count');
    const fill = document.getElementById('post-survey-fill');
    const message = document.getElementById('post-survey-message');
    const success = document.getElementById('post-survey-success');
    let index = 0;
    let busy = false;

    if (steps.length === 0) return;

    function setMessage(text) {
        message.textContent = text || '';
        message.className = 'registration-message' + (text ? ' is-error' : '');
    }

    function answered(step) {
        if (!step.hasAttribute('data-required')) return true;

        if (step.querySelectorAll('input:checked').length > 0) return true;

        const free = step.querySelector('textarea, select');

        return !!(free && free.value.trim() !== '');
    }

    function render() {
        steps.forEach(function (step, i) { step.hidden = i !== index; });

        const last = index === steps.length - 1;

        count.textContent = (index + 1) + ' / ' + steps.length + ' ข้อ';
        fill.style.width = Math.round(((index + 1) / steps.length) * 100) + '%';

        /* ข้อแรกไม่มีอะไรให้ย้อนกลับ เอาปุ่มออกจากแถวไปเลย ปุ่มถัดไปจะได้เต็มความกว้าง */
        back.hidden = index === 0;
        next.textContent = busy ? 'กำลังส่ง…' : (last ? 'ส่งแบบประเมิน' : 'ข้อถัดไป');
        next.disabled = busy || !answered(steps[index]);
    }

    function answers() {
        const result = {};

        form.querySelectorAll('[data-question-id]').forEach(function (question) {
            const id = question.dataset.questionId;
            const type = question.dataset.questionType;

            if (type === 'multi' || type === 'chips') {
                result[id] = Array.from(question.querySelectorAll('input:checked')).map(function (input) {
                    return input.value;
                });
                return;
            }

            const selected = question.querySelector('input:checked, select, textarea');
            result[id] = selected ? selected.value : null;
        });

        return result;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        /* ยังไม่ถึงข้อสุดท้าย = ปุ่มทำหน้าที่ "ข้อถัดไป" */
        if (index < steps.length - 1) {
            index += 1;
            render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        setMessage('');
        busy = true;
        render();

        try {
            const response = await fetch(config.storeUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ answers: answers() })
            });
            const data = await response.json().catch(function () { return {}; });

            if (!response.ok) {
                const firstError = Object.values(data.errors || {}).flat()[0];
                throw new Error(firstError || data.message || 'ไม่สามารถส่งแบบประเมินได้ กรุณาลองใหม่');
            }

            /* ส่งสำเร็จ — เหลือเฉพาะการ์ดขอบคุณ */
            steps.forEach(function (step) { step.hidden = true; });
            document.getElementById('post-survey-actions').hidden = true;
            document.getElementById('post-survey-progress').hidden = true;
            success.hidden = false;
            success.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (error) {
            setMessage(error.message);
            busy = false;
            render();
            return;
        }

        busy = false;
    });

    back.addEventListener('click', function () {
        if (index === 0 || busy) return;
        index -= 1;
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* เลือกคำตอบแล้วปุ่มถัดไปต้องกดได้ทันที ไม่ต้องรอ blur */
    form.addEventListener('change', render);
    form.addEventListener('input', render);

    render();

    window.setTimeout(function () {
        document.getElementById('post-survey-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 120);
})();
