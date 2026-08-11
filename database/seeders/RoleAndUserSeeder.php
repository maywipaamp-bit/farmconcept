<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * บทบาท สิทธิ์ระดับเมนู และผู้ใช้เดโม — ถอดจาก TFC_MOCK.roles / TFC_MOCK.users
 *
 * ผู้ใช้ที่ seed ที่นี่ใช้รหัสผ่านที่รู้กันสำหรับพัฒนาเท่านั้น
 * จึงมี guard กัน production ไว้ — ห้ามถอด guard นี้ออก
 */
class RoleAndUserSeeder extends Seeder
{
    /** รหัสผ่านของผู้ใช้เดโมทุกคน — ใช้กับเครื่องพัฒนาเท่านั้น มี guard กัน production ไว้ใน run() */
    private const DEV_PASSWORD = '4321';

    /** คีย์เมนูทั้งหมด ต้องตรงกับ assets/js/menu-config.js — เพิ่มเมนูที่นั่นแล้วต้องมาเพิ่มที่นี่ */
    private const MENU_KEYS = [
        'dashboard',
        'activities', 'activities-list', 'activities-registrants', 'activities-checkin', 'activities-responses',
        'health-assessment', 'cohort', 'evaluations-rounds', 'evaluations-responses',
        'evaluations',
        'master-data', 'master-data-areas', 'master-data-target-groups', 'master-data-programs',
        'master-data-instructors', 'master-data-activity-formats', 'master-data-follow-up-rounds',
        'users', 'users-list', 'users-roles',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('RoleAndUserSeeder สร้างบัญชีที่มีรหัสผ่านรู้กัน ห้ามรันบน production');
        }

        $this->seedRoles();
        $this->seedUsers();
    }

    private function seedRoles(): void
    {
        /* deny = คีย์เมนูที่บทบาทนั้นเข้าไม่ได้ ที่เหลืออนุญาตหมด
           เขียนแบบ deny-list เพราะสั้นกว่าและอ่านออกว่าบทบาทไหนถูกตัดอะไรบ้าง */
        $roles = [
            [
                'code' => 'super_admin', 'name' => 'ผู้ดูแลระบบสูงสุด',
                'description' => 'จัดการโครงการ ผู้ใช้งาน และข้อมูลกลางทั้งหมดของระบบ',
                'deny' => [],
            ],
            [
                'code' => 'project_admin', 'name' => 'ผู้ดูแลโครงการ',
                'description' => 'จัดการพื้นที่ กิจกรรม และรายงานภายในโครงการที่รับผิดชอบ',
                'deny' => [],
            ],
            [
                'code' => 'staff', 'name' => 'เจ้าหน้าที่โครงการ',
                'description' => 'จัดการกิจกรรม ลงทะเบียน ตรวจสอบการชำระเงิน และติดตามผลในพื้นที่ที่รับผิดชอบ',
                'deny' => [
                    'master-data', 'master-data-areas', 'master-data-target-groups', 'master-data-programs',
                    'master-data-instructors', 'master-data-activity-formats', 'master-data-follow-up-rounds',
                    'users', 'users-list', 'users-roles',
                ],
            ],
            [
                'code' => 'participant', 'name' => 'ผู้เข้าร่วมกิจกรรม',
                'description' => 'ลงทะเบียนกิจกรรม แนบหลักฐานการชำระเงิน และทำแบบประเมิน',
                'deny' => self::MENU_KEYS,
            ],
        ];

        foreach ($roles as $role) {
            DB::table('usr_roles')->updateOrInsert(['code' => $role['code']], [
                'name' => $role['name'], 'description' => $role['description'], 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $roleId = DB::table('usr_roles')->where('code', $role['code'])->value('id');

            foreach (self::MENU_KEYS as $key) {
                DB::table('usr_role_menu_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'menu_key' => $key],
                    ['is_allowed' => ! in_array($key, $role['deny'], true)]
                );
            }
        }
    }

    private function seedUsers(): void
    {
        /* [code, ชื่อ, username, อีเมล, รหัสพื้นที่, สถานะ, บทบาททั้งหมดของคนนั้น]
           USR-002 มีสองบทบาท — เป็นเหตุผลที่ usr_role_user เป็น many-to-many ไม่ใช่คอลัมน์เดียว */
        $users = [
            /* บัญชีสำหรับพัฒนา — ต้นแบบใช้ชื่อ admin ในหน้าล็อกอินอยู่แล้ว จึงคงชื่อเดิมไว้
               ไม่ได้อยู่ในชุดข้อมูลจำลอง เพิ่มเข้ามาเพื่อให้เข้าระบบทดสอบได้เร็ว */
            ['USR-000', 'ผู้ดูแลระบบ', 'admin', 'admin@thefarmconcept.org', null, 'ใช้งานอยู่', ['super_admin']],
            ['USR-001', 'สุนิสา แก้วมณี', 'sunisa01', 'sunisa@thefarmconcept.org', 'AREA-002', 'ใช้งานอยู่', ['staff']],
            ['USR-002', 'วีระ ศรีสมบัติ', 'weera02', 'weera@thefarmconcept.org', 'AREA-001', 'ใช้งานอยู่', ['project_admin', 'staff']],
            ['USR-003', 'ปิยะดา รุ่งเรือง', 'piyada03', 'piyada@thefarmconcept.org', 'AREA-003', 'ระงับการใช้งาน', ['staff']],
            ['USR-004', 'ธนากร ใจดี', 'thanakorn04', 'thanakorn@thefarmconcept.org', 'AREA-003', 'ใช้งานอยู่', ['staff']],
            ['USR-005', 'อรุณี ทองสุข', 'arunee05', 'arunee@thefarmconcept.org', null, 'ใช้งานอยู่', ['super_admin']],
        ];

        foreach ($users as [$code, $name, $username, $email, $areaCode, $status, $roleCodes]) {
            $areaId = $areaCode ? DB::table('mst_areas')->where('code', $areaCode)->value('id') : null;

            DB::table('users')->updateOrInsert(['code' => $code], [
                'name' => $name, 'username' => $username, 'email' => $email,
                'password' => Hash::make(self::DEV_PASSWORD),
                'area_id' => $areaId, 'status' => $status,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $userId = DB::table('users')->where('code', $code)->value('id');

            DB::table('usr_role_user')->where('user_id', $userId)->delete();
            foreach ($roleCodes as $roleCode) {
                DB::table('usr_role_user')->insert([
                    'user_id' => $userId,
                    'role_id' => DB::table('usr_roles')->where('code', $roleCode)->value('id'),
                ]);
            }
        }
    }
}
