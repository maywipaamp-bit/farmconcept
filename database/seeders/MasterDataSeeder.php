<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลพื้นฐานทั้งหมด — ถอดจาก assets/js/mock-data.js และ followup-template-service.js
 *
 * ทุกตารางใช้ updateOrInsert อ้าง `code` จึงรันซ้ำได้โดยไม่เกิดข้อมูลซ้ำ
 * ค่าที่คำนวณได้ (activityCount / memberCount / avgSatisfaction) ไม่ถูก seed
 * เพราะไม่มีคอลัมน์เก็บ — ดู docs/database-schema-proposal.md ส่วนที่ 3
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOptions();
        $this->seedDistricts();
        $this->seedAreas();
        $this->seedTargetGroups();
        $this->seedPrograms();
        $this->seedInstructors();
        $this->seedActivityFormats();
        $this->seedFollowUpRoundTemplates();
    }

    /**
     * รายการแบนทั้งหมดอยู่ตารางเดียว (ปม F.3)
     *
     * occupation: ต้นแบบมีสองชุดที่ไม่ตรงกัน — registrationOptions.occupations 7 ค่า
     * กับ cohort.JOBS 5 ค่า ที่นี่ seed เป็นชุดรวมโดยยึดคำของชุดแรกเป็นหลัก
     * และเพิ่ม "รับจ้างทั่วไป" ที่มีเฉพาะในชุดที่สอง
     * >>> รอทีมธุรกิจชี้ขาดชุดจริง (ปม F.5) แก้ที่นี่ที่เดียวแล้วรัน seeder ซ้ำ <<<
     */
    private function seedOptions(): void
    {
        $groups = [
            'occupation' => [
                'รับราชการ', 'พนักงานบริษัท', 'ธุรกิจส่วนตัว', 'เกษตรกร',
                'นักเรียน/นักศึกษา', 'แม่บ้าน', 'เกษียณอายุ', 'รับจ้างทั่วไป',
            ],
            'source_channel' => [
                'Facebook', 'LINE OA', 'เว็บไซต์', 'เพื่อนแนะนำ', 'ผู้นำชุมชน', 'สื่อสิ่งพิมพ์',
            ],
            'interest' => [
                'ปลูกผักปลอดสาร', 'ทำปุ๋ยหมัก', 'อาหารเพื่อสุขภาพ', 'สมุนไพรพื้นบ้าน',
                'ออกกำลังกาย', 'สุขภาพจิต', 'เกษตรอินทรีย์',
            ],
            'contact_channel' => ['โทรศัพท์', 'LINE', 'อีเมล', 'ผ่านผู้ดูแล'],
            'note_kind' => ['โทรติดตาม', 'เยี่ยมบ้าน', 'ส่งข้อความ LINE', 'พบที่กิจกรรม', 'ฝากผู้นำชุมชน', 'ส่ง SMS'],
            'purchase_channel' => ['หน้าร้าน', 'LINE OA', 'ออกบูธกิจกรรม', 'สั่งล่วงหน้า'],
        ];

        foreach ($groups as $group => $labels) {
            foreach ($labels as $i => $label) {
                DB::table('mst_options')->updateOrInsert(
                    ['option_group' => $group, 'code' => $this->slug($group, $i)],
                    ['label' => $label, 'sort_order' => $i + 1, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    /** code ของ mst_options เป็นรหัสสั้นคงที่ ไม่ใช่ข้อความไทย — เปลี่ยน label ได้โดยข้อมูลเก่าไม่กำพร้า */
    private function slug(string $group, int $index): string
    {
        return $group . '-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    }

    private function seedDistricts(): void
    {
        $map = [
            'กรุงเทพมหานคร' => ['เขตบางนา', 'เขตสายไหม', 'เขตบางพลัด', 'เขตบางเขน', 'เขตดอนเมือง', 'เขตจตุจักร'],
            'ปทุมธานี' => ['อำเภอเมืองปทุมธานี', 'อำเภอคลองหลวง', 'อำเภอลำลูกกา', 'อำเภอธัญบุรี'],
            'นนทบุรี' => ['อำเภอเมืองนนทบุรี', 'อำเภอปากเกร็ด', 'อำเภอบางบัวทอง'],
            'สมุทรปราการ' => ['อำเภอเมืองสมุทรปราการ', 'อำเภอบางพลี', 'อำเภอบางบ่อ'],
        ];

        foreach ($map as $province => $districts) {
            foreach ($districts as $name) {
                DB::table('mst_districts')->updateOrInsert(['province' => $province, 'name' => $name], []);
            }
        }
    }

    private function seedAreas(): void
    {
        $rows = [
            [
                'code' => 'AREA-001', 'name' => 'The Farm Concept',
                'province' => 'กรุงเทพมหานคร', 'district' => 'เขตบางนา',
                'area_type' => 'เอกชน', 'area_group' => 'พื้นที่ต้นแบบ',
                'start_date' => '2024-06-01', 'partner_org' => 'สสส. พลเมืองอาสา',
                'coordinator_name' => 'วีระ ศรีสมบัติ', 'coordinator_phone' => '082-222-3333',
                'coordinator_position' => 'หัวหน้าพื้นที่ต้นแบบ',
                'map_url' => 'https://maps.google.com/?q=The+Farm+Concept+บางนา',
            ],
            [
                'code' => 'AREA-002', 'name' => 'ชุมชนพูนทรัพย์',
                'province' => 'กรุงเทพมหานคร', 'district' => 'เขตสายไหม',
                'area_type' => 'ชุมชน/หมู่บ้าน', 'area_group' => 'พื้นที่ต้นแบบส่วนขยาย',
                'start_date' => '2025-01-15', 'partner_org' => 'สสส. พลเมืองอาสา',
                'coordinator_name' => 'อรุณี ทองสุข', 'coordinator_phone' => '081-111-2222',
                'coordinator_position' => 'ผู้ประสานงานชุมชน',
                'map_url' => 'https://maps.google.com/?q=ชุมชนพูนทรัพย์+เขตสายไหม',
            ],
            [
                'code' => 'AREA-003', 'name' => 'ชุมชนตึกร้าง',
                'province' => 'กรุงเทพมหานคร', 'district' => 'เขตบางพลัด',
                'area_type' => 'ชุมชน/หมู่บ้าน', 'area_group' => 'พื้นที่ต้นแบบส่วนขยาย',
                'start_date' => '2025-03-10', 'partner_org' => 'สสส. พลเมืองอาสา',
                'coordinator_name' => 'ปิยะดา รุ่งเรือง', 'coordinator_phone' => '083-333-4444',
                'coordinator_position' => 'ผู้ประสานงานชุมชน',
                'map_url' => 'https://maps.google.com/?q=ชุมชนตึกร้าง+เขตบางพลัด',
            ],
        ];

        foreach ($rows as $row) {
            $code = $row['code'];
            unset($row['code']);
            $row['status'] = 'ดำเนินการอยู่';
            DB::table('mst_areas')->updateOrInsert(['code' => $code], $row + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function seedTargetGroups(): void
    {
        $rows = [
            ['TG-001', 'เด็กและเยาวชน', '6-18 ปี', 5000],
            ['TG-002', 'วัยทำงาน', '19-59 ปี', 2000],
            ['TG-003', 'ผู้สูงอายุ', '60 ปีขึ้นไป', 1000],
            ['TG-004', 'กลุ่มเปราะบาง', 'ทุกช่วงวัย', 1000],
        ];

        foreach ($rows as $i => [$code, $name, $ageRange, $targetCount]) {
            DB::table('mst_target_groups')->updateOrInsert(['code' => $code], [
                'name' => $name, 'age_range' => $ageRange, 'target_count' => $targetCount,
                'is_active' => true, 'sort_order' => $i + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedPrograms(): void
    {
        $programs = [
            ['PROG-001', 'โปรแกรมกินดี อยู่ดี', 'โภชนาการ',
                ['รู้จักอาหารหลัก 5 หมู่', 'ผัก 5 สี สุขภาพดีทุกวัน', 'ลดหวาน มัน เค็ม', 'อ่านฉลากอาหารให้เป็น']],
            ['PROG-002', 'โปรแกรมปลูกกินเอง', 'เกษตรและอาหาร',
                ['ปลูกผักสวนครัวเบื้องต้น', 'ปลูกผักในพื้นที่จำกัด', 'ทำปุ๋ยหมักจากเศษอาหาร', 'จากแปลงสู่จาน']],
            ['PROG-003', 'โปรแกรม Food Literacy', 'ความรอบรู้ด้านอาหาร',
                ['รู้เลือก รู้กิน', 'จ่ายตลาดอย่างฉลาด', 'รู้จักอาหารปลอดภัย', 'วางแผนมื้ออาหารสุขภาพ']],
            ['PROG-004', 'โปรแกรมครัวสุขภาวะ', 'ครัวและการปรุงอาหาร',
                ['เมนูสุขภาพทำง่าย', 'Cooking Workshop ลดหวาน มัน เค็ม', 'อาหารสำหรับครอบครัว', 'ครัวชุมชนเพื่อสุขภาวะ']],
        ];

        foreach ($programs as [$code, $name, $category, $courses]) {
            DB::table('mst_programs')->updateOrInsert(['code' => $code], [
                'name' => $name, 'category' => $category,
                'status' => 'ดำเนินการอยู่', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $programId = DB::table('mst_programs')->where('code', $code)->value('id');

            foreach ($courses as $i => $courseName) {
                DB::table('mst_courses')->updateOrInsert(
                    ['program_id' => $programId, 'name' => $courseName],
                    ['sort_order' => $i + 1, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function seedInstructors(): void
    {
        $rows = [
            ['INS-001', 'ดร.กิตติพงศ์ วัฒนสุข', '08x-xxx-1111', 'ผู้เชี่ยวชาญด้านโภชนาการและสุขภาวะ',
                ['โภชนาการ', 'สุขภาวะชุมชน'], ['รู้จักอาหารหลัก 5 หมู่', 'อ่านฉลากอาหารให้เป็น']],
            ['INS-002', 'อาจารย์พิมพ์ชนก ศรีสมบัติ', '08x-xxx-2222', 'วิทยากรด้านอาหารและการปรับเปลี่ยนพฤติกรรม',
                ['อาหารเพื่อสุขภาพ', 'การปรับเปลี่ยนพฤติกรรม'], ['ผัก 5 สี สุขภาพดีทุกวัน', 'ลดหวาน มัน เค็ม', 'รู้เลือก รู้กิน']],
            ['INS-003', 'คุณภูริณัฐ วงศ์สวัสดิ์', '08x-xxx-3333', 'วิทยากรด้านสุขภาพและการออกกำลังกาย',
                ['สุขภาพ', 'การออกกำลังกาย'], ['วางแผนมื้ออาหารสุขภาพ']],
            ['INS-004', 'คุณกัญญารัตน์ มีสุข', '08x-xxx-4444', 'วิทยากรด้านการดูแลสุขภาวะครอบครัว',
                ['สุขภาวะครอบครัว', 'การดูแลผู้สูงอายุ'], ['ปลูกผักสวนครัวเบื้องต้น', 'จากแปลงสู่จาน']],
            ['INS-005', 'คุณปกรณ์ชัย ใจดี', '08x-xxx-5555', 'วิทยากรด้านการแปรรูปผลิตภัณฑ์ชุมชน',
                ['การแปรรูปอาหาร', 'ผลิตภัณฑ์ชุมชน'], ['ทำปุ๋ยหมักจากเศษอาหาร', 'จ่ายตลาดอย่างฉลาด']],
        ];

        foreach ($rows as [$code, $name, $phone, $expertise, $expertiseList, $courseNames]) {
            DB::table('mst_instructors')->updateOrInsert(['code' => $code], [
                'name' => $name, 'phone' => $phone, 'expertise' => $expertise, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $instructorId = DB::table('mst_instructors')->where('code', $code)->value('id');

            DB::table('mst_instructor_expertises')->where('instructor_id', $instructorId)->delete();
            foreach ($expertiseList as $item) {
                DB::table('mst_instructor_expertises')->insert(['instructor_id' => $instructorId, 'name' => $item]);
            }

            /* courseList ในต้นแบบอ้างหลักสูตรด้วยชื่อ — แปลงเป็น id ตอน seed
               ชื่อที่หาไม่เจอจะถูกข้ามเงียบ ๆ ไม่ได้ ต้องดังพอให้เห็นตอนรัน */
            DB::table('mst_instructor_course')->where('instructor_id', $instructorId)->delete();
            foreach ($courseNames as $courseName) {
                $courseId = DB::table('mst_courses')->where('name', $courseName)->value('id');

                if (! $courseId) {
                    $this->command?->warn("  ข้าม: ไม่พบหลักสูตร \"{$courseName}\" ของวิทยากร {$name}");
                    continue;
                }

                DB::table('mst_instructor_course')->insert([
                    'instructor_id' => $instructorId, 'course_id' => $courseId,
                ]);
            }
        }
    }

    private function seedActivityFormats(): void
    {
        $rows = [
            ['FMT-001', 'CRAFT', 'craft'],
            ['FMT-002', 'MIND', 'heart'],
            ['FMT-003', 'FOOD', 'food'],
            ['FMT-004', 'WORKSHOP', 'tool'],
            ['FMT-005', 'COMMUNITY', 'users'],
        ];

        foreach ($rows as [$code, $name, $icon]) {
            DB::table('mst_activity_formats')->updateOrInsert(['code' => $code], [
                'name' => $name, 'icon' => $icon, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /**
     * ค่าตั้งต้นชุดเดียวกับ followupTemplateService.defaults()
     * offset_days เป็น UNIQUE — ห้ามซ้ำ ไม่งั้นคนหนึ่งคนได้รอบครบกำหนดวันเดียวกันสองรอบ
     * line_notify / notify_days_before ย้ายมาจาก sampleFollowUpRounds ชุดเก่า (ปม B)
     */
    private function seedFollowUpRoundTemplates(): void
    {
        $rows = [
            ['FRT-1', 'ก่อนเข้าร่วม', 0, true, 7],
            ['FRT-2', '3 เดือน', 90, true, 7],
            ['FRT-3', '6 เดือน', 180, true, 7],
            ['FRT-4', '12 เดือน', 365, false, 14],
        ];

        foreach ($rows as $i => [$code, $name, $offsetDays, $lineNotify, $notifyDaysBefore]) {
            DB::table('mst_follow_up_round_templates')->updateOrInsert(['code' => $code], [
                'name' => $name, 'offset_days' => $offsetDays,
                'is_active' => true, 'sort_order' => $i + 1,
                'line_notify' => $lineNotify, 'notify_days_before' => $notifyDaysBefore,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
