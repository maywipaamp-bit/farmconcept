<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CohortProfile;
use App\Models\District;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Form;
use App\Models\Option;
use App\Models\Participant;
use App\Models\QrCode;
use App\Models\Role;
use App\Models\RoundBatch;
use App\Models\RoundBatchMember;
use App\Models\TargetGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingRoundTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['code' => 'tracking-admin', 'name' => 'Tracking admin', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'evaluations-rounds', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-TRACKING',
            'name' => 'Tracking tester',
            'email' => 'tracking@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    private function form(): Form
    {
        return Form::firstOrCreate(
            ['code' => 'FRM-HEALTH'],
            ['name' => 'แบบติดตามสุขภาพ', 'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_ACTIVE]
        );
    }

    private function area(): Area
    {
        return Area::firstOrCreate(['code' => 'AREA-1'], [
            'name' => 'ชุมชนพูนทรัพย์',
            'area_type_id' => $this->option('area_type', 'AT-1', 'ชุมชนเมือง')->id,
            'area_group_id' => $this->option('area_group', 'AG-1', 'กลุ่มกรุงเทพ')->id,
            'district_id' => District::firstOrCreate(['province' => 'กรุงเทพมหานคร', 'name' => 'เขตสายไหม'])->id,
        ]);
    }

    private function option(string $group, string $code, string $label): Option
    {
        return Option::firstOrCreate(
            ['option_group' => $group, 'code' => $code],
            ['label' => $label, 'sort_order' => 1, 'is_active' => true]
        );
    }

    private function targetGroup(string $code, string $name): TargetGroup
    {
        return TargetGroup::firstOrCreate([ 'code' => $code], ['name' => $name, 'is_active' => true, 'sort_order' => 1]);
    }

    /**
     * สร้างคนหนึ่งคนพร้อมใบติดตามหนึ่งใบ
     *
     * ชื่อรอบมาจาก template เสมอ — เทสต์ตั้งชื่อแปลก ๆ ได้ เพื่อพิสูจน์ว่าไม่มีที่ไหน hardcode "3 เดือน"
     */
    private function member(string $code, TargetGroup $group, string $due, ?string $lineId = null, ?string $roundName = null): FollowUpRound
    {
        $template = FollowUpRoundTemplate::firstOrCreate(
            ['code' => 'FRT-'.($roundName ?? 'ปกติ')],
            ['name' => $roundName ?? 'ติดตามครั้งที่หนึ่ง', 'offset_days' => crc32($roundName ?? 'x') % 900, 'is_active' => true, 'sort_order' => 1]
        );

        $participant = Participant::create([
            'code' => $code, 'person_code' => $code,
            'name' => 'ผู้ร่วม '.$code, 'phone' => '081-000-'.substr($code, -4),
            'area_id' => $this->area()->id,
            'target_group_id' => $group->id,
            'line_user_id' => $lineId,
        ]);

        $profile = CohortProfile::create([
            'participant_id' => $participant->id,
            'cohort_code' => 'CHT-'.$code,
            'entry_date' => '2026-01-01',
        ]);

        return FollowUpRound::create([
            'cohort_profile_id' => $profile->id,
            'template_id' => $template->id,
            'name' => $template->name,
            'offset_days' => $template->offset_days,
            'due_date' => $due,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ติดตามกลุ่มตัวอย่าง ส.ค. 2569',
            'form_id' => $this->form()->id,
            'due_from' => '2026-08-01',
            'due_to' => '2026-08-31',
            'target_group_ids' => [],
            'follow_up_round_ids' => [],
            'notification_template' => 'สวัสดีคุณ {ชื่อ} ถึงเวลาทำแบบประเมิน{รอบ} ภายใน {วันครบกำหนด}',
            'notify' => false,
        ], $overrides);
    }

    private function eligible(User $admin, array $query): array
    {
        return $this->actingAs($admin)
            ->getJson('/admin/tracking-rounds/eligible-members?'.http_build_query($query))
            ->assertOk()
            ->json();
    }

    public function test_eligible_members_filters_by_due_date_target_group_and_unanswered(): void
    {
        $admin = $this->admin();
        $elderly = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $working = $this->targetGroup('TG-2', 'วัยทำงาน');

        $inRange = $this->member('PID-0001', $elderly, '2026-08-12');
        $this->member('PID-0002', $elderly, '2026-09-20');                       // นอกช่วงวัน
        $this->member('PID-0003', $working, '2026-08-15');                       // คนละกลุ่มเป้าหมาย
        $answered = $this->member('PID-0004', $elderly, '2026-08-18');
        $answered->update(['answered_at' => now()]);                             // ตอบไปแล้ว

        $body = $this->eligible($admin, [
            'from' => '2026-08-01', 'to' => '2026-08-31', 'target_group_ids' => [$elderly->id],
        ]);

        $this->assertSame(1, $body['total']);
        $this->assertSame([$inRange->id], $body['allIds']);
        $this->assertSame('PID-0001', $body['rows'][0]['pid']);
    }

    public function test_eligible_members_returns_every_target_group_when_none_selected(): void
    {
        $admin = $this->admin();
        $this->member('PID-0001', $this->targetGroup('TG-1', 'ผู้สูงอายุ'), '2026-08-12');
        $this->member('PID-0002', $this->targetGroup('TG-2', 'วัยทำงาน'), '2026-08-13');

        $body = $this->eligible($admin, ['from' => '2026-08-01', 'to' => '2026-08-31']);

        $this->assertSame(2, $body['total']);
    }

    public function test_round_name_comes_from_the_follow_up_record_not_from_hardcoded_months(): void
    {
        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');

        /* ชื่อที่ไม่มีทางเดาได้จากจำนวนเดือน — ถ้ามีที่ไหน hardcode "3 เดือน" ไว้ เทสต์นี้จะจับได้ */
        $this->member('PID-0001', $group, '2026-08-12', null, 'ติดตามพิเศษ ก่อนปิดโครงการ');

        $body = $this->eligible($admin, ['from' => '2026-08-01', 'to' => '2026-08-31']);

        $this->assertSame('ติดตามพิเศษ ก่อนปิดโครงการ', $body['rows'][0]['round']);
    }

    /** เปลี่ยนกติกา: ใบที่เคยอยู่ในรอบอื่นแล้วยังเลือกซ้ำได้ — เปิดรอบใหม่ตามคนเดิมซ้ำได้
        ตัวกันข้อความถล่มคือคนที่ตอบแล้วหลุดจากรายชื่อเอง (answered_at) */
    public function test_a_person_already_pulled_into_another_round_can_be_offered_again(): void
    {
        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $round = $this->member('PID-0001', $group, '2026-08-12', 'U-line-1');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'follow_up_round_ids' => [$round->id],
        ]))->assertOk();

        $body = $this->eligible($admin, ['from' => '2026-08-01', 'to' => '2026-08-31']);
        $this->assertSame(1, $body['total'], 'ใบที่อยู่ในรอบอื่นแล้วต้องยังถูกเสนอ เพื่อเปิดรอบตามซ้ำได้');

        /* แต่ใบที่ตอบแล้วต้องหลุดจากรายชื่อ — นี่คือตัวกันแจ้งเตือนคนที่จบธุระแล้ว */
        $round->update(['answered_at' => now()]);
        $this->assertSame(0, $this->eligible($admin, ['from' => '2026-08-01', 'to' => '2026-08-31'])['total']);
    }

    public function test_saving_a_draft_stores_members_without_sending_any_notification(): void
    {
        Http::fake();
        config(['services.line.messaging_token' => 'test-token']);

        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $round = $this->member('PID-0001', $group, '2026-08-12', 'U-line-1');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'target_group_ids' => [$group->id],
            'follow_up_round_ids' => [$round->id],
            'notify' => false,
        ]))->assertOk()->assertJsonPath('data.state', RoundBatch::STATE_DRAFT);

        $this->assertSame(1, RoundBatchMember::count());
        $this->assertNull(RoundBatchMember::firstOrFail()->notified_at);
        Http::assertNothingSent();

        /* กลุ่มเป้าหมายที่เลือกไว้ต้องถูกเก็บ เพื่อให้เปิดร่างกลับมาแล้วรู้ว่าตั้งใจครอบคลุมกลุ่มไหน */
        $this->assertSame([$group->id], RoundBatch::firstOrFail()->targetGroups->pluck('id')->all());
    }

    public function test_notification_is_pushed_only_to_people_who_have_a_line_channel(): void
    {
        Http::fake(['api.line.me/*' => Http::response(['sentMessages' => []], 200)]);
        config(['services.line.messaging_token' => 'test-token']);

        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $withLine = $this->member('PID-0001', $group, '2026-08-12', 'U-line-1');
        $withoutLine = $this->member('PID-0002', $group, '2026-08-13');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'follow_up_round_ids' => [$withLine->id, $withoutLine->id],
            'notify' => true,
        ]))->assertOk()
            ->assertJsonPath('notify.sent', 1)
            ->assertJsonPath('notify.noChannel', 1)
            ->assertJsonPath('notify.failed', 0);

        /* ยิงออกครั้งเดียว — คนที่ไม่มี line_user_id ต้องไม่ถูกยิงเลย ไม่ใช่ยิงแล้วพัง */
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === 'U-line-1');

        $sent = RoundBatchMember::where('follow_up_round_id', $withLine->id)->firstOrFail();
        $this->assertSame(RoundBatchMember::CHANNEL_LINE, $sent->notify_channel);
        $this->assertSame(RoundBatchMember::RESULT_SENT, $sent->notify_result);
        $this->assertNotNull($sent->notified_at);

        $skipped = RoundBatchMember::where('follow_up_round_id', $withoutLine->id)->firstOrFail();
        $this->assertSame(RoundBatchMember::CHANNEL_NONE, $skipped->notify_channel);
        $this->assertSame(RoundBatchMember::RESULT_NO_CHANNEL, $skipped->notify_result);
        $this->assertNull($skipped->notified_at);
    }

    public function test_notification_text_replaces_the_placeholders_with_real_values(): void
    {
        Http::fake(['api.line.me/*' => Http::response([], 200)]);
        config(['services.line.messaging_token' => 'test-token']);

        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $round = $this->member('PID-0001', $group, '2026-08-12', 'U-line-1', 'ติดตามครั้งที่สาม');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'follow_up_round_ids' => [$round->id],
            'notify' => true,
        ]))->assertOk();

        /* ส่งเป็นการ์ด Flex — เนื้อความ (แทนตัวแปรแล้ว) อยู่ใน body รอบ/วันครบกำหนดเป็นบรรทัดโครงสร้าง
           และปุ่มพาไปหน้าแบบประเมินอยู่ใน footer */
        Http::assertSent(function ($request) {
            $message = $request['messages'][0];
            $card = json_encode($message['contents'], JSON_UNESCAPED_UNICODE);
            $text = $message['contents']['body']['contents'][2]['text'] ?? '';
            $button = $message['contents']['footer']['contents'][0]['action'] ?? [];

            return $message['type'] === 'flex'
                && str_contains($text, 'ผู้ร่วม PID-0001')
                && ! str_contains($text, '{')
                && str_contains($card, 'รอบติดตาม ติดตามครั้งที่สาม')
                && str_contains($card, 'ตอบได้ถึงวันที่ 12 ส.ค. 2569')
                && $button['label'] === 'เริ่มทำแบบประเมิน'
                && str_ends_with($button['uri'], '/health');
        });
    }

    public function test_resending_does_not_message_people_who_already_received_it(): void
    {
        Http::fake(['api.line.me/*' => Http::response([], 200)]);
        config(['services.line.messaging_token' => 'test-token']);

        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $round = $this->member('PID-0001', $group, '2026-08-12', 'U-line-1');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'follow_up_round_ids' => [$round->id], 'notify' => true,
        ]))->assertOk();

        $batch = RoundBatch::firstOrFail();
        $this->actingAs($admin)->postJson('/admin/tracking-rounds/'.$batch->code.'/send-notify')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_notification_is_reported_as_failed_when_line_is_not_configured(): void
    {
        Http::fake();
        config(['services.line.messaging_token' => null]);

        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $round = $this->member('PID-0001', $group, '2026-08-12', 'U-line-1');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'follow_up_round_ids' => [$round->id], 'notify' => true,
        ]))->assertOk()
            ->assertJsonPath('notify.sent', 0)
            ->assertJsonPath('notify.failed', 1)
            ->assertJsonPath('notify.lineConfigured', false);

        Http::assertNothingSent();
        $this->assertSame(RoundBatchMember::RESULT_FAILED, RoundBatchMember::firstOrFail()->notify_result);
    }

    public function test_answering_the_survey_marks_the_round_and_the_batch_member_as_answered(): void
    {
        Http::fake(['api.line.me/*' => Http::response([], 200)]);
        config(['services.line.messaging_token' => 'test-token']);

        $admin = $this->admin();
        $this->form();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');

        /* ครบกำหนดวันนี้ = อยู่ในช่วงที่เปิดให้ตอบ */
        $round = $this->member('PID-0001', $group, now()->toDateString(), 'U-line-1');
        $participant = $round->cohortProfile->participant;

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'due_from' => now()->subDay()->toDateString(),
            'due_to' => now()->addDay()->toDateString(),
            'follow_up_round_ids' => [$round->id],
            'notify' => true,
        ]))->assertOk()->assertJsonPath('data.state', RoundBatch::STATE_RUNNING);

        /* ผู้ตอบเข้าทาง QR: ยืนยันด้วยเบอร์ แล้วส่งคำตอบของรอบนั้น
           รายละเอียดของแต่ละหน้าจออยู่ใน TrackingRoundQrTest ที่นี่สนใจแค่ว่า sync ถึงรอบติดตามไหม */
        $this->healthQr();
        $base = '/health';

        /* เบอร์อย่างเดียวเข้าไม่ได้ ต้องยืนยันชื่อจริงอีกชั้น — รายละเอียดอยู่ใน TrackingRoundQrTest */
        $this->post($base.'/verify', ['phone' => $participant->phone])->assertRedirect($base.'/choose');
        $this->post($base.'/choose', [
            'participant_id' => $participant->id,
            'name_prefix' => mb_substr($participant->name, 0, 5),
        ])->assertRedirect($base.'/home');
        $this->post($base.'/rounds/'.$round->id.'/survey', [])
            ->assertRedirect($base.'/rounds/'.$round->id.'/done');

        $this->assertNotNull($round->fresh()->answered_at, 'ใบติดตามรายคนต้องถูก stamp ว่าตอบแล้ว');
        $this->assertDatabaseHas('evl_survey_responses', ['cohort_round_id' => $round->id]);

        /* สมาชิกในรอบต้องกลายเป็น "ตอบแล้ว" ตามทันที เพราะอ่านจากใบเดียวกัน ไม่ได้เก็บสำเนา */
        $this->assertTrue(RoundBatchMember::firstOrFail()->fresh()->hasAnswered());
        $this->assertSame('ตอบแล้ว', RoundBatchMember::firstOrFail()->fresh()->responseStatus());

        $this->actingAs($admin)->getJson('/admin/tracking-rounds')
            ->assertOk()
            ->assertJsonPath('rows.0.answered', 1)
            ->assertJsonPath('rows.0.state', RoundBatch::STATE_DONE);
    }

    public function test_qr_verification_only_returns_rounds_that_are_open_for_that_person(): void
    {
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');

        $open = $this->member('PID-0001', $group, now()->toDateString(), null, 'รอบที่เปิดอยู่');
        $participant = $open->cohortProfile->participant;

        /* ใบอีกใบของคนเดียวกันที่ยังไม่ถึงกำหนด ต้องไม่โผล่ ไม่งั้นตอบล่วงหน้าข้ามรอบได้ */
        FollowUpRound::create([
            'cohort_profile_id' => $open->cohort_profile_id,
            'name' => 'รอบที่ยังไม่ถึงกำหนด',
            'offset_days' => 900,
            'due_date' => now()->addYear()->toDateString(),
        ]);

        $this->healthQr();
        $base = '/health';

        /* เบอร์อย่างเดียวเข้าไม่ได้ ต้องยืนยันชื่อจริงอีกชั้น — รายละเอียดอยู่ใน TrackingRoundQrTest */
        $this->post($base.'/verify', ['phone' => $participant->phone])->assertRedirect($base.'/choose');
        $this->post($base.'/choose', [
            'participant_id' => $participant->id,
            'name_prefix' => mb_substr($participant->name, 0, 5),
        ])->assertRedirect($base.'/home');

        /* ทั้งสองรอบอยู่ในรายการ แต่กดทำได้เฉพาะรอบที่เปิดอยู่
           รอบที่ยังไม่ถึงกำหนดต้องเห็นว่ามีอยู่ ไม่ใช่หายไปเฉย ๆ จนคิดว่าตกหล่น
           แดชบอร์ดไม่แสดงชื่อแล้ว — ยืนยันว่าเข้าถูกคนจากรหัสบุคคลในคำทักทาย */
        $this->get($base.'/home')->assertOk()->assertSee($participant->person_code);

        $this->get($base.'/rounds')
            ->assertOk()
            ->assertSee('รอบที่เปิดอยู่')
            ->assertSee('รอบที่ยังไม่ถึงกำหนด')
            ->assertSee('ยังไม่เปิด')
            ->assertSee('ถึงกำหนด')
            ->assertSee('เริ่มทำ');
    }

    public function test_only_health_follow_up_forms_can_be_used_for_a_round(): void
    {
        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $round = $this->member('PID-0001', $group, '2026-08-12');

        $other = Form::create([
            'code' => 'FRM-REG', 'name' => 'แบบลงทะเบียน',
            'type' => Form::TYPE_REGISTRATION, 'status' => Form::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'form_id' => $other->id,
            'follow_up_round_ids' => [$round->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors('form_id');
    }

    public function test_creating_a_round_requires_a_name_a_form_and_at_least_one_member(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/tracking-rounds', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'form_id', 'due_from', 'due_to', 'follow_up_round_ids']);
    }

    public function test_list_page_renders(): void
    {
        $this->healthQr();

        $this->actingAs($this->admin())
            ->get('/admin/tracking-rounds')
            ->assertOk()
            ->assertSee('fb-rows')
            ->assertSee('QR ทำแบบประเมินติดตามสุขภาพ');
    }

    public function test_detail_page_splits_members_into_notifiable_and_unreachable(): void
    {
        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $withLine = $this->member('PID-0001', $group, '2026-08-12', 'U-line-1');
        $withoutLine = $this->member('PID-0002', $group, '2026-08-13');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'follow_up_round_ids' => [$withLine->id, $withoutLine->id],
        ]))->assertOk();

        $batch = RoundBatch::firstOrFail();

        $this->actingAs($admin)->get('/admin/tracking-rounds/'.$batch->code)->assertOk();

        $body = $this->actingAs($admin)
            ->getJson('/admin/tracking-rounds/'.$batch->code)
            ->assertOk()
            ->json('data');

        $this->assertSame(2, $body['total']);
        $this->assertSame(1, $body['notifiable']);
        $this->assertSame(1, $body['unreachable']);
        $this->assertSame([true, false], array_column($body['members'], 'line'));
    }

    public function test_offline_follow_up_result_is_saved_and_copied_to_the_person_history(): void
    {
        $admin = $this->admin();
        $group = $this->targetGroup('TG-1', 'ผู้สูงอายุ');
        $round = $this->member('PID-0002', $group, '2026-08-13');

        $this->actingAs($admin)->postJson('/admin/tracking-rounds', $this->payload([
            'follow_up_round_ids' => [$round->id],
        ]))->assertOk();

        $batch = RoundBatch::firstOrFail();
        $member = RoundBatchMember::firstOrFail();

        $this->actingAs($admin)
            ->postJson('/admin/tracking-rounds/'.$batch->code.'/members/'.$member->id.'/offline-log', [
                'kind' => 'โทรติดตาม',
                'note' => 'โทรแล้วรับปากว่าจะมาทำที่ศูนย์สัปดาห์หน้า',
            ])
            ->assertOk()
            ->assertJsonPath('data.offlineKind', 'โทรติดตาม');

        $this->assertDatabaseHas('evl_round_batch_members', [
            'id' => $member->id, 'offline_kind' => 'โทรติดตาม', 'offline_by' => $admin->id,
        ]);

        /* ต้องไปโผล่ในประวัติของคนนั้นด้วย จะได้ไม่ต้องคีย์ซ้ำสองที่ */
        $this->assertDatabaseHas('ptp_follow_up_notes', [
            'participant_id' => $round->cohortProfile->participant_id,
            'kind' => 'โทรติดตาม',
        ]);
    }

    private function healthQr(): QrCode
    {
        return QrCode::firstOrCreate(
            ['purpose' => 'health', 'activity_id' => null],
            ['token' => 'abcdefghijklmnopqrstuvwx', 'target_url' => '/h/abcdefghijklmnopqrstuvwx', 'is_active' => true]
        );
    }
}
