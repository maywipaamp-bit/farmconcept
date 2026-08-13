<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPostSurveyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Activity, 1: Form, 2: int, 3: int, 4: int} */
    private function activity(): array
    {
        $form = Form::create([
            'code' => 'EVL-POST-PUB',
            'name' => 'แบบประเมินหลังกิจกรรม',
            'description' => 'ช่วยบอกความคิดเห็นของคุณ',
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
        $single = $form->questions()->create([
            'sort_order' => 2,
            'question_type' => 'single',
            'text' => 'จะแนะนำกิจกรรมนี้หรือไม่',
            'is_required' => true,
        ]);
        $recommend = $single->options()->create([
            'sort_order' => 1,
            'label' => 'แนะนำ',
            'value' => 'recommend',
        ]);
        $single->options()->create([
            'sort_order' => 2,
            'label' => 'ไม่แนะนำ',
            'value' => 'not_recommend',
        ]);

        $activity = Activity::create([
            'code' => 'ACT-PUB-SURVEY',
            'name' => 'กิจกรรมเปิดแบบประเมิน',
            'type' => Activity::TYPE_ACTIVITY,
            'status' => 'ดำเนินการเสร็จสิ้น',
            'has_post_survey' => true,
            'is_published' => true,
            'visibility' => 'สาธารณะ',
            'public_sort_order' => 1,
            'survey_start_at' => now()->subHour(),
            'survey_end_at' => now()->addHour(),
        ]);
        $activity->forms()->attach($form->id, ['slot' => 'post_survey']);

        return [$activity, $form, $rating->id, $single->id, $recommend->id];
    }

    public function test_post_survey_qr_page_renders_the_linked_active_form(): void
    {
        [$activity] = $this->activity();

        $this->get('/activities/'.$activity->code.'?action=post-survey')
            ->assertOk()
            ->assertSee('แบบประเมินหลังกิจกรรม')
            ->assertSee('ความพึงพอใจโดยรวม')
            ->assertSee('จะแนะนำกิจกรรมนี้หรือไม่');
    }

    public function test_post_survey_is_saved_anonymously_with_answers(): void
    {
        [$activity, $form, $ratingId, $singleId, $optionId] = $this->activity();

        $this->postJson('/activities/'.$activity->code.'/post-survey', [
            'answers' => [
                (string) $ratingId => 5,
                (string) $singleId => $optionId,
            ],
        ])->assertCreated()->assertJsonPath('message', 'ส่งแบบประเมินเรียบร้อย ขอบคุณสำหรับความคิดเห็น');

        $this->assertDatabaseHas('evl_satisfaction_responses', [
            'form_id' => $form->id,
            'activity_id' => $activity->id,
        ]);
        $this->assertDatabaseHas('evl_answers', [
            'response_type' => 'satisfaction',
            'question_id' => $ratingId,
            'score' => 5,
        ]);
        $this->assertDatabaseHas('evl_answers', [
            'response_type' => 'satisfaction',
            'option_id' => $optionId,
        ]);
    }

    public function test_required_answer_and_option_ownership_are_validated(): void
    {
        [$activity, , $ratingId] = $this->activity();

        $this->postJson('/activities/'.$activity->code.'/post-survey', [
            'answers' => [(string) $ratingId => ''],
        ])->assertUnprocessable()->assertJsonValidationErrors(['answers.'.$ratingId]);

        $this->assertDatabaseCount('evl_satisfaction_responses', 0);
    }

    public function test_post_survey_is_blocked_outside_configured_window(): void
    {
        [$activity, , $ratingId, $singleId, $optionId] = $this->activity();
        $activity->update(['survey_end_at' => now()->subMinute()]);

        $this->postJson('/activities/'.$activity->code.'/post-survey', [
            'answers' => [
                (string) $ratingId => 5,
                (string) $singleId => $optionId,
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['survey']);
    }
}
