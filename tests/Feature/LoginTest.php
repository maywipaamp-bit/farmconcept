<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * หน้าเข้าสู่ระบบ — ตรวจโครงหน้าจอและเส้นทางหลังกดปุ่ม
 *
 * เน้นสามเรื่องที่พังเงียบได้และผู้ใช้เจอทันที
 *   1. ชิ้นส่วนบังคับตามสเปกยังอยู่ครบ (ปุ่มแสดง/ซ่อน · จำการเข้าสู่ระบบ · ทางออกรอง)
 *   2. กรอกผิดต้องได้ข้อความกลาง ๆ ที่ไม่บอกว่าผิดที่ช่องไหน
 *   3. ล็อกอินผ่านแล้วไปแดชบอร์ด
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password-for-test-only';

    private function user(): User
    {
        $role = Role::create(['code' => 'admin', 'name' => 'ผู้ดูแลระบบ', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'dashboard', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-TEST-1',
            'name' => 'ผู้ทดสอบ',
            'username' => 'admin01',
            'email' => 'test@thefarmconcept.test',
            'password' => Hash::make(self::PASSWORD),
            'status' => 'ใช้งานอยู่',
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    public function test_หน้าเข้าสู่ระบบมีชิ้นส่วนตามสเปกครบ(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('ระบบติดตามและประเมินผลการเปลี่ยนแปลงสุขภาพ');
        $response->assertSee('ชื่อผู้ใช้งาน');
        $response->assertSee('รหัสผ่าน');
        $response->assertSee('จำการเข้าสู่ระบบไว้');
        $response->assertSee('ดูกิจกรรมทั้งหมด โดยไม่ต้องเข้าสู่ระบบ');

        /* ปุ่มแสดง/ซ่อนต้องเป็น type="button" ไม่งั้นกดแล้วส่งฟอร์มแทนการสลับการมองเห็น */
        $response->assertSee('type="button" class="login-reveal"', false);
        $response->assertSee('aria-label="แสดงรหัสผ่าน"', false);

        /* สเปกตัดลิงก์ลืมรหัสผ่านออกโดยตั้งใจ เพราะผู้ใช้มีเฉพาะแอดมิน */
        $response->assertDontSee('ลืมรหัสผ่าน');
    }

    public function test_กรอกผิดได้ข้อความกลางที่ไม่บอกว่าผิดช่องไหน(): void
    {
        $this->user();

        $response = $this->from('/login')->post('/login', [
            'username' => 'admin01',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง']);
        $this->assertGuest();

        /* ข้อความต้องเหมือนกันทุกกรณี — ชื่อผู้ใช้ที่ไม่มีอยู่จริงก็ต้องได้ข้อความเดียวกัน
           ไม่งั้นเดาได้ว่าบัญชีไหนมีอยู่ในระบบ */
        $this->from('/login')
            ->post('/login', ['username' => 'ไม่มีบัญชีนี้', 'password' => 'x'])
            ->assertSessionHasErrors(['username' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง']);
    }

    public function test_ข้อความผิดพลาดถูกแสดงบนหน้าจอไม่ใช่แค่เก็บใน_session(): void
    {
        $this->user();

        $response = $this->followingRedirects()->post('/login', [
            'username' => 'admin01',
            'password' => 'wrong-password',
        ]);

        $response->assertOk();
        $response->assertSee('ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง');

        /* แถบ error ต้องไม่มี hidden ตอนมีข้อความ ไม่งั้นผู้ใช้ไม่เห็นว่าทำไมเข้าไม่ได้ */
        $response->assertDontSee('id="login-error" role="alert" hidden', false);
    }

    public function test_ล็อกอินสำเร็จแล้วไปหน้าแดชบอร์ด(): void
    {
        $user = $this->user();

        $response = $this->post('/login', [
            'username' => 'admin01',
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_บัญชีที่ถูกระงับเข้าไม่ได้และได้ข้อความเดียวกัน(): void
    {
        $this->user()->forceFill(['status' => 'ระงับการใช้งาน'])->save();

        $this->from('/login')
            ->post('/login', ['username' => 'admin01', 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['username' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง']);

        $this->assertGuest();
    }
}
