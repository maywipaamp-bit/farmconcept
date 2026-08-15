<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\CheckinLog;
use App\Models\Registration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * หน้า Check-in หน้างานฝั่งเจ้าหน้าที่ (admin/activities/checkin)
 *
 * จุดที่ต้องกันไม่ให้พังคือ "สถานะกับ audit log ต้องไปด้วยกันเสมอ" —
 * ถ้าเช็คอินแล้วไม่มี log หรือมี log แต่สถานะไม่เปลี่ยน จะไม่มีทางรู้ย้อนหลังว่าใครทำอะไร
 */
class AdminCheckinTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $menuKeys = ['activities-checkin']): User
    {
        $user = User::create([
            'code' => 'USR-CHECKIN',
            'name' => 'เจ้าหน้าที่หน้างาน',
            'username' => 'staff-checkin',
            'email' => 'checkin@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);

        $role = Role::create(['code' => 'staff', 'name' => 'staff', 'is_active' => true]);

        foreach ($menuKeys as $menuKey) {
            $role->menuPermissions()->create(['menu_key' => $menuKey, 'is_allowed' => true]);
        }

        $user->roles()->attach($role);

        return $user;
    }

    private function activity(array $attributes = []): Activity
    {
        return Activity::create(array_merge([
            'code' => 'ACT-CHECKIN',
            'name' => 'กิจกรรมเช็คอินหน้างาน',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => 'กำลังดำเนินการ',
            'requires_checkin' => true,
            'is_published' => true,
            'visibility' => 'สาธารณะ',
            'start_date' => now()->toDateString(),
            'checkin_start_at' => now()->subHour(),
            'checkin_end_at' => now()->addHour(),
        ], $attributes));
    }

    private function registration(Activity $activity, string $code, string $name): Registration
    {
        return $activity->registrations()->create([
            'code' => $code,
            'name' => $name,
            'phone' => '0812345678',
            'registered_at' => now()->subDay(),
        ]);
    }

    /** รายการกิจกรรมฝังมากับหน้าผ่าน @json ซึ่งแปลงอักษรไทยเป็น \uXXXX — เทียบด้วยรูปนั้น */
    private function asJsonText(string $value): string
    {
        return trim(json_encode($value), '"');
    }

    public function test_page_lists_only_activities_that_use_checkin(): void
    {
        $activity = $this->activity();
        $this->activity(['code' => 'ACT-NO-CHECKIN', 'name' => 'กิจกรรมไม่ใช้เช็คอิน', 'requires_checkin' => false]);

        $this->actingAs($this->staff())
            ->get('/admin/activities/checkin')
            ->assertOk()
            ->assertSee($this->asJsonText($activity->name), false)
            ->assertDontSee($this->asJsonText('กิจกรรมไม่ใช้เช็คอิน'), false);
    }

    public function test_snapshot_returns_registrations_of_that_activity_only(): void
    {
        $activity = $this->activity();
        $this->registration($activity, 'REG-A1', 'ผู้เข้าร่วม หนึ่ง');

        $other = $this->activity(['code' => 'ACT-OTHER', 'name' => 'กิจกรรมอื่น']);
        $this->registration($other, 'REG-B1', 'คนของกิจกรรมอื่น');

        $this->actingAs($this->staff())
            ->getJson('/admin/activities/'.$activity->code.'/checkin')
            ->assertOk()
            ->assertJsonCount(1, 'people')
            ->assertJsonPath('people.0.name', 'ผู้เข้าร่วม หนึ่ง')
            ->assertJsonPath('people.0.checkedInAt', '');
    }

    public function test_staff_check_in_stamps_server_time_and_writes_audit_log(): void
    {
        $activity = $this->activity();
        $registration = $this->registration($activity, 'REG-A1', 'ผู้เข้าร่วม หนึ่ง');

        $this->actingAs($staff = $this->staff())
            ->postJson('/admin/activities/'.$activity->code.'/checkin', [
                'registrationId' => $registration->code,
                'source' => 'staff',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'ผู้เข้าร่วม หนึ่ง')
            ->assertJsonPath('checkedInAt', now()->format('H:i'));

        $registration->refresh();
        $this->assertSame('เข้าร่วมแล้ว', $registration->checkin_status);
        $this->assertNotNull($registration->checked_in_at);

        $this->assertDatabaseHas('act_checkin_logs', [
            'registration_id' => $registration->id,
            'action' => 'check_in',
            'method' => 'staff',
            'performed_by' => $staff->id,
        ]);
    }

    public function test_checking_in_twice_is_not_an_error_and_does_not_move_the_time(): void
    {
        $activity = $this->activity();
        $registration = $this->registration($activity, 'REG-A1', 'ผู้เข้าร่วม หนึ่ง');
        $staff = $this->staff();

        $first = $this->actingAs($staff)
            ->postJson('/admin/activities/'.$activity->code.'/checkin', ['registrationId' => $registration->code])
            ->assertOk()->json('checkedInAtIso');

        $this->actingAs($staff)
            ->postJson('/admin/activities/'.$activity->code.'/checkin', ['registrationId' => $registration->code])
            ->assertOk()
            ->assertJsonPath('checkedInAtIso', $first);

        $this->assertSame(1, CheckinLog::query()->where('registration_id', $registration->id)->count());
    }

    public function test_undo_clears_the_time_and_records_who_did_it(): void
    {
        $activity = $this->activity();
        $registration = $this->registration($activity, 'REG-A1', 'ผู้เข้าร่วม หนึ่ง');
        $staff = $this->staff();

        $this->actingAs($staff)
            ->postJson('/admin/activities/'.$activity->code.'/checkin', ['registrationId' => $registration->code])
            ->assertOk();

        $this->actingAs($staff)
            ->deleteJson('/admin/activities/'.$activity->code.'/checkin/'.$registration->code, [
                'reason' => 'เจ้าหน้าที่ยกเลิกที่หน้างาน',
            ])
            ->assertOk()
            ->assertJsonPath('audit.registrationName', 'ผู้เข้าร่วม หนึ่ง')
            ->assertJsonPath('audit.actorUsername', 'staff-checkin');

        $registration->refresh();
        $this->assertNull($registration->checked_in_at);
        $this->assertSame('ยังไม่เข้าร่วม', $registration->checkin_status);

        $this->assertDatabaseHas('act_checkin_logs', [
            'registration_id' => $registration->id,
            'action' => 'undo',
            'performed_by' => $staff->id,
        ]);
    }

    public function test_undo_on_someone_who_never_checked_in_is_rejected(): void
    {
        $activity = $this->activity();
        $registration = $this->registration($activity, 'REG-A1', 'ผู้เข้าร่วม หนึ่ง');

        $this->actingAs($this->staff())
            ->deleteJson('/admin/activities/'.$activity->code.'/checkin/'.$registration->code)
            ->assertStatus(422);

        $this->assertDatabaseCount('act_checkin_logs', 0);
    }

    public function test_walk_in_is_created_as_manual_entry_and_checked_in_at_once(): void
    {
        $activity = $this->activity();

        $this->actingAs($this->staff())
            ->postJson('/admin/activities/'.$activity->code.'/walk-ins', [
                'name' => 'ผู้มาหน้างาน',
                'phone' => '089-999-9999',
                'roundKey' => '',
                'ageRange' => '',
                'consent' => true,
            ])
            ->assertOk()
            ->assertJsonPath('name', 'ผู้มาหน้างาน')
            ->assertJsonPath('walkIn', true);

        $registration = Registration::query()->where('name', 'ผู้มาหน้างาน')->firstOrFail();
        $this->assertTrue($registration->is_manual_entry);
        $this->assertNotNull($registration->checked_in_at);
        $this->assertSame('0899999999', $registration->phone);

        $this->assertDatabaseHas('act_checkin_logs', [
            'registration_id' => $registration->id,
            'action' => 'check_in',
            'method' => 'staff',
        ]);
    }

    public function test_walk_in_without_consent_is_rejected(): void
    {
        $activity = $this->activity();

        $this->actingAs($this->staff())
            ->postJson('/admin/activities/'.$activity->code.'/walk-ins', [
                'name' => 'ผู้มาหน้างาน',
                'phone' => '0899999999',
                'consent' => false,
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('act_registrations', 0);
    }

    public function test_cancelled_activity_cannot_be_checked_in(): void
    {
        $activity = $this->activity(['status' => Activity::STATUS_CANCELLED]);
        $registration = $this->registration($activity, 'REG-A1', 'ผู้เข้าร่วม หนึ่ง');

        $this->actingAs($this->staff())
            ->postJson('/admin/activities/'.$activity->code.'/checkin', ['registrationId' => $registration->code])
            ->assertForbidden();
    }

    public function test_user_without_the_checkin_menu_cannot_reach_the_endpoints(): void
    {
        $activity = $this->activity();

        $this->actingAs($this->staff(['activities-list']))
            ->getJson('/admin/activities/'.$activity->code.'/checkin')
            ->assertForbidden();
    }
}
