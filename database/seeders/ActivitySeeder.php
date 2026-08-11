<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityFormat;
use App\Models\ActivityRound;
use App\Models\Area;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\QrCode;
use App\Models\Registration;
use App\Models\TargetGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * กิจกรรม 5 รายการ + รอบ + ผู้ลงทะเบียน + QR — ถอดจาก TFC_MOCK.activities
 *
 * สวิตช์ทั้งสามไม่มีอยู่ในข้อมูลจำลองตรง ๆ (activity-create.js บรรทัด 859 เขียนไว้เอง)
 * จึง derive จากฟิลด์ที่มี:
 *   requires_registration = dataSource เป็น "ลงทะเบียนออนไลน์"
 *   requires_checkin      = มีช่วงเวลา check-in
 *   has_post_survey       = ผูกชุดแบบประเมินไว้
 */
class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->activities() as $data) {
            $activity = $this->upsertActivity($data);
            $this->syncPivots($activity, $data);
            $this->syncRounds($activity, $data['rounds']);
            $this->syncRegistrations($activity, $data['registered']);
            $this->syncQrCodes($activity);
        }

        $this->syncHealthQrCode();
    }

    private function upsertActivity(array $data): Activity
    {
        $program = Program::where('name', $data['program'])->first();
        $course = $data['course'] ? Course::where('name', $data['course'])->first() : null;

        /* ข้อมูลจำลองมีกิจกรรมที่หลักสูตรไม่ได้อยู่ในโปรแกรมที่ระบุ — seed ตามข้อมูลเดิม
           แต่ต้องดังพอให้เห็น ไม่ใช่กลืนหายไปเงียบ ๆ */
        if ($course && $program && $course->program_id !== $program->id) {
            $this->command?->warn("  {$data['code']}: หลักสูตร \"{$course->name}\" ไม่ได้อยู่ในโปรแกรม \"{$program->name}\"");
        }

        return Activity::updateOrCreate(['code' => $data['code']], [
            'name' => $data['name'],
            'description' => $data['description'],
            'type' => $data['type'],
            'participant_type' => $data['participant_type'],
            'program_id' => $program?->id,
            'course_id' => $course?->id,
            'format_id' => ActivityFormat::where('name', $data['format'])->value('id'),
            'data_source' => $data['data_source'],
            'status' => $data['status'],
            'requires_registration' => $data['data_source'] === 'ลงทะเบียนออนไลน์',
            'requires_checkin' => $data['checkin_start_at'] !== null,
            'has_post_survey' => $data['has_post_survey'],
            'has_fee' => $data['fee'] > 0,
            'fee' => $data['fee'],
            'capacity' => $data['capacity'],
            'organizer' => $data['organizer'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'checkin_start_at' => $data['checkin_start_at'],
            'checkin_end_at' => $data['checkin_end_at'],
            'is_published' => $data['is_published'],
            'publish_start_at' => $data['publish_start_at'],
            'publish_end_at' => $data['publish_end_at'],
            'visibility' => $data['visibility'],
            'is_featured' => $data['is_featured'],
        ]);
    }

    private function syncPivots(Activity $activity, array $data): void
    {
        $activity->areas()->sync(Area::whereIn('name', $data['areas'])->pluck('id'));
        $activity->instructors()->sync(Instructor::whereIn('name', $data['instructors'])->pluck('id'));
        $activity->targetGroups()->sync(TargetGroup::whereIn('name', $data['target_groups'])->pluck('id'));

        /* ชั้นที่ 2 ของแบบลงทะเบียน — เปิดทุกฟิลด์เป็นค่าเริ่มต้น บังคับเฉพาะเพศกับปีเกิด
           กิจกรรมปิดฟิลด์ไหนก็ได้ แต่ตั้งชื่อตัวเลือกเองไม่ได้ (ส่วนที่ 8 ของเอกสาร schema) */
        foreach (config('farmconcept.registration_fields.toggleable') as $i => $key) {
            $activity->regFields()->updateOrCreate(['field_key' => $key], [
                'is_enabled' => true,
                'is_required' => in_array($key, ['gender', 'birth_year'], true),
                'sort_order' => $i + 1,
            ]);
        }
    }

    private function syncRounds(Activity $activity, array $rounds): void
    {
        foreach ($rounds as $round) {
            ActivityRound::updateOrCreate(
                ['activity_id' => $activity->id, 'round_date' => $round['date']],
                [
                    'time_start' => $round['start'],
                    'time_end' => $round['end'],
                    'location' => $round['location'],
                    'capacity' => $round['capacity'],
                ]
            );
        }
    }

    /**
     * ผู้ลงทะเบียนถูกสร้างแบบกำหนดผลได้ ไม่ใช้ random — เปิดหน้าซ้ำกี่ครั้งตัวเลขก็เท่าเดิม
     * จำนวนตรงกับฟิลด์ registered ของข้อมูลจำลอง เพื่อให้คอลัมน์ "ลงทะเบียน" บนหน้าจอตรงกับต้นแบบ
     */
    private function syncRegistrations(Activity $activity, int $count): void
    {
        $first = ['สมชาย', 'วิภาดา', 'ธีรพงษ์', 'กัลยา', 'ประภาส', 'มณีรัตน์', 'อดิศักดิ์', 'พิมพ์ใจ', 'ณัฐวุฒิ', 'สุพรรณี'];
        $last = ['ใจงาม', 'สายใจ', 'แสงทอง', 'รุ่งเจริญ', 'ทองแท้', 'ใจบุญ', 'พูลสวัสดิ์', 'เพียรทำ'];
        $genders = ['female', 'male', 'male', 'female', 'other'];
        $rounds = $activity->rounds()->pluck('id')->all();
        $isPast = $activity->end_date !== null && $activity->end_date->isPast();

        for ($i = 0; $i < $count; $i++) {
            $checkedIn = $isPast && $i % 5 !== 0;

            Registration::updateOrCreate(
                ['code' => $activity->code . '-R' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'activity_id' => $activity->id,
                    'activity_round_id' => $rounds ? $rounds[$i % count($rounds)] : null,
                    'name' => $first[$i % count($first)] . ' ' . $last[$i % count($last)],
                    'phone' => '08' . (($i % 9) + 1) . '-' . str_pad((string) (100 + $i), 3, '0') . '-' . str_pad((string) (1000 + $i * 7), 4, '0'),
                    'gender' => $genders[$i % count($genders)],
                    'birth_year' => 1955 + (($i * 7) % 50),
                    'payment_status' => $activity->has_fee ? ($i % 4 === 0 ? 'รอตรวจสอบ' : 'ชำระแล้ว') : 'ชำระแล้ว',
                    'checkin_status' => $checkedIn ? 'เข้าร่วมแล้ว' : 'ยังไม่เข้าร่วม',
                    'registered_at' => $activity->start_date?->copy()->subDays(($i % 12) + 1) ?? now(),
                    'checked_in_at' => $checkedIn ? $activity->start_date?->copy()->setTime(9, ($i * 3) % 60) : null,
                    'is_manual_entry' => ! $activity->requires_registration,
                ]
            );
        }
    }

    /**
     * `public` สร้างทุกกิจกรรมเสมอ — เป็นทั้ง QR ประชาสัมพันธ์และ QR ลงทะเบียน (หน้าเดียวกัน)
     * is_active ผูกกับการเผยแพร่ ไม่ใช่การบันทึก
     */
    private function syncQrCodes(Activity $activity): void
    {
        $purposes = ['public' => true];

        if ($activity->requires_checkin) {
            $purposes['checkin'] = true;
        }

        if ($activity->has_post_survey) {
            $purposes['post_survey'] = true;
        }

        foreach ($purposes as $purpose => $_) {
            $this->ensureQrCode($activity->id, $purpose, $activity->is_published, [
                'public' => 'r', 'checkin' => 'c', 'post_survey' => 's',
            ][$purpose]);
        }
    }

    /** QR ถาวรของระบบติดตามสุขภาพ — แถวเดียวทั้งระบบ ไม่ผูกกับกิจกรรมใด */
    private function syncHealthQrCode(): void
    {
        $this->ensureQrCode(null, 'health', true, 'h');
    }

    /** token สุ่มตอนสร้างครั้งแรกและคงไว้ตลอด — ห้าม derive จาก id ไม่งั้นเดา URL ของกิจกรรมที่ยังไม่เผยแพร่ได้ */
    private function ensureQrCode(?int $activityId, string $purpose, bool $isActive, string $prefix): void
    {
        $existing = QrCode::where('activity_id', $activityId)->where('purpose', $purpose)->first();
        $token = $existing?->token ?? Str::lower(Str::random(24));

        QrCode::updateOrCreate(
            ['activity_id' => $activityId, 'purpose' => $purpose],
            ['token' => $token, 'target_url' => "/{$prefix}/{$token}", 'is_active' => $isActive]
        );
    }

    private function activities(): array
    {
        return [
            [
                'code' => 'ACT-2026-014',
                'name' => 'ปลูกผักปลอดสารสำหรับครอบครัว',
                'description' => 'กิจกรรมเรียนรู้การปลูกผักปลอดสารพิษ เหมาะสำหรับครอบครัวที่ต้องการเริ่มต้นปลูกผักไว้รับประทานเอง ผู้เข้าร่วมจะได้ลงมือปฏิบัติจริงตั้งแต่การเตรียมดิน เพาะกล้า จนถึงการดูแลรักษา',
                'type' => 'กิจกรรม', 'participant_type' => 'กลุ่มตัวอย่าง',
                'program' => 'โปรแกรมกินดี อยู่ดี', 'course' => 'ปลูกผักสวนครัวเบื้องต้น', 'format' => 'WORKSHOP',
                'data_source' => 'ลงทะเบียนออนไลน์', 'status' => 'เปิดรับสมัคร',
                'areas' => ['ชุมชนพูนทรัพย์'], 'instructors' => ['ดร.กิตติพงศ์ วัฒนสุข'], 'target_groups' => ['วัยทำงาน'],
                'start_date' => '2026-08-10', 'end_date' => '2026-08-10',
                'capacity' => 40, 'registered' => 32, 'fee' => 0,
                'organizer' => 'The Farm Concept ร่วมกับสำนักงานเขตสายไหม',
                'has_post_survey' => true,
                'checkin_start_at' => '2026-08-10 08:00', 'checkin_end_at' => '2026-08-10 18:00',
                'is_published' => true, 'publish_start_at' => '2026-07-20 09:00', 'publish_end_at' => '2026-08-09 23:59',
                'visibility' => 'สาธารณะ', 'is_featured' => true,
                'rounds' => [['date' => '2026-08-10', 'start' => '09:00', 'end' => '12:00', 'location' => 'ชุมชนพูนทรัพย์', 'capacity' => 40]],
            ],
            [
                'code' => 'ACT-2026-015',
                'name' => 'Workshop อาหารสุขภาพจากสวน',
                'description' => 'เรียนรู้การนำผักและสมุนไพรจากสวนมาปรุงเป็นเมนูอาหารเพื่อสุขภาพ พร้อมความรู้ด้านโภชนาการที่เหมาะกับทุกวัย',
                'type' => 'กิจกรรม', 'participant_type' => 'กลุ่มทั่วไป',
                'program' => 'โปรแกรมกินดี อยู่ดี', 'course' => 'รู้จักอาหารหลัก 5 หมู่', 'format' => 'WORKSHOP',
                'data_source' => 'ลงทะเบียนออนไลน์', 'status' => 'เต็มแล้ว',
                'areas' => ['The Farm Concept'], 'instructors' => ['อาจารย์พิมพ์ชนก ศรีสมบัติ'], 'target_groups' => ['วัยทำงาน', 'ผู้สูงอายุ'],
                'start_date' => '2026-08-17', 'end_date' => '2026-08-17',
                'capacity' => 30, 'registered' => 30, 'fee' => 200,
                'organizer' => 'The Farm Concept',
                'has_post_survey' => true,
                'checkin_start_at' => '2026-08-17 08:00', 'checkin_end_at' => '2026-08-17 18:00',
                'is_published' => true, 'publish_start_at' => '2026-07-25 09:00', 'publish_end_at' => '2026-08-16 23:59',
                'visibility' => 'สาธารณะ', 'is_featured' => false,
                'rounds' => [['date' => '2026-08-17', 'start' => '09:00', 'end' => '15:00', 'location' => 'The Farm Concept', 'capacity' => 30]],
            ],
            [
                'code' => 'ACT-2026-016',
                'name' => 'เรียนรู้การทำปุ๋ยหมัก',
                'description' => 'อบรมเชิงปฏิบัติการทำปุ๋ยหมักจากเศษอาหารและวัสดุเหลือใช้ในครัวเรือน ลดขยะ เพิ่มความอุดมสมบูรณ์ให้ดิน',
                'type' => 'กิจกรรม', 'participant_type' => 'กลุ่มทั่วไป',
                'program' => 'โปรแกรมปลูกกินเอง', 'course' => 'ทำปุ๋ยหมักจากเศษอาหาร', 'format' => 'MIND',
                'data_source' => 'ลงทะเบียนหน้างาน', 'status' => 'เปิดรับสมัคร',
                'areas' => ['ชุมชนตึกร้าง'], 'instructors' => ['ดร.กิตติพงศ์ วัฒนสุข'], 'target_groups' => ['วัยทำงาน'],
                'start_date' => '2026-08-24', 'end_date' => '2026-09-07',
                'capacity' => 25, 'registered' => 9, 'fee' => 0,
                'organizer' => 'The Farm Concept ร่วมกับชุมชนตึกร้าง',
                'has_post_survey' => true,
                'checkin_start_at' => '2026-08-24 08:00', 'checkin_end_at' => '2026-09-07 18:00',
                'is_published' => true, 'publish_start_at' => '2026-08-01 09:00', 'publish_end_at' => '2026-08-23 23:59',
                'visibility' => 'เฉพาะกลุ่มเป้าหมาย', 'is_featured' => false,
                'rounds' => [
                    ['date' => '2026-08-24', 'start' => '09:00', 'end' => '12:00', 'location' => 'ชุมชนตึกร้าง', 'capacity' => 25],
                    ['date' => '2026-09-07', 'start' => '09:00', 'end' => '12:00', 'location' => 'ชุมชนตึกร้าง', 'capacity' => 25],
                ],
            ],
            [
                'code' => 'ACT-2026-017',
                'name' => 'กิจกรรมฟื้นฟูสุขภาวะชุมชน',
                'description' => 'กิจกรรมรวมฐานการเรียนรู้ด้านสุขภาวะ ทั้งการออกกำลังกาย โภชนาการ และการปลูกผักสวนครัว สำหรับทุกกลุ่มวัยในชุมชน',
                'type' => 'อีเว้นท์', 'participant_type' => 'กลุ่มตัวอย่าง',
                'program' => 'โปรแกรมกินดี อยู่ดี', 'course' => 'ลดหวาน มัน เค็ม', 'format' => 'COMMUNITY',
                'data_source' => 'นำเข้าจากไฟล์', 'status' => 'ดำเนินการเสร็จสิ้น',
                'areas' => ['ชุมชนพูนทรัพย์', 'ชุมชนตึกร้าง'], 'instructors' => ['คุณกัญญารัตน์ มีสุข'], 'target_groups' => ['ผู้สูงอายุ', 'วัยทำงาน'],
                'start_date' => '2026-07-20', 'end_date' => '2026-07-20',
                'capacity' => 50, 'registered' => 47, 'fee' => 0,
                'organizer' => 'The Farm Concept',
                'has_post_survey' => true,
                'checkin_start_at' => '2026-07-20 08:00', 'checkin_end_at' => '2026-07-20 18:00',
                'is_published' => true, 'publish_start_at' => '2026-06-25 09:00', 'publish_end_at' => '2026-07-19 23:59',
                'visibility' => 'สาธารณะ', 'is_featured' => false,
                'rounds' => [['date' => '2026-07-20', 'start' => '09:00', 'end' => '16:00', 'location' => 'ชุมชนพูนทรัพย์', 'capacity' => 50]],
            ],
            [
                'code' => 'ACT-2026-018',
                'name' => 'ตลาดนัดผักปลอดสารประจำเดือน',
                'description' => 'ตลาดนัดจำหน่ายผักปลอดสารพิษจากเกษตรกรในเครือข่ายชุมชน พบปะพูดคุยแลกเปลี่ยนความรู้การปลูกผักกับเกษตรกรตัวจริง',
                'type' => 'อีเว้นท์', 'participant_type' => 'กลุ่มทั่วไป',
                'program' => 'โปรแกรมปลูกกินเอง', 'course' => null, 'format' => 'COMMUNITY',
                'data_source' => 'บันทึกโดยเจ้าหน้าที่', 'status' => 'ฉบับร่าง',
                'areas' => ['ชุมชนตึกร้าง'], 'instructors' => ['คุณปกรณ์ชัย ใจดี'], 'target_groups' => ['เด็กและเยาวชน', 'วัยทำงาน', 'ผู้สูงอายุ'],
                'start_date' => '2026-09-05', 'end_date' => '2026-09-05',
                'capacity' => 60, 'registered' => 4, 'fee' => 0,
                'organizer' => 'The Farm Concept ร่วมกับชุมชนตึกร้าง',
                'has_post_survey' => false,
                'checkin_start_at' => null, 'checkin_end_at' => null,
                'is_published' => false, 'publish_start_at' => null, 'publish_end_at' => null,
                'visibility' => 'สาธารณะ', 'is_featured' => false,
                'rounds' => [['date' => '2026-09-05', 'start' => '08:00', 'end' => '12:00', 'location' => 'ชุมชนตึกร้าง', 'capacity' => 60]],
            ],
        ];
    }
}
