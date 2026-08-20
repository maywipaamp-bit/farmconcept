<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\SurveyResponse;
use App\Support\ChartMath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * รายงานสุขภาพกลุ่มตัวอย่าง (admin/reports/cohort-health)
 *
 * ต่างจากรายงานความพึงพอใจตรงที่ติดตาม "คนเดิม" ข้ามหลายรอบเวลา
 * คำถามที่ต้องตอบคือคนนี้ดีขึ้นหรือแย่ลงหลังเข้าร่วมโครงการ ไม่ใช่กิจกรรมไหนคะแนนดี
 *
 * คะแนนสุขภาพของแต่ละรอบคิดเป็น 0–100 จากตำแหน่งคำตอบบนสเกลของข้อนั้น
 * ใช้ SurveyTrendService ตัดสินทิศของสเกล (บางข้อท้ายสเกลคือดี บางข้อท้ายสเกลคือแย่)
 * เกณฑ์เดียวกับแดชบอร์ดภาพรวมและหน้าผลตอบรายคน ตัวเลขสองหน้าจึงตรงกันเสมอ
 */
class CohortHealthReportService
{
    /** เกณฑ์ระดับความเสี่ยงจากคะแนนรอบล่าสุด (0–100) */
    private const RISK_WATCH_BELOW = 70.0;

    private const RISK_URGENT_BELOW = 50.0;

    /** ผลต่างที่ยังถือว่า "คงที่" — น้อยกว่านี้เป็นความแกว่งของการตอบ ไม่ใช่การเปลี่ยนแปลงจริง */
    private const STABLE_BAND = 3.0;

    public function __construct(private readonly SurveyTrendService $trend) {}

    /**
     * @param  array{from?: ?string, to?: ?string, area?: ?string, status?: ?string}  $filters
     */
    public function report(array $filters = []): array
    {
        $profiles = $this->profiles($filters);
        $scores = $this->scoresByParticipant($profiles->pluck('participant_id')->all());

        $people = $profiles->map(function (object $profile) use ($scores) {
            $rounds = $scores->get($profile->participant_id, collect());

            return $this->person($profile, $rounds);
        });

        /* กรองสถานะการติดตามหลังคำนวณ เพราะสถานะมาจากรอบที่ครบกำหนดเทียบกับรอบที่ตอบแล้ว
           ซึ่งรู้ได้ต่อเมื่อรวมข้อมูลของคนนั้นครบแล้วเท่านั้น */
        $status = $filters['status'] ?? '';

        if (in_array($status, ['complete', 'pending'], true)) {
            $people = $people->filter(fn (array $p) => $p['followUpStatus'] === $status);
        }

        $people = $people->sortBy([
            /* คนที่ต้องตามด่วนอยู่บนสุด แล้วค่อยไล่ตามคะแนนล่าสุดจากน้อยไปมาก */
            fn (array $a, array $b) => $this->riskWeight($b['risk']) <=> $this->riskWeight($a['risk']),
            fn (array $a, array $b) => ($a['latestScore'] ?? 101) <=> ($b['latestScore'] ?? 101),
        ])->values();

        return [
            'filters' => $this->filterOptions($filters),
            'summary' => $this->summary($people),
            'beforeAfter' => $this->beforeAfterChart($people),
            'riskDonut' => $this->riskDonut($people),
            'people' => $people->all(),
        ];
    }

