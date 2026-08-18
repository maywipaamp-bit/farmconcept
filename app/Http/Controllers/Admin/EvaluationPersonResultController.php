<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Participant;
use App\Models\Question;
use App\Models\SurveyResponse;
use App\Services\SurveyTrendService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * ผลตอบรายคน — คำตอบทุกรอบของคนหนึ่งคน วางเทียบกันเป็นตาราง คำถามเป็นแถว รอบเป็นคอลัมน์
 *
 * ตอบโจทย์ที่หน้า "ตอบแบบประเมิน" ตอบไม่ได้: หน้านั้นดูเป็นรายใบ อยากรู้ว่าคนหนึ่ง
 * เปลี่ยนไปยังไงต้องเปิดทีละใบแล้วจำเอา — หน้านี้เอาทุกใบของคนเดียวมาเรียงข้างกัน
 * เห็นทันทีว่าข้อไหนคำตอบขยับไปทางไหนระหว่างรอบ
 *
 * ใช้ได้เพราะแบบติดตามสุขภาพเป็นชุดคำถามชุดเดิมทุกรอบ (เงื่อนไขของการเทียบก่อน–หลัง)
 * ถ้าคนหนึ่งเคยตอบมากกว่าหนึ่งแบบประเมิน จะแยกเป็นตารางละแบบ ไม่ปนกัน
 */
class EvaluationPersonResultController extends Controller
{
    public function __construct(private readonly SurveyTrendService $trend)
    {
    }

    public function index(): View
    {
        $responses = SurveyResponse::query()
            ->with(['participant:id,person_code', 'form:id,name', 'cohortRound:id,name'])
            ->get();

        /* หนึ่งแถวต่อคน ไม่ใช่ต่อใบ — หน้านี้มีไว้เลือก "คน" ก่อนแล้วค่อยไปดูทุกรอบของเขา
           ไม่ส่งชื่อลงหน้าเลย (คำสั่งทีม) — กลุ่มตัวอย่างเป็นข้อมูลนิรนาม อ้างด้วยรหัสบุคคล */
        $people = $responses
            ->filter(fn (SurveyResponse $r) => $r->participant !== null)
            ->groupBy('participant_id')
            ->map(function (Collection $group) {
                $latest = $group->sortByDesc(fn (SurveyResponse $r) => $r->submitted_at?->timestamp ?? 0)->first();

                return [
                    'id' => $latest->participant->id,
                    'pid' => $latest->participant->person_code ?? '—',
                    'forms' => $group->pluck('form.name')->filter()->unique()->implode(' · '),
                    'rounds' => $group->count(),
                    'latestRound' => $latest->cohortRound?->name ?? 'ไม่ระบุรอบ',
                    'latestAt' => $latest->submitted_at?->toDateTimeString(),
                ];
            })
            ->sortByDesc(fn (array $p) => $p['latestAt'] ?? '')
            ->values();

        return view('admin.evaluations.person-results.index', ['people' => $people]);
    }

    public function show(int $participantId): View
    {
        $participant = Participant::find($participantId);

        abort_if($participant === null, 404);

        $responses = SurveyResponse::query()
            ->where('participant_id', $participant->id)
            ->with(['form:id,name', 'cohortRound:id,name,offset_days,due_date'])
            ->get()
            /* คอลัมน์เรียงตามลำดับรอบติดตามของคนนั้น (3 เดือน → 6 เดือน → …)
               ไม่ใช่ตามวันที่กดส่ง — คนตอบช้าข้ามรอบกันได้ แต่ลำดับรอบคือแกนของการเทียบ */
            ->sortBy([
                fn (SurveyResponse $a, SurveyResponse $b) => ($a->cohortRound?->offset_days ?? PHP_INT_MAX)
                    <=> ($b->cohortRound?->offset_days ?? PHP_INT_MAX),
                fn (SurveyResponse $a, SurveyResponse $b) => ($a->submitted_at?->timestamp ?? 0)
                    <=> ($b->submitted_at?->timestamp ?? 0),
            ])
            ->values();

        $answersByResponse = Answer::query()
            ->where('response_type', 'survey')
            ->whereIn('response_id', $responses->pluck('id'))
            ->with('option')
            ->get()
            ->groupBy('response_id');

        /* ตารางละแบบประเมิน — ชุดคำถามคนละชุดเอามาเรียงในตารางเดียวกันไม่ได้ */
        $matrices = $responses
            ->filter(fn (SurveyResponse $r) => $r->form_id !== null)
            ->groupBy('form_id')
            ->map(fn (Collection $group) => $this->buildMatrix($group, $answersByResponse))
            ->values();

        return view('admin.evaluations.person-results.show', [
            'participant' => $participant,
            'matrices' => $matrices,
            'totalRounds' => $responses->count(),
        ]);
    }

