<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * ตอบแบบประเมิน — คำตอบแบบติดตามสุขภาพที่กลุ่มตัวอย่างส่งเข้ามาจริง
 *
 * อ่านจาก evl_survey_responses อย่างเดียว ซึ่งเป็นคำตอบที่ผูกกับคนและรอบติดตามของเขา
 * คำตอบหลังกิจกรรม (evl_satisfaction_responses) ไม่รวมมาที่นี่ — ตอบแบบไม่ระบุตัวตน
 * และใช้ดูเป็นภาพรวมของกิจกรรม ไม่ใช่ไล่ดูรายคน จึงเป็นคนละงานกัน
 */
class EvaluationResponseController extends Controller
{
    public function index(): View
    {
        /* เรียงจากใหม่ไปเก่า — คนเปิดหน้านี้มาดูว่าเมื่อวานมีใครตอบเข้ามาบ้าง
           ไม่ได้มาไล่อ่านตั้งแต่คนแรกของโครงการ */
        $rows = $this->surveyRows()->sortByDesc('sortKey')->values();

        return view('admin.evaluations.responses', [
            'responses' => $rows,
            /* แท็บเป็นรอบติดตามที่มีคำตอบจริง ไม่ใช่รายการรอบทั้งหมดในระบบ
               รอบที่ยังไม่มีใครตอบขึ้นเป็นแท็บว่างก็ไม่มีประโยชน์ */
            'rounds' => $rows->pluck('context')->unique()->sort()->values(),
            'forms' => $rows->pluck('form')->unique()->sort()->values(),
        ]);
    }

    /**
     * คำตอบทีละข้อของใบหนึ่งใบ — ใช้ตอนกดดูรายละเอียด
     *
     * โหลดตอนกดดู ไม่ใช่ส่งมาพร้อมหน้า เพราะคำตอบ 383 ข้อของทั้งโครงการ
     * ส่งมาทั้งก้อนตั้งแต่เปิดหน้าทั้งที่ผู้ใช้เปิดดูจริงไม่กี่ใบ
     */
    public function show(int $id): JsonResponse
    {
        $response = SurveyResponse::with(['form', 'participant', 'cohortRound'])->find($id);

        abort_if($response === null, 404);

        $answers = Answer::query()
            ->where('response_type', 'survey')
            ->where('response_id', $id)
            ->with(['question.options', 'option'])
            ->get()
            /* เรียงตามลำดับข้อในแบบประเมิน ไม่ใช่ตามลำดับที่บันทึกลงฐาน
               ผู้อ่านกำลังเทียบกับกระดาษแบบประเมินในมือ ลำดับต้องตรงกัน */
            ->sortBy(fn (Answer $a) => $a->question?->sort_order ?? 0)
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'form' => $response->form?->name ?? 'ไม่ระบุแบบประเมิน',
                'submittedAt' => $response->submitted_at?->toDateTimeString(),
                'answers' => $answers->map(fn (Answer $a) => [
                    'question' => $a->question?->text ?? '(คำถามถูกลบไปแล้ว)',
                    'type' => $a->question?->question_type,
                    /* คำตอบมาได้สามทาง — ตัวเลือก, คะแนน, หรือข้อความ ข้อเดียวมีได้อย่างเดียว
                       แสดงตามที่มีจริง ไม่ต้องเดาว่าข้อนี้ควรเป็นแบบไหน */
                    'answer' => $a->option?->label
                        ?? ($a->text_value ?: ($a->score !== null ? (string) $a->score : '—')),
                ])->values(),
            ],
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function surveyRows(): Collection
    {
        return SurveyResponse::query()
            ->with(['form:id,name', 'participant:id,name,person_code', 'cohortRound:id,name'])
            ->withCount('answers')
            ->get()
            ->map(fn (SurveyResponse $r) => [
                'id' => $r->id,
                'form' => $r->form?->name ?? 'ไม่ระบุแบบประเมิน',
                'name' => $r->participant?->name ?? 'ไม่พบผู้ตอบ',
                'pid' => $r->participant?->person_code ?? '—',
                /* ใบติดตามสุขภาพผูกกับรอบของคนนั้น ต้องบอกว่ารอบไหน ไม่งั้นเทียบก่อน–หลังไม่ได้ */
                'context' => $r->cohortRound?->name ?? 'ไม่ระบุรอบ',
                'answers' => $r->answers_count,
                'at' => $r->submitted_at?->toDateTimeString(),
                'sortKey' => $r->submitted_at?->timestamp ?? 0,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
}
