<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Form;
use App\Models\Question;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ประเมินกิจกรรม — ทุกการอ่านผลแบบประเมินหลังกิจกรรมผ่านไฟล์นี้ที่เดียว
 *
 * แทนที่ assets/js/satisfaction-service.js ซึ่งอ่าน mock-data.js อยู่ฝั่งเบราว์เซอร์
 * กฎที่ไฟล์นั้นรับผิดชอบถูกยกมาทั้งชุด และยังเป็นกฎเดียวกันที่นี่
 *
 * 1. คำตอบรายชุดคือแหล่งข้อมูลเดียว — คะแนนเฉลี่ย คะแนนรายข้อ การกระจายดาว
 *    และจำนวนผู้ตอบ derive จากชุดเดียวกันหมด ไม่มีค่าสรุปเก็บแยกไว้ให้ขัดกันเอง
 * 2. อัตราการตอบ = ผู้ตอบ ÷ ผู้เข้าร่วมจริง (เช็คอินแล้ว) ไม่ใช่ผู้ลงทะเบียน
 * 3. ทุกเมธอดรับ Activity เป็นพารามิเตอร์แรก ข้อมูลแต่ละกิจกรรมไม่ปนกัน
 * 4. ไม่มีข้อมูลที่ระบุตัวตนผู้ตอบออกจากไฟล์นี้ — evl_satisfaction_responses ไม่มี
 *    คอลัมน์ที่ชี้ไปยังคนอยู่แล้ว (ปม C) และห้ามเพิ่ม join ใดที่ทำให้ย้อนกลับไปหาคนได้
 *    ตัวระบุเดียวที่ออกไปคือ seq = ลำดับภายในกิจกรรมนั้น
 * 5. การแบ่งหน้าทำที่ฐานข้อมูล — responseList() คืนเฉพาะแถวของหน้านั้นพร้อม total
 *
 * seq ถูกคิดจากรายการเต็ม "ก่อน" กรอง (ROW_NUMBER เหนือชุดทั้งกิจกรรม)
 * ตัวเลขของแต่ละคำตอบจึงไม่ขยับตามแท็บช่วงคะแนนหรือหน้าที่เปิดอยู่
 */
class ActivitySatisfactionService
{
    /** ค่า response_type ของ evl_answers ที่ผูกกับ evl_satisfaction_responses (morph map) */
    private const ANSWER_TYPE = 'satisfaction';

    /** เกณฑ์แปลคะแนนเฉลี่ยเป็นคำ — เรียงจากสูงไปต่ำ ตัวแรกที่ผ่านคือคำตอบ */
    private const GRADES = [
        [4.5, 'ดีมาก', 'success'],
        [4.0, 'ดี', 'success'],
        [3.5, 'ปานกลาง', 'warning'],
        [0.0, 'ต้องปรับปรุง', 'danger'],
    ];

    /** อัตราการตอบต่ำกว่านี้ถือว่ากลุ่มตัวอย่างน้อยเกินกว่าจะเป็นตัวแทนได้ */
    private const REPRESENTATIVE_MIN = 70;

    /** คะแนนรายข้อต่ำกว่านี้ถือว่าเป็นจุดที่ต้องปรับปรุง (แถบเปลี่ยนสี) */
    private const TOPIC_WARN_BELOW = 4.0;

