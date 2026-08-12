<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลของหน้า "ภาพรวมการดำเนินงาน" (admin/dashboard)
 *
 * ทั้งหน้าใช้ payload ก้อนเดียวจาก overview() — เท่ากับ endpoint เดียวตาม handoff หัวข้อ Data / API
 * โครงคีย์ที่คืนออกไปคือสัญญาระหว่าง service กับ view ห้ามให้ view ยิง query เองเพิ่ม
 *
 * กติกาที่ยึดไว้
 * - ตัวเลขทุกตัวมาจากฐานข้อมูลจริง ไม่มีค่าตัวอย่างหลงอยู่ในไฟล์นี้
 *   ตารางไหนยังว่างจะได้ค่า 0 กับ array ว่าง แล้ว view แสดง Empty State ของแผงนั้น
 *   ไม่ใช่ซ่อนแผงทิ้งหรือเติมเลขปลอมให้กราฟดูสวย
 * - ไม่มีค่าสีในไฟล์นี้เลย ส่งออกเป็น "อันดับ" (rank) แล้วให้ CSS แปลงเป็นเฉดเขียว
 *   ตาม CLAUDE.md ข้อ 7 ที่ห้าม hardcode สีนอก token
 * - เรขาคณิตของกราฟ (โดนัท เส้น treemap) คำนวณที่นี่ให้เสร็จ
 *   หน้าจอจึงอ่านค่าได้ครบตั้งแต่ HTML ชุดแรกโดยไม่ต้องรอ JS (ข้อ A11y ของ handoff)
 */
class DashboardService
{
    /**
     * ตัวกรองช่วงเวลาบนหัวหน้า — คีย์คือค่าที่ยอมรับใน query string ?range=
     * ตัวแรกเป็นค่าตั้งต้น
     */
    public const RANGES = [
        'all' => ['label' => 'ทั้งหมด', 'months' => null],
        '3m' => ['label' => '3 เดือน', 'months' => 3],
        '6m' => ['label' => '6 เดือน', 'months' => 6],
        '12m' => ['label' => '12 เดือน', 'months' => 12],
    ];

    /** จำนวนอันดับที่เฉดเขียวใน CSS รองรับ — เกินจากนี้วนกลับไปใช้เฉดอ่อนสุด */
    private const SCALE_STEPS = 5;

    /** จำนวนหลักสูตรที่แสดงในแผง "หลักสูตรที่มีผู้เข้าร่วมสูงสุด" */
    private const TOP_COURSE_LIMIT = 5;

    /** จำนวนเขตที่แสดงใน treemap — ที่เหลือถูกรวบเป็นบรรทัดสรุป */
    private const TREEMAP_LIMIT = 6;

    /**
     * ข้อมูลทั้งหน้าในคำขอเดียว
     *
     * @return array<string, mixed>
     */
    public function overview(string $range): array
    {
        $range = $this->normalizeRange($range);
        $since = $this->since($range);

        $participants = $this->participants($since);
        $cohort = $this->cohort();
        $assessment = $this->assessment();

        return [
            'range' => $range,
            'range_options' => $this->rangeOptions($range),
            'generated_at' => now(),
            'kpis' => $this->kpis($since, $participants['total'], $cohort['total']),
            'participants' => $participants,
            'cohort' => $cohort,
            'survey_rounds' => $this->surveyRounds($cohort['total']),
            'assessment' => $assessment,
            'areas' => $this->areas($since, $cohort['total']),
        ];
    }

    /** ค่า range ที่ไม่รู้จักให้ตกไปที่ค่าตั้งต้น ไม่ใช่ 404 — ผู้ใช้แก้ URL เล่นไม่ควรทำให้หน้าพัง */
    public function normalizeRange(?string $range): string
    {
        return array_key_exists((string) $range, self::RANGES)
            ? (string) $range
            : (string) array_key_first(self::RANGES);
    }

    /** วันเริ่มต้นของช่วงที่เลือก — null คือ "ทั้งหมด" ไม่ต้องกรองวันที่ */
    private function since(string $range): ?Carbon
    {
        $months = self::RANGES[$range]['months'];

        return $months === null ? null : now()->subMonthsNoOverflow($months)->startOfDay();
    }

    /** @return array<int, array<string, mixed>> */
    private function rangeOptions(string $current): array
    {
        return collect(self::RANGES)
            ->map(fn (array $option, string $key) => [
                'key' => $key,
                'label' => $option['label'],
                'active' => $key === $current,
            ])
            ->values()
            ->all();
    }

    /* ==========================================================================
       แถว 1 — การ์ด KPI
       ========================================================================== */

    /**
     * ตัวเลขสรุป 4 ใบ
     *
     * มีแค่ป้าย + ตัวเลข + หน่วย ไม่มีบรรทัดเปรียบเทียบใต้การ์ด
     * (ตัดออกตามที่ทีมสั่ง — บรรทัดนั้นเพิ่มตัวหนังสือสี่บรรทัดโดยไม่ได้ช่วยตัดสินใจอะไร
     * ตัวเลขเทียบช่วงเวลาดูได้จากตัวกรองช่วงบนหัวหน้าอยู่แล้ว)
     *
     * total ของผู้เข้าร่วมกับกลุ่มตัวอย่างรับมาจากผู้เรียก ไม่ query ซ้ำ
     * เพราะเป็นตัวเลขเดียวกับที่แถว 2 และแถว 3 ใช้ ต้องตรงกันเสมอ
     *
     * @return array<int, array<string, mixed>>
     */
    private function kpis(?Carbon $since, int $participantTotal, int $cohortTotal): array
    {
        return [
            [
                'key' => 'participants',
                'label' => 'ผู้เข้าร่วมกิจกรรมทั้งหมด',
                'value' => $participantTotal,
                'unit' => 'คน',
                'icon' => 'users',
            ],
            /* กลุ่มตัวอย่างอยู่ใบที่สองติดกับผู้เข้าร่วม เพราะเป็น "ส่วนย่อยของ" ตัวเลขใบแรก
               อ่านคู่กันได้ทันทีว่า 28 จาก 47 คน ไม่ต้องข้ามใบกิจกรรมไปหา */
            [
                'key' => 'cohort',
                'label' => 'จำนวนกลุ่มตัวอย่าง',
                'value' => $cohortTotal,
                'unit' => 'คน',
                'icon' => 'check',
            ],
            [
                'key' => 'activities',
                'label' => 'จำนวนกิจกรรม',
                'value' => $this->heldActivities($since)->count(),
                'unit' => 'ครั้ง',
                'icon' => 'calendar',
            ],
            [
                'key' => 'areas',
                'label' => 'พื้นที่ดำเนินงาน',
                'value' => DB::table('mst_areas')->count(),
                'unit' => 'พื้นที่',
                'icon' => 'pin',
            ],
        ];
    }

