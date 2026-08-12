<?php

namespace Database\Seeders;

use App\Models\ReviewItem;
use App\Models\ReviewRound;
use Illuminate\Database\Seeder;

/**
 * รอบส่งงานให้ตรวจ — หนึ่งแถวต่อหนึ่งเมนูของระบบ
 *
 * รายการสร้างจาก config/menu.php ทั้งหมด ไม่ได้พิมพ์รายชื่อซ้ำไว้ที่นี่
 * เพิ่มเมนูใหม่ในระบบแล้วรัน seeder ซ้ำ รายการตรวจงานจะมีเมนูนั้นตามมาเอง
 *
 * รันซ้ำได้ — เมนูที่มีอยู่แล้วจะคงสถานะ วันครบกำหนด และคอมเมนต์เดิมไว้
 * อัปเดตเฉพาะคำอธิบายฟังก์ชันกับลำดับ
 */
class ReviewRoundSeeder extends Seeder
{
    /** ฟังก์ชันที่ใช้งานได้จริงในแต่ละเมนู — คอลัมน์ "หน้าจอ" ในตารางตรวจงาน */
    private const FUNCTIONS = [
        'dashboard' => 'ตัวเลขสรุป · กราฟผู้เข้าร่วม · แผนภาพพื้นที่ · ตัวกรองช่วงเวลา',

        'activities' => 'กลุ่มเมนูจัดการกิจกรรม',
        'activities-list' => 'ค้นหา · กรองสถานะ · เพิ่ม/แก้ไข/ลบกิจกรรม · แบ่งหน้า',
        'activities-registrants' => 'รายชื่อผู้ลงทะเบียน · ค้นหา · ส่งออกข้อมูล',
        'activities-checkin' => 'เช็กอินหน้างาน · ค้นหาผู้เข้าร่วม',
        'activities-responses' => 'ผลประเมินรายกิจกรรม · สรุปคะแนน',

        'health-assessment' => 'กลุ่มเมนูประเมินสุขภาพ',
        'cohort' => 'ทะเบียนกลุ่มตัวอย่าง · รอบติดตามรายคน · บันทึกผล',
        'evaluations-rounds' => 'ตั้งช่วงเวลารอบติดตาม · ติดตามความคืบหน้า',
        'evaluations-responses' => 'คำตอบแบบประเมินรายคน · สรุปผล',

        'evaluations' => 'สร้างและแก้ไขแบบประเมิน · จัดชุดคำถาม',

        'master-data' => 'กลุ่มเมนูข้อมูลพื้นฐาน',
        'master-data-areas' => 'ค้นหา · กรอง · เพิ่ม/แก้ไข/ลบพื้นที่ · ผู้ประสานงาน · ผลรวมท้ายตาราง',
        'master-data-target-groups' => 'ค้นหา · กรอง · เพิ่ม/แก้ไข/ลบกลุ่มเป้าหมาย · ผลรวมจำนวนเป้าหมาย',
        'master-data-programs' => 'ค้นหา · กรอง · เพิ่ม/แก้ไขโปรแกรม · จัดการหลักสูตรย่อย',
        'master-data-instructors' => 'ค้นหา · กรอง · เพิ่ม/แก้ไขวิทยากร · รูปภาพ · ประวัติการสอน',
        'master-data-activity-formats' => 'ค้นหา · กรอง · เพิ่ม/แก้ไขหมวดหมู่ · เลือกไอคอน',
        'master-data-follow-up-rounds' => 'ตั้งระยะห่างของรอบเป็นวัน · ทดลองคำนวณวันครบกำหนด',

        'users' => 'กลุ่มเมนูผู้ใช้งาน',
        'users-list' => 'ค้นหา · กรองสถานะ · เพิ่ม/แก้ไขผู้ใช้ · ระงับ/คืนสิทธิ์ · บทบาทหลายค่า',
        'users-roles' => 'ค้นหา · กรอง · เพิ่ม/แก้ไขบทบาท · ตั้งสิทธิ์เข้าถึงเมนู',
    ];

    /**
     * เมนูที่พัฒนาเสร็จและเปิดให้ตรวจแล้วในรอบนี้
     *
     * ตั้งเฉพาะตอนสร้างแถวครั้งแรก — รันซ้ำจะไม่ทับสถานะที่ทีมงานหรือผู้ตรวจเปลี่ยนไปแล้ว
     */
    private const READY = [
        'dashboard', 'activities-list',
        'master-data-areas', 'master-data-target-groups', 'master-data-programs',
        'master-data-instructors', 'master-data-activity-formats', 'master-data-follow-up-rounds',
        'users-list', 'users-roles',
    ];

    public function run(): void
    {
        $round = ReviewRound::current() ?? ReviewRound::create([
            'round_no' => 1,
            'sender' => 'TheFarmConcept',
            'project_name' => 'แผนงานพัฒนาระบบติดตามและประเมินผลการเปลี่ยนแปลงสุขภาพระดับบุคคล',
            'project_start' => '2026-08-01',
            'project_end' => '2026-08-20',
            'action_plan_url' => 'https://docs.google.com/spreadsheets/d/1LktQcZ1Mk0_d3stfqh7hMASJ25vO9wrLlRLVzrWFnGs/edit?usp=sharing',
            'system_url' => 'http://157.85.104.53/login',
            'login_hint' => 'admin / 1234',
            'sent_at' => now()->toDateString(),
            'due_at' => now()->addDays(7)->toDateString(),
            'is_open' => true,
        ]);

        $order = 0;

        foreach (config('menu.items', []) as $item) {
            $this->sync($round, $item, ++$order);

            foreach ($item['children'] ?? [] as $child) {
                $this->sync($round, $child, ++$order);
            }
        }
    }

    private function sync(ReviewRound $round, array $menu, int $order): void
    {
        $key = $menu['key'];
        $ready = in_array($key, self::READY, true);

        $item = ReviewItem::firstOrNew(['round_id' => $round->id, 'menu_key' => $key]);

        /* คำอธิบายฟังก์ชันกับลำดับอัปเดตได้ทุกครั้ง — เป็นข้อมูลของโค้ด ไม่ใช่ของผู้ใช้ */
        $item->screen = self::FUNCTIONS[$key] ?? 'ยังไม่ได้ระบุฟังก์ชัน';
        $item->url = isset($menu['href']) ? '/'.ltrim($menu['href'], '/') : null;
        $item->sort_order = $order;

        /* สถานะกับวันครบกำหนดตั้งเฉพาะตอนสร้างใหม่ ไม่ทับของที่คนแก้ไว้แล้ว */
        if (! $item->exists) {
            $item->status = $ready ? 'ตรวจได้' : 'รอพัฒนา';
            $item->due_date = $ready ? $round->due_at : null;
        }

        $item->save();
    }
}
