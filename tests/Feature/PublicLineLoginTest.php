<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicLineLoginController;
use App\Models\Activity;
use App\Models\Form;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    /** กิจกรรมใบที่สอง — ใช้ตอนทดสอบว่าคนเดิมลงทะเบียนให้คนอื่นในกิจกรรมอื่นได้ */
    private function activity2(): Activity
    {
        $form = Form::create([
            'code' => 'EVL-REG-LINE-2',
            'name' => 'แบบลงทะเบียนสาธารณะ 2',
            'type' => Form::TYPE_REGISTRATION,
            'status' => Form::STATUS_ACTIVE,
            'registration_mode' => 'single',
            'max_participants' => 1,
            'is_anonymous' => false,
        ]);

        $activity = Activity::create([
            'code' => 'ACT-PUB-LINE-2',
            'name' => 'กิจกรรมที่สอง',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => 'เปิดรับสมัคร',
            'requires_registration' => true,
            'is_published' => true,
            'visibility' => 'สาธารณะ',
            'public_sort_order' => 2,
            'capacity' => 20,
        ]);
        $activity->forms()->attach($form->id, ['slot' => 'registration']);
        $activity->rounds()->create([
            'round_date' => now()->addDays(2)->toDateString(),
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

    public function test_scope_ถูกคั่นด้วย_20_ไม่ใช่เครื่องหมายบวก(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);

        /* LINE ไม่ตีความ "+" เป็นช่องว่าง จะอ่านเป็น scope ชื่อ "openid+profile" แล้วตอบ 400
           เทสต์นี้กันไม่ให้เผลอกลับไปใช้ค่าเริ่มต้นของ http_build_query อีก */
        $response = $this->get('/activities/'.$this->activity()->code.'/line/redirect');

        $response->assertRedirectContains('scope=openid%20profile');

        $this->assertStringNotContainsString(
            'scope=openid+profile',
            (string) $response->headers->get('location'),
        );
    }

    public function test_redirect_uri_ยึดตาม_app_url_เสมอ_ไม่เปลี่ยนตาม_host_ที่ผู้ใช้เข้ามา(): void
    {
        config([
            'services.line.channel_id' => '1234567890',
            'services.line.channel_secret' => 'secret',
            'app.url' => 'https://thefarmconcept.test',
        ]);
        $activity = $this->activity();

        /* เข้ามาคนละ host กัน แต่ redirect_uri ที่ส่งให้ LINE ต้องเป็นค่าเดียวกัน
           ไม่งั้นต้องไปลงทะเบียน Callback URL ใน Console ให้ครบทุกทางที่ผู้ใช้เข้ามาได้ */
        foreach (['http://localhost:8000', 'http://127.0.0.1:9999'] as $host) {
            $this->get($host.'/activities/'.$activity->code.'/line/redirect')
                ->assertRedirectContains(urlencode('https://thefarmconcept.test/auth/line/callback'));
        }
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

    public function test_line_ปฏิเสธแล้วบันทึกสาเหตุจริงลง_log_แต่ไม่หลุด_secret_และผู้ใช้เห็นข้อความกลาง(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'ความลับห้ามหลุด']);
        $activity = $this->activity();

        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'redirect_uri does not match',
            ], 400),
        ]);

        Log::spy();

        $this->withSession(['line_oauth' => ['state' => 'st', 'nonce' => 'no', 'activity' => $activity->code]])
            ->get('/auth/line/callback?code=abc&state=st')
            ->assertRedirect(route('public.activities.register', $activity->code))
            ->assertSessionMissing(PublicLineLoginController::SESSION_KEY);

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $context) {
                $this->assertStringContainsString('LINE Login', $message);
                $this->assertSame('invalid_grant', $context['error']);
                $this->assertSame('redirect_uri does not match', $context['error_description']);
                $this->assertStringNotContainsString('ความลับห้ามหลุด', json_encode($context, JSON_UNESCAPED_UNICODE));

                return true;
            })->once();
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

    public function test_ล็อกอิน_line_ค้างไว้แล้วลงทะเบียนให้คนอื่นด้วยเบอร์โทร_ต้องไม่ผูกกับผู้เข้าร่วมเจ้าของบัญชี(): void
    {
        $activity = $this->activity();

        /* เจ้าของบัญชี LINE ลงทะเบียนตัวเองไว้ก่อน */
        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->postJson('/activities/'.$activity->code.'/registration', [
                'phone' => '0812345678',
                'seat_count' => 1,
                'participants' => [['name' => 'เจ้าของบัญชี LINE']],
                'pdpa' => 1,
            ])->assertCreated();

        $owner = Participant::firstWhere('line_user_id', 'U0123456789abcdef');

        /* ยังล็อกอินค้างอยู่ แล้วกรอกเบอร์คนอื่นเพื่อลงทะเบียนให้เขา
           การจองต้องเป็นของผู้เข้าร่วมคนใหม่ ไม่ใช่ถูกยัดกลับไปที่เจ้าของบัญชี LINE */
        $other = $this->activity2();

        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->postJson('/activities/'.$other->code.'/registration', [
                'phone' => '0899998888',
                'seat_count' => 1,
                'participants' => [['name' => 'เพื่อนอีกคน']],
                'pdpa' => 1,
            ])->assertCreated();

        $friendRegistration = Registration::firstWhere('phone', '0899998888');
        $friend = Participant::find($friendRegistration->participant_id);

        $this->assertNotSame($owner->id, $friend->id);
        $this->assertSame('เพื่อนอีกคน', $friend->name);
        $this->assertNull($friend->line_user_id);
        /* บัญชี LINE ยังอยู่กับเจ้าของเดิมคนเดียว */
        $this->assertSame(1, Participant::where('line_user_id', 'U0123456789abcdef')->count());
    }

    public function test_เข้าผ่าน_line_แล้วเห็นประวัติกิจกรรมอื่นที่เคยลงทะเบียน(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);
        $activity = $this->activity();
        $other = $this->activity2();

        /* ลงทะเบียนกิจกรรมที่สองไว้ก่อน แล้วค่อยเปิดหน้าลงทะเบียนของกิจกรรมแรก */
        foreach ([$other, $activity] as $target) {
            $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
                ->postJson('/activities/'.$target->code.'/registration', [
                    'phone' => '0812345678',
                    'seat_count' => 1,
                    'participants' => [['name' => 'สมหญิง รักธรรมชาติ']],
                    'pdpa' => 1,
                ])->assertCreated();
        }

        $response = $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->get('/activities/'.$activity->code.'/register')
            ->assertOk()
            ->assertSee('กิจกรรมอื่นที่คุณเคยลงทะเบียน')
            ->assertSee($other->name);

        $history = $response->viewData('config')['line']['history'];

        $this->assertCount(1, $history);
        $this->assertSame($other->name, $history[0]['title']);
        /* กิจกรรมที่กำลังดูอยู่ต้องไม่ซ้ำในประวัติ เพราะแสดงเป็นรายละเอียดการจองด้านบนแล้ว */
        $this->assertNotContains($activity->name, array_column($history, 'title'));
    }

    public function test_เข้าด้วยเบอร์โทรอย่างเดียวต้องไม่เห็นประวัติของบัญชี_line(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);
        $activity = $this->activity();
        $other = $this->activity2();

        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->postJson('/activities/'.$other->code.'/registration', [
                'phone' => '0812345678',
                'seat_count' => 1,
                'participants' => [['name' => 'สมหญิง รักธรรมชาติ']],
                'pdpa' => 1,
            ])->assertCreated();

        /* ต้องล้าง session ก่อน — withSession() ของคำขอก่อนหน้าติดค้างมาถึงคำขอนี้ด้วย
           ถ้าไม่ล้าง เทสต์จะยังถือว่าล็อกอิน LINE อยู่ แล้ววัดผลผิดไปจากที่ตั้งใจ */
        $this->flushSession();

        /* ไม่ได้ล็อกอิน LINE = ยังไม่ได้ยืนยันตัวตน จึงต้องไม่เห็นประวัติของใคร */
        $this->get('/activities/'.$activity->code.'/register')
            ->assertOk()
            ->assertDontSee('กิจกรรมอื่นที่คุณเคยลงทะเบียน')
            ->assertDontSee($other->name);
    }

    public function test_หน้าจอลงทะเบียนแล้วมีทางออกไปกรอกเบอร์โทรเสมอ(): void
    {
        config(['services.line.channel_id' => '1234567890', 'services.line.channel_secret' => 'secret']);
        $activity = $this->activity();

        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->postJson('/activities/'.$activity->code.'/registration', [
                'phone' => '0812345678',
                'seat_count' => 1,
                'participants' => [['name' => 'สมหญิง รักธรรมชาติ']],
                'pdpa' => 1,
            ])->assertCreated();

        /* เปิดหน้าอีกครั้งจะถูกพาไปหน้าจอ "ลงทะเบียนแล้ว" — ต้องมีปุ่มออกจากระบบให้กลับไปใช้เบอร์โทรได้ */
        $this->withSession([PublicLineLoginController::SESSION_KEY => $this->lineProfile()])
            ->get('/activities/'.$activity->code.'/register')
            ->assertOk()
            ->assertSee('ออกจากระบบ LINE แล้วกรอกเบอร์โทร')
            ->assertSee(route('public.line.logout', $activity->code));
    }

    /**
     * กลุ่มตัวอย่างที่ลงทะเบียนเองผ่าน QR ไม่ได้ให้ชื่อจริงไว้ ระบบใช้รหัสบุคคลเป็นชื่อในระบบแทน
     * เอามาเติมช่อง "ชื่อ - นามสกุล" ไม่ได้ ผู้ใช้จะเห็น "P0005" แล้วส่งไปทั้งอย่างนั้น
     */
    public function test_ชื่อที่เป็นรหัสบุคคลต้องไม่ถูกเติมลงช่องชื่อ(): void
    {
        $activity = $this->activity();
        $profile = $this->lineProfile();

        Participant::create([
            'code' => 'P0005',
            'person_code' => 'P0005',
            'name' => 'P0005',
            'phone' => '0925399788',
            'line_user_id' => $profile['userId'],
        ]);

        $prefill = app(\App\Services\PublicRegistrationService::class)
            ->lastContactForLineUser($profile['userId']);

        $this->assertNull($prefill['name'], 'รหัสบุคคลต้องไม่ถูกส่งมาเป็นชื่อ');
        $this->assertSame('0925399788', $prefill['phone'], 'เบอร์ยังต้องเติมให้ตามเดิม');

        /* หน้าจอจะตกไปใช้ชื่อที่แสดงบน LINE แทน ซึ่งเป็นชื่อคนจริง ไม่ใช่รหัสของระบบ */
        $this->withSession([PublicLineLoginController::SESSION_KEY => $profile])
            ->get('/activities/'.$activity->code.'/register')
            ->assertOk()
            ->assertDontSee('"name":"P0005"', false);
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
