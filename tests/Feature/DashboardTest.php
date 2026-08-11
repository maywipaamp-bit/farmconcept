<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * แดชบอร์ดภาพรวม — ตรวจว่าหน้า render ได้และตัวเลขมาจากฐานข้อมูลจริง
 *
 * เน้นสองเรื่องที่พังเงียบได้ง่ายที่สุด
 *   1. query ทุกตัวรันผ่าน (แผงที่ตารางยังว่างต้องได้ Empty State ไม่ใช่ 500)
 *   2. คนหนึ่งที่ลงหลายกิจกรรมต้องนับเป็นหนึ่งคน ไม่ใช่นับตามใบลงทะเบียน
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['code' => 'admin', 'name' => 'ผู้ดูแลระบบ', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'dashboard', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-TEST-1',
            'name' => 'ผู้ทดสอบ',
            'email' => 'test@thefarmconcept.test',
            'password' => 'secret-not-used',
            'status' => 'ใช้งานอยู่',
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    public function test_หน้าแดชบอร์ดเปิดได้แม้ยังไม่มีข้อมูลในระบบ(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('ภาพรวมการดำเนินงาน');

        /* ตารางว่างต้องได้ Empty State ของแต่ละแผง ไม่ใช่กราฟที่มีแต่ศูนย์ */
        $response->assertSee('ยังไม่มีผู้ลงทะเบียนในช่วงเวลาที่เลือก');
        $response->assertSee('ยังไม่มีผู้เข้าร่วมกลุ่มตัวอย่าง');
        $response->assertSee('ยังเทียบผลก่อน–หลังไม่ได้');
    }

    public function test_นับผู้เข้าร่วมเป็นจำนวนคนไม่ใช่จำนวนใบลงทะเบียน(): void
    {
        $activities = collect(['ACT-T-1', 'ACT-T-2'])->map(fn (string $code) => Activity::create([
            'code' => $code,
            'name' => 'กิจกรรมทดสอบ ' . $code,
            'status' => 'เปิดรับสมัคร',
            'start_date' => now()->toDateString(),
        ]));

        /* คนเดียวกัน (เบอร์เดียวกัน) ลงทะเบียนสองกิจกรรม + อีกหนึ่งคน = 2 คน จาก 3 ใบ */
        $rows = [
            ['ACT-T-1', '0800000001', 'female', 1990],
            ['ACT-T-2', '0800000001', 'female', 1990],
            ['ACT-T-1', '0800000002', 'male', 1970],
        ];

        foreach ($rows as $index => [$code, $phone, $gender, $birthYear]) {
            DB::table('act_registrations')->insert([
                'code' => 'REG-T-' . $index,
                'activity_id' => $activities->firstWhere('code', $code)->id,
                'name' => 'ผู้เข้าร่วม ' . $index,
                'phone' => $phone,
                'gender' => $gender,
                'birth_year' => $birthYear,
                'registered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $data = app(\App\Services\DashboardService::class)->overview('all');

        $this->assertSame(2, $data['participants']['total']);
        $this->assertSame(2, $data['kpis'][0]['value']);
        $this->assertSame(1, collect($data['participants']['gender'])->firstWhere('key', 'female')['count']);
        $this->assertSame(1, collect($data['participants']['gender'])->firstWhere('key', 'male')['count']);
    }

    public function test_ตัวกรองช่วงเวลาคืนเนื้อในเป็น_html(): void
    {
        $response = $this->actingAs($this->admin())->getJson('/admin/dashboard/fragment?range=3m');

        $response->assertOk();
        $response->assertJsonStructure(['range', 'generated_at', 'html']);
        $this->assertSame('3m', $response->json('range'));
    }

    public function test_ช่วงเวลาที่ไม่รู้จักตกไปที่ค่าตั้งต้นไม่ใช่ข้อผิดพลาด(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/dashboard?range=ไม่มีจริง');

        $response->assertOk();
        $this->assertSame('all', app(\App\Services\DashboardService::class)->normalizeRange('ไม่มีจริง'));
    }

    public function test_ลิงก์เดิม_dashboard_html_ส่งต่อมาหน้าใหม่(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/dashboard.html')
            ->assertRedirect('/admin/dashboard');
    }
}
