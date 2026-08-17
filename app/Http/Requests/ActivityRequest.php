<?php

namespace App\Http\Requests;

use App\Models\Activity;
use App\Models\Form;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * ตรวจข้อมูลกิจกรรมก่อนบันทึก
 *
 * กติกาสำคัญ: **ฉบับร่างตรวจน้อย เผยแพร่ตรวจครบ**
 * ตามที่ docs/activity-module.md ข้อ 8 ตีความไว้ — ร่างต้องบันทึกค้างไว้ได้แม้กรอกไม่ครบ
 * ไม่งั้นแอดมินที่เก็บข้อมูลยังไม่ครบจะบันทึกอะไรไม่ได้เลย
 * แต่พอจะเผยแพร่ออกไปให้คนนอกเห็น ต้องครบและถูกต้อง
 */
class ActivityRequest extends FormRequest
{
    /** ค่าที่อนุญาต — ต้องตรงกับ config('farmconcept.activity_types') และ master list ฝั่งหน้าจอ */
    private const TYPES = ['กิจกรรม', 'อีเว้นท์', 'ข่าวสาร', Activity::TYPE_GENERAL];

    private const PARTICIPANT_TYPES = ['กลุ่มตัวอย่าง', 'กลุ่มทั่วไป'];

    /* ต้นฉบับอยู่ที่ Activity::STATUSES — ที่นี่และ ActivityController::updateStatus ต้องใช้ชุดเดียวกัน */
    private const STATUSES = Activity::STATUSES;

    private const VISIBILITIES = ['สาธารณะ', 'เฉพาะกลุ่มเป้าหมาย', 'เฉพาะผู้มีลิงก์'];

    public function authorize(): bool
    {
        return true;   /* สิทธิ์ตรวจที่ ActivityPolicy ผ่าน $this->authorize() ใน Controller */
    }

