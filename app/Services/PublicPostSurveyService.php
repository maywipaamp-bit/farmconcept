<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Form;
use App\Models\SatisfactionResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicPostSurveyService
{
    public function __construct(private readonly SurveyAnswerBuilder $answers) {}

    public function form(Activity $activity): ?Form
    {
        return $activity->forms()
            ->wherePivot('slot', 'post_survey')
            ->where('type', Form::TYPE_POST_ACTIVITY)
            ->where('status', Form::STATUS_ACTIVE)
            ->with(['questions.options'])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $answers
     * @param  bool  $byStaff  เจ้าหน้าที่ทำแทน — ข้ามการเช็กช่วงเวลาเปิดรับคำตอบ
     */
    public function submit(Activity $activity, array $answers, bool $byStaff = false): SatisfactionResponse
    {
        return DB::transaction(function () use ($activity, $answers, $byStaff): SatisfactionResponse {
            $lockedActivity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            $form = $this->form($lockedActivity);

            if ($lockedActivity->visibility !== 'สาธารณะ'
                || ! $lockedActivity->acceptsPostSurvey($byStaff)
                || ! $form) {
                throw ValidationException::withMessages([
                    'survey' => 'แบบประเมินนี้ยังไม่เปิดรับคำตอบหรือปิดรับคำตอบแล้ว',
                ]);
            }

            /* กติกาการตรวจคำตอบอยู่ที่ SurveyAnswerBuilder ที่เดียว
               ใช้ร่วมกับแบบติดตามสุขภาพผ่าน QR ซึ่งเก็บลงตาราง evl_answers เดียวกัน */
            $rows = $this->answers->rowsFor($form, $answers);

            $response = SatisfactionResponse::create([
                'form_id' => $form->id,
                'activity_id' => $lockedActivity->id,
                'submitted_at' => now(),
            ]);

            foreach ($rows as $row) {
                $response->answers()->create($row);
            }

            return $response;
        });
    }

}