    /**
     * แนวโน้มรายบุคคล — กราฟเส้นของคนเดียวข้ามทุกรอบที่เขาตอบ
     *
     * แยกเมธอดออกมาเพราะหน้าจอโหลดเมื่อผู้ใช้เลือกชื่อ ไม่ใช่คำนวณของทุกคนทิ้งไว้
     * กลุ่มตัวอย่างหลักร้อยคน × หลายรอบ ถ้าคำนวณล่วงหน้าทั้งหมดจะเปลืองโดยไม่มีใครดู
     */
    public function personTrend(int $participantId): ?array
    {
        $profile = $this->profiles([])->firstWhere('participant_id', $participantId);

        if ($profile === null) {
            return null;
        }

        $rounds = $this->scoresByParticipant([$participantId])->get($participantId, collect());

        if ($rounds->isEmpty()) {
            return null;
        }

        return [
            'personCode' => $profile->person_code ?? '—',
            'chart' => ChartMath::trendLine(
                $rounds->pluck('roundName')->all(),
                [[
                    'key' => 'score',
                    'label' => 'คะแนนสุขภาพ',
                    'values' => $rounds->pluck('score')->all(),
                ]],
                fixedMax: 100.0,
            ),
            'rounds' => $rounds->values()->all(),
        ];
    }

    /* ================================================================== */

