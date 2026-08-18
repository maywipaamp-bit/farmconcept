<?php

namespace App\Services;

use App\Models\Activity;
use App\Support\ChartMath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลสำหรับรายงานภาพรวมที่มองข้ามกิจกรรมทั้งหมด (admin/reports/*)
 *
 * ต่างจาก ActivityController::reports() ตรงที่หน้านั้นมองกิจกรรมเดียว หน้านี้มองทั้งโครงการ
 * แยกเป็น service ของตัวเองเพราะ query จะกวาดทุกกิจกรรมพร้อมกัน คนละรูปแบบกับที่มีอยู่เดิม
 *
 * ทุกเมธอดคำนวณจากคำตอบและการลงทะเบียนที่มีอยู่จริง ไม่มีการประมาณค่าที่ไม่มีที่มา
 */
class ActivityInsightsService
{
    private const MONTHS_BACK = 6;

    private const TH_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    private const STATUS_TONE = [
        'เปิดรับสมัคร' => 'info',
        'เต็มแล้ว' => 'warning',
        'ปิดรับสมัคร' => 'muted',
        'ดำเนินการเสร็จสิ้น' => 'success',
        'ยกเลิก' => 'danger',
        'ฉบับร่าง' => 'muted',
    ];

    /* ==================================================================
     * 1) ภาพรวมกิจกรรม
     * ================================================================== */
    public function overview(): array
    {
        $activities = Activity::query()->withCount([
            'registrations',
            'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
        ])->get(['id', 'code', 'name', 'status', 'start_date', 'created_at']);

        $totalRegistrations = (int) $activities->sum('registrations_count');
        $totalCheckedIn = (int) $activities->sum('checked_in_count');

        $months = $this->monthBuckets();

        $activityTrend = $this->countByMonth($months, Activity::query(), 'start_date');
        $registrationTrend = $this->countByMonth(
            $months,
            DB::table('act_registrations'),
            'registered_at',
        );

        $scoreTrend = $this->avgScoreByMonth($months);

        $overallScore = collect($scoreTrend)->filter(fn ($v) => $v !== null);

        return [
            'kpis' => [
                'activities' => $activities->count(),
                'registrations' => $totalRegistrations,
                'checkedIn' => $totalCheckedIn,
                'checkinRate' => $totalRegistrations > 0 ? (int) round($totalCheckedIn / $totalRegistrations * 100) : 0,
                'avgScore' => $overallScore->isEmpty() ? null : round($overallScore->avg(), 1),
            ],
            'monthlyTrend' => ChartMath::trendLine($months['labels'], [
                ['key' => 'activities', 'label' => 'กิจกรรมที่จัด', 'values' => $activityTrend],
                ['key' => 'registrations', 'label' => 'ผู้ลงทะเบียน', 'values' => $registrationTrend],
            ]),
            'scoreTrend' => ChartMath::trendLine($months['labels'], [
                ['key' => 'score', 'label' => 'คะแนนความพึงพอใจเฉลี่ย', 'values' => $scoreTrend],
            ], fixedMax: 5.0),
            'topActivities' => ChartMath::barList(
                $activities->sortByDesc('registrations_count')->take(5)
                    ->map(fn (Activity $a) => ['label' => $a->name, 'count' => (int) $a->registrations_count])
                    ->values(),
                limit: 5,
                rollUp: false,
            ),
            'statusDonut' => ChartMath::donut(
                $activities->countBy('status')
                    ->map(fn (int $count, string $status) => [
                        'label' => $status,
                        'count' => $count,
                        'tone' => self::STATUS_TONE[$status] ?? 'muted',
                    ])->values()->all()
            ),
        ];
    }

    /* ==================================================================
     * 2) ประสิทธิภาพกิจกรรม (แยกมิติ)
     * ================================================================== */
    public function performance(): array
    {
        $activities = Activity::query()
            ->with(['areas:id,name', 'course:id,name', 'program:id,name', 'instructors:id,name'])
            ->withCount([
                'registrations',
                'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            ])
            ->get(['id', 'code', 'name', 'capacity', 'program_id', 'course_id']);

        $scoreByActivity = $this->avgScoreByActivity();

        $rows = $activities->map(function (Activity $a) use ($scoreByActivity) {
            $registered = (int) $a->registrations_count;
            $checkedIn = (int) $a->checked_in_count;
            $capacity = (int) $a->capacity;

            return [
                'id' => $a->id,
                'activityLabel' => $a->name,
                /* พื้นที่จัดหนึ่งกิจกรรมมีได้หลายแห่ง — ใช้แห่งแรกจัดกลุ่ม เพราะกิจกรรมส่วนใหญ่มีพื้นที่เดียว
                   ถ้าไม่มีพื้นที่เลย จัดเข้ากลุ่ม "ไม่ระบุพื้นที่" แทนการตกหล่นจากรายงาน */
                'areaLabel' => $a->areas->first()?->name ?? 'ไม่ระบุพื้นที่',
                'courseInstructorLabel' => $this->courseInstructorLabel($a),
                'registered' => $registered,
                'checkedIn' => $checkedIn,
                'capacity' => $capacity,
                'fillRate' => $capacity > 0 ? (int) round($registered / $capacity * 100) : null,
                'checkinRate' => $registered > 0 ? (int) round($checkedIn / $registered * 100) : null,
                'avgScore' => $scoreByActivity->get($a->id),
            ];
        });

        return [
            'byActivity' => $this->dimensionTable($rows, fn (array $r) => $r['activityLabel'], includeCount: false),
            'byArea' => $this->dimensionTable($rows, fn (array $r) => $r['areaLabel']),
            'byCourseInstructor' => $this->dimensionTable($rows, fn (array $r) => $r['courseInstructorLabel']),
        ];
    }

    /**
     * รวมแถวรายกิจกรรมเข้าเป็นรายมิติ (พื้นที่ / หลักสูตร-วิทยากร) หรือส่งกลับทีละแถวเมื่อมิติคือกิจกรรมเอง
     *
     * fill/check-in rate ของมิติที่รวมหลายกิจกรรม คิดจากผลรวม (จำนวนคนรวม หาร ที่นั่งรวม)
     * ไม่ใช่ค่าเฉลี่ยของเปอร์เซ็นต์ — กันไม่ให้กิจกรรมเล็กที่เต็ม 100% ถ่วงตัวเลขเท่ากับกิจกรรมใหญ่
     */
    private function dimensionTable(Collection $rows, callable $groupKey, bool $includeCount = true): array
    {
        if (! $includeCount) {
            $table = $rows->sortByDesc('registered')->values()->all();

            return [
                'table' => $table,
                'fillRateBars' => ChartMath::barList($rows->map(fn ($r) => ['label' => $r['activityLabel'], 'count' => $r['fillRate'] ?? 0]), rollUp: false),
                'checkinRateBars' => ChartMath::barList($rows->map(fn ($r) => ['label' => $r['activityLabel'], 'count' => $r['checkinRate'] ?? 0]), rollUp: false),
                'scoreBars' => ChartMath::barList($rows->filter(fn ($r) => $r['avgScore'] !== null)->map(fn ($r) => ['label' => $r['activityLabel'], 'count' => $r['avgScore']]), rollUp: false),
            ];
        }

        $grouped = $rows->groupBy($groupKey)->map(function (Collection $group, string $label) {
            $registered = $group->sum('registered');
            $checkedIn = $group->sum('checkedIn');
            $capacity = $group->sum('capacity');
            $scored = $group->filter(fn ($r) => $r['avgScore'] !== null);

            return [
                'activityLabel' => $label,
                'activityCount' => $group->count(),
                'registered' => $registered,
                'checkedIn' => $checkedIn,
                'capacity' => $capacity,
                'fillRate' => $capacity > 0 ? (int) round($registered / $capacity * 100) : null,
                'checkinRate' => $registered > 0 ? (int) round($checkedIn / $registered * 100) : null,
                'avgScore' => $scored->isEmpty() ? null : round($scored->avg('avgScore'), 1),
            ];
        })->sortByDesc('registered')->values();

        return [
            'table' => $grouped->all(),
            'fillRateBars' => ChartMath::barList($grouped->map(fn ($r) => ['label' => $r['activityLabel'], 'count' => $r['fillRate'] ?? 0]), rollUp: false),
            'checkinRateBars' => ChartMath::barList($grouped->map(fn ($r) => ['label' => $r['activityLabel'], 'count' => $r['checkinRate'] ?? 0]), rollUp: false),
            'scoreBars' => ChartMath::barList($grouped->filter(fn ($r) => $r['avgScore'] !== null)->map(fn ($r) => ['label' => $r['activityLabel'], 'count' => $r['avgScore']]), rollUp: false),
        ];
    }

    /** "หลักสูตร A · วิทยากร B" — ไม่มีหลักสูตรให้ใช้ชื่อโปรแกรมแทน ไม่มีวิทยากรให้บอกตรง ๆ */
    private function courseInstructorLabel(Activity $a): string
    {
        $course = $a->course?->name ?? $a->program?->name ?? 'ไม่ระบุหลักสูตร';
        $instructors = $a->instructors->pluck('name')->implode(', ');

        return $course.' · '.($instructors !== '' ? $instructors : 'ไม่ระบุวิทยากร');
    }

    /* ==================================================================
     * 3) ผู้เข้าร่วมและช่องทาง
     * ================================================================== */
    public function participants(): array
    {
        $registrations = DB::table('act_registrations')
            ->leftJoin('mst_options as age', 'age.id', '=', 'act_registrations.age_range_id')
            ->leftJoin('mst_options as occ', 'occ.id', '=', 'act_registrations.occupation_id')
            ->leftJoin('mst_options as ch', 'ch.id', '=', 'act_registrations.source_channel_id')
            ->leftJoin('act_activities as act', 'act.id', '=', 'act_registrations.activity_id')
            ->select([
                'act_registrations.id', 'act_registrations.phone', 'act_registrations.email',
                'act_registrations.gender', 'act_registrations.checked_in_at', 'act_registrations.payment_status',
                'act.has_fee',
                'age.label as age_label', 'occ.label as occ_label', 'ch.label as channel_label',
            ])
            ->get();

        $genders = ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'เพศทางเลือก', 'undisclosed' => 'ไม่ระบุ'];

        $genderDonut = ChartMath::donut(
            $registrations->countBy(fn ($r) => $genders[$r->gender] ?? 'ไม่ระบุ')
                ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
                ->values()->all()
        );

        $ageDonut = ChartMath::donut(
            $registrations->countBy(fn ($r) => $r->age_label ?: 'ไม่ระบุ')
                ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
                ->values()->all()
        );

        $occupationBars = ChartMath::barList(
            $registrations->countBy(fn ($r) => $r->occ_label ?: 'ไม่ระบุ')
                ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
                ->values()
        );

        /* ผู้เข้าร่วมซ้ำ — ยึดเบอร์โทร/อีเมลเป็นตัวชี้ตัวคน เกณฑ์เดียวกับ ReportPeopleController
           นับเฉพาะคนที่เช็คอินแล้ว เพราะ "เข้าร่วมซ้ำ" ต้องแปลว่ามาจริง ไม่ใช่แค่ลงทะเบียนไว้ */
        $attended = $registrations->filter(fn ($r) => $r->checked_in_at !== null);
        $byIdentity = $attended->groupBy(function ($r) {
            $phone = preg_replace('/\D+/', '', (string) $r->phone);
            if ($phone !== '') {
                return 'phone:'.$phone;
            }

            $email = mb_strtolower(trim((string) $r->email));

            return $email !== '' ? 'email:'.$email : 'reg:'.$r->id;
        });
        $peopleCount = $byIdentity->count();
        $repeatCount = $byIdentity->filter(fn (Collection $g) => $g->count() >= 2)->count();

        /* ประสิทธิภาพช่องทาง — สัดส่วนที่มาลงทะเบียน และในนั้นมาเช็คอินจริงกี่ % */
        $byChannel = $registrations->groupBy(fn ($r) => $r->channel_label ?: 'ไม่ระบุช่องทาง');
        $channelRows = $byChannel->map(function (Collection $g, string $label) {
            $count = $g->count();
            $checkedIn = $g->filter(fn ($r) => $r->checked_in_at !== null)->count();

            return [
                'label' => $label,
                'count' => $count,
                'checkedIn' => $checkedIn,
                'checkinRate' => $count > 0 ? (int) round($checkedIn / $count * 100) : 0,
            ];
        })->sortByDesc('count')->values();

        /* Funnel — คำตอบแบบประเมินเป็นนิรนาม (ไม่ผูกกับ registration_id) จึงนับเป็นยอดรวมของขั้นนั้น
           ไม่ใช่การไล่คนกลุ่มเดียวกันทีละขั้นแบบสามขั้นแรก บอกไว้ในหน้าจอให้ชัดเจน */
        $paidStage = $registrations->filter(fn ($r) => ! $r->has_fee || $r->payment_status === 'ชำระแล้ว')->count();
        $checkedInStage = $registrations->filter(fn ($r) => $r->checked_in_at !== null)->count();
        $surveyStage = DB::table('evl_satisfaction_responses')->count();

        $funnelStages = [
            ['label' => 'ลงทะเบียน', 'count' => $registrations->count()],
            ['label' => 'ชำระเงิน', 'count' => $paidStage],
            ['label' => 'เช็คอิน', 'count' => $checkedInStage],
            ['label' => 'ทำแบบประเมิน', 'count' => $surveyStage],
        ];
        $funnelBase = max(1, $funnelStages[0]['count']);
        $funnel = collect($funnelStages)->map(fn (array $s) => $s + [
            'pct' => (int) round($s['count'] / $funnelBase * 100),
        ])->all();

        return [
            'genderDonut' => $genderDonut,
            'ageDonut' => $ageDonut,
            'occupationBars' => $occupationBars,
            'repeat' => [
                'people' => $peopleCount,
                'repeat' => $repeatCount,
                'repeatPct' => $peopleCount > 0 ? (int) round($repeatCount / $peopleCount * 100) : 0,
            ],
            'channels' => $channelRows->all(),
            'channelBars' => ChartMath::barList($channelRows->map(fn (array $r) => ['label' => $r['label'], 'count' => $r['count']]), rollUp: false),
            'funnel' => $funnel,
        ];
    }

    /* ==================================================================
     * 4) การเงิน
     * ================================================================== */
    public function finance(): array
    {
        $activities = Activity::query()
            ->where('has_fee', true)
            ->withCount([
                'registrations as paid_count' => fn ($q) => $q->where('payment_status', 'ชำระแล้ว'),
                'registrations as pending_count' => fn ($q) => $q->whereIn('payment_status', ['ยังไม่ชำระ', 'รอตรวจสอบ']),
                'registrations',
            ])
            ->get(['id', 'code', 'name', 'fee', 'capacity']);

        $rows = $activities->map(function (Activity $a) {
            $paid = (float) $a->fee * (int) $a->paid_count;
            $pending = (float) $a->fee * (int) $a->pending_count;
            /* คาดการณ์ = รายรับสูงสุดถ้าที่นั่งเต็ม ไม่มีเพดานที่นั่งให้ใช้ยอดจริงแทน (คาดการณ์ = จริง) */
            $forecast = (int) $a->capacity > 0 ? (float) $a->fee * (int) $a->capacity : $paid + $pending;

            return [
                'name' => $a->name,
                'paid' => $paid,
                'pending' => $pending,
                'forecast' => $forecast,
                'actual' => $paid,
                'attainment' => $forecast > 0 ? (int) round($paid / $forecast * 100) : 0,
            ];
        })->sortByDesc('forecast')->values();

        $totalPaid = $rows->sum('paid');
        $totalPending = $rows->sum('pending');

        $months = $this->monthBuckets();

        /* ไม่มีเวลาที่บันทึกตอนยืนยันชำระเงินแยกไว้ต่างหาก จึงอิงเดือนที่ลงทะเบียนเป็นตัวแทน
           (รายรับของการจองนั้นถูกนับเข้าเดือนที่จองไว้ ไม่ใช่เดือนที่เจ้าหน้าที่กดยืนยัน) */
        $monthlyRevenue = collect($months['ranges'])->map(function (array $range) {
            return (float) DB::table('act_registrations')
                ->join('act_activities', 'act_activities.id', '=', 'act_registrations.activity_id')
                ->where('act_activities.has_fee', true)
                ->where('act_registrations.payment_status', 'ชำระแล้ว')
                ->whereBetween('act_registrations.registered_at', [$range['start'], $range['end']])
                ->sum('act_activities.fee');
        })->all();

        return [
            'kpis' => [
                'paid' => $totalPaid,
                'pending' => $totalPending,
                'forecast' => $totalPaid + $totalPending,
            ],
            'byActivity' => $rows->all(),
            'monthlyTrend' => ChartMath::trendLine($months['labels'], [
                ['key' => 'revenue', 'label' => 'รายรับที่ชำระแล้ว (บาท)', 'values' => $monthlyRevenue],
            ]),
        ];
    }

    /* ==================================================================
     * ตัวช่วยรายเดือนที่ใช้ร่วมกันทุกหัวข้อ
     * ================================================================== */

    /** @return array{labels: array<int, string>, ranges: array<int, array{start: \Carbon\Carbon, end: \Carbon\Carbon}>} */
    private function monthBuckets(): array
    {
        $labels = [];
        $ranges = [];
        $cursor = now()->startOfMonth()->subMonths(self::MONTHS_BACK - 1);

        for ($i = 0; $i < self::MONTHS_BACK; $i++) {
            $labels[] = self::TH_MONTHS[$cursor->month - 1].' '.substr((string) ($cursor->year + 543), 2);
            $ranges[] = ['start' => $cursor->copy()->startOfMonth(), 'end' => $cursor->copy()->endOfMonth()];
            $cursor->addMonth();
        }

        return ['labels' => $labels, 'ranges' => $ranges];
    }

    /** @return array<int, float> */
    private function countByMonth(array $months, $query, string $column): array
    {
        return collect($months['ranges'])->map(
            fn (array $range) => (float) (clone $query)->whereBetween($column, [$range['start'], $range['end']])->count()
        )->all();
    }

    /** @return array<int, float|null> */
    private function avgScoreByMonth(array $months): array
    {
        return collect($months['ranges'])->map(function (array $range) {
            $value = DB::table('evl_satisfaction_responses as r')
                ->join('evl_answers as a', fn ($j) => $j->on('a.response_id', '=', 'r.id')->where('a.response_type', 'satisfaction'))
                ->whereNotNull('a.score')
                ->whereBetween('r.submitted_at', [$range['start'], $range['end']])
                ->avg('a.score');

            return $value === null ? null : round((float) $value, 2);
        })->all();
    }

    /** @return Collection<int, float> คะแนนเฉลี่ยต่อกิจกรรม คีย์เป็น activity_id */
    private function avgScoreByActivity(): Collection
    {
        return DB::table('evl_satisfaction_responses as r')
            ->join('evl_answers as a', fn ($j) => $j->on('a.response_id', '=', 'r.id')->where('a.response_type', 'satisfaction'))
            ->whereNotNull('a.score')
            ->groupBy('r.activity_id')
            ->selectRaw('r.activity_id, AVG(a.score) as avg_score')
            ->pluck('avg_score', 'activity_id')
            ->map(fn ($v) => round((float) $v, 1));
    }
}
