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
    /** ค่าที่บันทึกใน evl_answers.text_value เมื่อผู้ตอบยอมรับความยินยอม */
    public const CONSENT_ACCEPTED = 'ยอมรับ';


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
        /* ความยินยอมต้องมาก่อนการตรวจ "ค่าว่าง" — ช่องติ๊กที่ไม่ได้ติ๊กจะไม่ถูกส่งมาเลย
           ถ้าปล่อยให้ตกไปที่ด้านล่างจะได้ข้อความ "กรุณาตอบคำถาม" ซึ่งไม่ตรงกับสิ่งที่ต้องทำ
           (ต้องกดยอมรับ ไม่ใช่กรอกคำตอบ)

           เก็บเป็นข้อความ "ยอมรับ" เพื่อให้ย้อนดูได้ว่าใครยินยอมบ้าง ส่วนเวลาอยู่ที่ตัวคำตอบแล้ว */
        if ($question->question_type === 'consent') {
            if (! filter_var($value, FILTER_VALIDATE_BOOL)) {
                if ($question->is_required) {
                    $this->fail($question, 'ต้องยอมรับ “'.$question->text.'” ก่อนส่งแบบประเมิน');
                }

                return [];
            }

            return [['question_id' => $question->id, 'text_value' => self::CONSENT_ACCEPTED]];
        }

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
