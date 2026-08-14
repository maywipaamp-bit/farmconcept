<?php

namespace App\Http\Requests;

use App\Models\Form;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * สร้างรอบติดตาม (บันทึกร่าง หรือ สร้างแล้วส่งแจ้งเตือน)
 *
 * แบบประเมินล็อกไว้ชนิดเดียว — health_follow_up ที่เปิดใช้งานอยู่
 * ถ้าปล่อยให้เลือกชนิดอื่น คนที่สแกน QR จะได้แบบลงทะเบียนหรือแบบความพึงพอใจแทน
 */
class TrackingRoundRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'form_id' => [
                'required', 'integer',
                Rule::exists('evl_forms', 'id')
                    ->where('type', Form::TYPE_HEALTH_FOLLOW_UP)
                    ->where('status', Form::STATUS_ACTIVE),
            ],
            'due_from' => ['required', 'date'],
            'due_to' => ['required', 'date', 'after_or_equal:due_from'],
            'target_group_ids' => ['array'],
            'target_group_ids.*' => ['integer', 'exists:mst_target_groups,id'],

            /* ต้องเลือกอย่างน้อยหนึ่งคน ไม่งั้นได้รอบเปล่าที่ไม่มีใครต้องตอบ */
            'follow_up_round_ids' => ['required', 'array', 'min:1'],
            'follow_up_round_ids.*' => ['integer', 'distinct', 'exists:ptp_follow_up_rounds,id'],

            'notification_template' => ['nullable', 'string', 'max:1000'],
            'notify' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อรอบติดตาม',
            'form_id' => 'แบบประเมินที่ใช้',
            'due_from' => 'ครบกำหนดตั้งแต่',
            'due_to' => 'ถึงวันที่',
            'follow_up_round_ids' => 'รายชื่อผู้ติดตาม',
            'notification_template' => 'ข้อความแจ้งเตือน',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อรอบติดตาม',
            'form_id.required' => 'กรุณาเลือกแบบประเมินที่ใช้',
            'form_id.exists' => 'ใช้ได้เฉพาะแบบติดตามสุขภาพที่เปิดใช้งานอยู่',
            'due_from.required' => 'กรุณาระบุวันครบกำหนดเริ่มต้น',
            'due_to.after_or_equal' => 'วันสิ้นสุดต้องไม่อยู่ก่อนวันเริ่มต้น',
            'follow_up_round_ids.required' => 'กรุณาเลือกรายชื่อผู้ติดตามอย่างน้อยหนึ่งคน',
            'follow_up_round_ids.min' => 'กรุณาเลือกรายชื่อผู้ติดตามอย่างน้อยหนึ่งคน',
        ];
    }
}
