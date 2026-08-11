<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('usr_roles', 'name')->ignore($roleId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อบทบาท',
            'name.unique' => 'ชื่อบทบาทนี้มีอยู่ในระบบแล้ว',
            'is_active.required' => 'กรุณาระบุสถานะ',
        ];
    }
}
