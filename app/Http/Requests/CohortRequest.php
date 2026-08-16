<?php

namespace App\Http\Requests;

use App\Models\FollowUpRoundTemplate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ฟอร์มเพิ่มกลุ่มตัวอย่าง (/admin/cohort)
 *
 * รอบติดตามส่งมาเป็นคู่ template_id + due_date เสมอ ไม่ใช่แค่รายการรอบที่ติ๊ก
 * เพราะแอดมินแก้วันครบกำหนดรายรอบได้ก่อนบันทึก — ถ้ารับแค่ id แล้วให้เซิร์ฟเวอร์
 * บวก offset เอง วันที่ที่แอดมินแก้ทับจะหายไปเงียบ ๆ
 */
class CohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** เบอร์โทรตรวจจากตัวเลขล้วน ผู้ใช้จะพิมพ์ขีดมาหรือไม่ก็ได้ */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\D+/', '', (string) $this->input('phone')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /* ไม่มี person_code ที่นี่โดยตั้งใจ — เซิร์ฟเวอร์ออกรหัสให้เองตอนบันทึก
               ถ้ารับค่าจากฟอร์ม ใครก็ยิงรหัสที่ต้องการเข้ามาทับลำดับของระบบได้ */
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'regex:/^0[689]\d{8}$/'],
            'gender' => ['required', Rule::in(array_keys(config('farmconcept.genders')))],
            'age_range_id' => [
                'nullable', 'integer',
                Rule::exists('mst_options', 'id')->where('option_group', 'age_range')->where('is_active', true),
            ],
            'occupation_id' => [
                'nullable', 'integer',
                Rule::exists('mst_options', 'id')->where('option_group', 'occupation')->where('is_active', true),
            ],
            'area_id' => ['required', 'integer', 'exists:mst_areas,id'],
            'target_group_id' => [
                'required', 'integer',
                Rule::exists('mst_target_groups', 'id')->where('is_active', true),
            ],
            'source_code' => [
                'required', 'string',
                Rule::exists('mst_options', 'code')->where('option_group', 'cohort_source')->where('is_active', true),
            ],
            'entry_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(self::STATUSES)],

            /* ต้องเลือกอย่างน้อยหนึ่งรอบ ไม่งั้นได้กลุ่มตัวอย่างที่ไม่มีอะไรให้ติดตามเลย */
            'rounds' => ['required', 'array', 'min:1'],
            'rounds.*.template_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('mst_follow_up_round_templates', 'id')->where('is_active', true),
            ],
            'rounds.*.due_date' => ['required', 'date'],

            'consent' => ['required', 'accepted'],
            'consent_file_path' => ['nullable', 'string', 'max:255', 'starts_with:'.self::CONSENT_DIR.'/'],
        ];
    }

    /** สถานะที่แอดมินตั้งเองได้ตอนเพิ่ม — สถานะอื่นทั้งหมด derive จากวันครบกำหนด */
    public const STATUSES = ['กำลังติดตาม', 'รอเริ่มติดตาม', 'ยุติการติดตาม'];

    /** โฟลเดอร์ใบยินยอมบน disk local — ไม่ใช่ public เพราะเป็นเอกสารส่วนบุคคล */
    public const CONSENT_DIR = 'cohort-consents';

    /**
     * รอบที่ติ๊กมาต้องไม่มี offset ซ้ำกัน — ptp_follow_up_rounds มี unique
     * (cohort_profile_id, offset_days) อยู่ ถ้าปล่อยผ่านจะพังตอน insert เป็น 500
     * แทนที่จะเป็นข้อความบอกผู้ใช้
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $rounds = $this->input('rounds');

            if (! is_array($rounds) || $validator->errors()->hasAny(['entry_date', 'rounds'])) {
                return;
            }

            $entryDate = strtotime((string) $this->input('entry_date'));
            $seen = [];

            foreach ($rounds as $i => $round) {
                $due = strtotime((string) ($round['due_date'] ?? ''));

                if ($entryDate === false || $due === false) {
                    continue;
                }

                $offset = (int) round(($due - $entryDate) / 86400);

                if ($offset < 0) {
                    $validator->errors()->add("rounds.$i.due_date", 'วันครบกำหนดต้องไม่อยู่ก่อนวันที่เข้ากลุ่มตัวอย่าง');

                    continue;
                }

                if (isset($seen[$offset])) {
                    $validator->errors()->add("rounds.$i.due_date", 'มีรอบอื่นครบกำหนดวันเดียวกันอยู่แล้ว — แก้วันให้ต่างกัน');
                }

                $seen[$offset] = true;
            }
        });
    }

    /** รอบที่เลือก แปลงเป็น template + offset ของคนนี้ พร้อมให้ controller เขียนลงฐานได้เลย */
    public function selectedRounds(string $entryDate): array
    {
        $templates = FollowUpRoundTemplate::whereIn('id', array_column($this->validated()['rounds'], 'template_id'))
            ->get()->keyBy('id');

        $entry = strtotime($entryDate);

        return collect($this->validated()['rounds'])
            ->map(function (array $round) use ($templates, $entry): array {
                $template = $templates[$round['template_id']];

                return [
                    'template_id' => $template->id,
                    'name' => $template->name,
                    /* snapshot จำนวนวันของ "คนนี้" ไม่ใช่ของ template — แอดมินแก้วันครบกำหนดทับได้
                       ค่าที่เก็บจึงต้องเป็นระยะห่างจริงที่คำนวณย้อนกลับจากวันที่บนหน้าจอ */
                    'offset_days' => (int) round((strtotime($round['due_date']) - $entry) / 86400),
                    'due_date' => $round['due_date'],
                ];
            })
            ->sortBy('offset_days')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อ-นามสกุล',
            'phone' => 'เบอร์โทรศัพท์',
            'gender' => 'เพศ',
            'age_range_id' => 'ช่วงอายุ',
            'occupation_id' => 'อาชีพ',
            'area_id' => 'พื้นที่ดำเนินงาน',
            'target_group_id' => 'กลุ่มเป้าหมาย',
            'source_code' => 'แหล่งที่มา',
            'entry_date' => 'วันที่เข้ากลุ่มตัวอย่าง',
            'rounds' => 'รอบการติดตาม',
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
            'phone.regex' => 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก',
            'gender.required' => 'กรุณาเลือกเพศ',
            'area_id.required' => 'กรุณาเลือกพื้นที่ดำเนินงาน',
            'area_id.exists' => 'พื้นที่ที่เลือกไม่ถูกต้อง',
            'target_group_id.required' => 'กรุณาเลือกกลุ่มเป้าหมาย',
            'source_code.required' => 'กรุณาเลือกแหล่งที่มา',
            'entry_date.required' => 'กรุณาระบุวันที่เข้ากลุ่มตัวอย่าง',
            'rounds.required' => 'กรุณาเลือกรอบการติดตามอย่างน้อยหนึ่งรอบ',
            'rounds.min' => 'กรุณาเลือกรอบการติดตามอย่างน้อยหนึ่งรอบ',
            'rounds.*.template_id.exists' => 'รอบการติดตามที่เลือกถูกปิดใช้งานแล้ว',
            'consent.accepted' => 'กรุณายืนยันความยินยอมในการเก็บข้อมูล',
            'consent_file_path.starts_with' => 'ไฟล์ใบยินยอมไม่ถูกต้อง กรุณาอัปโหลดใหม่',
        ];
    }
}
