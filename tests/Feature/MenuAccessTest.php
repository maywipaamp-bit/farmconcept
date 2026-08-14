<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * สิทธิ์ระดับเมนู (EnsureMenuAccess)
 *
 * เคสที่พังจริงบนเครื่องผู้ใช้: ผู้ใช้ที่มี "มากกว่าหนึ่งบทบาท" เปิดหน้าหลังบ้านไม่ได้เลย
 * เพราะ middleware ตรวจสิทธิ์ก่อน view composer ที่เคย eager load ให้
 * และ Builder::hydrate เปิดการ์ด preventsLazyLoading เฉพาะตอนผลลัพธ์เกินหนึ่งแถว
 * บทบาทเดียวจึงรอดมาตลอด — บั๊กเลยไม่โผล่จนกว่าจะมีคนได้สองบทบาท
 */
class MenuAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRoles(array $roleCodes, array $menuKeys): User
    {
        $user = User::create([
            'code' => 'USR-MENU',
            'name' => 'ผู้ใช้หลายบทบาท',
            'email' => 'menu@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);

        foreach ($roleCodes as $code) {
            $role = Role::create(['code' => $code, 'name' => $code, 'is_active' => true]);

            foreach ($menuKeys as $menuKey) {
                $role->menuPermissions()->create(['menu_key' => $menuKey, 'is_allowed' => true]);
            }

            $user->roles()->attach($role);
        }

        return $user;
    }

    public function test_user_with_more_than_one_role_can_open_an_allowed_page(): void
    {
        $user = $this->userWithRoles(['project_admin', 'staff'], ['cohort']);

        $this->actingAs($user)->get('/admin/cohort')->assertOk();
    }

    public function test_user_with_a_single_role_can_open_an_allowed_page(): void
    {
        $user = $this->userWithRoles(['staff'], ['cohort']);

        $this->actingAs($user)->get('/admin/cohort')->assertOk();
    }

    public function test_user_without_the_menu_permission_is_rejected(): void
    {
        $user = $this->userWithRoles(['project_admin', 'staff'], ['evaluations-rounds']);

        $this->actingAs($user)->get('/admin/cohort')->assertForbidden();
    }
}
