<?php

namespace App\Http\Requests;

use App\Models\ConsentDocument;
use App\Models\Form;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EvaluationRequest extends FormRequest
{
    public const FIELD_KEYS = [
        'name', 'phone', 'gender', 'age_range', 'email',
        'occupation', 'source_channel', 'interests', 'pdpa',
    ];

    public const REQUIRED_FIELD_KEYS = ['name', 'phone', 'pdpa'];

    /* consent = ข้อความยินยอมพร้อมช่องติ๊ก ใช้ขอความยินยอมในแบบประเมินชุดไหนก็ได้
       ต่างจากฟิลด์ pdpa ตรงที่ฟิลด์นั้นมีเฉพาะแบบลงทะเบียนและแก้ข้อความไม่ได้ */
    /* text = ตอบแบบสั้น (ช่องบรรทัดเดียว) · paragraph = ตอบแบบยาว (กล่องหลายบรรทัด)
       ต่างกันที่หน้าตาช่องตอบเท่านั้น การตรวจและการเก็บใช้ทางเดียวกัน */
    public const QUESTION_TYPES = ['section', 'rating', 'single', 'multi', 'chips', 'dropdown', 'text', 'paragraph', 'consent'];

    public const CHOICE_TYPES = ['single', 'multi', 'chips', 'dropdown'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fields = collect($this->input('fields', []))->map(function ($field) {
            $field['is_enabled'] = filter_var($field['is_enabled'] ?? false, FILTER_VALIDATE_BOOL);
            $field['is_required'] = filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOL);

            return $field;
        })->values()->all();

        $questions = collect($this->input('questions', []))->map(function ($question) {
            $question['is_required'] = filter_var($question['is_required'] ?? false, FILTER_VALIDATE_BOOL);

            return $question;
        })->values()->all();

        $this->merge(['fields' => $fields, 'questions' => $questions]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:'.implode(',', Form::TYPES)],
            'status' => ['required', 'in:'.Form::STATUS_DRAFT.','.Form::STATUS_ACTIVE],

            'registration_mode' => ['nullable', 'required_if:type,'.Form::TYPE_REGISTRATION, 'in:single,group'],
            'max_participants' => ['nullable', 'required_if:registration_mode,group', 'integer', 'min:2', 'max:5'],

            'fields' => ['array'],
            'fields.*.key' => ['required', 'distinct', 'in:'.implode(',', self::FIELD_KEYS)],
            'fields.*.is_enabled' => ['required', 'boolean'],
            'fields.*.is_required' => ['required', 'boolean'],
            'fields.*.sort_order' => ['required', 'integer', 'min:0', 'max:1000'],

            'questions' => ['array'],
            'questions.*.type' => ['required', 'in:'.implode(',', self::QUESTION_TYPES)],
            'questions.*.text' => ['required', 'string', 'max:500'],
            'questions.*.dimension' => ['nullable', 'string', 'max:120'],
            'questions.*.is_required' => ['required', 'boolean'],
            'questions.*.sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'questions.*.options' => ['array'],
            'questions.*.options.*.label' => ['required', 'string', 'max:255'],
            'questions.*.options.*.is_other' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateRegistrationFields($validator);
            $this->validateQuestions($validator);
        }];
    }

    private function validateRegistrationFields(Validator $validator): void
    {
        if ($this->input('type') !== Form::TYPE_REGISTRATION) {
            return;
        }

        $fields = collect($this->input('fields', []))->keyBy('key');

        foreach (self::REQUIRED_FIELD_KEYS as $key) {
            $field = $fields->get($key);
            if (! $field || ! ($field['is_enabled'] ?? false) || ! ($field['is_required'] ?? false)) {
                $validator->errors()->add('fields', "ฟิลด์ {$key} ต้องเปิดใช้งานและเป็นข้อมูลบังคับ");
            }
        }
    }

    private function validateQuestions(Validator $validator): void
    {
        $questions = collect($this->input('questions', []));

        if ($this->input('status') === Form::STATUS_ACTIVE
            && $this->input('type') !== Form::TYPE_REGISTRATION
            && $questions->where('type', '!=', 'section')->isEmpty()) {
            $validator->errors()->add('questions', 'แบบประเมินที่เปิดใช้งานต้องมีคำถามอย่างน้อย 1 ข้อ');
        }

        /* รหัสเอกสารความยินยอมที่เปิดใช้อยู่ — ดึงครั้งเดียวนอกลูป */
        $consentCodes = $questions->contains(fn ($q) => ($q['type'] ?? null) === 'consent')
            ? ConsentDocument::query()->where('is_active', true)->pluck('code')->all()
            : [];

        foreach ($questions as $index => $question) {
            if (in_array($question['type'] ?? null, self::CHOICE_TYPES, true)
                && collect($question['options'] ?? [])->pluck('label')->filter(fn ($label) => trim((string) $label) !== '')->count() < 2) {
                $validator->errors()->add("questions.{$index}.options", 'คำถามแบบเลือกต้องมีตัวเลือกอย่างน้อย 2 ข้อ');
            }

            /* คำถามความยินยอมต้องชี้ไปเอกสารที่มีจริงและยังเปิดใช้อยู่
               ไม่งั้นผู้ตอบจะเจอลิงก์ที่กดแล้วไม่มีอะไรให้อ่าน แล้วยังต้องติ๊กยอมรับ */
            if (($question['type'] ?? null) === 'consent'
                && ! in_array($question['dimension'] ?? null, $consentCodes, true)) {
                $validator->errors()->add("questions.{$index}.dimension", 'กรุณาเลือกเอกสารความยินยอมของคำถามข้อนี้');
            }
        }
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อชุดแบบประเมิน',
            'type' => 'ประเภทแบบประเมิน',
            'registration_mode' => 'รูปแบบการจอง',
            'max_participants' => 'จำนวนผู้เข้าร่วมสูงสุด',
            'questions.*.text' => 'ข้อความคำถาม',
            'questions.*.options.*.label' => 'ชื่อตัวเลือก',
        ];
    }
}