    /**
     * กิจกรรมที่หน้านี้เลือกดูได้ — ที่ผูกแบบประเมินหลังกิจกรรมไว้ หรือที่มีคำตอบแล้ว
     * กิจกรรมที่ถอดแบบประเมินออกแล้วแต่ยังมีคำตอบค้างอยู่ต้องเลือกดูได้ ไม่งั้นข้อมูลเดิมหายจากหน้าจอ
     *
     * @return Collection<int, Activity>
     */
    public function selectableActivities(): Collection
    {
        return Activity::query()
            ->where(fn ($query) => $query
                /* whereHas อยู่นอก relation จริง จึงอ้างคอลัมน์ pivot ด้วยชื่อตารางเต็ม
                   ไม่ใช่ wherePivot ซึ่งใช้ได้เฉพาะกับ query ของ relation เอง */
                ->whereHas('forms', fn ($form) => $form
                    ->where('evl_forms.type', Form::TYPE_POST_ACTIVITY)
                    ->where('evl_form_activity.slot', 'post_survey'))
                ->orWhereHas('satisfactionResponses'))
            ->withCount('satisfactionResponses')
            ->orderByDesc('satisfaction_responses_count')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * ตัวเลขสรุปของกิจกรรมเดียว — การ์ดด้านบน การกระจายดาว และคะแนนรายข้อ
     *
     * @return array<string, mixed>
     */
    public function summary(Activity $activity): array
    {
        $scores = $this->perResponseScores($activity);
        $rated = $scores->filter(fn (?float $value) => $value !== null)->values();

        $average = $rated->isEmpty() ? 0.0 : $rated->sum() / $rated->count();
        $attended = $activity->registrations()->checkedIn()->count();
        $responseCount = $scores->count();
        $responseRate = $attended > 0 ? (int) round($responseCount / $attended * 100) : null;

        /* ปัดคะแนนเฉลี่ยของแต่ละชุดเป็นจำนวนเต็ม 1–5 แล้วนับ — เกณฑ์เดียวกับ pill ในตาราง */
        $rounded = $rated->map(fn (float $value) => (int) round($value));

        return [
            'responseCount' => $responseCount,
            'commentCount' => $this->commentCount($activity),
            'attendedCount' => $attended,
            'responseRate' => $responseRate,
            'isRepresentative' => $responseRate !== null && $responseRate >= self::REPRESENTATIVE_MIN,
            'average' => $rated->isEmpty() ? null : round($average, 2),
            /* ยังไม่มีใครตอบ = ยังตัดเกรดไม่ได้ ไม่ใช่ "ต้องปรับปรุง" (คะแนน 0 มาจากการหารศูนย์) */
            'grade' => $rated->isEmpty()
                ? ['label' => 'ยังไม่มีข้อมูล', 'tone' => 'neutral']
                : $this->grade($average),
            'highRatioPercent' => $rated->isEmpty()
                ? 0
                : (int) round($rounded->filter(fn (int $star) => $star >= 4)->count() / $rated->count() * 100),
            'distribution' => collect([5, 4, 3, 2, 1])->map(function (int $star) use ($rounded) {
                $count = $rounded->filter(fn (int $value) => $value === $star)->count();

                return [
                    'star' => $star,
                    'count' => $count,
                    'percent' => $rounded->isEmpty() ? 0 : (int) round($count / $rounded->count() * 100),
                ];
            })->values()->all(),
            'topics' => $this->topics($activity),
        ];
    }

    /**
     * ตารางรายคำตอบ — แบ่งหน้าที่ฐานข้อมูล
     *
     * $filters: band (all|praise|mid|improve) · keyword · limit · offset
     * ไม่ส่ง limit = ขอทั้งชุดที่ตรงเงื่อนไข (ใช้ตอนส่งออก Excel)
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, rows: array<int, array<string, mixed>>}
     */
    public function responseList(Activity $activity, array $filters = []): array
    {
        $query = DB::query()->fromSub($this->numberedResponses($activity), 't');

        $this->applyBand($query, (string) ($filters['band'] ?? 'all'));
        $this->applyKeyword($query, trim((string) ($filters['keyword'] ?? '')));

        $total = (clone $query)->count();

        $limit = (int) ($filters['limit'] ?? 0);
        if ($limit > 0) {
            $query->limit($limit)->offset(max(0, (int) ($filters['offset'] ?? 0)));
        }

        $rows = $query->orderBy('seq')->get();
        $rounds = $this->roundLabels($activity);
        $comments = $this->commentsOf($rows->pluck('id')->all());

        return [
            'total' => $total,
            'rows' => $rows->map(fn (object $row) => [
                'seq' => (int) $row->seq,
                /* null = ตอบแบบประเมินแต่ไม่มีข้อให้คะแนนเลย ต่างจากคะแนน 0 ซึ่งเป็นไปไม่ได้ */
                'score' => $row->avg_score === null ? null : (int) round((float) $row->avg_score),
                'average' => $row->avg_score === null ? null : round((float) $row->avg_score, 1),
                'band' => $this->band($row->avg_score === null ? null : (float) $row->avg_score),
                'round' => $rounds[$row->activity_round_id] ?? $rounds[0],
                'comment' => $comments[$row->id] ?? '',
                'submittedAt' => (string) $row->submitted_at,
            ])->values()->all(),
        ];
    }

    /**
     * คำตอบหนึ่งชุดต่อหนึ่งแถว พร้อมลำดับและคะแนนเฉลี่ย
     *
     * ROW_NUMBER ทำงานหลัง GROUP BY จึงได้ลำดับของ "ชุดคำตอบ" ไม่ใช่ของแถวคำตอบรายข้อ
     * และคิดก่อนตัวกรองใด ๆ ในชั้นนอก ลำดับจึงคงที่ไม่ว่าจะกรองอย่างไร
     */
    private function numberedResponses(Activity $activity): Builder
    {
        return DB::table('evl_satisfaction_responses as r')
            ->leftJoin('evl_answers as a', fn ($join) => $join
                ->on('a.response_id', '=', 'r.id')
                ->where('a.response_type', '=', self::ANSWER_TYPE))
            ->where('r.activity_id', $activity->id)
            ->groupBy('r.id', 'r.submitted_at', 'r.activity_round_id')
            ->selectRaw('r.id, r.submitted_at, r.activity_round_id')
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY r.submitted_at DESC, r.id DESC) as seq')
            ->selectRaw('AVG(a.score) as avg_score');
    }

