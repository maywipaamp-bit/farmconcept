<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Form;
use App\Models\Registration;
use App\Models\Role;
use App\Models\SatisfactionResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ประเมินกิจกรรม (admin/activities/responses)
 *
 * สิ่งที่ต้องไม่พังไม่ว่าจะแก้อะไร
 * 1. ลำดับผู้ตอบคิดจากรายการเต็มก่อนกรอง — กรองแล้วเลขต้องไม่ขยับ
 * 2. ไม่มีชื่อ เบอร์โทร หรือรหัสลงทะเบียนออกมาถึงหน้าจอ (ปม C)
 * 3. อัตราการตอบหารด้วยผู้เข้าร่วมจริง ไม่ใช่ผู้ลงทะเบียน
 */
class ActivityResponseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::create([
            'code' => 'USR-AR',
            'name' => 'เจ้าหน้าที่',
            'email' => 'ar@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);

        $role = Role::create(['code' => 'project_admin', 'name' => 'ผู้ดูแล', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'activities-responses', 'is_allowed' => true]);
        $role->menuPermissions()->create(['menu_key' => 'activities-list', 'is_allowed' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * กิจกรรมที่มีคำตอบสามชุด คะแนน 5 · 3 · 1 (ชุดใหม่สุดขึ้นก่อน)
     * และผู้ลงทะเบียนสี่คนที่เช็คอินจริงสองคน — อัตราการตอบจึงเป็น 150%
     * ที่ผิดปกติแบบนั้นตั้งใจ เพื่อพิสูจน์ว่าตัวหารคือผู้เข้าร่วมจริง ไม่ใช่ผู้ลงทะเบียน
     */
    private function seedActivity(): Activity
    {
        $form = Form::create([
            'code' => 'EVL-AR-01',
            'name' => 'แบบประเมินความพึงพอใจ',
            'type' => Form::TYPE_POST_ACTIVITY,
            'status' => Form::STATUS_ACTIVE,
            'is_anonymous' => true,
        ]);

        $rating = $form->questions()->create([
            'sort_order' => 1,
            'question_type' => 'rating',
            'text' => 'ความพึงพอใจโดยรวม',
            'is_required' => true,
        ]);

        $note = $form->questions()->create([
            'sort_order' => 2,
            'question_type' => 'text',
            'text' => 'ข้อเสนอแนะเพิ่มเติม',
            'is_required' => false,
        ]);

        $activity = Activity::create([
            'code' => 'ACT-AR-01',
            'name' => 'กิจกรรมทดสอบผลประเมิน',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => 'ดำเนินการเสร็จสิ้น',
            'has_post_survey' => true,
            'requires_registration' => true,
            'is_published' => true,
            'visibility' => 'สาธารณะ',
            'public_sort_order' => 1,
            'start_date' => '2026-08-01',
        ]);
        $activity->forms()->attach($form->id, ['slot' => 'post_survey']);

        $answers = [
            [5, 'จัดได้ดีมาก อยากให้จัดอีก', '2026-08-03 09:00:00'],
            [3, '', '2026-08-02 09:00:00'],
            [1, 'สถานที่คับแคบเกินไป', '2026-08-01 09:00:00'],
        ];

        foreach ($answers as [$score, $comment, $at]) {
            $response = SatisfactionResponse::create([
                'form_id' => $form->id,
                'activity_id' => $activity->id,
                'submitted_at' => $at,
            ]);
            $response->answers()->create(['question_id' => $rating->id, 'score' => $score]);

            if ($comment !== '') {
                $response->answers()->create(['question_id' => $note->id, 'text_value' => $comment]);
            }
        }

        foreach (range(1, 4) as $i) {
            Registration::create([
                'code' => 'REG-AR-'.$i,
                'activity_id' => $activity->id,
                'name' => 'ผู้ลงทะเบียน '.$i,
                'phone' => '08000000'.$i.'0',
                'registered_at' => now(),
                'checked_in_at' => $i <= 2 ? now() : null,
                'checkin_status' => $i <= 2 ? 'เข้าร่วมแล้ว' : 'ยังไม่เข้าร่วม',
            ]);
        }

        return $activity;
    }

    public function test_page_lists_responses_without_any_identifying_data(): void
    {
        $activity = $this->seedActivity();

        $this->actingAs($this->admin())
            ->get('/admin/activities/responses')
            ->assertOk()
            ->assertSee('ประเมินกิจกรรม')
            ->assertSee($activity->name)
            ->assertDontSee('ผู้ลงทะเบียน 1')
            ->assertDontSee('080000001');
    }

    public function test_legacy_html_url_redirects_to_the_new_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/activities/responses.html')
            ->assertRedirect('/admin/activities/responses');
    }

    public function test_data_endpoint_paginates_and_keeps_the_sequence_stable(): void
    {
        $this->seedActivity();
        $admin = $this->admin();

        $all = $this->actingAs($admin)
            ->getJson('/admin/activities/responses/data?id=ACT-AR-01&limit=10')
            ->assertOk()
            ->json();

        $this->assertSame(3, $all['total']);
        $this->assertSame([1, 2, 3], array_column($all['rows'], 'seq'));
        /* เรียงใหม่สุดก่อน ชุดแรกจึงเป็นคะแนน 5 */
        $this->assertSame([5, 3, 1], array_column($all['rows'], 'score'));
        $this->assertSame(['praise', 'mid', 'improve'], array_column($all['rows'], 'band'));

        $improve = $this->actingAs($admin)
            ->getJson('/admin/activities/responses/data?id=ACT-AR-01&band=improve&limit=10')
            ->assertOk()
            ->json();

        $this->assertSame(1, $improve['total']);
        /* กรองแล้วยังเป็นลำดับที่ 3 ของกิจกรรม ไม่ใช่ลำดับที่ 1 ของผลลัพธ์ */
        $this->assertSame(3, $improve['rows'][0]['seq']);
        $this->assertSame('สถานที่คับแคบเกินไป', $improve['rows'][0]['comment']);
    }

    public function test_keyword_matches_comment_text_and_response_number(): void
    {
        $this->seedActivity();
        $admin = $this->admin();

        $url = fn (string $keyword) => '/admin/activities/responses/data?'
            .http_build_query(['id' => 'ACT-AR-01', 'keyword' => $keyword, 'limit' => 10]);

        $byComment = $this->actingAs($admin)->getJson($url('คับแคบ'))->assertOk()->json();

        $this->assertSame(1, $byComment['total']);
        $this->assertSame(3, $byComment['rows'][0]['seq']);

        $bySeq = $this->actingAs($admin)->getJson($url('ผู้ตอบ #1'))->assertOk()->json();

        $this->assertSame(1, $bySeq['total']);
        $this->assertSame(1, $bySeq['rows'][0]['seq']);
    }

    public function test_summary_divides_by_checked_in_participants(): void
    {
        $this->seedActivity();

        $summary = $this->actingAs($this->admin())
            ->getJson('/admin/activities/responses/summary?id=ACT-AR-01')
            ->assertOk()
            ->json('summary');

        $this->assertSame(3, $summary['responseCount']);
        $this->assertSame(2, $summary['commentCount']);
        $this->assertSame(2, $summary['attendedCount']);
        $this->assertSame(150, $summary['responseRate']);
        $this->assertEquals(3, $summary['average']);
        $this->assertSame('ต้องปรับปรุง', $summary['grade']['label']);
        $this->assertSame(33, $summary['highRatioPercent']);

        $this->assertSame(
            [['star' => 5, 'count' => 1], ['star' => 4, 'count' => 0], ['star' => 3, 'count' => 1],
                ['star' => 2, 'count' => 0], ['star' => 1, 'count' => 1]],
            array_map(fn ($row) => ['star' => $row['star'], 'count' => $row['count']], $summary['distribution'])
        );

        $this->assertSame('ความพึงพอใจโดยรวม', $summary['topics'][0]['label']);
        $this->assertEquals(3, $summary['topics'][0]['average']);
        $this->assertTrue($summary['topics'][0]['needsWork']);
    }

    public function test_page_without_any_linked_form_shows_the_empty_state(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/activities/responses')
            ->assertOk()
            ->assertSee('ประเมินกิจกรรม');
    }

    public function test_user_without_the_menu_permission_is_rejected(): void
    {
        $user = User::create([
            'code' => 'USR-AR-2',
            'name' => 'ผู้ใช้ไม่มีสิทธิ์',
            'email' => 'ar2@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);
        $role = Role::create(['code' => 'staff', 'name' => 'เจ้าหน้าที่', 'is_active' => true]);
        $role->menuPermissions()->create(['menu_key' => 'activities-list', 'is_allowed' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/activities/responses')->assertForbidden();
    }
}