    /* ==========================================================================
       แถว 2 — ผู้เข้าร่วมกิจกรรม (เพศ · ช่วงอายุ · หลักสูตร)
       ========================================================================== */

    /**
     * ผู้เข้าร่วมกิจกรรมทั้งหมด แยกตามเพศ ช่วงอายุ และหลักสูตร
     *
     * นับ "คน" ไม่ใช่ "ใบลงทะเบียน" — คนหนึ่งลงหลายกิจกรรมต้องนับหนึ่ง
     * ไม่งั้นตัวเลขบนการ์ดจะบวมกว่าจำนวนคนจริงตามจำนวนกิจกรรมที่เขาไป
     *
     * @return array<string, mixed>
     */
    private function participants(?Carbon $since): array
    {
        $total = $this->distinctPeople($this->registrations($since));

        return [
            'total' => $total,
            'gender' => $this->genderBreakdown($since, $total),
            'age_bands' => $this->ageBands($since, $total),
            'occupations' => $this->occupations($since, $total),
            'top_courses' => $this->topCourses($since, $total),
        ];
    }

    /**
     * จำแนกตามอาชีพ
     *
     * ตัวเลือกอาชีพมาจาก mst_options กลุ่ม occupation ไม่ใช่ข้อความอิสระ
     * (occupation_raw มีไว้รับค่าที่ผู้ใช้พิมพ์เองเมื่อเลือก "อื่น ๆ" จึงรวบเป็นถังเดียว)
     * ถ้าเก็บเป็นข้อความอิสระทั้งหมด กิจกรรม A เขียน "เกษตรกร" กิจกรรม B เขียน "ทำนา"
     * แล้วรวมรายงานข้ามกิจกรรมไม่ได้ ตามเหตุผลใน migration ของ act_activity_reg_fields
     *
     * คืนรูปแบบเดียวกับ ageBands() เพราะทั้งคู่วาดด้วยแท่งแนวนอนชุดเดียวกัน
     *
     * @return array<int, array<string, mixed>>
     */
    private function occupations(?Carbon $since, int $total): array
    {
        $rows = $this->registrations($since)
            ->leftJoin('mst_options', 'mst_options.id', '=', 'act_registrations.occupation_id')
            ->where(fn (Builder $q) => $q
                ->whereNotNull('act_registrations.occupation_id')
                ->orWhereNotNull('act_registrations.occupation_raw'))
            ->select(
                DB::raw("COALESCE(mst_options.label, act_registrations.occupation_raw, 'ไม่ระบุ') as label"),
                $this->personKey('people')
            )
            ->groupBy('mst_options.id', 'mst_options.label', 'act_registrations.occupation_raw')
            ->orderByDesc('people')
            ->get();

        $max = $rows->max('people') ?: 1;
        $last = max($rows->count() - 1, 0);

        return $rows
            ->values()
            ->map(fn (object $row, int $index) => [
                'label' => $row->label,
                'count' => (int) $row->people,
                'pct' => $this->percentText((int) $row->people, $total),
                /* ความยาวแท่งเทียบกับอาชีพที่มากที่สุด ไม่ใช่เทียบยอดรวม
                   อาชีพที่มากสุดจะได้เต็มราง เห็นความต่างระหว่างอาชีพชัดกว่า */
                'bar' => round(((int) $row->people / $max) * 100, 2),
                /* เข้มสุด = มากสุด ไล่อ่อนลงตามอันดับ (ตรงข้ามกับช่วงอายุที่ไล่ตามอายุ) */
                'rank' => min($index, self::SCALE_STEPS - 1),
            ])
            ->all();
    }

    /**
     * จำแนกตามเพศ
     *
     * male/female แสดงเสมอเพราะ handoff กำหนดสีเฉพาะไว้ให้สองค่านี้
     * other/undisclosed/ค่าว่าง รวบเป็น "ไม่ระบุ" และแสดงเฉพาะเมื่อมีคนจริง
     *
     * @return array<int, array<string, mixed>>
     */
    private function genderBreakdown(?Carbon $since, int $total): array
    {
        $counts = $this->registrations($since)
            ->select('gender', $this->personKey('people'))
            ->groupBy('gender')
            ->pluck('people', 'gender');

        $unknown = $counts->except(['male', 'female'])->sum();

        $rows = [
            ['key' => 'female', 'label' => 'หญิง', 'count' => (int) $counts->get('female', 0)],
            ['key' => 'male', 'label' => 'ชาย', 'count' => (int) $counts->get('male', 0)],
        ];

        if ($unknown > 0) {
            $rows[] = ['key' => 'unknown', 'label' => 'ไม่ระบุ', 'count' => (int) $unknown];
        }

        return array_map(
            fn (array $row) => $row + ['pct' => $this->percentText($row['count'], $total)],
            $rows
        );
    }

