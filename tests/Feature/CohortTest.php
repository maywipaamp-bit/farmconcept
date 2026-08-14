<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CohortProfile;
use App\Models\District;
use App\Models\FollowUpNote;
use App\Models\FollowUpRoundTemplate;
use App\Models\Option;
use App\Models\Participant;
use App\Models\Role;
use App\Models\TargetGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CohortTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['code' => 'cohort-admin', 'name' => 'Cohort admin', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'cohort', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-COHORT',
            'name' => 'Cohort tester',
            'email' => 'cohort@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    /** ค่าตั้งต้นชุดเดียวกับที่ MasterDataSeeder ใส่ให้ระบบจริง */
    private function seedRoundTemplates(): void
    {
        $rows = [
            ['FRT-1', 'ก่อนเข้าร่วม', 0, true, 1],
            ['FRT-2', '3 เดือน', 90, true, 2],
            ['FRT-3', '6 เดือน', 180, true, 3],
            ['FRT-4', '12 เดือน', 365, true, 4],
            ['FRT-5', '24 เดือน', 730, false, 5],
        ];

        foreach ($rows as [$code, $name, $offset, $active, $sort]) {
            FollowUpRoundTemplate::create([
                'code' => $code, 'name' => $name, 'offset_days' => $offset,
                'is_active' => $active, 'sort_order' => $sort,
            ]);
        }
    }

    /** พื้นที่ต้องมี ประเภท · กลุ่ม · อำเภอ ครบ (NOT NULL ตั้งแต่ drop_area_text_columns) */
    private function area(): Area
    {
        return Area::firstOrCreate(['code' => 'AREA-1'], [
            'name' => 'ชุมชนพูนทรัพย์',
            'area_type_id' => $this->option('area_type', 'AT-1', 'ชุมชนเมือง')->id,
            'area_group_id' => $this->option('area_group', 'AG-1', 'กลุ่มกรุงเทพ')->id,
            'district_id' => District::firstOrCreate(['province' => 'กรุงเทพมหานคร', 'name' => 'เขตสายไหม'])->id,
        ]);
    }

    /** อาชีพกับประเภทพื้นที่มาจาก MasterDataSeeder ซึ่งไม่ได้รันในเทสต์ จึงสร้างเท่าที่ใช้ */
    private function option(string $group, string $code, string $label): Option
    {
        return Option::firstOrCreate(
            ['option_group' => $group, 'code' => $code],
            ['label' => $label, 'sort_order' => 1, 'is_active' => true]
        );
    }

    private function targetGroup(): TargetGroup
    {
        return TargetGroup::firstOrCreate(
            ['code' => 'TG-1'],
            ['name' => 'ผู้สูงอายุ', 'is_active' => true, 'sort_order' => 1]
        );
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        $templates = FollowUpRoundTemplate::active()->get();
        $entryDate = '2026-01-15';

        return array_merge([
            'person_code' => 'PID-0001',
            'name' => 'สมชาย ใจดี',
            'phone' => '081-234-5678',
            'gender' => 'male',
            'area_id' => $this->area()->id,
            'target_group_id' => $this->targetGroup()->id,
            'source_code' => 'walk_in',
            'entry_date' => $entryDate,
            'rounds' => $templates->map(fn (FollowUpRoundTemplate $t) => [
                'template_id' => $t->id,
                'due_date' => now()->parse($entryDate)->addDays($t->offset_days)->toDateString(),
            ])->all(),
            'consent' => 1,
        ], $overrides);
    }

    public function test_lookups_returns_only_active_rounds_ordered_by_sort_order(): void
    {
        $this->seedRoundTemplates();
        $this->option('occupation', 'OCC-1', 'เกษตรกร');

        $body = $this->actingAs($this->admin())
            ->getJson('/admin/cohort/lookups')
            ->assertOk()
            ->json();

        $this->assertSame(
            ['ก่อนเข้าร่วม', '3 เดือน', '6 เดือน', '12 เดือน'],
            array_column($body['followUpRounds'], 'label'),
            'รายการรอบต้องมาจาก mst_follow_up_round_templates ที่ is_active เรียงตาม sort_order เท่านั้น'
        );

        $this->assertSame([0, 90, 180, 365], array_column($body['followUpRounds'], 'offsetDays'));
        $this->assertSame('PID-0001', $body['nextPersonCode']);

        /* ช่วงอายุ อาชีพ และแหล่งที่มา ต้องมาจาก master กลาง ไม่ใช่ค่าที่เขียนตายในหน้าจอ */
        $this->assertNotEmpty($body['ageRanges']);
        $this->assertNotEmpty($body['occupations']);
        $this->assertContains('walk_in', array_column($body['sources'], 'value'));
    }

    public function test_page_renders_with_lookups_embedded(): void
    {
        $this->seedRoundTemplates();
        $this->area();
        $this->targetGroup();

        $this->actingAs($this->admin())
            ->get('/admin/cohort')
            ->assertOk()
            ->assertSee('co-form-grid')
            ->assertSee('co-round-chips')
            ->assertSee('co-due-table')
            /* ตัวเลือกฝังมากับหน้าเป็น JSON (ข้อความไทยถูก escape เป็น \uXXXX)
               ฟอร์มจึงใช้งานได้ทันทีโดยไม่ต้องรอ request รอบสอง */
            ->assertSee('nextPersonCode')
            ->assertSee('followUpRounds')
            ->assertSee('targetGroups');
    }

    public function test_store_creates_rounds_with_due_date_from_offset_days(): void
    {
        $this->seedRoundTemplates();

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', $this->payload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $profile = CohortProfile::firstOrFail();

        $this->assertSame('2026-01-15', $profile->entry_date->toDateString());
        $this->assertSame('walk_in', $profile->source_type);
        $this->assertSame('081-234-5678', $profile->participant->phone);

        $this->assertSame(
            ['ก่อนเข้าร่วม' => '2026-01-15', '3 เดือน' => '2026-04-15', '6 เดือน' => '2026-07-14', '12 เดือน' => '2027-01-15'],
            $profile->rounds->mapWithKeys(fn ($r) => [$r->name => $r->due_date->toDateString()])->all()
        );

        $this->assertSame([0, 90, 180, 365], $profile->rounds->pluck('offset_days')->all());

        $this->assertDatabaseHas('ptp_consents', [
            'participant_id' => $profile->participant_id,
            'status' => 'ยินยอม',
            'recorded_via' => 'admin_cohort',
        ]);
    }

    public function test_admin_can_override_due_date_of_a_single_round(): void
    {
        $this->seedRoundTemplates();

        $rounds = $this->payload()['rounds'];
        $rounds[1]['due_date'] = '2026-05-01'; // เลื่อนรอบ 3 เดือน ออกไปเอง

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', $this->payload(['rounds' => $rounds]))
            ->assertOk();

        $round = CohortProfile::firstOrFail()->rounds->firstWhere('name', '3 เดือน');

        $this->assertSame('2026-05-01', $round->due_date->toDateString());
        /* offset ต้องถูกคำนวณย้อนกลับจากวันที่จริง ไม่ใช่ลอกมาจาก template
           ไม่งั้นวันครบกำหนดกับจำนวนวันในระเบียนเดียวกันจะขัดกันเอง */
        $this->assertSame(106, $round->offset_days);
    }

    public function test_only_selected_rounds_are_created(): void
    {
        $this->seedRoundTemplates();

        $rounds = array_slice($this->payload()['rounds'], 0, 2);

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', $this->payload(['rounds' => $rounds]))
            ->assertOk();

        $this->assertSame(['ก่อนเข้าร่วม', '3 เดือน'], CohortProfile::firstOrFail()->rounds->pluck('name')->all());
    }

    public function test_person_code_never_collides_with_an_existing_participant(): void
    {
        $this->seedRoundTemplates();
        $admin = $this->admin();

        Participant::create([
            'code' => 'PID-0001', 'person_code' => 'PID-0001',
            'name' => 'คนที่มีอยู่แล้ว', 'phone' => '0800000000',
        ]);

        /* หน้าจอถือรหัสเก่าไว้ตั้งแต่ก่อนมีคนอื่นบันทึก — ต้องไม่ทับกันและต้องไม่ปฏิเสธทั้งฟอร์ม */
        $this->actingAs($admin)
            ->postJson('/admin/cohort', $this->payload(['person_code' => 'PID-0002']))
            ->assertOk();

        $this->assertSame(
            ['PID-0001', 'PID-0002'],
            Participant::orderBy('id')->pluck('person_code')->all()
        );

        $this->actingAs($admin)
            ->postJson('/admin/cohort', $this->payload(['person_code' => 'PID-0001']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('person_code');
    }

    public function test_required_fields_are_validated(): void
    {
        $this->seedRoundTemplates();

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'person_code', 'name', 'phone', 'gender',
                'area_id', 'target_group_id', 'source_code', 'entry_date', 'rounds', 'consent',
            ]);
    }

    public function test_at_least_one_round_and_consent_are_required(): void
    {
        $this->seedRoundTemplates();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/admin/cohort', $this->payload(['rounds' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rounds');

        $this->actingAs($admin)
            ->postJson('/admin/cohort', $this->payload(['consent' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('consent');
    }

    public function test_inactive_round_cannot_be_selected(): void
    {
        $this->seedRoundTemplates();
        $inactive = FollowUpRoundTemplate::where('is_active', false)->firstOrFail();

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', $this->payload([
                'rounds' => [['template_id' => $inactive->id, 'due_date' => '2028-01-14']],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rounds.0.template_id');
    }

    public function test_two_rounds_cannot_share_the_same_due_date(): void
    {
        $this->seedRoundTemplates();
        $templates = FollowUpRoundTemplate::active()->get();

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', $this->payload([
                'rounds' => [
                    ['template_id' => $templates[0]->id, 'due_date' => '2026-01-15'],
                    ['template_id' => $templates[1]->id, 'due_date' => '2026-01-15'],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rounds.1.due_date');
    }

    public function test_phone_must_be_a_thai_mobile_number(): void
    {
        $this->seedRoundTemplates();

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', $this->payload(['phone' => '02-123-4567']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_consent_upload_accepts_documents_and_rejects_other_types(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $path = $this->actingAs($admin)
            ->postJson('/admin/cohort/upload-consent', ['file' => UploadedFile::fake()->create('consent.pdf', 120, 'application/pdf')])
            ->assertOk()
            ->json('path');

        Storage::disk('local')->assertExists($path);
        $this->assertStringStartsWith('cohort-consents/', $path);

        $this->actingAs($admin)
            ->postJson('/admin/cohort/upload-consent', ['file' => UploadedFile::fake()->create('consent.exe', 10)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_uploaded_consent_file_is_attached_to_the_consent_record(): void
    {
        Storage::fake('local');
        $this->seedRoundTemplates();
        $admin = $this->admin();

        $path = $this->actingAs($admin)
            ->postJson('/admin/cohort/upload-consent', ['file' => UploadedFile::fake()->image('consent.jpg')])
            ->assertOk()
            ->json('path');

        $this->actingAs($admin)
            ->postJson('/admin/cohort', $this->payload(['consent_file_path' => $path]))
            ->assertOk();

        $this->assertDatabaseHas('ptp_consents', ['file_path' => $path]);
    }

    public function test_status_defaults_to_following_and_only_stopping_is_persisted(): void
    {
        $this->seedRoundTemplates();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/cohort', $this->payload())->assertOk();

        /* "กำลังติดตาม" ไม่มีคอลัมน์เก็บ — derive จากวันครบกำหนดล้วน ๆ */
        $this->assertNull(CohortProfile::firstOrFail()->stopped_at);

        $this->actingAs($admin)
            ->postJson('/admin/cohort', $this->payload([
                'person_code' => 'PID-0002',
                'status' => 'ยุติการติดตาม',
            ]))
            ->assertOk()
            ->assertJsonPath('data.stopped', true);

        $this->assertNotNull(CohortProfile::orderByDesc('id')->firstOrFail()->stopped_at);
    }

    public function test_detail_page_opens_for_a_person_who_has_follow_up_notes(): void
    {
        $this->seedRoundTemplates();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/cohort', $this->payload())->assertOk();

        $profile = CohortProfile::firstOrFail();

        /* บันทึกติดตามถูกสร้างโดยรอบติดตาม (แจ้งเตือน LINE / ติดตามนอกระบบ)
           หน้ารายละเอียดต้องเปิดได้เมื่อมีบันทึกแล้ว ไม่ใช่เฉพาะตอนที่ยังว่าง */
        FollowUpNote::create([
            'participant_id' => $profile->participant_id,
            'source' => 'ระบบแจ้งเตือน',
            'kind' => 'แจ้งเตือน LINE',
            'noted_at' => now(),
            'body' => 'ส่งแจ้งเตือน 3 เดือน · ส่งสำเร็จ',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get('/admin/cohort/'.$profile->id)->assertOk();
    }

    public function test_optional_master_data_is_stored_as_foreign_keys(): void
    {
        $this->seedRoundTemplates();

        $ageRange = Option::group('age_range')->firstOrFail();
        $occupation = $this->option('occupation', 'OCC-1', 'เกษตรกร');

        $this->actingAs($this->admin())
            ->postJson('/admin/cohort', $this->payload([
                'age_range_id' => $ageRange->id,
                'occupation_id' => $occupation->id,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('ptp_participants', [
            'person_code' => 'PID-0001',
            'age_range_id' => $ageRange->id,
            'occupation_id' => $occupation->id,
            'gender' => 'male',
        ]);
    }
}
