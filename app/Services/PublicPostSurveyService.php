<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Form;
use App\Models\Question;
use App\Models\SatisfactionResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicPostSurveyService
{
    public function form(Activity $activity): ?Form
    {
        return $activity->forms()
            ->wherePivot('slot', 'post_survey')
            ->where('type', Form::TYPE_POST_ACTIVITY)
            ->where('status', Form::STATUS_ACTIVE)
            ->with(['questions.options'])
            ->first();
    }

    /** @param array<string, mixed> $answers */
    public function submit(Activity $activity, array $answers): SatisfactionResponse
    {
        return DB::transaction(function () use ($activity, $answers): SatisfactionResponse {
            $lockedActivity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            $form = $this->form($lockedActivity);

            if ($lockedActivity->visibility !== 'สาธารณะ'
                || ! $lockedActivity->acceptsPostSurvey()
                || ! $form) {
                throw ValidationException::withMessages([
                    'survey' => 'แบบประเมินนี้ยังไม่เปิดรับคำตอบหรือปิดรับคำตอบแล้ว',
                ]);
            }

            $questions = $form->questions->where('question_type', '!=', 'section');
            $rows = [];

            foreach ($questions as $question) {
                $value = $answers[(string) $question->id] ?? null;
                $rows = [...$rows, ...$this->answerRows($question, $value)];
            }

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

    /** @return array<int, array<string, mixed>> */
    private function answerRows(Question $question, mixed $value): array
    {
        $missing = $value === null || $value === '' || $value === [];
        if ($missing) {
            if ($question->is_required) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'กรุณาตอบคำถาม “'.$question->text.'”',
                ]);
            }

            return [];
        }

        if ($question->question_type === 'rating') {
            $score = filter_var($value, FILTER_VALIDATE_INT);
            if ($score === false || $score < 1 || $score > 5) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'คะแนนของคำถาม “'.$question->text.'” ไม่ถูกต้อง',
                ]);
            }

            return [['question_id' => $question->id, 'score' => $score]];
        }

        if ($question->question_type === 'text') {
            $text = trim((string) $value);
            if (mb_strlen($text) > 5000) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'คำตอบของ “'.$question->text.'” ยาวเกินไป',
                ]);
            }

            return [['question_id' => $question->id, 'text_value' => $text]];
        }

        $values = in_array($question->question_type, ['multi', 'chips'], true)
            ? (array) $value
            : [$value];
        $optionIds = collect($values)->map(fn ($id) => (int) $id)->unique()->values();
        $validIds = $question->options->whereIn('id', $optionIds)->pluck('id')->values();

        if ($validIds->count() !== $optionIds->count()) {
            throw ValidationException::withMessages([
                'answers.'.$question->id => 'ตัวเลือกของคำถาม “'.$question->text.'” ไม่ถูกต้อง',
            ]);
        }

        return $validIds->map(fn (int $optionId) => [
            'question_id' => $question->id,
            'option_id' => $optionId,
        ])->all();
    }
}
