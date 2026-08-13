<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'username' => [
                'required',
                'string',
                'max:60',
                Rule::unique('users', 'username')->ignore($this->user()?->id),
            ],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'phone.required' => 'กรุณากรอกเบอร์โทร',
            'username.required' => 'กรุณากรอก Username',
            'username.unique' => 'Username นี้ถูกใช้งานแล้ว',
            'password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร',
            'avatar.image' => 'ไฟล์ที่เลือกต้องเป็นรูปภาพเท่านั้น',
            'avatar.mimes' => 'รองรับเฉพาะไฟล์ .jpg และ .png',
            'avatar.max' => 'ขนาดรูปภาพต้องไม่เกิน 5 MB',
        ];
    }
}
