<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = trim((string) $this->input('email'));

        $this->merge([
            'phone' => preg_replace('/\D+/', '', (string) $this->input('phone')),
            'email' => $email !== '' ? mb_strtolower($email) : null,
            'participants' => collect($this->input('participants', []))
                ->map(fn ($person) => is_array($person)
                    ? array_merge($person, ['name' => trim((string) ($person['name'] ?? ''))])
                    : $person)
                ->all(),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^0[689]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:160'],
            'seat_count' => ['required', 'integer', 'min:1', 'max:5'],
            'participants' => ['required', 'array', 'size:'.$this->integer('seat_count')],
            'participants.*.name' => ['required', 'string', 'max:160', 'distinct:ignore_case'],
            'participants.*.age_range_id' => [
                'nullable', 'integer',
                Rule::exists('mst_options', 'id')->where('option_group', 'age_range')->where('is_active', true),
            ],
            'participants.*.occupation_id' => [
                'nullable', 'integer',
                Rule::exists('mst_options', 'id')->where('option_group', 'occupation')->where('is_active', true),
            ],
            'source_channel_id' => [
                'nullable', 'integer',
                Rule::exists('mst_options', 'id')->where('option_group', 'source_channel')->where('is_active', true),
            ],
            'note' => ['nullable', 'string', 'max:255'],
            'activity_round_id' => ['nullable', 'integer'],
            'pdpa' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'phone' => 'เบอร์โทรศัพท์',
            'email' => 'อีเมล',
            'seat_count' => 'จำนวนที่นั่ง',
            'participants' => 'รายชื่อผู้เข้าร่วม',
            'participants.*.name' => 'ชื่อ–นามสกุลผู้เข้าร่วม',
            'participants.*.age_range_id' => 'ช่วงอายุ',
            'participants.*.occupation_id' => 'อาชีพ',
            'source_channel_id' => 'ช่องทางที่ทราบข่าวกิจกรรม',
            'note' => 'หมายเหตุ',
            'activity_round_id' => 'รอบกิจกรรม',
            'pdpa' => 'การยอมรับเงื่อนไขการเข้าร่วม',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก',
            'participants.size' => 'กรุณากรอกชื่อผู้เข้าร่วมให้ครบตามจำนวนที่นั่ง',
            'participants.*.name.distinct' => 'ชื่อผู้เข้าร่วมในรายการเดียวกันต้องไม่ซ้ำกัน',
            'pdpa.accepted' => 'กรุณายอมรับเงื่อนไขการเข้าร่วมและนโยบายความเป็นส่วนตัว',
        ];
    }
}