    /**
     * จำแนกตามช่วงอายุ — เกณฑ์มาจาก config/farmconcept.php ที่เดียว
     *
     * ดึงมาแค่ (ปีเกิด, จำนวนคน) แล้วจัดกลุ่มใน PHP ไม่ประกอบ CASE ลง SQL
     * จำนวนแถวเท่ากับจำนวนปีเกิดที่ต่างกัน (หลักสิบ) และทำให้เกณฑ์อยู่ที่ config จุดเดียว
     *
     * @return array<int, array<string, mixed>>
     */
    private function ageBands(?Carbon $since, int $total): array
    {
        $bands = collect(config('farmconcept.age_bands'))
            ->map(fn (array $band) => $band + ['count' => 0])
            ->all();

        $byYear = $this->registrations($since)
            ->whereNotNull('birth_year')
            ->select('birth_year', $this->personKey('people'))
            ->groupBy('birth_year')
            ->pluck('people', 'birth_year');

        $thisYear = (int) now()->year;

        foreach ($byYear as $year => $people) {
            $age = $thisYear - (int) $year;

            foreach ($bands as $index => $band) {
                if ($band['max'] === null || $age <= $band['max']) {
                    $bands[$index]['count'] += (int) $people;
                    break;
                }
            }
        }

        $max = collect($bands)->max('count') ?: 1;
        $last = count($bands) - 1;

        return collect($bands)
            ->values()
            ->map(fn (array $band, int $index) => [
                'label' => $band['label'],
                'count' => $band['count'],
                'pct' => $this->percentText($band['count'], $total),
                /* ความยาวแท่งเทียบกับช่วงที่มากที่สุด ไม่ใช่เทียบยอดรวม
                   ช่วงที่มากสุดจะได้เต็มราง ทำให้เห็นความต่างระหว่างช่วงชัดกว่า */
                'bar' => round(($band['count'] / $max) * 100, 2),
                /* เฉดเข้มขึ้นตามอายุ ให้อ่านลำดับช่วงได้จากสีเหมือนต้นแบบ */
                'rank' => $last - $index,
            ])
            ->all();
    }

    /**
     * หลักสูตรที่มีผู้เข้าร่วมสูงสุด
     *
     * ผูกผ่าน act_activities.course_id — กิจกรรมที่ยังไม่ผูกหลักสูตรจะไม่ถูกนับ
     * (โชว์เป็น "ไม่ระบุหลักสูตร" จะทำให้อันดับเพี้ยนเพราะเป็นถังรวมของทุกกิจกรรมที่ยังกรอกไม่ครบ)
     *
     * @return array<int, array<string, mixed>>
     */
    private function topCourses(?Carbon $since, int $total): array
    {
        $rows = $this->registrations($since)
            ->join('act_activities', 'act_activities.id', '=', 'act_registrations.activity_id')
            ->join('mst_courses', 'mst_courses.id', '=', 'act_activities.course_id')
            ->whereNull('act_activities.deleted_at')
            ->select('mst_courses.name', $this->personKey('people'))
            ->groupBy('mst_courses.id', 'mst_courses.name')
            ->orderByDesc('people')
            ->orderBy('mst_courses.name')
            ->limit(self::TOP_COURSE_LIMIT)
            ->get();

        $max = $rows->max('people') ?: 1;

        return $rows
            ->values()
            ->map(fn (object $row, int $index) => [
                'rank' => $index,
                'no' => $index + 1,
                'label' => $row->name,
                'count' => (int) $row->people,
                'pct' => $this->percentText((int) $row->people, $total),
                'bar' => round(((int) $row->people / $max) * 100, 2),
            ])
            ->all();
    }

    /* ==========================================================================
       แถว 3 ซ้าย — กลุ่มตัวอย่างจำแนกตามกลุ่มเป้าหมาย (โดนัท)
       ========================================================================== */

    /**
     * กลุ่มตัวอย่างแยกตามกลุ่มเป้าหมาย พร้อมค่า stroke ของวงโดนัท
     *
     * @return array<string, mixed>
     */
    private function cohort(): array
    {
        $rows = DB::table('ptp_cohort_profiles')
            ->join('ptp_participants', 'ptp_participants.id', '=', 'ptp_cohort_profiles.participant_id')
            ->leftJoin('mst_target_groups', 'mst_target_groups.id', '=', 'ptp_participants.target_group_id')
            ->whereNull('ptp_participants.deleted_at')
            ->whereNull('ptp_cohort_profiles.stopped_at')
            ->select(
                DB::raw("COALESCE(mst_target_groups.name, 'ไม่ระบุกลุ่ม') as label"),
                DB::raw('count(*) as people')
            )
            ->groupBy('mst_target_groups.id', 'mst_target_groups.name')
            ->orderByDesc('people')
            ->get();

        $total = (int) $rows->sum('people');

        return [
            'total' => $total,
            'groups' => $this->donutSegments($rows, $total),
        ];
    }

