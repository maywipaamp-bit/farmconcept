<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * แปลงคำตอบที่ผู้ใช้ส่งมาเป็นแถวของ evl_answers พร้อมตรวจความถูกต้อง
 *
 * ใช้ร่วมกันระหว่างแบบประเมินความพึงพอใจหลังกิจกรรม (นิรนาม)
 * กับแบบติดตามสุขภาพผ่าน QR (ระบุตัวตน) — สองที่นี้เก็บคำตอบลงตารางเดียวกัน
 * กติกาการตรวจจึงต้องเป็นชุดเดียวกัน ไม่งั้นแบบเดียวกันตอบผ่านคนละทางแล้วได้ผลไม่เหมือนกัน
 */
class SurveyAnswerBuilder
{
    /**
     * @param  array<string, mixed>  $answers  คีย์เป็น id ของคำถาม
     * @return array<int, array<string, mixed>>
     */
    public function rowsFor(Form $form, array $answers): array
    {
        $rows = [];

        foreach ($this->answerableQuestions($form) as $question) {
            $value = $answers[(string) $question->id] ?? null;
            $rows = [...$rows, ...$this->rowsForQuestion($question, $value)];
        }

        return $rows;
    }

    /** 'section' เป็นหัวข้อคั่น ไม่ใช่คำถาม จึงไม่มีคำตอบและไม่ถูกบังคับกรอก */
    public function answerableQuestions(Form $form): Collection
    {
        return $form->questions->where('question_type', '!=', 'section');
    }

    /** @return array<int, array<string, mixed>> */
    private function rowsForQuestion(Question $question, mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            if ($question->is_required) {
                $this->fail($question, 'กรุณาตอบคำถาม “'.$question->text.'”');
            }

            return [];
        }

        if ($question->question_type === 'rating') {
            $score = filter_var($value, FILTER_VALIDATE_INT);

            if ($score === false || $score < 1 || $score > config('farmconcept.assessment_score_max')) {
                $this->fail($question, 'คะแนนของคำถาม “'.$question->text.'” ไม่ถูกต้อง');
            }

            return [['question_id' => $question->id, 'score' => $score]];
        }

        if ($question->question_type === 'text') {
            $text = trim((string) $value);

            if (mb_strlen($text) > 5000) {
                $this->fail($question, 'คำตอบของ “'.$question->text.'” ยาวเกินไป');
            }

            return [['question_id' => $question->id, 'text_value' => $text]];
        }

        $values = in_array($question->question_type, ['multi', 'chips'], true) ? (array) $value : [$value];
        $optionIds = collect($values)->map(fn ($id) => (int) $id)->unique()->values();
        $validIds = $question->options->whereIn('id', $optionIds)->pluck('id')->values();

        if ($validIds->count() !== $optionIds->count()) {
            $this->fail($question, 'ตัวเลือกของคำถาม “'.$question->text.'” ไม่ถูกต้อง');
        }

        return $validIds->map(fn (int $optionId) => [
            'question_id' => $question->id,
            'option_id' => $optionId,
        ])->all();
    }

    private function fail(Question $question, string $message): never
    {
        throw ValidationException::withMessages(['answers.'.$question->id => $message]);
    }
}
