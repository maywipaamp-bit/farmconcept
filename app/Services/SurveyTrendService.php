<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;

/**
 * กติกากลางของการอ่านแนวโน้มจากคำตอบแบบประเมิน
 *
 * ใช้ร่วมกันระหว่างหน้า "ผลตอบรายคน" (เทียบรอบของคนเดียว) กับ "ผลการวิเคราะห์"
 * (นับรวมทั้งโครงการ) — สองหน้าต้องตีความคำตอบเดียวกันเป็นทิศเดียวกันเสมอ
 * แยกกติกาไว้คนละที่เมื่อไร วันหนึ่งหน้าหนึ่งบอกดีขึ้นอีกหน้าบอกแย่ลงแน่นอน
 */
class SurveyTrendService
{
    /**
     * ทิศของสเกล: +1 = ตัวเลือกลำดับสูงกว่าแปลว่าดีขึ้น · -1 = แย่ลง · 0 = ไม่รู้ทิศ
     *
     * แบบในระบบมีสเกลสองแบบที่ทิศสวนกัน — "ไม่เห็นด้วยอย่างยิ่ง → เห็นด้วย" (ท้ายสเกลคือดี)
     * กับ "รู้ / ไม่รู้" (ท้ายสเกลคือแย่) จะถือว่าลำดับสูง = ดีเสมอไม่ได้
     * จึงดูจากฝั่งที่คำตอบเชิงลบอยู่: ถ้าอยู่ต้นสเกล การขยับขึ้นคือดีขึ้น
     * ถ้าอยู่ท้ายสเกล การขยับขึ้นคือแย่ลง · ไม่มีคำเชิงลบเลยก็ไม่เดา (คืน 0 = บอกแค่ว่าเปลี่ยน)
     *
     * "เชิงลบ" = ป้ายมีคำว่า "ไม่" อยู่ข้างใน ไม่ใช่แค่ขึ้นต้น — สเกลจริงในระบบใช้
     * "ทำไม่ได้เลย" กับ "ไม่ทำเลย" ปนกัน คำปฏิเสธไม่ได้อยู่ต้นคำเสมอ
     *
     * คะแนน rating ถือว่ามากคือดีตามธรรมเนียมของแบบประเมิน
     */
    public function scaleDirection(Question $question): int
    {
        if ($question->question_type === 'rating') {
            return 1;
        }

        if ($question->question_type !== 'single') {
            return 0;
        }

        [$negative, $positive] = $question->options->partition(
            fn ($option) => str_contains((string) $option->label, 'ไม่')
        );

        if ($negative->isEmpty() || $positive->isEmpty()) {
            return 0;
        }

        return $negative->avg('sort_order') < $positive->avg('sort_order') ? 1 : -1;
    }

    /**
     * ตำแหน่งของคำตอบบนสเกลของข้อนั้น — null เมื่อคำตอบไม่ใช่สเกล (ข้อความ) หรือไม่ได้ตอบ
     */
    public function position(?Answer $answer): ?int
    {
        if ($answer === null) {
            return null;
        }

        if ($answer->option !== null) {
            return (int) $answer->option->sort_order;
        }

        return $answer->score !== null ? (int) $answer->score : null;
    }

    /**
     * แปลผลต่างของตำแหน่ง (ปลาย - ต้น) ตามทิศของสเกล
     *
     * @return 'up'|'down'|'same'|'changed'
     */
    public function verdict(int $step, int $direction): string
    {
        if ($direction === 0) {
            return $step === 0 ? 'same' : 'changed';
        }

        $step *= $direction;

        return $step > 0 ? 'up' : ($step < 0 ? 'down' : 'same');
    }
}
