<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicPostSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'answers.required' => 'กรุณาตอบแบบประเมินก่อนส่ง',
            'answers.array' => 'รูปแบบคำตอบไม่ถูกต้อง',
        ];
    }
}
