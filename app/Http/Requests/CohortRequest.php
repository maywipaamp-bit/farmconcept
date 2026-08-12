<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CohortRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'gender' => ['nullable', 'string', 'max:20'],
            'age_band' => ['nullable', 'string', 'max:50'],
            'occupation_id' => ['nullable', 'integer', 'exists:mst_options,id'],
            'source_channel_id' => ['nullable', 'integer', 'exists:mst_options,id'],
            'area_id' => ['required', 'integer', 'exists:mst_areas,id'],
            'target_group_id' => ['nullable', 'integer', 'exists:mst_target_groups,id'],
            'entry_date' => ['required', 'date'],
            'consent' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'area_id.required' => 'กรุณาเลือกพื้นที่ดำเนินงาน',
            'area_id.exists' => 'พื้นที่ที่เลือกไม่ถูกต้อง',
            'entry_date.required' => 'กรุณาระบุวันที่เข้ากลุ่มตัวอย่าง',
            'consent.accepted' => 'กรุณายืนยันความยินยอมในการเก็บข้อมูล',
        ];
    }
}
