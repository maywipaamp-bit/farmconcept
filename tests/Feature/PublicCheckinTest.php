<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCheckinTest extends TestCase
{
    use RefreshDatabase;

    private function activity(): Activity
    {
        return Activity::create([
            'code' => 'ACT-PUB-CHECKIN',
            'name' => 'กิจกรรมเปิด Check-in',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => 'เปิดรับสมัคร',
            'requires_checkin' => true,
            'is_published' => true,
            'visibility' => 'สาธารณะ',
            'public_sort_order' => 1,
            'checkin_start_at' => now()->subHour(),
            'checkin_end_at' => now()->addHour(),
        ]);
    }

    private function registration(Activity $activity, string $code, string $name, string $phone = '0812345678'): Registration
    {
        return $activity->registrations()->create([
            'code' => $code,
            'name' => $name,
            'phone' => $phone,
            'registered_at' => now()->subDay(),
        ]);
    }

    public function test_qr_checkin_page_asks_for_phone_before_showing_names(): void
    {
        $activity = $this->activity();

        $this->get('/activities/'.$activity->code.'?action=checkin')
            ->assertOk()
            ->assertSee('ยืนยันเบอร์โทรศัพท์เพื่อ Check-in')
            ->assertSee('เลือกรายชื่อเพื่อ Check-in')
            ->assertSee('TFC_PUBLIC_CHECKIN');
    }

    public function test_phone_lookup_returns_all_names_in_the_same_booking(): void
    {
        $activity = $this->activity();
        $this->registration($activity, 'REG-CHECKIN-1', 'ผู้จอง คนที่หนึ่ง');
        $this->registration($activity, 'REG-CHECKIN-2', 'ผู้จอง คนที่สอง');

        $this->postJson('/activities/'.$activity->code.'/checkin/lookup', [
            'phone' => '081-234-5678',
        ])->assertOk()
            ->assertJsonCount(2, 'registrations')
            ->assertJsonPath('registrations.0.name', 'ผู้จอง คนที่หนึ่ง')
            ->assertJsonPath('registrations.1.name', 'ผู้จอง คนที่สอง');
    }

    public function test_user_can_check_in_only_the_selected_name(): void
    {
        $activity = $this->activity();
        $first = $this->registration($activity, 'REG-CHECKIN-1', 'ผู้จอง คนที่หนึ่ง');
        $second = $this->registration($activity, 'REG-CHECKIN-2', 'ผู้จอง คนที่สอง');

        $this->postJson('/activities/'.$activity->code.'/checkin', [
            'phone' => '0812345678',
            'registration_code' => $second->code,
        ])->assertOk()
            ->assertJsonPath('registration.code', $second->code)
            ->assertJsonPath('registration.checkedIn', true);

        $this->assertNull($first->fresh()->checked_in_at);
        $this->assertNotNull($second->fresh()->checked_in_at);
        $this->assertDatabaseHas('act_registrations', [
            'id' => $second->id,
            'checkin_status' => 'เข้าร่วมแล้ว',
        ]);
        $this->assertDatabaseHas('act_checkin_logs', [
            'registration_id' => $second->id,
            'action' => 'check_in',
            'method' => 'scan',
        ]);
    }

    public function test_registration_cannot_be_checked_in_with_a_different_phone(): void
    {
        $activity = $this->activity();
        $registration = $this->registration($activity, 'REG-CHECKIN-1', 'ผู้จอง คนที่หนึ่ง');

        $this->postJson('/activities/'.$activity->code.'/checkin', [
            'phone' => '0899999999',
            'registration_code' => $registration->code,
        ])->assertUnprocessable()->assertJsonValidationErrors(['registration_code']);

        $this->assertNull($registration->fresh()->checked_in_at);
        $this->assertDatabaseCount('act_checkin_logs', 0);
    }

    public function test_checkin_is_blocked_outside_the_configured_window(): void
    {
        $activity = $this->activity();
        $activity->update([
            'checkin_start_at' => now()->addHour(),
            'checkin_end_at' => now()->addHours(2),
        ]);
        $this->registration($activity, 'REG-CHECKIN-1', 'ผู้จอง คนที่หนึ่ง');

        $this->postJson('/activities/'.$activity->code.'/checkin/lookup', [
            'phone' => '0812345678',
        ])->assertUnprocessable()->assertJsonValidationErrors(['checkin']);
    }
}