    /** ช่องติ๊กที่ไม่ถูกส่งมาเมื่อไม่ได้ติ๊ก ต้องตีเป็น false ไม่ใช่ปล่อยเป็น null */
    protected function prepareForValidation(): void
    {
        foreach (['requires_registration', 'requires_checkin', 'has_post_survey', 'has_fee', 'is_published', 'is_featured'] as $flag) {
            $this->merge([$flag => $this->boolean($flag)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $publishing = $this->boolean('is_published');

        /* ประเภท "ทั่วไป" บังคับกรอกแค่ชื่อ แม้ตอนเผยแพร่ — งานอย่างแบบสอบถามการเยี่ยมชมสวน
           ไม่มีวันจัด ไม่มีรอบ ไม่มีพื้นที่/กลุ่มเป้าหมายให้กรอกตั้งแต่แรก
           บังคับไปก็ได้แต่ข้อมูลกรอกส่ง ๆ ซึ่งทำให้รายงานที่อิงฟิลด์เหล่านี้เชื่อถือไม่ได้ */
        $isGeneral = $this->input('type') === Activity::TYPE_GENERAL;

        /* required_if แบบนี้อ่านง่ายกว่าเขียน if/else สองชุด และข้อความ error ออกมาถูกอัตโนมัติ */
        $whenPublishing = ($publishing && ! $isGeneral) ? 'required' : 'nullable';

        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],

            'type' => [$whenPublishing, 'in:'.implode(',', self::TYPES)],
            'participant_type' => ['nullable', 'in:'.implode(',', self::PARTICIPANT_TYPES)],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'visibility' => ['required', 'in:'.implode(',', self::VISIBILITIES)],

            /* อีเวนท์แม่ — ตรวจว่าเป็นอีเวนท์จริงและไม่ใช่ตัวเองใน after() เพราะ exists ตรวจได้แค่ว่ามีแถวอยู่ */
            'parent_event_id' => ['nullable', 'integer', 'exists:act_activities,id'],

            'program_id' => ['nullable', 'integer', 'exists:mst_programs,id'],
            'course_id' => ['nullable', 'integer', 'exists:mst_courses,id'],
            /* รูปแบบไม่บังคับแม้ตอนเผยแพร่ — กิจกรรมหลายรายการไม่เข้าพวกกับรูปแบบที่มีในระบบ
               บังคับไว้แล้วแอดมินต้องเลือกมั่ว ๆ ซึ่งทำให้ตัวกรองตามรูปแบบเชื่อถือไม่ได้ */
            'format_id' => ['nullable', 'integer', 'exists:mst_activity_formats,id'],

            'venue_mode' => ['nullable', 'string', 'max:60'],
            'data_source' => ['nullable', 'string', 'max:60'],
            'organizer' => ['nullable', 'string', 'max:200'],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:100000'],

            'requires_registration' => ['boolean'],
            'requires_checkin' => ['boolean'],
            'has_post_survey' => ['boolean'],
            'registration_form_id' => [
                'nullable',
                'required_if:requires_registration,true',
                'integer',
                Rule::exists('evl_forms', 'id')->where(fn ($query) => $query
                    ->where('type', Form::TYPE_REGISTRATION)
                    ->where('status', Form::STATUS_ACTIVE)),
            ],
            'post_survey_form_id' => [
                'nullable',
                'required_if:has_post_survey,true',
                'integer',
                Rule::exists('evl_forms', 'id')->where(fn ($query) => $query
                    ->where('type', Form::TYPE_POST_ACTIVITY)
                    ->where('status', Form::STATUS_ACTIVE)),
            ],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],

            'has_fee' => ['boolean'],
            'fee' => ['nullable', 'required_if:has_fee,true', 'numeric', 'min:0', 'max:1000000'],

            'start_date' => [$whenPublishing, 'date'],
            'end_date' => [$whenPublishing, 'date', 'after_or_equal:start_date'],

            /* ช่วงเวลาเปิด–ปิดระบบทั้งชุด (เช็คอิน · แบบประเมิน · รับสมัคร) ไม่บังคับกรอก
               เว้นว่าง = เปิดตลอด ไม่จำกัดช่วง — ยังตรวจลำดับก่อน/หลังอยู่เมื่อกรอกมาทั้งคู่ */
            'checkin_start_at' => ['nullable', 'date'],
            'checkin_end_at' => ['nullable', 'date', 'after:checkin_start_at'],

            'survey_start_at' => ['nullable', 'date'],
            'survey_end_at' => ['nullable', 'date', 'after:survey_start_at'],

            'publish_start_at' => ['nullable', 'date'],
            'publish_end_at' => ['nullable', 'date', 'after:publish_start_at'],
            'registration_start_at' => ['nullable', 'date'],
            'registration_end_at' => ['nullable', 'date', 'after_or_equal:registration_start_at'],
            'public_sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],

            'area_ids' => [$whenPublishing, 'array'],
            'area_ids.*' => ['integer', 'exists:mst_areas,id'],
            'instructor_ids' => ['array'],
            'instructor_ids.*' => ['integer', 'exists:mst_instructors,id'],
            'target_group_ids' => [$whenPublishing, 'array'],
            'target_group_ids.*' => ['integer', 'exists:mst_target_groups,id'],

            'rounds' => [$whenPublishing, 'array'],
            'rounds.*.round_date' => ['required', 'date'],
            'rounds.*.time_start' => ['required', 'date_format:H:i'],
            'rounds.*.time_end' => ['required', 'date_format:H:i', 'after:rounds.*.time_start'],
            'rounds.*.location' => ['nullable', 'string', 'max:200'],
            'rounds.*.capacity' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อกิจกรรม',
            'type' => 'ประเภทกิจกรรม',
            'participant_type' => 'ประเภทผู้เข้าร่วม',
            'status' => 'สถานะ',
            'visibility' => 'การมองเห็น',
            'format_id' => 'รูปแบบกิจกรรม',
            'fee' => 'ค่าเข้าร่วม',
            'start_date' => 'วันที่เริ่ม',
            'end_date' => 'วันที่สิ้นสุด',
            'checkin_start_at' => 'เวลาเริ่มเช็คอิน',
            'checkin_end_at' => 'เวลาสิ้นสุดเช็คอิน',
            'survey_start_at' => 'เวลาเริ่มทำแบบประเมิน',
            'survey_end_at' => 'เวลาสิ้นสุดทำแบบประเมิน',
            'publish_start_at' => 'เวลาเริ่มเผยแพร่',
            'publish_end_at' => 'เวลาสิ้นสุดเผยแพร่',
            'registration_start_at' => 'เวลาเปิดรับสมัคร',
            'registration_end_at' => 'เวลาปิดรับสมัคร',
            'registration_form_id' => 'แบบลงทะเบียน',
            'post_survey_form_id' => 'แบบประเมินหลังกิจกรรม',
            'public_sort_order' => 'ลำดับการแสดงหน้าเว็บ',
            'parent_event_id' => 'อีเวนท์ที่สังกัด',
            'area_ids' => 'สถานที่จัด',
            'target_group_ids' => 'กลุ่มเป้าหมาย',
            'rounds' => 'รอบกิจกรรม',

            /* ต้องประกาศ wildcard ด้วย ไม่งั้นข้อความจะหลุดชื่อฟิลด์ดิบอย่าง
               "rounds.0.time_end ต้องอยู่หลัง rounds.0.time_start" ออกไปให้ผู้ใช้เห็น */
            'rounds.*.round_date' => 'วันที่จัด',
            'rounds.*.time_start' => 'เวลาเริ่ม',
            'rounds.*.time_end' => 'เวลาสิ้นสุด',
            'rounds.*.location' => 'สถานที่',
            'rounds.*.capacity' => 'จำนวนที่รับ',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            /* ข้อความมาตรฐานของ required_if จะพ่นชื่อคอลัมน์กับค่า true ออกมาตรง ๆ ซึ่งอ่านไม่รู้เรื่อง */
            'fee.required_if' => 'กรุณาระบุค่าเข้าร่วม เมื่อเลือกว่ากิจกรรมมีค่าใช้จ่าย',
            'checkin_start_at.required_if' => 'กรุณาระบุเวลาเริ่มเช็คอิน เมื่อเปิดใช้การเช็คอิน',
            'checkin_end_at.required_if' => 'กรุณาระบุเวลาสิ้นสุดเช็คอิน เมื่อเปิดใช้การเช็คอิน',
            'registration_end_at.required_if' => 'กรุณาระบุเวลาปิดรับสมัคร เมื่อเปิดใช้การลงทะเบียน',
            'registration_form_id.required_if' => 'กรุณาเลือกแบบลงทะเบียนที่เปิดใช้งาน',
            'registration_form_id.exists' => 'แบบลงทะเบียนที่เลือกไม่ถูกต้องหรือยังไม่ได้เปิดใช้งาน',
            'post_survey_form_id.required_if' => 'กรุณาเลือกแบบประเมินหลังกิจกรรมที่เปิดใช้งาน',
            'post_survey_form_id.exists' => 'แบบประเมินหลังกิจกรรมที่เลือกไม่ถูกต้องหรือยังไม่ได้เปิดใช้งาน',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $v) => $this->checkRoundsDoNotOverlap($v),
            fn (Validator $v) => $this->checkPublishWindow($v),
            fn (Validator $v) => $this->checkRegistrationWindow($v),
            fn (Validator $v) => $this->checkParentEvent($v),
        ];
    }

    /**
     * อีเวนท์แม่ต้องเป็นอีเวนท์จริง · ไม่ใช่ตัวเอง · และอีเวนท์เองห้ามมีแม่
     *
     * ห้ามซ้อนหลายชั้นเพราะยังไม่มีข้อกำหนดว่าอีเวนท์ในอีเวนท์หมายความว่าอะไร
     * ปล่อยไว้จะได้โครงสร้างที่ลึกไม่จำกัดโดยไม่มีใครตั้งใจ แล้วรายงานจะนับซ้ำ
     */
    private function checkParentEvent(Validator $validator): void
    {
        $parentId = $this->input('parent_event_id');

        if ($this->input('type') === Activity::TYPE_EVENT && $parentId) {
            $validator->errors()->add('parent_event_id', 'อีเวนท์ซ้อนในอีเวนท์อื่นไม่ได้');

            return;
        }

        if (! $parentId) {
            return;
        }

        /* route model binding ใส่กิจกรรมที่กำลังแก้ไว้ให้แล้ว ใช้กันไม่ให้ชี้หาตัวเอง */
        $editing = $this->route('activity');

        if ($editing && (int) $parentId === $editing->id) {
            $validator->errors()->add('parent_event_id', 'กิจกรรมอยู่ในตัวเองไม่ได้');

            return;
        }

        if (! Activity::whereKey($parentId)->where('type', Activity::TYPE_EVENT)->exists()) {
            $validator->errors()->add('parent_event_id', 'รายการที่เลือกไม่ใช่อีเวนท์');
        }
    }

    /**
     * รอบกิจกรรมห้ามทับซ้อนกัน
     *
     * ทับซ้อน = วันเดียวกันและช่วงเวลาเหลื่อมกัน ผู้เข้าร่วมจะเลือกได้สองรอบที่ไปพร้อมกันไม่ได้
     * เทียบทุกคู่เพราะจำนวนรอบต่อกิจกรรมมีไม่เกินหลักหน่วย ไม่ต้องหาวิธีที่เร็วกว่านี้
     */
    private function checkRoundsDoNotOverlap(Validator $validator): void
    {
        $rounds = array_values($this->input('rounds', []) ?: []);

        foreach ($rounds as $i => $a) {
            foreach (array_slice($rounds, $i + 1, null, true) as $j => $b) {
                if (($a['round_date'] ?? null) !== ($b['round_date'] ?? null)) {
                    continue;
                }

                if (($a['time_start'] ?? '') < ($b['time_end'] ?? '') && ($b['time_start'] ?? '') < ($a['time_end'] ?? '')) {
                    $validator->errors()->add(
                        "rounds.{$j}.time_start",
                        'ช่วงเวลาทับซ้อนกับรอบที่ '.($i + 1)
                    );
                }
            }
        }
    }

    /** ถ้าระบุช่วงเวลาเผยแพร่ เวลาสิ้นสุดต้องไม่เลยวันที่เริ่มกิจกรรม */
    private function checkPublishWindow(Validator $validator): void
    {
        if (! $this->boolean('is_published')) {
            return;
        }

        $publishEnd = $this->input('publish_end_at');
        $startDate = $this->input('start_date');

        if ($publishEnd && $startDate && $publishEnd > $startDate.' 23:59:59') {
            $validator->errors()->add('publish_end_at', 'เวลาสิ้นสุดเผยแพร่ต้องไม่เลยวันที่จัดกิจกรรม');
        }
    }

    /** วันปิดรับสมัครต้องไม่เลยวันจัดกิจกรรม */
    private function checkRegistrationWindow(Validator $validator): void
    {
        if (! $this->boolean('requires_registration')) {
            return;
        }

        $registrationEnd = $this->input('registration_end_at');
        $startDate = $this->input('start_date');

        if ($registrationEnd && $startDate && $registrationEnd > $startDate.' 23:59:59') {
            $validator->errors()->add('registration_end_at', 'เวลาปิดรับสมัครต้องไม่เลยวันที่จัดกิจกรรม');
        }
    }
}
