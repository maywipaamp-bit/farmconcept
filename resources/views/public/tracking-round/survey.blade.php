@extends('public.activities.layout')

@section('title', $round->name)

@section('content')
    <section class="detail-card tr-card">
        @includeWhen($proxyFor, 'public.tracking-round.partials.proxy-banner', ['proxyFor' => $proxyFor])

        @if($form === null)
            {{-- ไม่มีแบบประเมินที่เปิดใช้งาน = ปัญหาการตั้งค่าฝั่งหลังบ้าน ไม่ใช่ความผิดผู้ตอบ
                 บอกให้ชัดแล้วพากลับ ดีกว่าโชว์ฟอร์มเปล่าที่กดส่งแล้วไม่มีอะไรเกิดขึ้น --}}
            <div class="tr-done">
                <h2>ยังไม่มีแบบประเมินให้ทำ</h2>
                <p>กรุณาติดต่อเจ้าหน้าที่โครงการ</p>
                <a class="tr-primary-button" href="{{ route('public.tracking-round-qr.rounds') }}">ย้อนกลับ</a>
            </div>
        @else
            @php
                /* แยกหัวข้อคั่น (section) ออกจากคำถามจริง แล้วผูกหัวข้อล่าสุดไว้กับคำถามที่อยู่ใต้มัน
                   หัวข้อจึงไปโผล่เป็นบรรทัดเล็กเหนือคำถาม แทนที่จะกินพื้นที่เป็นข้อของตัวเอง */
                $steps = [];
                $section = null;

                foreach ($form->questions as $question) {
                    if ($question->question_type === 'section') {
                        $section = $question->text;

                        continue;
                    }

                    $steps[] = ['question' => $question, 'section' => $section];
                }
            @endphp

            @if($errors->any())
                <div class="tr-error" role="alert">{{ $errors->first() }}</div>
            @endif

            {{-- ความคืบหน้า: ชื่อรอบซ้าย · ข้อที่เท่าไรจากทั้งหมดขวา แล้วแถบด้านล่าง --}}
            <div class="tr-progress">
                <div class="tr-progress-head">
                    <span class="tr-progress-round">{{ $round->name }}</span>
                    <span class="tr-progress-count" id="tr-count">1 / {{ count($steps) }} ข้อ</span>
                </div>
                <div class="tr-progress-track">
                    <span class="tr-progress-fill" id="tr-fill" style="width: {{ count($steps) ? round(100 / count($steps)) : 0 }}%"></span>
                </div>
            </div>

            <form method="POST" action="{{ route('public.tracking-round-qr.survey.submit', $round->id) }}" id="tr-form">
                @csrf

                @foreach($steps as $index => $step)
                    @php($question = $step['question'])
                    @php($multi = in_array($question->question_type, ['multi', 'chips'], true))

                    {{-- ทุกข้ออยู่ในหน้าเดียวกันหมด แต่แสดงทีละข้อด้วย JS
                         ถ้าเบราว์เซอร์ปิด JS จะเห็นครบทุกข้อและยังกดส่งได้ ไม่ใช่หน้าเปล่า --}}
                    <fieldset class="tr-step" data-step="{{ $index }}" @if($question->is_required) data-required @endif>
                        <div class="tr-question">
                            @if($step['section'])
                                <span class="tr-question-section">{{ $step['section'] }}</span>
                            @endif
                            <legend class="tr-question-text">
                                ข้อ {{ $index + 1 }}: {{ $question->text }}
                                @if($question->is_required)<span class="tr-required">*</span>@endif
                            </legend>
                            @if($multi)
                                <span class="tr-question-hint">เลือกได้มากกว่าหนึ่งข้อ</span>
                            @endif
                        </div>

                        @if($question->question_type === 'rating')
                            <div class="tr-options">
                                @foreach(config('farmconcept.tracking_round.rating_labels') as $score => $label)
                                    <label class="tr-option">
                                        <input type="radio" name="answer_{{ $question->id }}" value="{{ $score }}"
                                               @checked(old('answer_'.$question->id) == $score)>
                                        <span class="tr-option-dot"></span>
                                        <span class="tr-option-label">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($question->question_type === 'text')
                            {{-- ตอบแบบสั้น: ช่องบรรทัดเดียว กล่องสูง ๆ ชวนให้เขียนยาวเกินกว่าที่ถาม --}}
                            <input type="text" class="tr-input" name="answer_{{ $question->id }}" maxlength="5000"
                                   placeholder="พิมพ์คำตอบ…" value="{{ old('answer_'.$question->id) }}">
                        @elseif($question->question_type === 'paragraph')
                            <textarea class="tr-textarea" name="answer_{{ $question->id }}" rows="4" maxlength="5000"
                                      placeholder="พิมพ์คำตอบ…">{{ old('answer_'.$question->id) }}</textarea>
                        @elseif($question->question_type === 'dropdown')
                            <select class="tr-select" name="answer_{{ $question->id }}">
                                <option value="">เลือกคำตอบ</option>
                                @foreach($question->options as $option)
                                    <option value="{{ $option->id }}" @selected(old('answer_'.$question->id) == $option->id)>{{ $option->label }}</option>
                                @endforeach
                            </select>
                        @elseif($question->question_type === 'consent')
                            {{-- ความยินยอม: ติ๊กช่องเดียว ข้อความในลิงก์กดแล้วเปิดอ่านเอกสารฉบับเต็ม --}}
                            <div class="tr-options">
                                <label class="tr-option">
                                    <input type="checkbox" name="answer_{{ $question->id }}" value="1"
                                           @checked(old('answer_'.$question->id))>
                                    <span class="tr-option-dot is-square"></span>
                                    {{-- ข้อความในลิงก์คือชื่อเอกสาร ไม่ใช่ชื่อคำถาม — ชื่อคำถามอยู่หัวข้อด้านบนแล้ว --}}
                                    <span class="tr-option-label">
                                        อ่านและยอมรับ@if(isset($consentDocs[$question->dimension]))<a href="#" data-consent-doc="{{ $question->dimension }}">{{ $consentDocs[$question->dimension]['title'] }}</a>@else{{ $question->text }}@endif
                                    </span>
                                </label>
                            </div>
                        @else
                            <div class="tr-options">
                                @foreach($question->options as $option)
                                    <label class="tr-option">
                                        <input type="{{ $multi ? 'checkbox' : 'radio' }}"
                                               name="answer_{{ $question->id }}{{ $multi ? '[]' : '' }}"
                                               value="{{ $option->id }}">
                                        <span class="tr-option-dot{{ $multi ? ' is-square' : '' }}"></span>
                                        <span class="tr-option-label">{{ $option->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </fieldset>
                @endforeach

                <div class="tr-actions" id="tr-actions">
                    <button type="button" class="tr-ghost-button" id="tr-back">กลับ</button>
                    <button type="submit" class="tr-primary-button tr-next" id="tr-next">ส่งแบบประเมิน</button>
                </div>
            </form>
        @endif
    </section>

    @include('public.partials.consent-dialog', ['consentDocs' => $consentDocs])
@endsection

@push('page-script')
<script>
/* ทำทีละข้อ — ทุกข้ออยู่ใน DOM อยู่แล้ว ที่นี่แค่ซ่อน/แสดงและคุมปุ่ม
   ไม่ยิงเซิร์ฟเวอร์รายข้อ เพราะคำตอบครึ่ง ๆ ที่ค้างไว้จะกลายเป็นข้อมูลวิจัยที่ใช้ไม่ได้ */
(function () {
    var steps = Array.prototype.slice.call(document.querySelectorAll('.tr-step'));

    if (steps.length === 0) return;

    var back = document.getElementById('tr-back');
    var next = document.getElementById('tr-next');
    var count = document.getElementById('tr-count');
    var fill = document.getElementById('tr-fill');
    var index = 0;

    function answered(step) {
        if (! step.hasAttribute('data-required')) return true;

        var picked = step.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked');
        if (picked.length > 0) return true;

        /* input[type=text] คือคำถามแบบ "ตอบแบบสั้น" — ขาดไปแล้วข้อบังคับตอบจะกดต่อไม่ได้ */
        var free = step.querySelector('textarea, select, input[type="text"]');

        return !! (free && free.value.trim() !== '');
    }

    function render() {
        steps.forEach(function (step, i) { step.hidden = i !== index; });

        var last = index === steps.length - 1;

        count.textContent = (index + 1) + ' / ' + steps.length + ' ข้อ';
        fill.style.width = Math.round(((index + 1) / steps.length) * 100) + '%';

        /* ข้อแรกไม่มีอะไรให้ย้อนกลับ เอาออกจากแถวไปเลย ปุ่มถัดไปจะได้เต็มความกว้าง
           ไม่ใช่กันที่ว่างไว้ให้ปุ่มที่กดไม่ได้ */
        back.hidden = index === 0;
        next.textContent = last ? 'ส่งแบบประเมิน' : 'ข้อถัดไป';
        next.type = last ? 'submit' : 'button';
        next.disabled = ! answered(steps[index]);
    }

    next.addEventListener('click', function (event) {
        if (index === steps.length - 1) return;   /* ข้อสุดท้ายปล่อยให้ submit ตามปกติ */

        event.preventDefault();
        index += 1;
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    back.addEventListener('click', function () {
        if (index === 0) return;
        index -= 1;
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* เลือกคำตอบแล้วปุ่มถัดไปต้องกดได้ทันที ไม่ต้องรอ blur */
    document.getElementById('tr-form').addEventListener('change', render);
    document.getElementById('tr-form').addEventListener('input', render);

    render();
})();
</script>
@endpush
