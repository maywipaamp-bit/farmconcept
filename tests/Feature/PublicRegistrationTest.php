<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Activity, 1: Form} */
    private function activity(string $mode = 'group', int $maxSeats = 5, int $capacity = 20): array
    {
        $form = Form::create([
            'code' => 'EVL-REG-PUB',
            'name' => 'แบบลงทะเบียนสาธารณะ',
            'type' => Form::TYPE_REGISTRATION,
            'status' => Form::STATUS_ACTIVE,
            'registration_mode' => $mode,
            'max_participants' => $maxSeats,
            'is_anonymous' => false,
        ]);

        $activity = Activity::create([
            'code' => 'ACT-PUB-REG',
            'name' => 'กิจกรรมเปิดลงทะเบียน',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => 'เปิดรับสมัคร',
            'requires_registration' => true,
            'is_published' => true,
            'visibility' => 'สาธารณะ',
            'public_sort_order' => 1,
            'capacity' => $capacity,
        ]);
        $activity->forms()->attach($form->id, ['slot' => 'registration']);
        $activity->rounds()->create([
            'round_date' => now()->addDay()->toDateString(),
            'time_start' => '09:00',
            'time_end' => '12:00',
            'capacity' => $capacity,
        ]);

        return [$activity, $form];
    }

    public function test_หน้า_public_แสดงขั้นตรวจเบอร์และจำนวนที่นั่งตามแบบลงทะเบียน(): void
    {
        [$activity] = $this->activity(maxSeats: 5);

        $this->get('/activities/'.$activity->code.'?action=registration')
            ->assertOk()
            ->assertSee('ตรวจสอบเบอร์โทรศัพท์')
            ->assertSee('5 ที่นั่ง')
            ->assertSee('data-open="true"', false);
    }

    public function test_ตรวจเบอร์แล้วจองหลายที่นั่งและบันทึกชื่อครบทุกคน(): void
    {
        [$activity] = $this->activity(maxSeats: 5);
        $round = $activity->rounds->first();

        $this->postJson('/activities/'.$activity->code.'/registration/check-phone', [
            'phone' => '0812345678',
        ])->assertOk()->assertJsonPath('available', true)->assertJsonPath('maxSeats', 5);

        $this->postJson('/activities/'.$activity->code.'/registration', [
            'phone' => '0812345678',
            'seat_count' => 3,
            'activity_round_id' => $round->id,
            'names' => ['สมชาย ใจดี', 'สมหญิง ใจงาม', 'เด็กชาย ฟาร์มดี'],
            'pdpa' => 1,
        ])->assertCreated()->assertJsonCount(3, 'registrationCodes');

        $this->assertDatabaseCount('act_registrations', 3);
        $this->assertDatabaseCount('ptp_participants', 3);
        $this->assertDatabaseCount('ptp_consents', 3);
        foreach (['สมชาย ใจดี', 'สมหญิง ใจงาม', 'เด็กชาย ฟาร์มดี'] as $name) {
            $this->assertDatabaseHas('act_registrations', [
                'activity_id' => $activity->id,
                'activity_round_id' => $round->id,
                'name' => $name,
                'phone' => '0812345678',
            ]);
            $this->assertDatabaseHas('ptp_participants', [
                'name' => $name,
                'phone' => '0812345678',
                'consent_status' => 'ยินยอม',
            ]);
        }
    }

    public function test_เบอร์เดิมลงทะเบียนกิจกรรมซ้ำไม่ได้(): void
    {
        [$activity] = $this->activity();
        $activity->registrations()->create([
            'code' => 'REG-EXISTING',
            'name' => 'ผู้ลงทะเบียนเดิม',
            'phone' => '0812345678',
            'registered_at' => now(),
        ]);

        $this->postJson('/activities/'.$activity->code.'/registration/check-phone', [
            'phone' => '0812345678',
        ])->assertStatus(409)->assertJsonPath('available', false);

        $this->postJson('/activities/'.$activity->code.'/registration', [
            'phone' => '0812345678',
            'seat_count' => 1,
            'names' => ['ผู้ลงทะเบียนซ้ำ'],
            'pdpa' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function test_แบบลงทะเบียนคนเดียวไม่อนุญาตให้จองเกินหนึ่งที่นั่ง(): void
    {
        [$activity] = $this->activity(mode: 'single', maxSeats: 5);

        $this->postJson('/activities/'.$activity->code.'/registration', [
            'phone' => '0899999999',
            'seat_count' => 2,
            'names' => ['คนที่หนึ่ง', 'คนที่สอง'],
            'pdpa' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors(['seat_count']);
    }

    public function test_ตรวจจำนวนที่ว่างซ้ำตอนบันทึกเพื่อไม่ให้จองเกิน(): void
    {
        [$activity] = $this->activity(maxSeats: 5, capacity: 2);

        $this->postJson('/activities/'.$activity->code.'/registration', [
            'phone' => '0866666666',
            'seat_count' => 3,
            'names' => ['คนที่หนึ่ง', 'คนที่สอง', 'คนที่สาม'],
            'pdpa' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors(['seat_count']);

        $this->assertDatabaseCount('act_registrations', 0);
    }
}
