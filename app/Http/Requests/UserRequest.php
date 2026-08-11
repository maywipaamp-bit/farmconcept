<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isUpdate = ! is_null($userId);

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:60',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => $isUpdate
                ? ['nullable', 'string', 'min:4', 'max:255']
                : ['required', 'string', 'min:4', 'max:255'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('usr_roles', 'name')],
            'status' => ['required', 'string', Rule::in(['ใช้งานอยู่', 'ระงับการใช้งาน'])],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'username.required' => 'กรุณากรอก Username',
            'username.unique' => 'Username นี้ถูกใช้งานแล้ว',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร',
            'roles.required' => 'กรุณาเลือกบทบาทอย่างน้อย 1 บทบาท',
            'roles.min' => 'กรุณาเลือกบทบาทอย่างน้อย 1 บทบาท',
            'roles.*.exists' => 'บทบาทที่เลือกไม่ถูกต้อง',
            'status.required' => 'กรุณาระบุสถานะ',
            'avatar.image' => 'ไฟล์รูปภาพต้องเป็นไฟล์รูปภาพเท่านั้น',
            'avatar.max' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB',
        ];
    }
}
