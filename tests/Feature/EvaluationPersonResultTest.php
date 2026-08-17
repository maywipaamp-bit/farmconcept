<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\CohortProfile;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Form;
use App\Models\Participant;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ผลตอบรายคน — ตารางคำถาม × รอบ ของคนหนึ่งคน พร้อมแนวโน้มดีขึ้น/ลดลง
 *
 * หัวใจของหน้านี้คือทิศของสเกล: แบบจริงมีทั้งสเกลที่ท้ายคือดี (ไม่เห็นด้วย → เห็นด้วย)
 * และสเกลที่ท้ายคือแย่ (รู้ / ไม่รู้) — เทสต์ต้องครอบทั้งสองทาง ไม่งั้นลูกศรชี้ผิดข้าง
 * โดยไม่มีอะไรพัง
 */
class EvaluationPersonResultTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        if ($this->admin !== null) {
            return $this->admin;
        }

        $role = Role::create(['code' => 'result-admin', 'name' => 'Result admin', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'evaluations-person-results', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-RESULT', 'name' => 'Result tester',
            'email' => 'result@example.test', 'password' => 'not-used', 'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $this->admin = $user;
    }

    private function form(): Form
    {
        return Form::firstOrCreate(
            ['code' => 'FRM-HEALTH'],
            ['name' => 'แบบติดตามสุขภาพ', 'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_ACTIVE]
        );
    }

    /** @param  array<int, string>  $labels */
    private function question(string $text, array $labels, int $order): Question
    {
        $question = Question::create([
            'form_id' => $this->form()->id,
            'question_type' => 'single',
            'text' => $text,
            'sort_order' => $order,
        ]);

        foreach ($labels as $i => $label) {
            QuestionOption::create([
                'question_id' => $question->id,
                'label' => $label,
                'value' => 'option_'.($i + 1),
                'sort_order' => $i + 1,
            ]);
        }

        return $question;
    }

    private function participant(): Participant
    {
        return Participant::firstOrCreate(['code' => 'PID-01'], [
            'person_code' => 'PID-01', 'name' => 'PID-01', 'phone' => '081-000-0001',
        ]);
    }

    /**
     * ใบตอบหนึ่งรอบ — เลือกตัวเลือกตามลำดับที่ส่งเข้ามา (คีย์ = question id, ค่า = ลำดับตัวเลือก)
     *
     * @param  array<int, int>  $picks
     */
    private function respond(string $roundName, int $offsetDays, string $submittedAt, array $picks): SurveyResponse
    {
        $profile = CohortProfile::firstOrCreate(
            ['participant_id' => $this->participant()->id],
            ['cohort_code' => 'CHT-01', 'entry_date' => '2026-01-01']
        );

        $template = FollowUpRoundTemplate::firstOrCreate(
            ['code' => 'FRT-'.$offsetDays],
            ['name' => $roundName, 'offset_days' => $offsetDays, 'is_active' => true, 'sort_order' => 1]
        );

        $round = FollowUpRound::create([
            'cohort_profile_id' => $profile->id,
            'template_id' => $template->id,
            'name' => $roundName,
            'offset_days' => $offsetDays,
            'due_date' => '2026-08-17',
            'answered_at' => $submittedAt,
        ]);

        $response = SurveyResponse::create([
            'form_id' => $this->form()->id,
            'participant_id' => $this->participant()->id,
            'cohort_round_id' => $round->id,
            'submitted_at' => $submittedAt,
        ]);

        foreach ($picks as $questionId => $position) {
            Answer::create([
                'response_type' => 'survey',
                'response_id' => $response->id,
                'question_id' => $questionId,
                'option_id' => QuestionOption::where('question_id', $questionId)
                    ->where('sort_order', $position)->firstOrFail()->id,
            ]);
        }

        return $response;
    }

    public function test_รายชื่อแสดงหนึ่งแถวต่อคน_ไม่ใช่ต่อใบตอบ(): void
    {
        $q = $this->question('ข้อเดียว', ['ไม่เห็นด้วย', 'เห็นด้วย'], 1);
        $this->respond('3 เดือน', 90, '2026-05-01 10:00:00', [$q->id => 1]);
        $this->respond('6 เดือน', 180, '2026-08-01 10:00:00', [$q->id => 2]);

        $this->actingAs($this->admin())->get('/admin/evaluations/person-results')
            ->assertOk()
            ->assertSee('ตอบแล้ว 1 คน')
            ->assertSee('PID-01');
    }

    public function test_คอลัมน์เรียงตามลำดับรอบ_ไม่ใช่วันที่กดส่ง(): void
    {
        $q = $this->question('ข้อเดียว', ['ไม่เห็นด้วย', 'เห็นด้วย'], 1);
        /* รอบ 3 เดือนถูกส่งทีหลังรอบ 6 เดือน — คอลัมน์ต้องยังเรียง 3 → 6 ตามลำดับรอบ */
        $this->respond('6 เดือน', 180, '2026-08-01 10:00:00', [$q->id => 2]);
        $this->respond('3 เดือน', 90, '2026-08-15 10:00:00', [$q->id => 1]);

        $html = $this->actingAs($this->admin())
            ->get('/admin/evaluations/person-results/'.$this->participant()->id)
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($html, '6 เดือน'),
            strpos($html, '3 เดือน'),
            'คอลัมน์รอบ 3 เดือนต้องมาก่อนรอบ 6 เดือน'
        );
    }

    public function test_สเกลปกติ_ตอบขยับขึ้นสเกลนับเป็นดีขึ้น(): void
    {
        $q = $this->question('ฉันกินผักทุกวัน', ['ไม่เห็นด้วยอย่างยิ่ง', 'ไม่เห็นด้วย', 'เห็นด้วยบ้าง', 'เห็นด้วย'], 1);
        $this->respond('3 เดือน', 90, '2026-05-01 10:00:00', [$q->id => 2]);
        $this->respond('6 เดือน', 180, '2026-08-01 10:00:00', [$q->id => 4]);

        $this->actingAs($this->admin())
            ->get('/admin/evaluations/person-results/'.$this->participant()->id)
            ->assertOk()
            ->assertSee('ดีขึ้นจากรอบก่อนหน้า')
            ->assertSee('ดีขึ้น <b class="num">1</b> ข้อ', false);
    }

    public function test_สเกลกลับทาง_รู้เป็นไม่รู้ต้องนับเป็นลดลง(): void
    {
        /* "ไม่รู้" อยู่ท้ายสเกล — การขยับขึ้นตามลำดับตัวเลือกคือแย่ลง ไม่ใช่ดีขึ้น */
        $q = $this->question('อาหารหลัก 5 กลุ่มคืออะไร', ['รู้', 'ไม่รู้'], 1);
        $this->respond('3 เดือน', 90, '2026-05-01 10:00:00', [$q->id => 1]);
        $this->respond('6 เดือน', 180, '2026-08-01 10:00:00', [$q->id => 2]);

        $this->actingAs($this->admin())
            ->get('/admin/evaluations/person-results/'.$this->participant()->id)
            ->assertOk()
            ->assertSee('ลดลงจากรอบก่อนหน้า')
            ->assertSee('ลดลง <b class="num">1</b> ข้อ', false);
    }

    public function test_สเกลที่คำปฏิเสธไม่ได้อยู่ต้นคำ_ยังบอกทิศได้(): void
    {
        /* สเกลจริงของหมวดทักษะ — คำปฏิเสธซ่อนกลางคำ ("ทำไม่ได้เลย") เคยทำให้ระบบบอกทิศไม่ได้ */
        $q = $this->question('ฉันวางแผนมื้ออาหารเอง', ['ทำไม่ได้เลย', 'ทำไม่ค่อยได้', 'ทำได้บ้าง', 'ทำได้', 'ทำได้ดี'], 1);
        $this->respond('3 เดือน', 90, '2026-05-01 10:00:00', [$q->id => 3]);
        $this->respond('6 เดือน', 180, '2026-08-01 10:00:00', [$q->id => 5]);

        $this->actingAs($this->admin())
            ->get('/admin/evaluations/person-results/'.$this->participant()->id)
            ->assertOk()
            ->assertSee('ดีขึ้น <b class="num">1</b> ข้อ', false)
            /* "บอกทิศไม่ได้" เฉย ๆ ใช้ไม่ได้ — คำนี้อยู่ใน legend ของหัวการ์ดเสมอ
               ต้องเช็คที่ร่องรอยของช่องที่บอกทิศไม่ได้จริง ๆ แทน */
            ->assertDontSee('คำตอบเปลี่ยนจากรอบก่อนหน้า')
            ->assertDontSee('เปลี่ยนแต่บอกทิศไม่ได้');
    }

    public function test_ภาพรวมเทียบรอบแรกกับรอบล่าสุด_ไม่ใช่รอบติดกัน(): void
    {
        /* 2 → 4 → 3: รอบท้ายลดจากรอบกลาง แต่ยังสูงกว่ารอบแรก — ภาพรวมต้องเป็นดีขึ้น */
        $q = $this->question('ฉันกินผักทุกวัน', ['ไม่เห็นด้วยอย่างยิ่ง', 'ไม่เห็นด้วย', 'เห็นด้วยบ้าง', 'เห็นด้วย'], 1);
        $this->respond('3 เดือน', 90, '2026-03-01 10:00:00', [$q->id => 2]);
        $this->respond('6 เดือน', 180, '2026-06-01 10:00:00', [$q->id => 4]);
        $this->respond('12 เดือน', 365, '2026-08-01 10:00:00', [$q->id => 3]);

        $this->actingAs($this->admin())
            ->get('/admin/evaluations/person-results/'.$this->participant()->id)
            ->assertOk()
            ->assertSee('ดีขึ้น <b class="num">1</b> ข้อ', false)
            ->assertSee('ลดลง <b class="num">0</b> ข้อ', false);
    }

    public function test_คนที่ไม่มีสิทธิ์เมนูนี้เข้าไม่ได้(): void
    {
        $role = Role::create(['code' => 'no-access', 'name' => 'No access', 'is_active' => true]);
        $user = User::create([
            'code' => 'USR-NOACCESS', 'name' => 'No access',
            'email' => 'noaccess@example.test', 'password' => 'not-used', 'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/evaluations/person-results')->assertForbidden();
    }

    public function test_คนที่ไม่มีในระบบขึ้น_404(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/evaluations/person-results/999999')
            ->assertNotFound();
    }
}
