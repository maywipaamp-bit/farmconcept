<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Form;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Activity, 1: Form} */
    private function activity(string $mode = 'group', int $maxSeats = 5, int $capacity = 20, bool $hasFee = false): array
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
            'has_fee' => $hasFee,
            'fee' => $hasFee ? 350 : 0,
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

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'phone' => '0812345678',
            'seat_count' => 1,
            'participants' => [['name' => 'สมหญิง รักธรรมชาติ']],
            'pdpa' => 1,
        ], $overrides);
    }

    public function test_หน้า_flow_ลงทะเบียนเปิดได้และลิงก์เดิม_action_registration_ส่งต่อมา(): void
    {
        [$activity] = $this->activity();

        $this->get('/activities/'.$activity->code.'/register')
            ->assertOk()
            ->assertSee('ลงทะเบียนเข้าร่วมกิจกรรม')
            /* ปุ่มถูกลดน้ำหนักเป็นทางเลือกรองตามสเปกหน้าจอใหม่ ข้อความจึงสั้นลงเหลือ "ถัดไป" */
            ->assertSee('ถัดไป')
            /* หัวข้อของหน้าจอกรอกข้อมูลย้ายขึ้นไปอยู่บนหัวหน้าจอแทนหัวข้อในตัวฟอร์ม */
            ->assertSee('ข้อมูลผู้ลงทะเบียน');

        $this->get('/activities/'.$activity->code.'?action=registration')
            ->assertRedirect('/activities/'.$activity->code.'/register');
    }

    public function test_ตรวจสิทธิ์ด้วยเบอร์หรืออีเมล_แยกคนใหม่กับคนที่ลงทะเบียนแล้ว(): void
    {
        [$activity] = $this->activity();

        $this->postJson('/activities/'.$activity->code.'/registration/check', [
            'contact' => '081-234-5678',
        ])->assertOk()->assertJsonPath('registered', false)->assertJsonPath('maxSeats', 5);

        $this->postJson('/activities/'.$activity->code.'/registration/check', [
            'contact' => 'ไม่ใช่เบอร์หรืออีเมล',
        ])->assertUnprocessable();

        $activity->registrations()->create([
            'code' => 'REG-EXISTING',
            'name' => 'ผู้ลงทะเบียนเดิม',
            'phone' => '0812345678',
            'email' => 'somying@example.com',
            'registered_at' => now(),
        ]);

        $this->postJson('/activities/'.$activity->code.'/registration/check', [
            'contact' => '0812345678',
        ])->assertOk()
            ->assertJsonPath('registered', true)
            ->assertJsonPath('booking.code', 'REG-EXISTING')
            ->assertJsonPath('booking.activityTitle', 'กิจกรรมเปิดลงทะเบียน');

        $this->postJson('/activities/'.$activity->code.'/registration/check', [
            'contact' => 'Somying@Example.com',
        ])->assertOk()->assertJsonPath('registered', true);
    }

    public function test_จองหลายที่นั่งบันทึกชื่อและข้อมูลประกอบครบทุกคน(): void
    {
        [$activity] = $this->activity(maxSeats: 5);
        $round = $activity->rounds->first();

        $age = Option::create(['option_group' => 'age_range', 'code' => 'AGE-T1', 'label' => '25–39 ปี', 'sort_order' => 1, 'is_active' => true]);
        $job = Option::create(['option_group' => 'occupation', 'code' => 'OCC-T1', 'label' => 'เกษตรกร', 'sort_order' => 1, 'is_active' => true]);
        $source = Option::create(['option_group' => 'source_channel', 'code' => 'SRC-T1', 'label' => 'Facebook', 'sort_order' => 1, 'is_active' => true]);

        $this->postJson('/activities/'.$activity->code.'/registration', $this->payload([
            'seat_count' => 3,
            'activity_round_id' => $round->id,
            'email' => 'somying@example.com',
            'source_channel_id' => $source->id,
            'note' => 'แพ้อาหารทะเล',
            'participants' => [
                ['name' => 'สมชาย ใจดี', 'age_range_id' => $age->id, 'occupation_id' => $job->id],
                ['name' => 'สมหญิง ใจงาม', 'age_range_id' => $age->id],
                ['name' => 'เด็กชาย ฟาร์มดี'],
            ],
        ]))->assertCreated()
            ->assertJsonCount(3, 'registrationCodes')
            ->assertJsonPath('booking.seats', 3);

        $this->assertDatabaseCount('act_registrations', 3);
        $this->assertDatabaseCount('ptp_participants', 3);
        $this->assertDatabaseCount('ptp_consents', 3);

        $this->assertDatabaseHas('act_registrations', [
            'name' => 'สมชาย ใจดี',
            'phone' => '0812345678',
            'email' => 'somying@example.com',
            'age_range_id' => $age->id,
            'occupation_id' => $job->id,
            'source_channel_id' => $source->id,
            'dietary_note' => 'แพ้อาหารทะเล',
        ]);
        $this->assertDatabaseHas('act_registrations', [
            'name' => 'สมหญิง ใจงาม',
            'age_range_id' => $age->id,
            'email' => null,
            'source_channel_id' => null,
        ]);
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

        $this->postJson('/activities/'.$activity->code.'/registration', $this->payload([
            'participants' => [['name' => 'ผู้ลงทะเบียนซ้ำ']],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function test_แบบลงทะเบียนคนเดียวไม่อนุญาตให้จองเกินหนึ่งที่นั่ง(): void
    {
        [$activity] = $this->activity(mode: 'single', maxSeats: 5);

        $this->postJson('/activities/'.$activity->code.'/registration', $this->payload([
            'phone' => '0899999999',
            'seat_count' => 2,
            'participants' => [['name' => 'คนที่หนึ่ง'], ['name' => 'คนที่สอง']],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['seat_count']);
    }

    public function test_ตรวจจำนวนที่ว่างซ้ำตอนบันทึกเพื่อไม่ให้จองเกิน(): void
    {
        [$activity] = $this->activity(maxSeats: 5, capacity: 2);

        $this->postJson('/activities/'.$activity->code.'/registration', $this->payload([
            'phone' => '0866666666',
            'seat_count' => 3,
            'participants' => [['name' => 'คนที่หนึ่ง'], ['name' => 'คนที่สอง'], ['name' => 'คนที่สาม']],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['seat_count']);

        $this->assertDatabaseCount('act_registrations', 0);
    }

    public function test_แจ้งชำระเงินพร้อมสลิปเปลี่ยนสถานะเป็นรอตรวจสอบ(): void
    {
        Storage::fake('local');
        [$activity] = $this->activity(hasFee: true);

        $store = $this->postJson('/activities/'.$activity->code.'/registration', $this->payload([
            'seat_count' => 2,
            'participants' => [['name' => 'สมชาย ใจดี'], ['name' => 'สมหญิง ใจงาม']],
        ]))->assertCreated();

        $codes = $store->json('registrationCodes');

        $this->post('/activities/'.$activity->code.'/registration/payment', [
            'codes' => $codes,
            'slip' => UploadedFile::fake()->image('slip.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('booking.paymentLabel', 'แจ้งชำระแล้ว 700 ฿ · รอตรวจสอบ');

        $this->assertDatabaseCount('act_payment_slips', 1);
        $this->assertDatabaseHas('act_payment_slips', ['amount' => 700, 'status' => 'รอตรวจสอบ']);

        foreach ($codes as $code) {
            $this->assertDatabaseHas('act_registrations', ['code' => $code, 'payment_status' => 'รอตรวจสอบ']);
        }

        $slipPath = $activity->registrations()->first()->paymentSlips()->first()->file_path;
        Storage::disk('local')->assertExists($slipPath);
    }
}
