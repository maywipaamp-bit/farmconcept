<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicLineLoginController;
use App\Models\Activity;
use App\Models\Form;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLineLoginTest extends TestCase
{
    use RefreshDatabase;

    private function activity(): Activity
    {
        $form = Form::create([
            'code' => 'EVL-REG-LINE',
            'name' => 'แบบลงทะเบียนสาธารณะ',
            'type' => Form::TYPE_REGISTRATION,
            'status' => Form::STATUS_ACTIVE,
            'registration_mode' => 'single',
            'max_participants' => 1,
            'is_anonymous' => false,
        ]);

        $activity = Activity::create([
            'code' => 'ACT-PUB-LINE',
            'name' => 'กิจกรรมเปิดลงทะเบียน',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => 'เปิดรับสมัคร',
            'requires_registration' => true,
            'is_published' => true,
            'visibility' => 'สาธารณะ',
            'public_sort_order' => 1,
            'capacity' => 20,
        ]);
        $activity->forms()->attach($form->id, ['slot' => 'registration']);
        $activity->rounds()->create([
            'round_date' => now()->addDay()->toDateString(),
            'time_start' => '09:00',
            'time_end' => '12:00',
            'capacity' => 20,
        ]);

        return $activity;
    }

    /** @return array<string, mixed> */
    private function lineProfile(): array
    {
        return [
            'userId' => 'U0123456789abcdef',
            'displayName' => 'สมหญิง จาก LINE',
            'pictureUrl' => 'https://profile.line-scdn.net/abc',
            'email' => null,
        ];
    }

    public function test_ยังไม่ตั้งค่า_channel_แล้วไม่แสดงปุ่มเข้าสู่ระบบด้วย_line(): void
    {
        config(['services.line.channel_id' => null, 'services.line.channel_secret' => null]);

        $this->get('/activities/'.$this->activity()->code.'/register')
            ->assertOk()
            ->assertDontSee('เข้าสู่ระบบด้วย LINE');
    }

    public function test_ตั้งค่าแล้วปุ่มพาไปหน้ายินยอมของ_line_พร้อม_state_และ_nonce(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);
        $activity = $this->activity();

        $this->get('/activities/'.$activity->code.'/register')
            ->assertOk()
            ->assertSee('เข้าสู่ระบบด้วย LINE');

        $response = $this->get('/activities/'.$activity->code.'/line/redirect');

        $response->assertRedirectContains('https://access.line.me/oauth2/v2.1/authorize');
        $response->assertRedirectContains('client_id=1234567890');
        $response->assertSessionHas('line_oauth');

        $oauth = session('line_oauth');
        $this->assertSame($activity->code, $oauth['activity']);
        $this->assertNotEmpty($oauth['state']);
        $this->assertNotEmpty($oauth['nonce']);
    }

    public function test_callback_ที่_state_ไม่ตรงถูกปฏิเสธและไม่เก็บโปรไฟล์(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);
        $activity = $this->activity();

        $this->withSession(['line_oauth' => ['state' => 'ของจริง', 'nonce' => 'n', 'activity' => $activity->code]])
            ->get('/auth/line/callback?code=abc&state=ของปลอม')
            ->assertRedirect(route('public.activities.register', $activity->code))
            ->assertSessionMissing(PublicLineLoginController::SESSION_KEY);
    }

    public function test_ล็อกอิน_line_แล้วหน้าลงทะเบียนแสดงชื่อบัญชีและเติมชื่อให้ฟอร์ม(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);

        $response = $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->get('/activities/'.$this->activity()->code.'/register')
            ->assertOk()
            ->assertSee('เชื่อมบัญชี LINE แล้ว')
            ->assertSee('ใช้บัญชีนี้ลงทะเบียนต่อ')
            ->assertSee('สมหญิง จาก LINE');

        $config = $response->viewData('config');
        $this->assertSame('สมหญิง จาก LINE', $config['line']['prefill']['name']);
        $this->assertNull($config['line']['booking']);
    }

    public function test_ลงทะเบียนขณะล็อกอิน_line_แล้วบัญชีถูกผูกกับผู้เข้าร่วม(): void
    {
        $activity = $this->activity();

        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->postJson('/activities/'.$activity->code.'/registration', [
                'phone' => '0812345678',
                'seat_count' => 1,
                'participants' => [['name' => 'สมหญิง รักธรรมชาติ']],
                'pdpa' => 1,
            ])
            ->assertCreated();

        $participant = Participant::firstWhere('line_user_id', 'U0123456789abcdef');

        $this->assertNotNull($participant);
        $this->assertSame('สมหญิง จาก LINE', $participant->line_display_name);
        $this->assertSame('https://profile.line-scdn.net/abc', $participant->line_picture_url);
    }

    public function test_บัญชี_line_ที่เคยลงทะเบียนแล้วกลับมาเห็นหน้าจอลงทะเบียนแล้วทันที(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);
        $activity = $this->activity();

        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->postJson('/activities/'.$activity->code.'/registration', [
                'phone' => '0812345678',
                'seat_count' => 1,
                'participants' => [['name' => 'สมหญิง รักธรรมชาติ']],
                'pdpa' => 1,
            ])
            ->assertCreated();

        $response = $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->get('/activities/'.$activity->code.'/register')
            ->assertOk();

        $booking = $response->viewData('config')['line']['booking'];

        $this->assertNotNull($booking);
        $this->assertSame(Registration::firstWhere('phone', '0812345678')->code, $booking['code']);
    }

    public function test_บัญชี_line_ที่ผูกกับผู้เข้าร่วมคนอื่นแล้วไม่ถูกเขียนทับจนบันทึกล้ม(): void
    {
        $activity = $this->activity();

        Participant::create([
            'code' => 'PID-OTHER',
            'person_code' => 'PID-OTHER',
            'name' => 'คนอื่น',
            'phone' => '0899999999',
            'consent_status' => 'ยินยอม',
            'line_user_id' => 'U0123456789abcdef',
        ]);

        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->postJson('/activities/'.$activity->code.'/registration', [
                'phone' => '0812345678',
                'seat_count' => 1,
                'participants' => [['name' => 'สมหญิง รักธรรมชาติ']],
                'pdpa' => 1,
            ])
            ->assertCreated();

        /* บัญชี LINE ยังอยู่กับเจ้าของเดิม และการจองของคนใหม่ยังบันทึกสำเร็จ */
        $this->assertSame(1, Participant::where('line_user_id', 'U0123456789abcdef')->count());
        $this->assertSame('คนอื่น', Participant::firstWhere('line_user_id', 'U0123456789abcdef')->name);
        $this->assertNotNull(Registration::firstWhere('phone', '0812345678'));
    }

    public function test_ออกจากบัญชี_line_แล้วโปรไฟล์ถูกล้างจาก_session(): void
    {
        $activity = $this->activity();

        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->post('/activities/'.$activity->code.'/line/logout')
            ->assertRedirect(route('public.activities.register', $activity->code))
            ->assertSessionMissing(PublicLineLoginController::SESSION_KEY);
    }
}
