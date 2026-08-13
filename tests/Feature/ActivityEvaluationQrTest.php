<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Form;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityEvaluationQrTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['code' => 'activity-admin', 'name' => 'ผู้ดูแลกิจกรรม', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'activities-list', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-ACT-1',
            'name' => 'ผู้ทดสอบกิจกรรม',
            'email' => 'activity@test.local',
            'password' => 'secret-not-used',
            'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    private function form(string $code, string $type, string $status = Form::STATUS_ACTIVE): Form
    {
        return Form::create([
            'code' => $code,
            'name' => 'แบบประเมิน '.$code,
            'type' => $type,
            'status' => $status,
            'is_anonymous' => $type === Form::TYPE_POST_ACTIVITY,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Form $registration, Form $postSurvey): array
    {
        return [
            'name' => 'กิจกรรมทดสอบการผูกแบบประเมิน',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => Activity::STATUS_DRAFT,
            'visibility' => 'สาธารณะ',
            'requires_registration' => true,
            'requires_checkin' => true,
            'has_post_survey' => true,
            'registration_form_id' => $registration->id,
            'post_survey_form_id' => $postSurvey->id,
            'registration_end_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'checkin_start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'checkin_end_at' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
            'has_fee' => false,
            'is_published' => false,
            'is_featured' => false,
            'rounds' => [],
            'area_ids' => [],
            'instructor_ids' => [],
            'target_group_ids' => [],
        ];
    }

    public function test_สร้างกิจกรรมแล้วผูกแบบประเมินและสร้าง_qr_สามประเภท(): void
    {
        $registration = $this->form('EVL-REG-1', Form::TYPE_REGISTRATION);
        $postSurvey = $this->form('EVL-POST-1', Form::TYPE_POST_ACTIVITY);

        $response = $this->actingAs($this->admin())
            ->postJson('/admin/activities', $this->payload($registration, $postSurvey));

        $response->assertCreated();
        $activity = Activity::where('code', $response->json('code'))->firstOrFail();

        $this->assertDatabaseHas('evl_form_activity', [
            'activity_id' => $activity->id,
            'form_id' => $registration->id,
            'slot' => 'registration',
        ]);
        $this->assertDatabaseHas('evl_form_activity', [
            'activity_id' => $activity->id,
            'form_id' => $postSurvey->id,
            'slot' => 'post_survey',
        ]);

        $this->assertSame(
            ['checkin', 'post_survey', 'public'],
            $activity->qrCodes()->orderBy('purpose')->pluck('purpose')->all(),
        );
        $this->assertSame(0, $activity->qrCodes()->where('is_active', true)->count());
    }

    public function test_ปฏิเสธแบบประเมินผิดประเภทหรือยังไม่เปิดใช้งาน(): void
    {
        $postSurvey = $this->form('EVL-POST-2', Form::TYPE_POST_ACTIVITY);
        $inactiveRegistration = $this->form('EVL-REG-2', Form::TYPE_REGISTRATION, Form::STATUS_DRAFT);
        $payload = $this->payload($inactiveRegistration, $postSurvey);
        $payload['registration_form_id'] = $postSurvey->id;

        $this->actingAs($this->admin())
            ->postJson('/admin/activities', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['registration_form_id']);
    }

    public function test_ปิดสวิตช์แล้วถอดแบบประเมินแต่เก็บ_qr_เดิมไว้แบบปิดใช้งาน(): void
    {
        $registration = $this->form('EVL-REG-3', Form::TYPE_REGISTRATION);
        $postSurvey = $this->form('EVL-POST-3', Form::TYPE_POST_ACTIVITY);
        $create = $this->actingAs($this->admin())
            ->postJson('/admin/activities', $this->payload($registration, $postSurvey));
        $activity = Activity::where('code', $create->json('code'))->firstOrFail();

        $payload = $this->payload($registration, $postSurvey);
        $payload['requires_registration'] = false;
        $payload['requires_checkin'] = false;
        $payload['has_post_survey'] = false;
        $payload['registration_form_id'] = null;
        $payload['post_survey_form_id'] = null;
        $payload['registration_end_at'] = null;
        $payload['checkin_start_at'] = null;
        $payload['checkin_end_at'] = null;

        $this->putJson('/admin/activities/'.$activity->code, $payload)->assertOk();

        $this->assertDatabaseMissing('evl_form_activity', ['activity_id' => $activity->id]);
        $this->assertSame(3, $activity->qrCodes()->count());
        $this->assertSame(0, $activity->qrCodes()->where('is_active', true)->count());
    }

    public function test_ดาวน์โหลด_png_จริงและสแกน_token_ที่เปิดใช้งานได้(): void
    {
        $activity = Activity::create([
            'code' => 'ACT-QR-001',
            'name' => 'กิจกรรม QR',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => Activity::STATUS_DRAFT,
        ]);
        $qr = $activity->qrCodes()->create([
            'purpose' => 'public',
            'token' => 'abcdefghijklmnopqrstuvwx',
            'target_url' => '/r/abcdefghijklmnopqrstuvwx',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/activities/'.$activity->code.'/qr/public?download=1')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get($qr->target_url)->assertStatus(410);

        $qr->update(['is_active' => true]);
        $this->get($qr->target_url)
            ->assertRedirect('/activities/'.$activity->code.'?action=registration&qr='.$qr->token);
        $this->assertSame(1, $qr->fresh()->scan_count);
    }
}
