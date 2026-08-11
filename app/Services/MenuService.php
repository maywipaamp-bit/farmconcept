<?php

namespace App\Services;

use App\Models\User;

/**
 * โครงเมนูและการตรวจสิทธิ์ระดับเมนู
 *
 * อ่าน config/menu.php ที่เป็นแหล่งความจริงเดียว แล้วให้บริการสองอย่าง
 *   1. filterFor()  — โครงเมนูที่ตัดรายการที่ผู้ใช้ไม่มีสิทธิ์ออกแล้ว สำหรับวาดแถบซ้าย
 *   2. menuKeyFor() — แปลง URL ที่ขอมาเป็น menu_key เพื่อให้ middleware ตรวจสิทธิ์ก่อน render
 */
class MenuService
{
    /**
     * โครงเมนูสำหรับผู้ใช้คนหนึ่ง — ตัดรายการที่ไม่มีสิทธิ์ออก
     * หัวข้อกลุ่มที่ลูกถูกตัดหมดจะถูกตัดตามไปด้วย ไม่เหลือหัวข้อว่าง ๆ ที่กดแล้วไม่มีอะไร
     *
     * @return array<int, array<string, mixed>>
     */
    public function filterFor(User $user): array
    {
        $items = [];

        foreach (config('menu.items') as $item) {
            if (isset($item['children'])) {
                $children = array_values(array_filter(
                    $item['children'],
                    fn (array $child) => $user->canAccessMenu($child['key'])
                ));

                if ($children === []) {
                    continue;
                }

                $items[] = ['children' => $children] + $item;

                continue;
            }

            if ($user->canAccessMenu($item['key'])) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * menu_key ของ path ที่ขอมา
     *
     * คืน false เมื่อไม่รู้จัก path นั้น — ผู้เรียกต้องปฏิเสธ ไม่ใช่ปล่อยผ่าน
     * คืน null เมื่อเป็นหน้าที่ทุกคนที่ล็อกอินแล้วเข้าได้ (เช่น หน้าโปรไฟล์ของตัวเอง)
     */
    public function menuKeyFor(string $path): string|null|false
    {
        $path = ltrim($path, '/');

        foreach ($this->flatten() as $item) {
            $candidates = array_merge([$item['href'] ?? null], $item['alsoMatch'] ?? []);

            if (in_array($path, array_filter($candidates), true)) {
                return $item['key'];
            }
        }

        $extra = config('menu.extra_paths');

        return array_key_exists($path, $extra) ? $extra[$path] : false;
    }

    /**
     * สิทธิ์แบบกว้างที่ผู้ใช้มี — คำนวณจากสิทธิ์ระดับเมนูตาม permission_map
     * รูปแบบเดียวกับ roles[].permissions ของต้นแบบ จึงเสียบแทนได้เลย
     *
     * @return array<string, bool>
     */
    public function broadPermissionsFor(User $user): array
    {
        $result = [];

        foreach (config('menu.permission_map') as $permission => $menuKeys) {
            $result[$permission] = collect($menuKeys)
                ->contains(fn (string $key) => $user->canAccessMenu($key));
        }

        return $result;
    }

    /**
     * ก้อนข้อมูลที่ฝั่งหน้าจอต้องใช้ทั้งหมด — ฉีดเป็นสคริปต์เดียวทั้งหน้า Blade และหน้า static เดิม
     *
     * รูปแบบตรงกับที่ต้นแบบคาดไว้ทุกตัว (TFC_MENU · TFC_PERMISSION_MAP · TFC_MOCK.roles)
     * สคริปต์เดิมจึงทำงานต่อได้โดยไม่ต้องแก้ แต่ค่าที่ได้เป็นสิทธิ์จริงของผู้ใช้ที่ล็อกอินอยู่
     *
     * เมนูที่ถูกกรองออกแล้วจะไม่ถูกส่งมาเลย ไม่ใช่ส่งมาแล้วซ่อนด้วย CSS
     * ผู้ใช้จึงอ่านชื่อเมนูที่ตัวเองไม่มีสิทธิ์ไม่ได้แม้จะเปิดดู source
     *
     * @return array<string, mixed>
     */
    public function clientContextFor(User $user): array
    {
        $menuPermissions = [];

        foreach ($this->flatten() as $item) {
            $menuPermissions[$item['key']] = $user->canAccessMenu($item['key']);
        }

        return [
            'menu' => $this->filterFor($user),
            'permissionMap' => config('menu.permission_map'),
            'currentUser' => $user->toClientPayload(),
            'role' => [
                'code' => $user->roles->first()?->code ?? '',
                'menuPermissions' => $menuPermissions,
                'permissions' => $this->broadPermissionsFor($user),
            ],
        ];
    }

    /**
     * เมนูทุกระดับเรียงเป็นชั้นเดียว — หัวข้อกลุ่มที่ไม่มี href ถูกตัดออก
     *
     * @return array<int, array<string, mixed>>
     */
    private function flatten(): array
    {
        $flat = [];

        foreach (config('menu.items') as $item) {
            if (isset($item['href'])) {
                $flat[] = $item;
            }

            foreach ($item['children'] ?? [] as $child) {
                $flat[] = $child;
            }
        }

        return $flat;
    }
}