    /**
     * ความเห็นของคำตอบที่จะแสดงจริงเท่านั้น — คำถามปลายเปิดมีได้หลายข้อ
     * จึงต่อเป็นบรรทัดเดียวคั่นด้วย · ให้ตารางแสดงช่องเดียวได้
     *
     * ต่อใน PHP ไม่ใช่ GROUP_CONCAT เพราะไวยากรณ์ต่างกันในแต่ละ driver
     * และเป็นคำขอเดียวต่อหนึ่งหน้า ไม่ใช่หนึ่งคำขอต่อหนึ่งแถว
     *
     * @param  array<int, int>  $responseIds
     * @return array<int, string>
     */
    private function commentsOf(array $responseIds): array
    {
        if ($responseIds === []) {
            return [];
        }

        return DB::table('evl_answers')
            ->where('response_type', self::ANSWER_TYPE)
            ->whereIn('response_id', $responseIds)
            ->whereNotNull('text_value')
            ->orderBy('id')
            ->get(['response_id', 'text_value'])
            ->groupBy('response_id')
            ->map(fn ($group) => $group
                ->pluck('text_value')
                ->map(fn (string $text) => trim($text))
                ->filter()
                ->implode(' · '))
            ->all();
    }

    /** แท็บช่วงคะแนน — เกณฑ์ต้องตรงกับ band() ไม่งั้นแท็บกับสี pill จะไม่ตรงกัน */
    private function applyBand(Builder $query, string $band): void
    {
        match ($band) {
            'praise' => $query->whereRaw('ROUND(avg_score) >= 4'),
            'mid' => $query->whereRaw('ROUND(avg_score) = 3'),
            'improve' => $query->whereRaw('ROUND(avg_score) <= 2'),
            default => null,
        };
    }

    /**
     * ค้นได้สองอย่างเท่านั้น — ลำดับผู้ตอบ กับข้อความในความเห็น
     *
     * ความเห็นค้นด้วย EXISTS ไม่ใช่คอลัมน์ที่ต่อไว้แล้ว เพราะการต่อทำใน PHP
     * ผลจึงตรงกับที่แสดงเสมอ และไม่ผูกกับไวยากรณ์รวมข้อความของ driver ใด
     * ลำดับผู้ตอบเทียบกับ seq ตรง ๆ — ผู้ใช้พิมพ์ "12" หรือ "ผู้ตอบ #12" ก็เจอเหมือนกัน
     */
    private function applyKeyword(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        /* ไม่ระบุ ESCAPE แล้วผู้ใช้พิมพ์ % หรือ _ จะกลายเป็นไวลด์การ์ดโดยไม่ตั้งใจ
           ใช้ ! เป็นตัวหนีแทน \ เพราะ \ ใน string literal ถูกตีความต่างกันในแต่ละ driver
           (MySQL มองเป็นตัวหนีของ literal · SQLite มองเป็นอักขระธรรมดา) ส่วน ! เหมือนกันทุกที่ */
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword).'%';
        $seq = (int) preg_replace('/\D/', '', $keyword);

