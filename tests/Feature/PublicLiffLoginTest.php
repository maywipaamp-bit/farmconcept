<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicLineLoginController;
use App\Models\CohortProfile;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Participant;
use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * เข้าสู่ระบบผ่าน LIFF — หน้าติดตามสุขภาพถูกเปิด "ในแอป LINE" แทนเบราว์เซอร์นอก
 *
 * มีไว้แก้ปัญหา: สแกน QR จากตัวสแกนของกล้อง iPhone แล้วกดปุ่มเข้าสู่ระบบด้วย LINE ไม่ได้
 * เพราะเบราว์เซอร์ในแอปกล้องสลับไปแอป LINE ไม่ได้ — LIFF เปิดหน้านี้ในแอป LINE ตรง ๆ
 * จึงไม่ต้องเด้งออกไปที่ไหนเลย
 *
 * เส้นทางเข้าสู่ระบบใช้ตรรกะเดียวกับ LINE Login แบบเดิม (resolveLineIdentity)
 * ต่างกันแค่ตรงที่ได้ userId มา — เทสชุดนี้จึงเน้นที่ endpoint /health/liff เอง
 * ไม่ทวนกติกาสลับบัญชี/ผูกบัญชีซ้ำที่มีเทสของ TrackingRoundQrTest คลุมอยู่แล้ว
 */
class PublicLiffLoginTest extends TestCase
{
    use RefreshDatabase;

    private function qr(): QrCode
    {
        return QrCode::firstOrCreate(
            ['purpose' => 'health', 'activity_id' => null],
            ['token' => 'abcdefghijklmnopqrstuvwx', 'target_url' => '/h/abcdefghijklmnopqrstuvwx', 'is_active' => true]
        );
    }

    private function url(string $suffix = ''): string
    {
        $this->qr();

        return '/health'.$suffix;
    }

    private function template(): FollowUpRoundTemplate
    {
        return FollowUpRoundTemplate::firstOrCreate(
            ['code' => 'FRT-0'],
            ['name' => 'ติดตามครั้งที่หนึ่ง', 'offset_days' => 0, 'is_active' => true, 'sort_order' => 1]
        );
    }

    private function member(string $code, ?string $lineId = null): FollowUpRound
    {
        $participant = Participant::create([
            'code' => $code, 'person_code' => $code,
            'name' => 'ผู้ร่วม '.$code, 'phone' => '081-000-'.substr($code, -4), 'line_user_id' => $lineId,
        ]);

        $profile = CohortProfile::create([
            'participant_id' => $participant->id,
            'cohort_code' => 'CHT-'.$code,
            'entry_date' => now()->toDateString(),
        ]);

        return FollowUpRound::create([
            'cohort_profile_id' => $profile->id,
            'template_id' => $this->template()->id,
            'name' => $this->template()->name,
            'offset_days' => 0,
            'due_date' => now()->toDateString(),
        ]);
    }

    private function configureLine(): void
    {
        config([
            'services.line.channel_id' => '1234567890',
            'services.line.channel_secret' => 'secret',
            'services.line.liff_id' => '1234567890-abcdefgh',
        ]);
    }

    public function test_ยังไม่ตั้งค่า_liff_แล้วเรียก_endpoint_ได้_404(): void
    {
        config(['services.line.liff_id' => null]);

        $this->postJson($this->url('/liff'), ['id_token' => 'anything'])
            ->assertNotFound();
    }

    public function test_ตั้งค่า_liff_แล้วหน้าจอมีสคริปต์และแผงรอเข้าสู่ระบบ(): void
    {
        $this->configureLine();

        $this->get($this->url())
            ->assertOk()
            ->assertSee('tr-liff-cover', false)
            ->assertSee('static.line-scdn.net/liff', false)
            ->assertSee('liff.init', false);
    }

    public function test_ไม่ตั้งค่า_liff_แล้วหน้าจอไม่มีแผงหรือสคริปต์_liff(): void
    {
        config(['services.line.liff_id' => null]);

        $this->get($this->url())
            ->assertOk()
            ->assertDontSee('tr-liff-cover', false)
            ->assertDontSee('static.line-scdn.net/liff', false);
    }

    /** โทเค็นปลอมหรือหมดอายุ — LINE ปฏิเสธ ต้องได้คำตอบที่บอกเหตุผล ไม่ใช่ 500 */
    public function test_id_token_ที่ตรวจไม่ผ่านคืนข้อความผิดพลาดแทนที่จะล่ม(): void
    {
        $this->configureLine();

        Http::fake(['api.line.me/oauth2/v2.1/verify' => Http::response(['error' => 'invalid_request'], 400)]);

        $this->postJson($this->url('/liff'), ['id_token' => 'bad-token'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertNull(session(PublicLineLoginController::SESSION_KEY));
    }

    /** บัญชี LINE นี้ผูกกับคนไว้แล้ว — ต้องเข้าแดชบอร์ดได้เลยโดยไม่ต้องกรอกเบอร์ */
    public function test_บัญชี_line_ที่ผูกไว้แล้วเข้าสู่ระบบผ่าน_liff_ได้ทันที(): void
    {
        $this->configureLine();
        $this->member('P0001', 'U-liff-1');

        Http::fake(['api.line.me/oauth2/v2.1/verify' => Http::response([
            'sub' => 'U-liff-1', 'name' => 'ผู้ใช้ LINE', 'picture' => null,
        ], 200)]);

        $this->postJson($this->url('/liff'), ['id_token' => 'good-token'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', url($this->url('/home')));

        $this->assertNotNull(session(PublicLineLoginController::SESSION_KEY));
    }

    /** บัญชี LINE นี้ยังไม่ผูกกับใคร และไม่มีใครล็อกอินอยู่ก่อน — ต้องถูกพากลับไปกรอกเบอร์ ไม่ใช่ 500 */
    public function test_บัญชี_line_ที่ยังไม่ผูกกับใครถูกพาไปกรอกเบอร์(): void
    {
        $this->configureLine();

        Http::fake(['api.line.me/oauth2/v2.1/verify' => Http::response([
            'sub' => 'U-liff-unknown', 'name' => 'ผู้ใช้ LINE', 'picture' => null,
        ], 200)]);

        $this->postJson($this->url('/liff'), ['id_token' => 'good-token'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', url($this->url()));
    }

    /** ไม่ตรวจ id_token กับ LINE เลยถ้ายังไม่ตั้ง LIFF — endpoint ต้องปิดตายไปเลย ไม่ใช่พยายามทำงาน */
    public function test_ปิด_liff_แล้วไม่มีการยิงคำขอไปตรวจกับ_line(): void
    {
        config(['services.line.liff_id' => null]);

        Http::fake();

        $this->postJson($this->url('/liff'), ['id_token' => 'good-token'])
            ->assertNotFound();

        Http::assertNothingSent();
    }
}
