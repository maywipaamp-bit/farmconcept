<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\D+/', '', (string) $this->input('phone')),
            'names' => collect($this->input('names', []))
                ->map(fn ($name) => trim((string) $name))
                ->all(),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^0[689]\d{8}$/'],
            'seat_count' => ['required', 'integer', 'min:1', 'max:5'],
            'names' => ['required', 'array', 'size:'.$this->integer('seat_count')],
            'names.*' => ['required', 'string', 'max:160', 'distinct:ignore_case'],
            'activity_round_id' => ['nullable', 'integer'],
            'pdpa' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'phone' => 'เบอร์โทรศัพท์',
            'seat_count' => 'จำนวนที่นั่ง',
            'names' => 'รายชื่อผู้เข้าร่วม',
            'names.*' => 'ชื่อ–นามสกุลผู้เข้าร่วม',
            'activity_round_id' => 'รอบกิจกรรม',
            'pdpa' => 'การยอมรับนโยบาย PDPA',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก',
            'names.size' => 'กรุณากรอกชื่อผู้เข้าร่วมให้ครบตามจำนวนที่นั่ง',
            'names.*.distinct' => 'ชื่อผู้เข้าร่วมในรายการเดียวกันต้องไม่ซ้ำกัน',
            'pdpa.accepted' => 'กรุณายอมรับเงื่อนไขการเข้าร่วมกิจกรรมและนโยบาย PDPA',
        ];
    }
}
