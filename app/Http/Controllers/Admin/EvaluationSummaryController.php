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
 * สรุปผลแบบประเมิน — รายงานวิจัยรายแบบประเมิน หัวข้อละการ์ด ไม่พับซ่อน
 *
 * ต่อแบบประเมินหนึ่งชุดให้ครบเครื่องมือมาตรฐานของบทที่ 4:
 *   1. ข้อมูลทั่วไปของกลุ่มตัวอย่าง (โดนัทเพศ/ช่วงอายุ + ตารางจำนวนและร้อยละ)
 *   2. ตารางแจกแจงความถี่รายข้อ n (%) แยกตามรอบ
 *   3. ตารางค่าเฉลี่ยและส่วนเบี่ยงเบนมาตรฐาน x̄ (S.D.) แยกตามรอบ
 *   4. กราฟการเปลี่ยนแปลงสุทธิ (% รอบล่าสุด − % รอบแรก) พร้อมประโยคสรุปผล
 *
 * ค่าเฉลี่ยคิดจากตำแหน่งของตัวเลือกบนสเกล (1..k) เพราะแบบประเมินไม่มีคะแนนตัวเลข
 * กำกับตัวเลือกไว้ — เป็น proxy ที่ต้องบอกไว้ตรง ๆ ในหน้ารายงาน ไม่ใช่แอบทำเนียน
 *
 * ภาพรวมระดับโครงการ (โดนัทกลุ่มเป้าหมาย · การตอบรายรอบ · ก่อน–หลัง)
 * อยู่ที่เมนู "ผลการวิเคราะห์" — หน้านี้เจาะรายแบบประเมินอย่างเดียว
 */
class EvaluationSummaryController extends Controller
{
    public function __construct(private readonly SurveyTrendService $trend)
    {
    }

    public function index(): View
    {
        $responses = SurveyResponse::query()
            ->with([
                'form:id,name',
                'cohortRound:id,name,offset_days',
                'participant:id,gender,age_range_id,area_id,target_group_id',
                'participant.targetGroup:id,name',
                'participant.area:id,name',
                'participant.ageRange:id,label',
            ])
            ->whereNotNull('participant_id')
            ->whereNotNull('form_id')
            ->get();

        $answers = Answer::query()
            ->where('response_type', 'survey')
            ->whereIn('response_id', $responses->pluck('id'))
            ->with('option')
            ->get()
            ->groupBy('response_id');

        $reports = $responses->groupBy('form_id')
            ->map(fn (Collection $group) => $this->buildReport($group, $answers))
            ->values();

        return view('admin.evaluations.summary', ['reports' => $reports]);
    }

