<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicCheckinLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\D+/', '', (string) $this->input('phone')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^0[689]\d{8}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex' => 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก',
        ];
    }
}
