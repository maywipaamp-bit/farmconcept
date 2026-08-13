(function () {
    'use strict';

    const config = window.TFC_PUBLIC_POST_SURVEY;
    const form = document.getElementById('public-post-survey-form');
    if (!config || !form) return;

    const submitButton = document.getElementById('post-survey-submit');
    const message = document.getElementById('post-survey-message');
    const success = document.getElementById('post-survey-success');

    function setBusy(busy) {
        submitButton.disabled = busy;
        submitButton.textContent = busy ? 'กำลังส่ง…' : 'ส่งแบบประเมิน';
    }

    function setMessage(text) {
        message.textContent = text || '';
        message.className = 'registration-message' + (text ? ' is-error' : '');
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
        setMessage('');

        if (!form.reportValidity()) return;

        setBusy(true);
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

            Array.from(form.children).forEach(function (element) {
                if (element !== success && element.type !== 'hidden') element.hidden = true;
            });
            success.hidden = false;
            success.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (error) {
            setMessage(error.message);
        } finally {
            setBusy(false);
        }
    });

    window.setTimeout(function () {
        document.getElementById('post-survey-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 120);
})();
