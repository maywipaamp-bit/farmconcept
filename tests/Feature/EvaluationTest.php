<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['code' => 'evaluation-admin', 'name' => 'ผู้ดูแลแบบประเมิน', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'evaluations', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-EVL-1',
            'name' => 'ผู้ทดสอบแบบประเมิน',
            'email' => 'evaluation@test.local',
            'password' => 'secret-not-used',
            'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    /** @return array<int, array<string, mixed>> */
    private function registrationFields(): array
    {
        $required = ['name', 'phone', 'pdpa'];
        $keys = ['name', 'phone', 'gender', 'age_range', 'email', 'occupation', 'source_channel', 'interests', 'pdpa'];

        return collect($keys)->map(fn (string $key, int $index) => [
            'key' => $key,
            'is_enabled' => true,
            'is_required' => in_array($key, $required, true),
            'sort_order' => $index + 1,
        ])->all();
    }

    public function test_สร้างแบบลงทะเบียนแบบจองหลายคนและเก็บฟิลด์มาตรฐานได้(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/admin/evaluations', [
            'name' => 'แบบลงทะเบียนทดสอบ',
            'description' => 'รองรับการจองแทน',
            'type' => Form::TYPE_REGISTRATION,
            'status' => Form::STATUS_ACTIVE,
            'registration_mode' => 'group',
            'max_participants' => 5,
            'fields' => $this->registrationFields(),
            'questions' => [],
        ]);

        $response->assertCreated()->assertJsonPath('form.type', Form::TYPE_REGISTRATION);
        $form = Form::where('code', $response->json('form.code'))->firstOrFail();

        $this->assertSame(5, $form->max_participants);
        $this->assertFalse($form->is_anonymous);
        $this->assertCount(9, $form->fields);
        $this->assertSame(['name', 'pdpa', 'phone'], $form->fields->where('is_required', true)->pluck('field_key')->sort()->values()->all());
    }

    public function test_สร้างแบบหลังกิจกรรมเป็นนิรนามพร้อมตัวเลือกคำถามได้(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/admin/evaluations', [
            'name' => 'แบบประเมินหลังจบกิจกรรม',
            'type' => Form::TYPE_POST_ACTIVITY,
            'status' => Form::STATUS_ACTIVE,
            'fields' => [],
            'questions' => [[
                'type' => 'single',
                'text' => 'จะแนะนำกิจกรรมนี้หรือไม่',
                'dimension' => 'ความพึงพอใจ',
                'is_required' => true,
                'sort_order' => 1,
                'options' => [
                    ['label' => 'แนะนำ', 'is_other' => false],
                    ['label' => 'ไม่แนะนำ', 'is_other' => false],
                ],
            ]],
        ]);

        $response->assertCreated();
        $form = Form::where('code', $response->json('form.code'))->firstOrFail();
        $this->assertTrue($form->is_anonymous);
        $this->assertCount(2, $form->questions->first()->options);
    }

    public function test_แบบติดตามสุขภาพระบุตัวตนและบันทึกร่างได้แม้ยังไม่มีคำถาม(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/admin/evaluations', [
            'name' => 'แบบติดตามสุขภาพฉบับร่าง',
            'type' => Form::TYPE_HEALTH_FOLLOW_UP,
            'status' => Form::STATUS_DRAFT,
            'fields' => [],
            'questions' => [],
        ]);

        $response->assertCreated();
        $this->assertFalse(Form::where('code', $response->json('form.code'))->firstOrFail()->is_anonymous);
    }

    public function test_เปิดใช้แบบประเมินทั่วไปไม่ได้ถ้ายังไม่มีคำถาม(): void
    {
        $this->actingAs($this->admin())->postJson('/admin/evaluations', [
            'name' => 'แบบที่ยังไม่ครบ',
            'type' => Form::TYPE_POST_ACTIVITY,
            'status' => Form::STATUS_ACTIVE,
            'fields' => [],
            'questions' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('questions');
    }

    public function test_ลบได้เมื่อยังไม่มีคำตอบ_สถานะไม่ใช่เกณฑ์(): void
    {
        $user = $this->admin();
        $draft = Form::create([
            'code' => 'EVL-TEST-DRAFT', 'name' => 'ร่างลบได้', 'type' => Form::TYPE_HEALTH_FOLLOW_UP,
            'status' => Form::STATUS_DRAFT, 'created_by' => $user->id, 'updated_by' => $user->id,
        ]);
        $active = Form::create([
            'code' => 'EVL-TEST-ACTIVE', 'name' => 'เปิดใช้แต่ยังไม่มีคำตอบ ลบได้', 'type' => Form::TYPE_HEALTH_FOLLOW_UP,
            'status' => Form::STATUS_ACTIVE, 'created_by' => $user->id, 'updated_by' => $user->id,
        ]);
        $answered = Form::create([
            'code' => 'EVL-TEST-ANSWERED', 'name' => 'มีคำตอบแล้วลบไม่ได้', 'type' => Form::TYPE_REGISTRATION,
            'status' => Form::STATUS_DRAFT, 'created_by' => $user->id, 'updated_by' => $user->id,
        ]);
        $question = $answered->questions()->create([
            'question_type' => 'text',
            'text' => 'คำถามลงทะเบียน',
            'sort_order' => 1,
        ]);
        \App\Models\Answer::create([
            'question_id' => $question->id,
            'response_type' => 'registration',
            'response_id' => 1,
            'text_value' => 'คำตอบทดสอบ',
        ]);

        $this->actingAs($user)->deleteJson('/admin/evaluations/'.$answered->code)->assertUnprocessable();
        $this->actingAs($user)->deleteJson('/admin/evaluations/'.$active->code)->assertOk();
        $this->actingAs($user)->deleteJson('/admin/evaluations/'.$draft->code)->assertOk();
        $this->assertDatabaseMissing('evl_forms', ['id' => $draft->id]);
        $this->assertDatabaseMissing('evl_forms', ['id' => $active->id]);
        $this->assertDatabaseHas('evl_forms', ['id' => $answered->id]);
    }

    public function test_หน้ารายการสร้างและแก้ไขใช้_clean_url_และผ่านสิทธิ์เมนู(): void
    {
        $user = $this->admin();
        $form = Form::create([
            'code' => 'EVL-ROUTE-1', 'name' => 'แบบทดสอบเส้นทาง',
            'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_DRAFT,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ]);

        $this->actingAs($user)->get('/admin/evaluations')->assertOk()->assertSee('แบบประเมิน');
        $this->actingAs($user)->get('/admin/evaluations/create')->assertOk()->assertSee('สร้างแบบประเมิน');
        $this->actingAs($user)->get('/admin/evaluations/'.$form->code.'/edit')->assertOk()->assertSee('แก้ไขแบบประเมิน');
        $this->actingAs($user)->getJson('/admin/evaluations/data')->assertOk()->assertJsonStructure(['forms']);
    }

    public function test_ลิงก์_html_เดิม_redirect_ไป_clean_url(): void
    {
        $user = $this->admin();
        $form = Form::create([
            'code' => 'EVL-LEGACY-1', 'name' => 'แบบทดสอบลิงก์เดิม',
            'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_DRAFT,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ]);

        $this->actingAs($user)->get('/admin/evaluations/list.html')
            ->assertRedirect('/admin/evaluations');
        $this->actingAs($user)->get('/admin/evaluations/create.html')
            ->assertRedirect(route('admin.evaluations.create'));
        $this->actingAs($user)->get('/admin/evaluations/create.html?id='.$form->code)
            ->assertRedirect(route('admin.evaluations.edit', $form));
    }

    public function test_ไม่สามารถแก้ไขแบบประเมินที่มีคำตอบแล้วและแจ้งข้อความทำสำเนา(): void
    {
        $user = $this->admin();
        $form = Form::create([
            'code' => 'EVL-RESP-1', 'name' => 'แบบประเมินมีคำตอบ',
            'type' => Form::TYPE_REGISTRATION, 'status' => Form::STATUS_ACTIVE,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ]);
        $question = $form->questions()->create([
            'question_type' => 'text',
            'text' => 'คำถามลงทะเบียน',
            'sort_order' => 1,
        ]);
        \App\Models\Answer::create([
            'question_id' => $question->id,
            'response_type' => 'registration',
            'response_id' => 1,
            'text_value' => 'คำตอบทดสอบ',
        ]);

        $response = $this->actingAs($user)->putJson('/admin/evaluations/'.$form->code, [
            'name' => 'พยายามแก้แบบเดิม',
            'type' => Form::TYPE_REGISTRATION,
            'status' => Form::STATUS_ACTIVE,
            'registration_mode' => 'single',
            'fields' => $this->registrationFields(),
            'questions' => [
                [
                    'type' => 'text',
                    'text' => 'คำถามลงทะเบียนแก้ใหม่',
                    'is_required' => false,
                    'sort_order' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.form.0', 'แบบประเมินนี้มีคำตอบแล้ว จึงแก้ไขโครงสร้างเดิมไม่ได้ กรุณาทำสำเนาเป็นชุดใหม่');
    }

    public function test_ทำสำเนาแบบประเมินผ่าน_api_duplicate(): void
    {
        $user = $this->admin();
        $form = Form::create([
            'code' => 'EVL-DUP-1', 'name' => 'แบบประเมินต้นฉบับ',
            'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_ACTIVE,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/admin/evaluations/'.$form->code.'/duplicate');
        $response->assertCreated()->assertJsonPath('form.name', 'แบบประเมินต้นฉบับ (สำเนา)');
    }

    /**
     * IIS บนเซิร์ฟเวอร์ปลายทางดัก PUT / PATCH / DELETE ไว้ตั้งแต่ก่อนถึง PHP (WebDAVModule)
     * ตอบ 405 พร้อม Allow: GET, HEAD, OPTIONS, TRACE — บนเครื่องที่รันด้วย Herd ไม่เจอเพราะไม่มีโมดูลนี้
     *
     * หน้าจอจึงส่งเป็น POST แล้วบอกเมธอดจริงผ่านหัวข้อ X-HTTP-Method-Override
     * เทสต์นี้คุมว่าเส้นทางนั้นยังใช้ได้จริง ถ้าวันหนึ่ง Laravel เลิกอ่านหัวข้อนี้จะได้รู้ทันที
     */
    public function test_เปลี่ยนสถานะและลบผ่าน_post_ที่แนบ_method_override_ได้(): void
    {
        $user = $this->admin();
        $form = Form::create([
            'code' => 'EVL-OVERRIDE-1', 'name' => 'แบบประเมินทดสอบเมธอด',
            'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_ACTIVE,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson('/admin/evaluations/'.$form->code.'/status',
                ['status' => Form::STATUS_INACTIVE],
                ['X-HTTP-Method-Override' => 'PATCH'])
            ->assertOk();

        $this->assertSame(Form::STATUS_INACTIVE, $form->fresh()->status);

        /* ลบก็ต้องผ่านเส้นทางเดียวกันได้ — ต้องเป็นฉบับร่างก่อนถึงลบได้ตามกติกาเดิม */
        $form->update(['status' => Form::STATUS_DRAFT]);

        $this->actingAs($user)
            ->postJson('/admin/evaluations/'.$form->code, [], ['X-HTTP-Method-Override' => 'DELETE'])
            ->assertOk();

        $this->assertDatabaseMissing('evl_forms', ['id' => $form->id]);
    }

    /** แบบประเมินที่ยังไม่ถูกนำไปใช้ (ยังไม่มีคำตอบ) ต้องแก้ไขได้ แม้จะเปิดใช้งานอยู่ */
    public function test_แบบประเมินที่เปิดใช้งานแต่ยังไม่มีคำตอบยังแก้ไขได้(): void
    {
        $user = $this->admin();
        $form = Form::create([
            'code' => 'EVL-EDIT-1', 'name' => 'แบบประเมินที่ยังไม่มีใครตอบ',
            'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_ACTIVE,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ]);
        $form->questions()->create([
            'sort_order' => 1, 'question_type' => 'rating', 'text' => 'คำถามเดิม', 'is_required' => true,
        ]);

        $this->actingAs($user)
            ->putJson('/admin/evaluations/'.$form->code, [
                'name' => 'แก้ชื่อแล้ว',
                'type' => Form::TYPE_HEALTH_FOLLOW_UP,
                'status' => Form::STATUS_ACTIVE,
                'fields' => [],
                'questions' => [
                    ['type' => 'rating', 'text' => 'คำถามใหม่', 'is_required' => true, 'sort_order' => 1, 'options' => []],
                ],
            ])
            ->assertOk();

        $this->assertSame('แก้ชื่อแล้ว', $form->fresh()->name);
        $this->assertSame('คำถามใหม่', $form->fresh()->questions()->first()->text);
    }
}
