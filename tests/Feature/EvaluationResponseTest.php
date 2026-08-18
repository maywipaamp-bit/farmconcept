<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Answer;
use App\Models\CohortProfile;
use App\Models\FollowUpRound;
use App\Models\Form;
use App\Models\Participant;
use App\Models\Question;
use App\Models\Role;
use App\Models\SatisfactionResponse;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * หน้า "ตอบแบบประเมิน" — คำตอบแบบติดตามสุขภาพที่กลุ่มตัวอย่างส่งเข้ามาจริง
 *
 * อ่านจาก evl_survey_responses อย่างเดียว — คำตอบหลังกิจกรรมอยู่คนละตารางและตอบแบบไม่ระบุตัวตน
 * จึงต้องไม่ถูกดึงมาปนในหน้าที่ไล่ดูรายคน
 */
class EvaluationResponseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['code' => 'resp-admin', 'name' => 'ผู้ดูแลคำตอบ', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'evaluations-responses', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-RESP', 'name' => 'ผู้ทดสอบ', 'email' => 'resp@test.local',
            'password' => 'not-used', 'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    private function healthResponse(): SurveyResponse
    {
        $form = Form::create([
            'code' => 'FRM-H', 'name' => 'แบบติดตามสุขภาพ',
            'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_ACTIVE,
        ]);

        $question = Question::create([
            'form_id' => $form->id, 'sort_order' => 1, 'question_type' => 'rating',
            'text' => 'สุขภาพโดยรวมของคุณตอนนี้', 'is_required' => true,
        ]);

        $participant = Participant::create([
            'code' => 'P0001', 'person_code' => 'P0001',
            'name' => 'สมชาย ใจดี', 'phone' => '0812345678',
        ]);

        /* ใบตอบผูกกับใบติดตามเสมอ (cohort_round_id เป็น NOT NULL) — ทุกคำตอบต้องบอกได้ว่ามาจากรอบไหน */
        $profile = CohortProfile::create([
            'participant_id' => $participant->id,
            'cohort_code' => 'CHT-0001',
            'entry_date' => now()->toDateString(),
        ]);

        $round = FollowUpRound::create([
            'cohort_profile_id' => $profile->id,
            'name' => 'ก่อนเข้าร่วม',
            'offset_days' => 0,
            'due_date' => now()->toDateString(),
        ]);

        $response = SurveyResponse::create([
            'form_id' => $form->id,
            'participant_id' => $participant->id,
            'cohort_round_id' => $round->id,
            'submitted_at' => now(),
        ]);

        Answer::create([
            'response_type' => 'survey', 'response_id' => $response->id,
            'question_id' => $question->id, 'score' => 4,
        ]);

        return $response;
    }

    private function satisfactionResponse(): SatisfactionResponse
    {
        $form = Form::create([
            'code' => 'FRM-S', 'name' => 'แบบประเมินหลังกิจกรรม',
            'type' => Form::TYPE_POST_ACTIVITY, 'status' => Form::STATUS_ACTIVE, 'is_anonymous' => true,
        ]);

        $activity = Activity::create([
            'code' => 'ACT-1', 'name' => 'กิจกรรมทดสอบ', 'type' => Activity::TYPE_ACTIVITY,
            'status' => 'เปิดรับสมัคร', 'visibility' => 'สาธารณะ',
        ]);

        return SatisfactionResponse::create([
            'form_id' => $form->id,
            'activity_id' => $activity->id,
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function test_หน้ารายการแสดงคำตอบพร้อมผู้ตอบและรอบติดตาม(): void
    {
        $this->healthResponse();

        $this->actingAs($this->admin())
            ->get('/admin/evaluations/responses')
            ->assertOk()
            ->assertViewHas('responses', function ($rows) {
                $row = $rows->first();

                /* ต้องไม่มีคีย์ name เลย — ชื่อห้ามหลุดลง JSON ของหน้า (ข้อมูลนิรนาม) */
                return $rows->count() === 1
                    && ! array_key_exists('name', $row)
                    && $row['pid'] === 'P0001'
                    && $row['context'] === 'ก่อนเข้าร่วม'
                    && $row['answers'] === 1;
            })
            /* แท็บมาจากรอบที่มีคำตอบจริง ไม่ใช่รายการรอบทั้งหมดในระบบ */
            ->assertViewHas('rounds', fn ($rounds) => $rounds->all() === ['ก่อนเข้าร่วม']);
    }

    /**
     * คำตอบหลังกิจกรรมอยู่คนละตาราง (evl_satisfaction_responses) และตอบแบบไม่ระบุตัวตน
     * หน้านี้ดูคำตอบรายคนของกลุ่มตัวอย่าง จึงต้องไม่ดึงใบพวกนั้นมาปน
     */
    public function test_คำตอบหลังกิจกรรมต้องไม่โผล่ในหน้านี้(): void
    {
        $this->healthResponse();
        $this->satisfactionResponse();

        $this->actingAs($this->admin())
            ->get('/admin/evaluations/responses')
            ->assertOk()
            ->assertViewHas('responses', fn ($rows) => $rows->count() === 1);
    }

    public function test_ดูคำตอบรายข้อของใบหนึ่งใบได้(): void
    {
        $response = $this->healthResponse();

        $this->actingAs($this->admin())
            ->getJson('/admin/evaluations/responses/'.$response->id)
            ->assertOk()
            ->assertJsonPath('data.form', 'แบบติดตามสุขภาพ')
            ->assertJsonPath('data.answers.0.question', 'สุขภาพโดยรวมของคุณตอนนี้')
            /* คะแนนถูกเก็บที่คอลัมน์ score ไม่ใช่ text_value — ต้องแสดงออกมาได้ */
            ->assertJsonPath('data.answers.0.answer', '4');
    }

    public function test_ใบที่ไม่มีอยู่จริงตอบ_404(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/admin/evaluations/responses/999999')
            ->assertNotFound();
    }

    public function test_ผู้ใช้ที่ไม่มีสิทธิ์เมนูนี้เข้าไม่ได้(): void
    {
        $user = User::create([
            'code' => 'USR-NONE', 'name' => 'ไม่มีสิทธิ์', 'email' => 'none@test.local',
            'password' => 'not-used', 'status' => 'ใช้งานอยู่',
        ]);

        $this->actingAs($user)->get('/admin/evaluations/responses')->assertForbidden();
    }
}
