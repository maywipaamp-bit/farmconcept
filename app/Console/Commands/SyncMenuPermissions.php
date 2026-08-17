<?php

namespace App\Console\Commands;

use Database\Seeders\RoleAndUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * เติมสิทธิ์เมนูของบทบาทให้ครบตาม config/menu.php
 *
 * ทำไมต้องมี: การไม่มีแถวใน usr_role_menu_permissions มีค่าเท่ากับ "ไม่มีสิทธิ์"
 * เพิ่มเมนูใหม่ใน config แล้วไม่เติมแถว ทุกบทบาทจะเข้าเมนูนั้นไม่ได้ทันที
 * และอาการที่เห็นคือ 403 ทั้งที่โค้ดถูกต้อง — เคยเกิดมาแล้วกับ activities-registrants,
 * activities-checkin, activities-responses และ reports-people
 *
 * ปลอดภัยกว่าการรัน RoleAndUserSeeder ทั้งตัว เพราะ seeder นั้น updateOrInsert ตาราง users ด้วย
 * ซึ่งจะรีเซ็ตรหัสผ่านของผู้ใช้จริงทุกคนเป็นรหัสสำหรับพัฒนา
 *
 * เติมเฉพาะแถวที่ยังไม่มี — ไม่แตะแถวที่มีอยู่แล้ว การปิดสิทธิ์ที่แอดมินตั้งไว้เองจึงไม่ถูกเปิดกลับ
 */
class SyncMenuPermissions extends Command
{
    protected $signature = 'menu:sync-permissions {--dry-run : แสดงแถวที่จะเพิ่ม โดยยังไม่เขียนลงฐาน}';

    protected $description = 'เติมสิทธิ์เมนูที่ยังไม่มีแถวให้แต่ละบทบาท ตามกติกาใน RoleAndUserSeeder';

    public function handle(): int
    {
        /* คีย์ทั้งหมดที่ config รู้จัก รวมหัวข้อหมวดที่มี href ของตัวเองด้วย */
        $keys = [];

        foreach (config('menu.items') as $item) {
            if (isset($item['href'])) {
                $keys[] = $item['key'];
            }

            foreach ($item['children'] ?? [] as $child) {
                $keys[] = $child['key'];
            }

            /* หัวข้อหมวดที่มีเมนูย่อย — เก็บคีย์ไว้ด้วยเพราะ permission_map อ้างถึง */
            if (! empty($item['children'])) {
                $keys[] = $item['key'];
            }
        }

        $keys = array_values(array_unique($keys));
        $denyByRole = collect(RoleAndUserSeeder::roleDefinitions())->keyBy('code');
        $rows = [];

        foreach (DB::table('usr_roles')->get(['id', 'code', 'name']) as $role) {
            $definition = $denyByRole->get($role->code);

            if (! $definition) {
                $this->warn('ข้ามบทบาท "'.$role->code.'" — ไม่มีกติกาสิทธิ์กำหนดไว้ใน RoleAndUserSeeder');

                continue;
            }

            foreach ($keys as $key) {
                $exists = DB::table('usr_role_menu_permissions')
                    ->where('role_id', $role->id)
                    ->where('menu_key', $key)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $allowed = ! in_array($key, $definition['deny'], true);
                $rows[] = ['role_id' => $role->id, 'menu_key' => $key, 'is_allowed' => $allowed, 'role' => $role->code];
            }
        }

        if ($rows === []) {
            $this->info('สิทธิ์เมนูครบทุกบทบาทแล้ว ไม่มีอะไรต้องเติม');

            return self::SUCCESS;
        }

        $this->table(
            ['บทบาท', 'เมนู', 'สิทธิ์'],
            array_map(fn (array $r) => [$r['role'], $r['menu_key'], $r['is_allowed'] ? 'อนุญาต' : 'ไม่อนุญาต'], $rows)
        );

        if ($this->option('dry-run')) {
            $this->warn('โหมด --dry-run: ยังไม่ได้เขียนลงฐาน');

            return self::SUCCESS;
        }

        foreach ($rows as $r) {
            DB::table('usr_role_menu_permissions')->insert([
                'role_id' => $r['role_id'],
                'menu_key' => $r['menu_key'],
                'is_allowed' => $r['is_allowed'],
            ]);
        }

        $this->info('เติมสิทธิ์เมนู '.count($rows).' แถวเรียบร้อย');

        return self::SUCCESS;
    }
}