    /** @return Collection<int, object> */
    private function profiles(array $filters): Collection
    {
        $query = DB::table('ptp_cohort_profiles as c')
            ->join('ptp_participants as p', 'p.id', '=', 'c.participant_id')
            ->leftJoin('mst_areas as a', 'a.id', '=', 'p.area_id')
            ->whereNull('c.stopped_at')
            ->whereNull('p.deleted_at');

        if (! empty($filters['from'])) {
            $query->whereDate('c.entry_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('c.entry_date', '<=', $filters['to']);
        }

        if (! empty($filters['area'])) {
            $query->where('a.id', $filters['area']);
        }

        return $query
            ->orderBy('c.entry_date')
            ->get([
                'c.id as profile_id', 'c.participant_id', 'c.cohort_code', 'c.entry_date',
                'p.person_code', 'p.area_id', 'a.name as area_name',
            ]);
    }

    /**
     * คะแนนรายรอบของแต่ละคน เรียงตามลำดับรอบติดตาม (ก่อนเข้าร่วม → 3 เดือน → …)
     *
     * @param  array<int, int>  $participantIds
     * @return Collection<int, Collection<int, array<string, mixed>>>
     */
    private function scoresByParticipant(array $participantIds): Collection
    {
        if ($participantIds === []) {
            return collect();
        }

        $responses = SurveyResponse::query()
            ->whereIn('participant_id', $participantIds)
            ->with('cohortRound:id,name,offset_days,due_date')
            ->get(['id', 'participant_id', 'form_id', 'cohort_round_id', 'submitted_at']);

        if ($responses->isEmpty()) {
            return collect();
        }

        $questions = Question::query()
            ->whereIn('form_id', $responses->pluck('form_id')->filter()->unique())
            ->with('options')
            ->get()
            ->keyBy('id');

        $answers = Answer::query()
            ->where('response_type', 'survey')
            ->whereIn('response_id', $responses->pluck('id'))
            ->get(['id', 'response_id', 'question_id', 'option_id', 'score'])
            ->groupBy('response_id');

        $scoreMax = (int) config('farmconcept.assessment_score_max');

        return $responses
            ->groupBy('participant_id')
            ->map(function (Collection $group) use ($answers, $questions, $scoreMax) {
                return $group
                    ->sortBy([
                        fn (SurveyResponse $a, SurveyResponse $b) => ($a->cohortRound?->offset_days ?? PHP_INT_MAX)
                            <=> ($b->cohortRound?->offset_days ?? PHP_INT_MAX),
                        fn (SurveyResponse $a, SurveyResponse $b) => ($a->submitted_at?->timestamp ?? 0)
                            <=> ($b->submitted_at?->timestamp ?? 0),
                    ])
                    ->map(function (SurveyResponse $response) use ($answers, $questions, $scoreMax) {
                        $score = $this->responseScore($answers->get($response->id, collect()), $questions, $scoreMax);

                        return [
                            'roundName' => $response->cohortRound?->name ?? 'ไม่ระบุรอบ',
                            'offsetDays' => $response->cohortRound?->offset_days,
                            'score' => $score,
                            'submittedAt' => $response->submitted_at,
                        ];
                    })
                    /* ใบที่คิดคะแนนไม่ได้เลย (ตอบแต่ข้อความล้วน) ไม่ควรกลายเป็นจุด 0 บนกราฟ */
                    ->filter(fn (array $row) => $row['score'] !== null)
                    ->values();
            });
    }

    /**
     * คะแนนรวมของใบตอบหนึ่งใบ เป็น 0–100
     *
     * @param  Collection<int, Answer>  $rows
     * @param  Collection<int, Question>  $questions
     */
    private function responseScore(Collection $rows, Collection $questions, int $scoreMax): ?float
    {
        $values = $rows
            ->map(function (Answer $answer) use ($questions, $scoreMax) {
                $question = $questions->get($answer->question_id);

                return $question === null ? null : $this->normalized($question, $answer, $scoreMax);
            })
            ->filter(fn (?float $v) => $v !== null);

        return $values->isEmpty() ? null : round($values->avg() * 100, 1);
    }

    /** ตำแหน่งคำตอบบนสเกลของข้อนั้น เป็น 0–1 (1 = ดีที่สุด) — สูตรเดียวกับ DashboardService */
    private function normalized(Question $question, Answer $answer, int $scoreMax): ?float
    {
        if ($answer->score !== null) {
            return (((float) $answer->score) - 1) / max($scoreMax - 1, 1);
        }

        if ($answer->option_id === null) {
            return null;
        }

        $option = $question->options->firstWhere('id', (int) $answer->option_id);
        $max = $question->options->count();
        $direction = $this->trend->scaleDirection($question);

        if ($option === null || $max < 2 || $direction === 0) {
            return null;
        }

        $position = (int) $option->sort_order;

        return $direction === 1
            ? ($position - 1) / ($max - 1)
            : ($max - $position) / ($max - 1);
    }

    /** @param  Collection<int, array<string, mixed>>  $rounds */
    private function person(object $profile, Collection $rounds): array
    {
        $first = $rounds->first();
        $latest = $rounds->last();
        $previous = $rounds->count() >= 2 ? $rounds[$rounds->count() - 2] : null;

        $latestScore = $latest['score'] ?? null;
        $change = ($latestScore !== null && $previous !== null) ? $latestScore - $previous['score'] : null;

        /* รอบที่ถึงกำหนดแล้ว = ต้องตอบแล้ว · ยังไม่ถึงกำหนดยังไม่นับว่าค้าง */
        $dueRounds = DB::table('ptp_follow_up_rounds')
            ->where('cohort_profile_id', $profile->profile_id)
            ->whereDate('due_date', '<=', Carbon::today())
            ->get(['id', 'name', 'answered_at']);

        $missing = $dueRounds->whereNull('answered_at')->count();

        return [
            'participantId' => (int) $profile->participant_id,
            'personCode' => $profile->person_code ?? '—',
            'cohortCode' => $profile->cohort_code,
            'area' => $profile->area_name ?? 'ไม่ระบุพื้นที่',
            'entryDate' => $profile->entry_date,
            'roundsAnswered' => $rounds->count(),
            'roundsDue' => $dueRounds->count(),
            'roundsMissing' => $missing,
            'followUpStatus' => $missing === 0 ? 'complete' : 'pending',
            'latestRound' => $latest['roundName'] ?? null,
            'latestScore' => $latestScore,
            'firstScore' => $first['score'] ?? null,
            'change' => $change === null ? null : round($change, 1),
            'direction' => $this->direction($change),
            'risk' => $this->risk($latestScore),
        ];
    }

    /** ดีขึ้น / คงที่ / แย่ลง — เทียบรอบล่าสุดกับรอบก่อนหน้า */
    private function direction(?float $change): string
    {
        if ($change === null) {
            return 'unknown';
        }

        if (abs($change) < self::STABLE_BAND) {
            return 'same';
        }

        return $change > 0 ? 'up' : 'down';
    }

    private function risk(?float $score): string
    {
        if ($score === null) {
            return 'unknown';
        }

        if ($score < self::RISK_URGENT_BELOW) {
            return 'urgent';
        }

        return $score < self::RISK_WATCH_BELOW ? 'watch' : 'normal';
    }

    private function riskWeight(string $risk): int
    {
        return ['urgent' => 3, 'watch' => 2, 'normal' => 1, 'unknown' => 0][$risk] ?? 0;
    }

    /** @param  Collection<int, array<string, mixed>>  $people */
    private function summary(Collection $people): array
    {
        $total = $people->count();
        $withTrend = $people->filter(fn (array $p) => $p['direction'] !== 'unknown');
        $trendBase = max(1, $withTrend->count());

        return [
            'total' => $total,
            'complete' => $people->where('followUpStatus', 'complete')->count(),
            'pending' => $people->where('followUpStatus', 'pending')->count(),
            'missingRounds' => $people->sum('roundsMissing'),
            'trendBase' => $withTrend->count(),
            'up' => $withTrend->where('direction', 'up')->count(),
            'upPct' => (int) round($withTrend->where('direction', 'up')->count() / $trendBase * 100),
            'same' => $withTrend->where('direction', 'same')->count(),
            'samePct' => (int) round($withTrend->where('direction', 'same')->count() / $trendBase * 100),
            'down' => $withTrend->where('direction', 'down')->count(),
            'downPct' => (int) round($withTrend->where('direction', 'down')->count() / $trendBase * 100),
        ];
    }

    /**
     * ค่าเฉลี่ยคะแนนรอบแรก (ก่อนเข้าร่วม) เทียบรอบล่าสุด — ระดับกลุ่ม ไม่ใช่รายคน
     *
     * @param  Collection<int, array<string, mixed>>  $people
     */
    private function beforeAfterChart(Collection $people): array
    {
        $withBoth = $people->filter(fn (array $p) => $p['firstScore'] !== null && $p['latestScore'] !== null);

        if ($withBoth->isEmpty()) {
            return [];
        }

        $before = round($withBoth->avg('firstScore'), 1);
        $after = round($withBoth->avg('latestScore'), 1);

        /* ไม่ใช้ ChartMath::barList เพราะตัวนั้นเรียงจากมากไปน้อย ซึ่งจะสลับ "ก่อน" ไปอยู่หลัง "หลัง"
           เมื่อคะแนนดีขึ้น — กราฟก่อน/หลังต้องคงลำดับเวลาเสมอ ไม่งั้นอ่านกลับด้าน
           เทียบเป็น % ของ 100 ตรง ๆ เพราะคะแนนเป็นสเกล 0–100 อยู่แล้ว ไม่ต้องอิงค่าสูงสุดในชุด */
        return [
            ['label' => 'ก่อนเข้าร่วม', 'count' => $before, 'pct' => (int) round($before), 'tone' => 'mid'],
            ['label' => 'รอบล่าสุด', 'count' => $after, 'pct' => (int) round($after), 'tone' => $after >= $before ? 'good' : 'low'],
        ];
    }

    /** @param  Collection<int, array<string, mixed>>  $people */
    private function riskDonut(Collection $people): array
    {
        return ChartMath::donut([
            ['label' => 'ปกติ', 'count' => $people->where('risk', 'normal')->count(), 'tone' => 'success'],
            ['label' => 'เฝ้าระวัง', 'count' => $people->where('risk', 'watch')->count(), 'tone' => 'warning'],
            ['label' => 'ต้องติดตามด่วน', 'count' => $people->where('risk', 'urgent')->count(), 'tone' => 'danger'],
            ['label' => 'ยังไม่มีคะแนน', 'count' => $people->where('risk', 'unknown')->count(), 'tone' => 'muted'],
        ]);
    }

    private function filterOptions(array $filters): array
    {
        return [
            'from' => $filters['from'] ?? '',
            'to' => $filters['to'] ?? '',
            'area' => $filters['area'] ?? '',
            'status' => $filters['status'] ?? '',
            'areas' => DB::table('mst_areas')->orderBy('name')->get(['id', 'name'])->all(),
        ];
    }
}
