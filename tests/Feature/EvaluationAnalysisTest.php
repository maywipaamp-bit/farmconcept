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
use App\Models\TargetGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * สรุปผลแบบประเมิน (รายงานวิจัยรายแบบ) + ผลการวิเคราะห์ (ภาพรวมโครงการ)
 *
 * จุดที่พลาดแล้วรายงานทั้งฉบับเชื่อไม่ได้: ทิศของสเกล (รู้/ไม่รู้ กลับทางกับสเกลเห็นด้วย)
 * ฐานร้อยละ (ต้องเป็นผู้ตอบข้อนั้นในรอบนั้น) และ S.D. ที่คิดจากคนเดียวไม่ได้
 */
class EvaluationAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        if ($this->admin !== null) {
            return $this->admin;
        }

        $role = Role::create(['code' => 'analysis-admin', 'name' => 'Analysis admin', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'evaluations-analysis', 'is_allowed' => true]);
        $role->menuPermissions()->create(['menu_key' => 'evaluations-summary', 'is_allowed' => true]);

        $user = User::create([
            'code' => 'USR-ANALYSIS', 'name' => 'Analysis tester',
            'email' => 'analysis@example.test', 'password' => 'not-used', 'status' => 'ใช้งานอยู่',
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
    private function question(string $text, array $labels, int $order, string $type = 'single'): Question
    {
        $question = Question::create([
            'form_id' => $this->form()->id,
            'question_type' => $type,
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

    /**
     * คนหนึ่งคนพร้อมคำตอบหลายรอบ — คีย์ของ $rounds คือชื่อรอบ ค่าคือ [question id => ลำดับตัวเลือก]
     *
     * @param  array<string, array<int, int>>  $rounds
     */
    private function person(string $code, array $rounds): Participant
    {
        $participant = Participant::create([
            'code' => $code, 'person_code' => $code, 'name' => $code, 'phone' => '081-000-'.substr($code, -4),
        ]);

        $profile = CohortProfile::create([
            'participant_id' => $participant->id,
            'cohort_code' => 'CHT-'.$code,
            'entry_date' => '2026-01-01',
        ]);

        $offset = 0;

        foreach ($rounds as $roundName => $picks) {
            $offset += 90;

            $template = FollowUpRoundTemplate::firstOrCreate(
                ['code' => 'FRT-'.$offset],
                ['name' => $roundName, 'offset_days' => $offset, 'is_active' => true, 'sort_order' => 1]
            );

            $round = FollowUpRound::create([
                'cohort_profile_id' => $profile->id,
                'template_id' => $template->id,
                'name' => $roundName,
                'offset_days' => $offset,
                'due_date' => '2026-08-17',
                'answered_at' => now(),
            ]);

            $response = SurveyResponse::create([
                'form_id' => $this->form()->id,
                'participant_id' => $participant->id,
                'cohort_round_id' => $round->id,
                'submitted_at' => now(),
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
        }

        return $participant;
    }

    public function test_ตารางค่าเฉลี่ยแสดง_xbar_และ_sd_รายรอบ(): void
    {
        $q = $this->question('ฉันกินผักทุกวัน', ['ไม่เห็นด้วยอย่างยิ่ง', 'ไม่เห็นด้วย', 'เห็นด้วยบ้าง', 'เห็นด้วย'], 1);

        /* ตำแหน่ง 2 กับ 4 ในรอบ 3 เดือน → x̄ 3.00, S.D. = √2 ≈ 1.41 (หาร n-1)
           รอบ 6 เดือนตอบคนเดียว → มีค่าเฉลี่ยแต่ S.D. เป็นขีด */
        $this->person('P-A', ['3 เดือน' => [$q->id => 2], '6 เดือน' => [$q->id => 4]]);
        $this->person('P-B', ['3 เดือน' => [$q->id => 4]]);

        $this->actingAs($this->admin())->get('/admin/evaluations/summary')
            ->assertOk()
            ->assertSee('(สเกล 1–4)')
            ->assertSee('3.00 (1.41)')
            ->assertSee('4.00 (–)');
    }

    public function test_กราฟเปลี่ยนแปลงสุทธิใช้สีตามความหมายของสเกล_ไม่ใช่ตามลำดับตัวเลือก(): void
    {
        /* "รู้" เป็นตัวเลือกแรกแต่คือฝั่งดี — แท่งต้องได้โทนเขียวเข้ม (tone 4)
           และ "ไม่รู้" ตัวเลือกท้ายคือฝั่งแย่ — ต้องได้โทนแดง (tone 0)
           ถ้าไล่สีตามลำดับตัวเลือกเฉย ๆ กราฟจะโชว์กลับข้างว่าคนไม่รู้คือเรื่องดี */
        $q = $this->question('อาหารหลัก 5 กลุ่มคืออะไร', ['รู้', 'ไม่รู้'], 1);

        $this->person('P-A', ['3 เดือน' => [$q->id => 1], '6 เดือน' => [$q->id => 2]]);

        $html = $this->actingAs($this->admin())->get('/admin/evaluations/summary')
            ->assertOk()
            ->getContent();

        /* คอลัมน์ "รู้" (ลดจาก 100% เหลือ 0% = -100 จุด) ต้องมีแท่งโทนเขียวเข้มอยู่ข้างใน */
        $this->assertMatchesRegularExpression('/title="รู้ · -100\.0 จุด"[\s\S]{0,600}?is-tone-4/u', $html);
        $this->assertMatchesRegularExpression('/title="ไม่รู้ · \+100\.0 จุด"[\s\S]{0,600}?is-tone-0/u', $html);
    }

    public function test_ข้อที่บอกทิศไม่ได้ใช้โทนสีกลาง_ไม่เดาว่าฝั่งไหนดี(): void
    {
        /* สเกลไม่มีคำเชิงลบเลย — ระบบต้องไม่ตัดสินว่ามื้อไหนคือคำตอบที่ดี */
        $q = $this->question('มื้อโปรดของคุณ', ['เช้า', 'กลางวัน', 'เย็น'], 1);

        $this->person('P-A', ['3 เดือน' => [$q->id => 1], '6 เดือน' => [$q->id => 3]]);

        $html = $this->actingAs($this->admin())->get('/admin/evaluations/summary')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('is-neutral-', $html);
        /* หน้ามีข้อเดียวและบอกทิศไม่ได้ — ต้องไม่มีสีเชิงตัดสิน (tone) โผล่ที่ไหนเลย */
        $this->assertStringNotContainsString('is-tone-', $html);
    }

    public function test_ตารางข้อมูลทั่วไปนับจำนวนและร้อยละของผู้ตอบ(): void
    {
        $q = $this->question('ข้อเดียว', ['ไม่เห็นด้วย', 'เห็นด้วย'], 1);

        $a = $this->person('P-A', ['3 เดือน' => [$q->id => 1]]);
        $b = $this->person('P-B', ['3 เดือน' => [$q->id => 2]]);
        $group = TargetGroup::firstOrCreate(['code' => 'TG-EL'], ['name' => 'ผู้สูงอายุ', 'is_active' => true, 'sort_order' => 1]);
        $a->update(['gender' => 'หญิง', 'target_group_id' => $group->id]);
        $b->update(['gender' => 'หญิง']);

        $this->actingAs($this->admin())->get('/admin/evaluations/summary')
            ->assertOk()
            ->assertSee('ข้อมูลทั่วไปของกลุ่มตัวอย่าง (n=2)')
            /* เพศหญิงทั้งคู่ = 2 คน 100.0% · กลุ่มเป้าหมายกรอกไว้คนเดียว อีกคนต้องขึ้น "ไม่ระบุ" */
            ->assertSeeInOrder([
                '<td class="is-option">หญิง</td>',
                '<td class="an-research-cell">2</td>',
                '<td class="an-research-cell">100.0</td>',
            ], false)
            ->assertSeeInOrder(['ผู้สูงอายุ', 'ไม่ระบุ']);
    }

    public function test_หน้าผลการวิเคราะห์มีโดนัทเพศและช่วงอายุของกลุ่มตัวอย่าง(): void
    {
        $q = $this->question('ข้อเดียว', ['ไม่เห็นด้วย', 'เห็นด้วย'], 1);
        $this->person('P-A', ['3 เดือน' => [$q->id => 1]])->update(['gender' => 'female']);

        $this->actingAs($this->admin())->get('/admin/evaluations/analysis')
            ->assertOk()
            ->assertSee('กลุ่มตัวอย่างตามเพศ')
            ->assertSee('กลุ่มตัวอย่างตามช่วงอายุ')
            /* รหัสเพศอังกฤษในฐานต้องถูกแปลเป็นไทยบนหน้ารายงาน */
            ->assertSee('หญิง')
            ->assertSee('dbo-donut-seg', false);
    }

    public function test_ตารางสรุปแบบงานวิจัยแสดงจำนวนและร้อยละแยกตามรอบ(): void
    {
        $q = $this->question('อาหารหลัก 5 กลุ่ม', ['รู้', 'ไม่รู้'], 1);

        /* 3 เดือน: รู้ 2 คน · 6 เดือน: รู้ 1 ไม่รู้ 1 */
        $this->person('P-A', ['3 เดือน' => [$q->id => 1], '6 เดือน' => [$q->id => 2]]);
        $this->person('P-B', ['3 เดือน' => [$q->id => 1], '6 เดือน' => [$q->id => 1]]);

        $this->actingAs($this->admin())->get('/admin/evaluations/summary')
            ->assertOk()
            ->assertSee('(n=2)')
            /* แถว "รู้": 2 (100.0) → 1 (50.0) · แถว "ไม่รู้": 0 (0.0) → 1 (50.0)
               ศูนย์ต้องแสดงเป็น 0 (0.0) ไม่ใช่แถวหาย — การไม่มีใครเลือกก็คือข้อมูล */
            ->assertSeeInOrder([
                '<td class="is-option">รู้</td>',
                '2 (100.0)',
                '1 (50.0)',
                '<td class="is-option">ไม่รู้</td>',
                '0 (0.0)',
                '1 (50.0)',
            ], false);
    }

    public function test_รอบที่ไม่มีใครตอบข้อนั้นแสดงขีด_ไม่ใช่ร้อยละจากฐานศูนย์(): void
    {
        $q1 = $this->question('ข้อที่ตอบทุกรอบ', ['ไม่เห็นด้วย', 'เห็นด้วย'], 1);
        $q2 = $this->question('ข้อที่ถูกข้ามในรอบหลัง', ['ไม่เห็นด้วย', 'เห็นด้วย'], 2);

        $this->person('P-A', [
            '3 เดือน' => [$q1->id => 1, $q2->id => 2],
            '6 เดือน' => [$q1->id => 2],   /* ข้อสองไม่ได้ตอบ */
        ]);

        $this->actingAs($this->admin())->get('/admin/evaluations/summary')
            ->assertOk()
            ->assertSee('<td class="an-research-cell is-zero">–</td>', false);
    }

    public function test_กราฟก่อนหลังทำงานกับคำตอบแบบตัวเลือก_และใช้ชื่อหมวดเป็นหัวข้อ(): void
    {
        /* การ์ดก่อน–หลัง (ยกมาจากแดชบอร์ด) เคยว่างตลอดเพราะรองรับแต่คำตอบแบบ score
           ทั้งที่แบบประเมินจริงเก็บเป็นตัวเลือก และใช้หมวด (section) แทนคอลัมน์ dimension */
        Question::create([
            'form_id' => $this->form()->id, 'question_type' => 'section',
            'text' => 'พฤติกรรมการกิน', 'sort_order' => 1,
        ]);
        $q = $this->question('ฉันกินผักทุกวัน', ['ไม่เห็นด้วย', 'เห็นด้วย'], 2);

        $this->person('P-A', ['3 เดือน' => [$q->id => 1], '6 เดือน' => [$q->id => 2]]);

        $this->actingAs($this->admin())->get('/admin/evaluations/analysis')
            ->assertOk()
            ->assertDontSee('ยังเทียบผลก่อน–หลังไม่ได้')
            ->assertSee('พฤติกรรมการกิน')
            ->assertSee('+100.0 จุด');
    }

    public function test_สเกลกลับทางถูกกลับค่าก่อนคิดคะแนนก่อนหลัง(): void
    {
        /* รู้ (ตำแหน่ง 1) → ไม่รู้ (ตำแหน่ง 2): ตำแหน่งเพิ่มแต่ความหมายคือแย่ลง
           คะแนนต้องลด (-100 จุด) ไม่ใช่เพิ่ม — ถ้าไม่กลับทิศ กราฟจะโชว์ว่าโครงการได้ผลทั้งที่ตรงข้าม */
        Question::create([
            'form_id' => $this->form()->id, 'question_type' => 'section',
            'text' => 'ความรอบรู้', 'sort_order' => 1,
        ]);
        $q = $this->question('อาหารหลัก 5 กลุ่ม', ['รู้', 'ไม่รู้'], 2);

        $this->person('P-A', ['3 เดือน' => [$q->id => 1], '6 เดือน' => [$q->id => 2]]);

        $this->actingAs($this->admin())->get('/admin/evaluations/analysis')
            ->assertOk()
            ->assertSee('-100.0 จุด');
    }

    public function test_กราฟเปลี่ยนแปลงสุทธิคิดจุดจากร้อยละรอบแรกและรอบล่าสุด(): void
    {
        $q = $this->question('อาหารหลัก 5 กลุ่ม', ['รู้', 'ไม่รู้'], 1);

        /* รอบแรก: รู้ 50% · รอบล่าสุด: รู้ 100% → รู้ +50.0 จุด · ไม่รู้ −50.0 จุด */
        $this->person('P-A', ['3 เดือน' => [$q->id => 1], '6 เดือน' => [$q->id => 1]]);
        $this->person('P-B', ['3 เดือน' => [$q->id => 2], '6 เดือน' => [$q->id => 1]]);

        $this->actingAs($this->admin())->get('/admin/evaluations/summary')
            ->assertOk()
            ->assertSee('+50.0')
            ->assertSee('-50.0')
            ->assertSee('สัดส่วน "รู้" เพิ่มขึ้น 50.0 จุด', false)
            ->assertSee('"ไม่รู้" ลดลง 50.0 จุด', false);
    }

    public function test_คนที่ไม่มีสิทธิ์เมนูนี้เข้าไม่ได้(): void
    {
        $role = Role::create(['code' => 'no-access', 'name' => 'No access', 'is_active' => true]);
        $user = User::create([
            'code' => 'USR-NOACCESS', 'name' => 'No access',
            'email' => 'noaccess@example.test', 'password' => 'not-used', 'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/evaluations/analysis')->assertForbidden();
        $this->actingAs($user)->get('/admin/evaluations/summary')->assertForbidden();
    }
}