    /**
     * รายงานของแบบประเมินหนึ่งชุด
     *
     * @param  Collection<int, SurveyResponse>  $responses
     * @param  Collection<int|string, Collection<int, Answer>>  $answersByResponse
     * @return array<string, mixed>
     */
    private function buildReport(Collection $responses, Collection $answersByResponse): array
    {
        /* คอลัมน์รอบ — รอบที่มีคนตอบจริง เรียงตามลำดับรอบ พร้อม n ของรอบนั้น
           รอบชื่อเดียวกันของหลายคน (ทุกคนมี "ก่อนเข้าร่วม" ของตัวเอง) ถูกรวมเป็นคอลัมน์เดียว */
        $rounds = $responses
            ->filter(fn (SurveyResponse $r) => $r->cohortRound !== null)
            ->groupBy(fn (SurveyResponse $r) => $r->cohortRound->name)
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'offset' => $group->min(fn (SurveyResponse $r) => $r->cohortRound->offset_days ?? PHP_INT_MAX),
                'n' => $group->count(),
            ])
            ->sortBy('offset')
            ->values()
            ->all();

        $roundNames = array_column($rounds, 'name');

        /* นับคำตอบและเก็บตำแหน่งบนสเกลไว้พร้อมกันในรอบเดียว
           tallies: question → label → round → จำนวนคน (ตารางความถี่และกราฟ)
           positions: question → round → [ตำแหน่ง] (ตาราง x̄, S.D.) */
        $tallies = [];
        $positions = [];

        foreach ($responses as $response) {
            $roundName = $response->cohortRound?->name;

            if ($roundName === null) {
                continue;
            }

            foreach ($answersByResponse->get($response->id) ?? [] as $answer) {
                $label = $answer->option?->label
                    ?? ($answer->score !== null ? (string) $answer->score : null);

                if ($label === null) {
                    continue;
                }

                $tallies[$answer->question_id][$label][$roundName]
                    = ($tallies[$answer->question_id][$label][$roundName] ?? 0) + 1;

                $position = $this->trend->position($answer);

                if ($position !== null) {
                    $positions[$answer->question_id][$roundName][] = $position;
                }
            }
        }

        $questions = Question::query()
            ->where('form_id', $responses->first()->form_id)
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        $sections = [];
        $current = null;

        foreach ($questions as $question) {
            if ($question->question_type === 'section') {
                if ($current !== null) {
                    $sections[] = $current;
                }
                $current = ['name' => $question->text, 'questions' => []];

                continue;
            }

            /* ข้อความอิสระ (ข้อเสนอแนะ) แจกแจงเป็นความถี่ไม่ได้ ไม่อยู่ในรายงาน */
            if (! in_array($question->question_type, ['single', 'rating'], true)) {
                continue;
            }

            $answerRows = $this->answerRows($question, $tallies[$question->id] ?? [], $roundNames);

            $row = [
                'text' => $question->text,
                'answers' => $answerRows,
                /* ฐานร้อยละของแต่ละรอบ = จำนวนคนที่ตอบ "ข้อนี้" ในรอบนั้นจริง
                   ไม่ใช่ n ของรอบ — คนข้ามข้อได้ ฐานสองแบบนี้จึงไม่จำเป็นต้องเท่ากัน */
                'answerTotals' => array_map(
                    fn (int $i) => array_sum(array_column(array_column($answerRows, 'counts'), $i)),
                    array_keys($roundNames)
                ),
                'scaleMax' => $question->options->count(),
                'stats' => $this->statsRow($positions[$question->id] ?? [], $roundNames),
                'tones' => $this->tones($question),
            ];

            $row['net'] = $this->netChanges($row['answers'], $row['answerTotals'], $roundNames);

            $current ??= ['name' => null, 'questions' => []];
            $current['questions'][] = $row;
        }

        if ($current !== null && $current['questions'] !== []) {
            $sections[] = $current;
        }

        $demographics = $this->demographics(
            $responses->pluck('participant')->filter()->unique('id')->values()
        );

        return [
            'form' => $responses->first()->form?->name ?? 'ไม่ระบุแบบประเมิน',
            'people' => $responses->groupBy('participant_id')->count(),
            'rounds' => $rounds,
            'sections' => $sections,
            'demographics' => $demographics,
        ];
    }

    /**
     * ตารางแจกแจงของหนึ่งข้อ: ตัวเลือกเป็นแถว รอบเป็นคอลัมน์ ช่องคือจำนวนคนที่ตอบ
     *
     * แถวเรียงตามลำดับตัวเลือกบนสเกล และแสดงครบทุกตัวเลือกแม้ไม่มีใครเลือกเลย —
     * "ไม่มีใครตอบ ไม่รู้ เลยสักรอบ" คือข้อมูล ไม่ใช่แถวที่ควรหาย
     *
     * @param  array<string, array<string, int>>  $tally  label → round → count
     * @param  array<int, string>  $rounds
     * @return array<int, array{label: string, counts: array<int, int>}>
     */
    private function answerRows(Question $question, array $tally, array $rounds): array
    {
        $labels = $question->options->isNotEmpty()
            ? $question->options->pluck('label')->all()
            /* ข้อแบบให้คะแนน (rating) ไม่มีรายการตัวเลือก — ใช้ค่าที่ถูกตอบจริง เรียงจากน้อยไปมาก */
            : collect(array_keys($tally))->sort(SORT_NATURAL)->values()->all();

        return array_map(fn (string $label) => [
            'label' => $label,
            'counts' => array_map(fn (string $round) => $tally[$label][$round] ?? 0, $rounds),
        ], $labels);
    }

    /**
     * ค่าเฉลี่ยและส่วนเบี่ยงเบนมาตรฐานรายรอบของหนึ่งข้อ — จากตำแหน่งบนสเกล (1..k)
     *
     * S.D. แบบกลุ่มตัวอย่าง (หาร n-1) ตามธรรมเนียมงานวิจัย · ตอบคนเดียวคิด S.D. ไม่ได้ (null)
     *
     * @param  array<string, array<int, int>>  $byRound  round → [ตำแหน่ง]
     * @param  array<int, string>  $rounds
     * @return array<int, array{n: int, mean: float|null, sd: float|null}>
     */
    private function statsRow(array $byRound, array $rounds): array
    {
        return array_map(function (string $round) use ($byRound) {
            $values = $byRound[$round] ?? [];
            $n = count($values);

            if ($n === 0) {
                return ['n' => 0, 'mean' => null, 'sd' => null];
            }

            $mean = array_sum($values) / $n;
            $sd = $n < 2 ? null : sqrt(
                array_sum(array_map(fn (int $v) => ($v - $mean) ** 2, $values)) / ($n - 1)
            );

            return ['n' => $n, 'mean' => $mean, 'sd' => $sd];
        }, $rounds);
    }

    /**
     * ระดับสีของแต่ละตัวเลือก สำหรับกราฟแท่งซ้อน: 0 = แย่สุด (แดง) … 4 = ดีสุด (เขียวเข้ม)
     *
     * เรียงสีตามความหมาย ไม่ใช่ตามลำดับตัวเลือก — สเกล "รู้/ไม่รู้" ตัวเลือกแรกคือฝั่งดี
     * ทิศอ่านจาก SurveyTrendService กติกาเดียวกับทุกหน้า · ข้อที่บอกทิศไม่ได้คืน null
     * ให้กราฟใช้โทนกลาง ไม่แสร้งบอกว่าอะไรดีอะไรแย่
     *
     * @return array<string, int>|null  label → 0..4
     */
    private function tones(Question $question): ?array
    {
        $direction = $this->trend->scaleDirection($question);
        $count = $question->options->count();

        if ($direction === 0 || $count < 2) {
            return null;
        }

        $tones = [];

        foreach ($question->options->values() as $i => $option) {
            /* ตำแหน่งเชิงความหมาย 0=แย่สุด แล้วยืดลงพาเลตต์ 5 สี */
            $goodness = $direction === 1 ? $i : ($count - 1 - $i);
            $tones[$option->label] = (int) round($goodness * 4 / ($count - 1));
        }

        return $tones;
    }

    /**
     * การเปลี่ยนแปลงสุทธิของแต่ละระดับคำตอบ: % รอบล่าสุด − % รอบแรก (หน่วยเป็นจุด)
     *
     * "รอบแรก/รอบล่าสุด" คือรอบแรกและรอบสุดท้ายที่มีคนตอบข้อนี้จริง ไม่ใช่คอลัมน์แรก/ท้าย
     * ของตาราง — บางข้อเพิ่งถูกเพิ่มเข้าแบบประเมินภายหลัง รอบต้น ๆ จึงว่าง
     *
     * @param  array<int, array{label: string, counts: array<int, int>}>  $answerRows
     * @param  array<int, int>  $totals
     * @param  array<int, string>  $roundNames
     * @return array{before: string, after: string, max: float, rows: array<int, array{label: string, change: float}>}|null
     */
    private function netChanges(array $answerRows, array $totals, array $roundNames): ?array
    {
        $indexes = array_keys(array_filter($totals, fn (int $total) => $total > 0));

        if (count($indexes) < 2) {
            return null;
        }

        $first = $indexes[0];
        $last = end($indexes);

        $rows = array_map(fn (array $answer) => [
            'label' => $answer['label'],
            'change' => ($answer['counts'][$last] / $totals[$last] * 100)
                - ($answer['counts'][$first] / $totals[$first] * 100),
        ], $answerRows);

        return [
            'before' => $roundNames[$first],
            'after' => $roundNames[$last],
            /* สเกลความสูงแท่ง — เทียบกับค่าสัมบูรณ์ที่มากที่สุดของข้อนั้น */
            'max' => max(1.0, ...array_map(fn (array $row) => abs($row['change']), $rows)),
            'rows' => $rows,
        ];
    }

    /**
     * ตารางข้อมูลทั่วไปของกลุ่มตัวอย่าง — จำนวนและร้อยละของผู้ตอบแบบประเมินชุดนี้
     *
     * ฐานคือ "ผู้ตอบ" ไม่ใช่กลุ่มตัวอย่างทั้งหมด เพราะตารางนี้อธิบาย sample ของรายงาน
     * ค่าที่ไม่ได้กรอกรวมเป็น "ไม่ระบุ" — ตัดทิ้งเมื่อไรร้อยละจะรวมไม่ถึงร้อยแบบไม่มีคำอธิบาย
     *
     * @param  Collection<int, Participant>  $participants
     * @return array<int, array{name: string, rows: array<int, array{label: string, n: int, pct: float}>}>
     */
    private function demographics(Collection $participants): array
    {
        $total = $participants->count();

        if ($total === 0) {
            return [];
        }

        /* ค่าในฐานเป็นรหัสอังกฤษ (มาจากฟอร์มลงทะเบียน) — ตารางรายงานต้องเป็นคำไทย */
        $genderLabels = ['female' => 'หญิง', 'male' => 'ชาย', 'other' => 'อื่น ๆ'];

        $traits = [
            'เพศ' => fn (Participant $p) => filled($p->gender)
                ? ($genderLabels[$p->gender] ?? $p->gender)
                : 'ไม่ระบุ',
            'ช่วงอายุ' => fn (Participant $p) => $p->ageRange?->label ?? 'ไม่ระบุ',
            'กลุ่มเป้าหมาย' => fn (Participant $p) => $p->targetGroup?->name ?? 'ไม่ระบุ',
            'พื้นที่' => fn (Participant $p) => $p->area?->name ?? 'ไม่ระบุ',
        ];

        return collect($traits)
            ->map(fn (callable $trait, string $name) => [
                'name' => $name,
                'rows' => $participants->groupBy($trait)
                    ->map(fn (Collection $group, string $label) => [
                        'label' => $label,
                        'n' => $group->count(),
                        'pct' => $group->count() / $total * 100,
                    ])
                    ->sortByDesc('n')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
