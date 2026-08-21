<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * เช็คอินหนึ่งคนจากหน้าจอเจ้าหน้าที่
 *
 * สิทธิ์ตรวจที่ middleware `menu:activities-list` และ policy ของกิจกรรมแล้ว
 * ที่นี่จึงเหลือแค่ตรวจรูปของข้อมูล
 */
class AdminCheckinRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'registrationId' => ['required', 'string', 'max:24'],
            /* scan = ผู้เข้าร่วมสแกนเอง · staff = เจ้าหน้าที่กดให้ — ตรงกับ enum ของ act_checkin_logs.method */
            'source' => ['nullable', 'in:scan,staff'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'registrationId.required' => 'ไม่ได้ระบุผู้ลงทะเบียนที่จะเช็คอิน',
        ];
    }
}