    /**
     * แปลงจำนวนเป็นค่า stroke-dasharray/offset ของวงโดนัท
     *
     * รัศมีกับความหนาต้องตรงกับที่ handoff กำหนด (r=76 · stroke 30 · hover 34)
     * เพราะ dasharray คิดจากเส้นรอบวงของรัศมีนั้น
     *
     * @param  Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function donutSegments(Collection $rows, int $total): array
    {
        $circumference = 2 * M_PI * 76;
        $offset = 0.0;

        return $rows
            ->values()
            ->map(function (object $row, int $index) use ($total, $circumference, &$offset) {
                $length = $total > 0 ? ((int) $row->people / $total) * $circumference : 0;

                $segment = [
                    'label' => $row->label,
                    'count' => (int) $row->people,
                    'pct' => $this->percentText((int) $row->people, $total),
                    'rank' => min($index, self::SCALE_STEPS - 1),
                    /* เว้นช่อง 3 หน่วยระหว่างชิ้น ให้เห็นรอยต่อโดยไม่ต้องวาดเส้นขอบ */
                    'dash' => round(max($length - 3, 0), 2).' '.round($circumference - max($length - 3, 0), 2),
                    'offset' => round(-$offset, 2),
                ];

                $offset += $length;

                return $segment;
            })
            ->all();
    }

    /* ==========================================================================
       แถว 3 ขวา — การตอบแบบประเมินสุขภาพรายรอบ
       ========================================================================== */

    /**
     * อัตราการตอบแบบติดตามของแต่ละรอบ
     *
     * จัดกลุ่มด้วย offset_days ไม่ใช่ชื่อรอบ เพราะชื่อเป็น snapshot รายคน
     * (ดูคอมเมนต์ใน migration ptp_follow_up_rounds) คนที่เข้าต่างเวลากันอาจได้ชื่อไม่ตรงกัน
     * แต่ offset_days เท่ากันคือรอบเดียวกันแน่นอน
     *
     * @return array<string, mixed>
     */
    private function surveyRounds(int $base): array
    {
        $rows = DB::table('ptp_follow_up_rounds')
            ->join('ptp_cohort_profiles', 'ptp_cohort_profiles.id', '=', 'ptp_follow_up_rounds.cohort_profile_id')
            ->whereNull('ptp_cohort_profiles.stopped_at')
            ->select(
                'ptp_follow_up_rounds.offset_days',
                DB::raw('min(ptp_follow_up_rounds.name) as label'),
                DB::raw('count(*) as assigned'),
                DB::raw('count(ptp_follow_up_rounds.answered_at) as done')
            )
            ->groupBy('ptp_follow_up_rounds.offset_days')
            ->orderBy('ptp_follow_up_rounds.offset_days')
            ->get();

        $last = max($rows->count() - 1, 0);

        $rounds = $rows
            ->values()
            ->map(function (object $row, int $index) use ($last) {
                /* ฐานคือ "จำนวนที่ถูกกำหนดให้ตอบรอบนั้น" ไม่ใช่กลุ่มตัวอย่างทั้งหมด
                   คนที่เข้าใหม่ยังไม่ถึงกำหนดรอบ 12 เดือน ไม่ควรถูกนับเป็น "ยังไม่ตอบ" */
                $assigned = (int) $row->assigned;
                $done = (int) $row->done;

                return [
                    'label' => $row->label,
                    'assigned' => $assigned,
                    'done' => $done,
                    'missing' => $assigned - $done,
                    'pct' => $assigned > 0 ? round(($done / $assigned) * 100) : 0,
                    'rank' => min($index, self::SCALE_STEPS - 1),
                    'is_last' => $index === $last,
                ];
            })
            ->all();

        return [
            'base' => $base,
            'rounds' => $rounds,
            'stats' => $this->roundStats($rounds),
        ];
    }

    /**
     * สรุป 3 ตัวเลขท้ายการ์ด — คำนวณจากรอบที่ได้มา ไม่ query เพิ่ม
     *
     * @param  array<int, array<string, mixed>>  $rounds
     * @return array<int, array<string, mixed>>
     */
    private function roundStats(array $rounds): array
    {
        if ($rounds === []) {
            return [];
        }

        /* ตอบครบทุกรอบที่ถึงกำหนดแล้ว — นับรายคน ไม่ใช่เอา min ของแต่ละรอบมาชน */
        $complete = DB::table('ptp_cohort_profiles')
            ->whereNull('stopped_at')
            ->whereExists(fn (Builder $q) => $q->from('ptp_follow_up_rounds')
                ->whereColumn('ptp_follow_up_rounds.cohort_profile_id', 'ptp_cohort_profiles.id'))
            ->whereNotExists(fn (Builder $q) => $q->from('ptp_follow_up_rounds')
                ->whereColumn('ptp_follow_up_rounds.cohort_profile_id', 'ptp_cohort_profiles.id')
                ->whereNull('ptp_follow_up_rounds.answered_at'))
            ->count();

        $incomplete = DB::table('ptp_cohort_profiles')
            ->whereNull('stopped_at')
            ->whereExists(fn (Builder $q) => $q->from('ptp_follow_up_rounds')
                ->whereColumn('ptp_follow_up_rounds.cohort_profile_id', 'ptp_cohort_profiles.id')
                ->whereNull('ptp_follow_up_rounds.answered_at'))
            ->count();

        $lastRound = end($rounds);

        return [
            [
                'value' => number_format($complete),
                'label' => 'ตอบครบทุกรอบที่ถึงกำหนด',
                'tone' => 'good',
            ],
            [
                'value' => number_format($incomplete),
                'label' => 'ขาดการตอบอย่างน้อย 1 รอบ · ต้องติดตาม',
                'tone' => $incomplete > 0 ? 'warn' : 'good',
            ],
            [
                'value' => $lastRound['pct'].'%',
                'label' => 'อัตราตอบกลับรอบ '.$lastRound['label'],
                'tone' => 'info',
            ],
        ];
    }

    /* ==========================================================================
       แถว 4 — ผลประเมินก่อนและหลังเข้าร่วม
       ========================================================================== */

    /**
     * คะแนนรายหัวข้อ ก่อนเข้าร่วม vs รอบสุดท้ายที่มีคำตอบ
     *
     * หัวข้อ = evl_questions.dimension (คอลัมน์นี้มีไว้จัดกลุ่มรายด้านโดยเฉพาะ)
     * คะแนนดิบเป็น 1..score_max แปลงเป็น % ด้วย (avg - 1) / (max - 1)
     * ไม่ใช่ avg / max เพราะคะแนนต่ำสุดที่ตอบได้คือ 1 ไม่ใช่ 0
     * ถ้าหารด้วย max ตรง ๆ คนที่ตอบต่ำสุดทุกข้อจะได้ 20% ไม่ใช่ 0%
     *
     * @return array<string, mixed>
     */
    private function assessment(): array
    {
        $scoreMax = max(2, (int) config('farmconcept.assessment_score_max'));

        $rounds = DB::table('ptp_follow_up_rounds')
            ->join('evl_survey_responses', 'evl_survey_responses.cohort_round_id', '=', 'ptp_follow_up_rounds.id')
            ->distinct()
            ->orderBy('ptp_follow_up_rounds.offset_days')
            ->pluck('ptp_follow_up_rounds.offset_days');

        if ($rounds->count() < 2) {
            /* ต้องมีอย่างน้อยสองรอบถึงจะเทียบ "ก่อน" กับ "หลัง" ได้
               คีย์ต้องครบชุดเดียวกับกรณีมีข้อมูล ไม่งั้น view ต้องเช็ก isset ทุกจุด */
            return [
                'base' => 0,
                'before_label' => '',
                'after_label' => '',
                'topics' => [],
                'chart' => $this->trendChart([]),
                'insights' => [],
            ];
        }

        $firstOffset = (int) $rounds->first();
        $lastOffset = (int) $rounds->last();

        $before = $this->dimensionScores($firstOffset, $scoreMax);
        $after = $this->dimensionScores($lastOffset, $scoreMax);

        /* เอาเฉพาะหัวข้อที่มีคำตอบทั้งสองรอบ — หัวข้อที่มีข้างเดียวลากเส้นเทียบไม่ได้ */
        $topics = $before
            ->keys()
            ->filter(fn (string $dimension) => $after->has($dimension))
            ->values()
            ->map(fn (string $dimension) => [
                'label' => $dimension,
                'before' => round($before[$dimension], 1),
                'after' => round($after[$dimension], 1),
                'gain' => round($after[$dimension] - $before[$dimension], 1),
            ])
            ->all();

        return [
            'base' => (int) DB::table('evl_survey_responses')
                ->join('ptp_follow_up_rounds', 'ptp_follow_up_rounds.id', '=', 'evl_survey_responses.cohort_round_id')
                ->where('ptp_follow_up_rounds.offset_days', $lastOffset)
                ->distinct()
                ->count('evl_survey_responses.participant_id'),
            'before_label' => $this->roundLabel($firstOffset),
            'after_label' => $this->roundLabel($lastOffset),
            'topics' => $topics,
            'chart' => $this->trendChart($topics),
            'insights' => $this->insights($topics, $lastOffset),
        ];
    }

    /**
     * เรขาคณิตของกราฟเส้นเปรียบเทียบก่อน/หลัง
     *
     * คิดบน viewBox 600x240 แล้วส่งตำแหน่งออกไปเป็นเปอร์เซ็นต์ เพื่อให้กราฟยืดตามความกว้างจริงได้
     * ป้าย % วางเหนือจุดก่อน ถ้าชนป้ายอื่นในคอลัมน์เดียวกันย้ายไปใต้จุด ถ้ายังชนอีกให้ซ่อน
     * (ค่ายังอ่านได้จาก tooltip และจากตาราง fallback) ตาม handoff หัวข้อ Interactions
     *
     * @param  array<int, array<string, mixed>>  $topics
     * @return array<string, mixed>
     */
    private function trendChart(array $topics): array
    {
        $count = count($topics);

        if ($count === 0) {
            return ['grid' => [], 'ticks' => [], 'series' => []];
        }

        /* เว้นขอบซ้าย/ขวาไว้ให้ป้ายตัวเลขของจุดริมสุดไม่ล้นกรอบ */
        $inset = 56;
        $span = 600 - ($inset * 2);
        $x = fn (int $index) => $count > 1 ? $inset + ($index * $span / ($count - 1)) : 300;
        $y = fn (float $percent) => 220 - ($percent / 100) * 200;

        /* ระยะเลื่อนป้ายจากจุด และระยะห่างต่ำสุดที่ยังอ่านแยกกันได้ — หน่วยเดียวกับ viewBox */
        $labelOffset = 20;
        $minGap = 18;

        $series = [
            ['key' => 'before', 'field' => 'before'],
            ['key' => 'after', 'field' => 'after'],
        ];

        /* จัดตำแหน่งป้ายทีละคอลัมน์ ไล่จากจุดที่อยู่สูงสุดลงมา
           จุดบนได้เลือก "เหนือ" ก่อน จุดล่างจึงเหลือ "ใต้" ซึ่งเป็นผลที่อ่านง่ายที่สุด */
        $labels = [];

        foreach (array_keys($topics) as $index) {
            $order = collect($series)
                ->map(fn (array $line) => ['key' => $line['key'], 'y' => $y((float) $topics[$index][$line['field']])])
                ->sortBy('y')
                ->values();

            $taken = [];

            foreach ($order as $point) {
                $candidates = [$point['y'] - $labelOffset, $point['y'] + $labelOffset];
                $chosen = null;

                foreach ($candidates as $candidate) {
                    $fits = collect($taken)->every(fn (float $used) => abs($used - $candidate) >= $minGap);

                    if ($fits) {
                        $chosen = $candidate;
                        $taken[] = $candidate;
                        break;
                    }
                }

                $labels[$index][$point['key']] = $chosen;
            }
        }

        return [
            'grid' => array_map(fn (int $percent) => [
                'y' => round($y($percent), 2),
                'is_base' => $percent === 0,
            ], [0, 25, 50, 75, 100]),
            'ticks' => array_map(fn (int $percent) => $percent.'%', [100, 75, 50, 25, 0]),
            'series' => array_map(fn (array $line) => [
                'key' => $line['key'],
                'points' => implode(' ', array_map(
                    fn (int $index) => round($x($index), 2).','.round($y((float) $topics[$index][$line['field']]), 2),
                    array_keys($topics)
                )),
                'dots' => array_map(fn (int $index) => [
                    'topic' => $topics[$index]['label'],
                    'value' => $topics[$index][$line['field']],
                    'left' => round(($x($index) / 600) * 100, 3),
                    'top' => round(($y((float) $topics[$index][$line['field']]) / 240) * 100, 3),
                    'label_top' => $labels[$index][$line['key']] === null
                        ? null
                        : round(($labels[$index][$line['key']] / 240) * 100, 3),
                ], array_keys($topics)),
            ], $series),
        ];
    }

    /**
     * คะแนนเฉลี่ยรายหัวข้อของรอบหนึ่ง เป็นเปอร์เซ็นต์ 0–100
     *
     * @return Collection<string, float>
     */
    private function dimensionScores(int $offsetDays, int $scoreMax): Collection
    {
        return DB::table('evl_answers')
            ->join('evl_questions', 'evl_questions.id', '=', 'evl_answers.question_id')
            ->join('evl_survey_responses', 'evl_survey_responses.id', '=', 'evl_answers.response_id')
            ->join('ptp_follow_up_rounds', 'ptp_follow_up_rounds.id', '=', 'evl_survey_responses.cohort_round_id')
            ->where('evl_answers.response_type', 'survey')
            ->whereNotNull('evl_answers.score')
            ->whereNotNull('evl_questions.dimension')
            ->where('ptp_follow_up_rounds.offset_days', $offsetDays)
            ->groupBy('evl_questions.dimension')
            /* ต้องตั้ง alias ให้ค่าเฉลี่ยแล้ว pluck จาก alias นั้น
               ใส่ DB::raw() เป็นชื่อคอลัมน์ของ pluck ตรง ๆ ไม่ได้ — Laravel หา property
               ชื่อ "avg(evl_answers.score)" ในผลลัพธ์ไม่เจอ แล้วคืน null ทุกแถวเงียบ ๆ
               ทำให้คะแนนทุกหัวข้อกลายเป็น 0% โดยไม่มี error (เจอตอนมีข้อมูลจริงเข้ามา) */
            ->select('evl_questions.dimension', DB::raw('avg(evl_answers.score) as average'))
            ->pluck('average', 'evl_questions.dimension')
            ->map(fn ($average) => max(0, min(100, (((float) $average - 1) / ($scoreMax - 1)) * 100)));
    }

    /** ชื่อรอบที่ผู้ใช้อ่าน — ใช้ชื่อที่บันทึกไว้จริง ไม่แปลงจากจำนวนวันเอง */
    private function roundLabel(int $offsetDays): string
    {
        return (string) DB::table('ptp_follow_up_rounds')
            ->where('offset_days', $offsetDays)
            ->value('name');
    }

    /**
     * แผง "ผลวิเคราะห์แนวโน้ม" — 4 ข้อสรุปที่คำนวณจากตัวเลข ไม่ใช่ข้อความตายตัว
     *
     * handoff ระบุให้คำนวณจากข้อมูลที่ได้ ไม่ให้ backend ส่งประโยคมา
     * ที่นี่คือชั้นเดียวกันกับที่หน้าจอ render — รับ $topics ที่คำนวณเสร็จแล้วเข้ามา
     * ไม่ยิง query เพิ่มเพื่อหาข้อสรุป ยกเว้นจำนวนคนที่ยังไม่ตอบรอบสุดท้าย
     *
     * @param  array<int, array<string, mixed>>  $topics
     * @return array<int, array<string, mixed>>
     */
    private function insights(array $topics, int $lastOffset): array
    {
        if ($topics === []) {
            return [];
        }

        $collection = collect($topics);
        $avgBefore = $collection->avg('before');
        $avgAfter = $collection->avg('after');
        $best = $collection->sortByDesc('gain')->first();
        $worst = $collection->sortBy('after')->first();

        $pending = DB::table('ptp_follow_up_rounds')
            ->join('ptp_cohort_profiles', 'ptp_cohort_profiles.id', '=', 'ptp_follow_up_rounds.cohort_profile_id')
            ->whereNull('ptp_cohort_profiles.stopped_at')
            ->where('ptp_follow_up_rounds.offset_days', $lastOffset)
            ->whereNull('ptp_follow_up_rounds.answered_at')
            ->count();

        $lastLabel = $this->roundLabel($lastOffset);
        $gap = $avgAfter - $avgBefore;

        return [
            [
                'icon' => 'trend',
                'tone' => $gap >= 0 ? 'good' : 'warn',
                'value' => ($gap >= 0 ? '+' : '').number_format($gap, 1),
                'title' => 'จุดโดยเฉลี่ย',
                'note' => 'ค่าเฉลี่ยทุกหัวข้อเปลี่ยนจาก '.number_format($avgBefore, 1)
                    .'% เป็น '.number_format($avgAfter, 1).'%'
                    .' · ดีขึ้น '.$collection->where('gain', '>', 0)->count()
                    .' จาก '.$collection->count().' หัวข้อ',
            ],
            [
                'icon' => 'star',
                'tone' => 'good',
                'value' => ($best['gain'] >= 0 ? '+' : '').number_format($best['gain'], 1),
                'title' => $best['label'],
                'note' => 'พัฒนามากที่สุด แนะนำถอดบทเรียนหลักสูตรนี้ไปใช้กับหัวข้ออื่น',
            ],
            [
                'icon' => 'alert',
                'tone' => 'warn',
                'value' => number_format($worst['after'], 1).'%',
                'title' => $worst['label'],
                'note' => 'ยังต่ำที่สุดหลังเข้าร่วม ควรเพิ่มกิจกรรมและติดตามกลุ่มนี้เป็นพิเศษ',
            ],
            [
                'icon' => 'clock',
                'tone' => $pending > 0 ? 'muted' : 'good',
                'value' => number_format($pending),
                'title' => 'รายที่ยังไม่ตอบรอบ'.($lastLabel ? ' '.$lastLabel : 'สุดท้าย'),
                'note' => $pending > 0
                    ? 'ตามให้ครบจะทำให้แนวโน้มนี้แม่นยำขึ้น'
                    : 'ตอบครบทุกรายแล้ว แนวโน้มนี้ใช้อ้างอิงได้เต็มที่',
            ],
        ];
    }

    /* ==========================================================================
       แถว 5 — พื้นที่ดำเนินงาน
       ========================================================================== */

    /**
     * จำนวนกิจกรรมรายพื้นที่ (treemap) และกลุ่มตัวอย่างรายพื้นที่ (แท่งซ้อน)
     *
     * @return array<string, mixed>
     */
    private function areas(?Carbon $since, int $cohortTotal): array
    {
        $rows = DB::table('act_activity_area')
            ->join('act_activities', 'act_activities.id', '=', 'act_activity_area.activity_id')
            ->join('mst_areas', 'mst_areas.id', '=', 'act_activity_area.area_id')
            ->whereNull('act_activities.deleted_at')
            ->where('act_activities.status', '!=', Activity::STATUS_DRAFT)
            ->when($since, fn (Builder $q) => $q->where('act_activities.start_date', '>=', $since->toDateString()))
            ->select('mst_areas.name', DB::raw('count(distinct act_activities.id) as activities'))
            ->groupBy('mst_areas.id', 'mst_areas.name')
            ->orderByDesc('activities')
            ->orderBy('mst_areas.name')
            ->get();

        $shown = $rows->take(self::TREEMAP_LIMIT);
        $total = (int) $rows->sum('activities');

        return [
            'activity_total' => $total,
            'area_count' => $rows->count(),
            'hidden_count' => max($rows->count() - $shown->count(), 0),
            'treemap' => $this->treemap($shown, $total),
            'target_groups' => $this->targetGroupLegend(),
            'samples' => $this->cohortByArea($cohortTotal),
        ];
    }

    /**
     * Treemap แบบ squarified — คืนกล่องเป็นเปอร์เซ็นต์ของกรอบ
     *
     * คิดบนกรอบ 100x100 หน่วย จึงไม่ต้องรู้ขนาดจริงของ container
     * และ CSS จัดวางด้วย left/top/width/height เป็น % ได้เลย
     * อัลกอริทึมเดียวกับต้นแบบ: ต่อกล่องเข้าแถวจนอัตราส่วนด้านเริ่มแย่ลงแล้วตัดแถว
     *
     * @param  Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function treemap(Collection $rows, int $total): array
    {
        if ($total <= 0 || $rows->isEmpty()) {
            return [];
        }

        $items = $rows->values()->map(fn (object $row, int $index) => [
            'label' => $row->name,
            'count' => (int) $row->activities,
            'rank' => $index,
            'area' => ((int) $row->activities / $total) * 100 * 100,
        ])->all();

        $boxes = [];
        $x = 0.0;
        $y = 0.0;
        $width = 100.0;
        $height = 100.0;
        $i = 0;
        $count = count($items);

        /* อัตราส่วนด้านที่แย่ที่สุดของแถวหนึ่ง — ยิ่งใกล้ 1 ยิ่งเป็นสี่เหลี่ยมจัตุรัส */
        $worst = function (array $row, float $side): float {
            $sum = array_sum(array_column($row, 'area'));
            $thickness = $side > 0 ? $sum / $side : 0;

            if ($thickness <= 0) {
                return INF;
            }

            return max(array_map(function (array $item) use ($thickness) {
                $length = $item['area'] / $thickness;

                return $length > 0 ? max($thickness / $length, $length / $thickness) : INF;
            }, $row));
        };

        while ($i < $count) {
            $vertical = $width >= $height;
            $side = $vertical ? $height : $width;

            $row = [$items[$i]];
            $best = $worst($row, $side);
            $j = $i;

            while ($j + 1 < $count) {
                $candidate = array_merge($row, [$items[$j + 1]]);
                $score = $worst($candidate, $side);

                if ($score > $best) {
                    break;
                }

                $row = $candidate;
                $best = $score;
                $j++;
            }

            $thickness = $side > 0 ? array_sum(array_column($row, 'area')) / $side : 0;
            $offset = 0.0;

            foreach ($row as $item) {
                $length = $thickness > 0 ? $item['area'] / $thickness : 0;

                $boxes[] = $item + ($vertical
                    ? ['x' => $x, 'y' => $y + $offset, 'w' => $thickness, 'h' => $length]
                    : ['x' => $x + $offset, 'y' => $y, 'w' => $length, 'h' => $thickness]);

                $offset += $length;
            }

            if ($vertical) {
                $x += $thickness;
                $width -= $thickness;
            } else {
                $y += $thickness;
                $height -= $thickness;
            }

            $i = $j + 1;
        }

        return array_map(fn (array $box) => [
            'label' => $box['label'],
            'count' => $box['count'],
            'pct' => $this->percentText($box['count'], $total),
            'rank' => min($box['rank'], self::SCALE_STEPS - 1),
            'left' => round($box['x'], 3),
            'top' => round($box['y'], 3),
            'width' => round($box['w'], 3),
            'height' => round($box['h'], 3),
            /* กล่องเตี้ยซ่อนชื่อไว้ เหลือแต่ตัวเลข — JS วัดขนาดจริงแล้วปรับให้อีกชั้น */
            'compact' => $box['h'] < 21,
        ], $boxes);
    }

    /** @return array<int, array<string, mixed>> */
    private function targetGroupLegend(): array
    {
        return DB::table('mst_target_groups')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('name')
            ->values()
            ->map(fn (string $name, int $index) => [
                'label' => $name,
                'rank' => min($index, self::SCALE_STEPS - 1),
            ])
            ->all();
    }

    /**
     * กลุ่มตัวอย่างแต่ละพื้นที่ แยกตามกลุ่มเป้าหมาย (แท่งซ้อน)
     *
     * ความยาวแท่งรวมเทียบกับพื้นที่ที่มากที่สุด ส่วนแต่ละท่อนเป็นสัดส่วนภายในพื้นที่นั้น
     * จึงเทียบขนาดระหว่างพื้นที่และดูส่วนผสมภายในได้ในกราฟเดียว
     *
     * @return array<int, array<string, mixed>>
     */
    private function cohortByArea(int $cohortTotal): array
    {
        $rows = DB::table('ptp_cohort_profiles')
            ->join('ptp_participants', 'ptp_participants.id', '=', 'ptp_cohort_profiles.participant_id')
            ->leftJoin('mst_areas', 'mst_areas.id', '=', 'ptp_participants.area_id')
            ->leftJoin('mst_target_groups', 'mst_target_groups.id', '=', 'ptp_participants.target_group_id')
            ->whereNull('ptp_participants.deleted_at')
            ->whereNull('ptp_cohort_profiles.stopped_at')
            ->select(
                DB::raw("COALESCE(mst_areas.name, 'ไม่ระบุพื้นที่') as area"),
                DB::raw("COALESCE(mst_target_groups.name, 'ไม่ระบุกลุ่ม') as target_group"),
                DB::raw('count(*) as people')
            )
            ->groupBy('mst_areas.id', 'mst_areas.name', 'mst_target_groups.id', 'mst_target_groups.name')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $groups = collect($this->targetGroupLegend());
        $byArea = $rows->groupBy('area')
            ->map(fn (Collection $items) => [
                'total' => (int) $items->sum('people'),
                'by_group' => $items->pluck('people', 'target_group'),
            ])
            ->sortByDesc('total');

        $max = $byArea->max('total') ?: 1;

        return $byArea
            ->map(fn (array $area, string $name) => [
                'label' => $name,
                'count' => $area['total'],
                'pct' => $this->percentText($area['total'], $cohortTotal),
                'bar' => round(($area['total'] / $max) * 100, 2),
                'segments' => $groups
                    ->map(fn (array $group) => [
                        'label' => $group['label'],
                        'rank' => $group['rank'],
                        'count' => (int) $area['by_group']->get($group['label'], 0),
                        'width' => $area['total'] > 0
                            ? round(((int) $area['by_group']->get($group['label'], 0) / $area['total']) * 100, 2)
                            : 0,
                    ])
                    ->filter(fn (array $segment) => $segment['count'] > 0)
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /* ==========================================================================
       ตัวช่วยที่ใช้ร่วมกัน
       ========================================================================== */

    /** ใบลงทะเบียนในช่วงที่เลือก — จุดเริ่มต้นของทุก query ในแถว 2 */
    private function registrations(?Carbon $since): Builder
    {
        return DB::table('act_registrations')
            ->when($since, fn (Builder $q) => $q->where('act_registrations.registered_at', '>=', $since));
    }

    /**
     * กิจกรรมที่ "จัดจริง" — ตัดฉบับร่างออก เพราะยังไม่ได้เกิดขึ้น
     * นับรวมกิจกรรมที่ยังไม่ระบุวันเริ่มเมื่อดูช่วง "ทั้งหมด" แต่กรองออกเมื่อเลือกช่วงเวลา
     */
    private function heldActivities(?Carbon $since): Builder
    {
        return DB::table('act_activities')
            ->whereNull('deleted_at')
            ->where('status', '!=', Activity::STATUS_DRAFT)
            ->when($since, fn (Builder $q) => $q->where('start_date', '>=', $since->toDateString()));
    }

    /**
     * นิพจน์นับ "จำนวนคน" จากตารางใบลงทะเบียน
     *
     * ใบลงทะเบียนที่ยังไม่ผูก participant_id (กรอกหน้างาน) ใช้เบอร์โทรเป็นตัวระบุตัวตน
     * เติมคำนำหน้า p/h กันเลข id ไปชนกับเบอร์โทรที่หน้าตาเหมือนกัน
     *
     * ใช้ CASE ไม่ใช่ coalesce(concat(...), concat(...)) เพราะ CONCAT ตีความ NULL ไม่เหมือนกัน
     * ในแต่ละฐาน — MySQL คืน NULL เมื่อมีอาร์กิวเมนต์เป็น NULL แต่ SQLite ข้าม NULL ทิ้ง
     * ทำให้ทุกแถวยุบเหลือค่าเดียวและนับได้ 1 คนเสมอ (เจอตอนรันเทสต์บน SQLite)
     */
    private function personKey(string $alias): Expression
    {
        return DB::raw(
            'count(distinct case'
            ." when act_registrations.participant_id is null then concat('h', act_registrations.phone)"
            ." else concat('p', act_registrations.participant_id)"
            ." end) as {$alias}"
        );
    }

    private function distinctPeople(Builder $query): int
    {
        return (int) $query
            ->select($this->personKey('people'))
            ->value('people');
    }

    /** เปอร์เซ็นต์แบบข้อความ — ฐานเป็น 0 ต้องได้ขีด ไม่ใช่ 0.0% ที่อ่านเหมือนมีข้อมูลแล้ว */
    private function percentText(int $value, int $total): string
    {
        return $total > 0 ? number_format(($value / $total) * 100, 1).'%' : '—';
    }
}