        $query->where(function (Builder $inner) use ($like, $seq): void {
            $inner->whereExists(fn (Builder $exists) => $exists
                ->from('evl_answers')
                ->whereColumn('evl_answers.response_id', 't.id')
                ->where('evl_answers.response_type', self::ANSWER_TYPE)
                /* ตัวหนีต้องเป็นค่าคงที่ในประโยค ไม่ใช่พารามิเตอร์ — MySQL ไม่รับ placeholder ตรงนี้ */
                ->whereRaw("evl_answers.text_value LIKE ? ESCAPE '!'", [$like]));

            if ($seq > 0) {
                $inner->orWhere('seq', $seq);
            }
        });
    }

    private function band(?float $average): string
    {
        if ($average === null) {
            return 'none';
        }

        $score = (int) round($average);

        return match (true) {
            $score >= 4 => 'praise',
            $score === 3 => 'mid',
            default => 'improve',
        };
    }

    /**
     * ป้ายชื่อรอบของกิจกรรม — คีย์ 0 คือค่าเริ่มต้นของคำตอบที่ไม่ได้ผูกรอบไว้
     * เกณฑ์ตั้งชื่อเหมือนหน้าผู้ลงทะเบียน: มีรอบเดียวไม่ต้องใส่เลขรอบให้รก
     *
     * @return array<int, string>
     */
    private function roundLabels(Activity $activity): array
    {
        $rounds = $activity->rounds()->orderBy('round_date')->orderBy('id')->pluck('id');

        if ($rounds->count() <= 1) {
            return [0 => 'รอบเดียว'];
        }

        $labels = [0 => 'ไม่ระบุรอบ'];
        foreach ($rounds as $index => $id) {
            $labels[$id] = 'รอบ '.($index + 1);
        }

        return $labels;
    }

    /**
     * คะแนนเฉลี่ยของแต่ละชุดคำตอบ — null คือชุดที่ไม่มีข้อให้คะแนนเลย
     *
     * @return Collection<int, float|null>
     */
    private function perResponseScores(Activity $activity): Collection
    {
        return DB::table('evl_satisfaction_responses as r')
            ->leftJoin('evl_answers as a', fn ($join) => $join
                ->on('a.response_id', '=', 'r.id')
                ->where('a.response_type', '=', self::ANSWER_TYPE))
            ->where('r.activity_id', $activity->id)
            ->groupBy('r.id')
            ->selectRaw('AVG(a.score) as avg_score')
            ->pluck('avg_score')
            ->map(fn ($value) => $value === null ? null : (float) $value);
    }

    private function commentCount(Activity $activity): int
    {
        return DB::table('evl_answers as a')
            ->join('evl_satisfaction_responses as r', 'r.id', '=', 'a.response_id')
            ->where('a.response_type', self::ANSWER_TYPE)
            ->where('r.activity_id', $activity->id)
            ->whereRaw("NULLIF(TRIM(a.text_value), '') IS NOT NULL")
            ->distinct()
            ->count('r.id');
    }

    /**
     * คะแนนเฉลี่ยรายข้อ — เฉพาะคำถามชนิดให้คะแนน ข้อที่ไม่มีใครตอบไม่ต้องแสดง
     *
     * @return array<int, array<string, mixed>>
     */
    private function topics(Activity $activity): array
    {
        $stats = DB::table('evl_answers as a')
            ->join('evl_satisfaction_responses as r', 'r.id', '=', 'a.response_id')
            ->where('a.response_type', self::ANSWER_TYPE)
            ->where('r.activity_id', $activity->id)
            ->whereNotNull('a.score')
            ->groupBy('a.question_id')
            ->selectRaw('a.question_id, AVG(a.score) as avg_score, COUNT(*) as answered')
            ->get()
            ->keyBy('question_id');

        if ($stats->isEmpty()) {
            return [];
        }

        return Question::query()
            ->whereIn('id', $stats->keys())
            ->orderBy('form_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'text', 'dimension'])
            ->map(function (Question $question) use ($stats) {
                $average = round((float) $stats[$question->id]->avg_score, 1);

                return [
                    'label' => $question->text,
                    'dimension' => $question->dimension,
                    'average' => $average,
                    'answered' => (int) $stats[$question->id]->answered,
                    'needsWork' => $average < self::TOPIC_WARN_BELOW,
                ];
            })->values()->all();
    }

    /** @return array{label: string, tone: string} */
    private function grade(float $average): array
    {
        foreach (self::GRADES as [$min, $label, $tone]) {
            if ($average >= $min) {
                return ['label' => $label, 'tone' => $tone];
            }
        }

        return ['label' => 'ต้องปรับปรุง', 'tone' => 'danger'];
    }
}
