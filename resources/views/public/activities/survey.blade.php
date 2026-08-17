@extends('public.activities.layout')

@section('title', 'แบบประเมิน · '.$activity->name)

@section('content')
    {{-- ไม่มีหัวกิจกรรมด้านบน — ชื่อชุดแบบประเมินในแถบความคืบหน้าบอกอยู่แล้วว่ากำลังตอบเรื่องอะไร
         ที่นี่เหลือแค่คำถามทีละข้อ เหมือนหน้าเช็กอิน --}}
    @if(! $enabled)
        <section class="detail-card tr-card">
            <div class="tr-done">
                <h2>ยังไม่เปิดแบบประเมิน</h2>
                <p>กิจกรรมนี้ยังไม่อยู่ในช่วงรับคำตอบ หรือแบบประเมินปิดรับคำตอบแล้ว</p>
                <a class="tr-primary-button" href="{{ route('public.activities.show', $activity->code) }}">กลับไปหน้ากิจกรรม</a>
            </div>
        </section>
    @else
        {{-- หน้าตาเดียวกับแบบประเมินติดตาม (tr-*): ทีละข้อ + แถบความคืบหน้า
             ทุกข้ออยู่ใน DOM ตั้งแต่แรก JS แค่ซ่อน/แสดง — ปิด JS แล้วยังเห็นครบและส่งได้ --}}
        @php
            /* แยกหัวข้อคั่น (section) ออกจากคำถามจริง แล้วผูกหัวข้อล่าสุดไว้กับคำถามที่อยู่ใต้มัน */
            $surveySteps = [];
            $surveySection = null;

            foreach ($form->questions as $question) {
                if ($question->question_type === 'section') {
                    $surveySection = $question->text;

                    continue;
                }

                $surveySteps[] = ['question' => $question, 'section' => $surveySection];
            }
        @endphp

        <section class="detail-card tr-card" id="post-survey-form">
            <div class="tr-progress" id="post-survey-progress">
                <div class="tr-progress-head">
                    <span class="tr-progress-round">{{ $form->name }}</span>
                    <span class="tr-progress-count" id="post-survey-count">1 / {{ count($surveySteps) }} ข้อ</span>
                </div>
                <div class="tr-progress-track">
                    <span class="tr-progress-fill" id="post-survey-fill" style="width: {{ count($surveySteps) ? round(100 / count($surveySteps)) : 0 }}%"></span>
                </div>
            </div>

            <form id="public-post-survey-form" novalidate>
                @csrf
                @foreach($surveySteps as $index => $step)
                    @php($question = $step['question'])
                    @php($multi = in_array($question->question_type, ['multi', 'chips'], true))

                    <fieldset class="tr-step" data-step="{{ $index }}"
                              data-question-id="{{ $question->id }}" data-question-type="{{ $question->question_type }}"
                              @if($question->is_required) data-required @endif>
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
                                {{-- ป้ายคะแนนชุดเดียวกับแบบประเมินติดตาม — หน้าตาและลำดับต้องตรงกัน --}}
                                @foreach(config('farmconcept.tracking_round.rating_labels') as $score => $label)
                                    <label class="tr-option">
                                        <input type="radio" name="answer_{{ $question->id }}" value="{{ $score }}">
                                        <span class="tr-option-dot"></span>
                                        <span class="tr-option-label">{{ $score }} · {{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($question->question_type === 'text')
                            {{-- ตอบแบบสั้น: ช่องบรรทัดเดียว กล่องสูง ๆ ชวนให้เขียนยาวเกินกว่าที่ถาม --}}
                            <input type="text" class="tr-input" name="answer_{{ $question->id }}" maxlength="5000" placeholder="พิมพ์คำตอบ…">
                        @elseif($question->question_type === 'paragraph')
                            <textarea class="tr-textarea" name="answer_{{ $question->id }}" rows="4" maxlength="5000" placeholder="พิมพ์คำตอบ…"></textarea>
                        @elseif($question->question_type === 'dropdown')
                            <select class="tr-select" name="answer_{{ $question->id }}">
                                <option value="">เลือกคำตอบ</option>
                                @foreach($question->options as $option)
                                    <option value="{{ $option->id }}">{{ $option->label }}</option>
                                @endforeach
                            </select>
                        @elseif($question->question_type === 'consent')
                            {{-- ความยินยอม: ติ๊กช่องเดียว ข้อความในลิงก์กดแล้วเปิดอ่านเอกสารฉบับเต็ม
                                 เนื้อหามาจาก mst_consent_documents ตามรหัสที่คำถามข้อนี้อ้างถึง --}}
                            <div class="tr-options">
                                <label class="tr-option">
                                    <input type="checkbox" name="answer_{{ $question->id }}" value="1">
                                    <span class="tr-option-dot is-square"></span>
                                    {{-- ข้อความในลิงก์คือชื่อเอกสาร ไม่ใช่ชื่อคำถาม
                                         ชื่อคำถามแสดงเป็นหัวข้อด้านบนอยู่แล้ว ซ้ำสองที่จะอ่านเหมือนพูดเรื่องเดิมสองรอบ --}}
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

                <p class="registration-message" id="post-survey-message" aria-live="polite"></p>

                <div class="tr-actions" id="post-survey-actions">
                    <button type="button" class="tr-ghost-button" id="post-survey-back">กลับ</button>
                    <button type="submit" class="tr-primary-button tr-next" id="post-survey-submit">ส่งแบบประเมิน</button>
                </div>

                <div class="tr-done" id="post-survey-success" hidden role="status">
                    <h2>ส่งแบบประเมินแล้ว</h2>
                    <p>ขอบคุณสำหรับความคิดเห็นของคุณ</p>
                    {{-- ตอบเสร็จแล้วไม่มีอะไรให้ทำต่อในหน้ากิจกรรม พากลับหน้าหลักของเว็บเลย --}}
                    <a class="tr-primary-button" href="{{ route('public.activities') }}">กลับไปหน้าหลัก</a>
                </div>
            </form>
        </section>

        @include('public.partials.consent-dialog', ['consentDocs' => $consentDocs])
    @endif
@endsection

@push('page-script')
    @if($enabled)
        <script>
            window.TFC_PUBLIC_POST_SURVEY = @json(['storeUrl' => $storeUrl]);
        </script>
        <script src="@assetv('assets/js/public-post-survey.js')" defer></script>
    @endif
@endpush