    /**
     * ตารางของแบบประเมินหนึ่งชุด
     *
     * @param  Collection<int, SurveyResponse>  $responses  ใบตอบของแบบนี้ เรียงตามรอบแล้ว
     * @param  Collection<int|string, Collection<int, Answer>>  $answersByResponse
     * @return array<string, mixed>
     */
    private function buildMatrix(Collection $responses, Collection $answersByResponse): array
    {
        $questions = Question::query()
            ->where('form_id', $responses->first()->form_id)
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        $columns = $responses->map(fn (SurveyResponse $r) => [
            'round' => $r->cohortRound?->name ?? 'ไม่ระบุรอบ',
            'at' => $r->submitted_at?->toDateString(),
        ])->values()->all();

        $rows = [];

        foreach ($questions as $question) {
            /* หัวหมวด — เป็นแถวคั่น ไม่มีคำตอบของตัวเอง */
            if ($question->question_type === 'section') {
                $rows[] = ['type' => 'section', 'text' => $question->text];

                continue;
            }

            $direction = $this->trend->scaleDirection($question);
            $cells = [];
            $previousPos = null;

            foreach ($responses as $response) {
                $answer = $answersByResponse->get($response->id)?->firstWhere('question_id', $question->id);
                $cell = $this->cell($question, $answer);

                /* แนวโน้มเทียบกับรอบก่อนหน้าที่มีคำตอบ ไม่ใช่คอลัมน์ติดกันเฉย ๆ
                   บางรอบอาจไม่ได้ตอบข้อนี้ การเทียบข้ามช่องว่างยังบอกทิศได้อยู่ */
                if ($cell['pos'] !== null && $previousPos !== null) {
                    $cell['trend'] = $this->trend->verdict($cell['pos'] - $previousPos, $direction);
                }

                if ($cell['pos'] !== null) {
                    $previousPos = $cell['pos'];
                }

                $cells[] = $cell;
            }

            $rows[] = [
                'type' => 'question',
                'text' => $question->text,
                'cells' => $cells,
                'overall' => $this->overall($cells, $direction),
            ];
        }

        return [
            'form' => $responses->first()->form?->name ?? 'ไม่ระบุแบบประเมิน',
            'columns' => $columns,
            'rows' => $this->attachSectionSummaries($rows),
            'summary' => $this->tally($rows),
        ];
    }

    /**
     * ผลรวมของหนึ่งข้อ: รอบแรกที่ตอบ → รอบล่าสุดที่ตอบ
     *
     * เทียบหัวกับท้าย ไม่ใช่ทีละรอบ — คำถามคือ "โดยรวมเขาขยับไปทางไหน"
     * ขึ้น ๆ ลง ๆ ระหว่างทางดูได้จากลูกศรรายช่องอยู่แล้ว
     *
     * @param  array<int, array{pos: int|null}>  $cells
     * @return 'up'|'down'|'same'|'changed'|null  null = ตอบไม่ถึงสองรอบ เทียบไม่ได้
     */
    private function overall(array $cells, int $direction): ?string
    {
        $answered = array_values(array_filter($cells, fn (array $c) => $c['pos'] !== null));

        if (count($answered) < 2) {
            return null;
        }

        return $this->trend->verdict(end($answered)['pos'] - $answered[0]['pos'], $direction);
    }

    /**
     * นับผลรวมของข้อชุดหนึ่งเป็น ดีขึ้น/คงเดิม/ลดลง/บอกทิศไม่ได้
     *
     * นับเป็น "จำนวนข้อ" ไม่ใช่คะแนนรวม — ตัวเลือกของแบบประเมินไม่มีคะแนนตัวเลขกำกับ
     * การตีคะแนนให้เองคือการแต่งตัวเลขที่แบบประเมินไม่ได้ออกแบบไว้
     *
     * @param  array<int, array<string, mixed>>  $rows  เฉพาะ type=question ถูกนับ
     * @return array{up: int, down: int, same: int, changed: int}
     */
    private function tally(array $rows): array
    {
        $summary = ['up' => 0, 'down' => 0, 'same' => 0, 'changed' => 0];

        foreach ($rows as $row) {
            if (($row['type'] ?? null) === 'question' && ($row['overall'] ?? null) !== null) {
                $summary[$row['overall']]++;
            }
        }

        return $summary;
    }

    /**
     * เติมสรุปของแต่ละหมวดลงในแถวหัวหมวด — หมวดคือ "ด้าน" ที่ผู้ใช้อยากรู้ว่าดีขึ้นหรือแย่ลง
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function attachSectionSummaries(array $rows): array
    {
        foreach ($rows as $i => $row) {
            if ($row['type'] !== 'section') {
                continue;
            }

            $questions = [];

            for ($j = $i + 1; $j < count($rows) && $rows[$j]['type'] === 'question'; $j++) {
                $questions[] = $rows[$j];
            }

            $rows[$i]['summary'] = $this->tally($questions);
        }

        return $rows;
    }

    /**
     * ค่าในหนึ่งช่อง — ป้ายคำตอบ กับตำแหน่งบนสเกล (ถ้าข้อนั้นเป็นสเกล)
     *
     * @return array{label: string, pos: int|null, max: int|null, trend: string|null}
     */
    private function cell(Question $question, ?Answer $answer): array
    {
        if ($answer === null) {
            return ['label' => '—', 'pos' => null, 'max' => null, 'trend' => null];
        }

        $pos = $this->trend->position($answer);

        if ($answer->option !== null) {
            return [
                'label' => $answer->option->label,
                'pos' => $pos,
                'max' => $question->options->count(),
                'trend' => null,
            ];
        }

        if ($answer->score !== null) {
            return ['label' => (string) $answer->score, 'pos' => $pos, 'max' => null, 'trend' => null];
        }

        return ['label' => $answer->text_value ?: '—', 'pos' => null, 'max' => null, 'trend' => null];
    }
}
