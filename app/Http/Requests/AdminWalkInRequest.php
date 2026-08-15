<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * เพิ่มผู้เข้าร่วมหน้างาน (Walk-in) แล้วเช็คอินให้ทันที
 *
 * ความยินยอมต้องติ๊กจริงเสมอ — หน้าจอ disable ปุ่มไว้แล้วชั้นหนึ่ง
 * แต่การซ่อนปุ่มกันแค่การกดพลาด ไม่ได้กันคนที่ยิงคำขอตรงเข้ามา
 */
class AdminWalkInRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:30'],
            'roundKey' => ['nullable', 'string', 'max:20'],
            'ageRange' => ['nullable', 'string', 'max:60'],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ–นามสกุล',
            'phone.required' => 'กรุณากรอกเบอร์โทร',
            'consent.accepted' => 'ต้องได้รับความยินยอมจากผู้เข้าร่วมก่อนบันทึก',
        ];
    }
}
