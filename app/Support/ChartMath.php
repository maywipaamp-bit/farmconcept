<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * ตัวช่วยคำนวณเรขาคณิตของกราฟที่ใช้ร่วมกันหลายหน้ารายงาน
 *
 * แยกออกมาจากเดิมที่แต่ละคอนโทรลเลอร์เขียนสูตรเดียวกันซ้ำ (ActivityController::donutSegments,
 * ::rankedDonut, ::barList) — ที่นี่เป็นจุดเดียวสำหรับหน้ารายงานที่เพิ่มใหม่ ไม่แตะของเดิม
 * เพื่อไม่ให้กระทบหน้าจอที่ทดสอบผ่านแล้ว
 */
class ChartMath
{
    /**
     * โดนัทจากรายการ {label, count, tone} ที่นับมาแล้ว — ใช้กับ partials/report-donut
     *
     * @param  array<int, array{label: string, count: int, tone?: string}>  $items
     * @return array{total: int, segments: array<int, array<string, mixed>>}
     */
    public static function donut(array $items): array
    {
        $items = array_values(array_filter($items, fn (array $i) => $i['count'] > 0));
        $total = array_sum(array_column($items, 'count'));
        $circumference = 2 * M_PI * 76;
        $offset = 0.0;

        $segments = collect($items)->map(function (array $item, int $index) use ($total, $circumference, &$offset) {
            $length = $total > 0 ? ($item['count'] / $total) * $circumference : 0;

            $segment = [
                'label' => $item['label'],
                'count' => $item['count'],
                'pct' => $total > 0 ? (int) round($item['count'] / $total * 100) : 0,
                'dash' => round(max($length - 3, 0), 2).' '.round($circumference - max($length - 3, 0), 2),
                'offset' => round(-$offset, 2),
            ];

            $segment += isset($item['tone']) ? ['tone' => $item['tone']] : ['rank' => $index % 5];

            $offset += $length;

            return $segment;
        })->values()->all();

        return ['total' => $total, 'segments' => $segments];
    }

    /**
     * แท่งกราฟแนวนอนจาก {label, count} — เรียงมากไปน้อย ตัดเหลือ $limit อันดับแรก
     * ที่เหลือรวมเป็น "อื่น ๆ" ถ้า $rollUp เป็นจริง (ปิดได้เมื่อ label มีความหมายเฉพาะตัว เช่น ชื่อกิจกรรม)
     *
     * @param  Collection<int, array{label: string, count: int|float}>  $rows
     * @return array<int, array{label: string, count: int|float, pct: int}>
     */
    public static function barList(Collection $rows, int $limit = 8, bool $rollUp = true): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $sorted = $rows->sortByDesc('count')->values();
        $top = $sorted->take($limit);

        if ($rollUp && $sorted->count() > $limit) {
            $rest = $sorted->slice($limit)->sum('count');
            $top->push(['label' => 'อื่น ๆ', 'count' => $rest]);
        }

        $max = $top->max('count') ?: 1;

        return $top->map(fn (array $row) => [
            'label' => $row['label'],
            'count' => $row['count'],
            'pct' => (int) round(($row['count'] / $max) * 100),
        ])->values()->all();
    }

    /**
     * กราฟเส้นหลายชุดข้อมูลบนแกนหมวดหมู่เดียวกัน (เช่น รายเดือน) — เรขาคณิตชุดเดียวกับ
     * DashboardService::trendChart แต่ทำให้ทั่วไปขึ้น: สเกลแกน Y คำนวณจากค่าจริง ไม่ใช่ตรึงที่ 0–100
     *
     * @param  array<int, string>  $categories  ป้ายแกน X เช่น ['ม.ค. 69', 'ก.พ. 69', ...]
     * @param  array<int, array{key: string, label: string, values: array<int, float>}>  $series
     * @param  float|null  $fixedMax  บังคับเพดานแกน Y (ใช้กับสเกลคะแนน 1–5 หรือ % ที่ต้องคงที่ทุกครั้ง)
     * @return array{grid: array, ticks: array, series: array, categories: array}
     */
    public static function trendLine(array $categories, array $series, ?float $fixedMax = null): array
    {
        $count = count($categories);

        if ($count === 0 || $series === []) {
            return ['grid' => [], 'ticks' => [], 'series' => [], 'categories' => []];
        }

        $allValues = collect($series)->flatMap(fn (array $s) => $s['values'])->filter(fn ($v) => $v !== null);
        $max = $fixedMax ?? max(1.0, (float) ($allValues->max() ?? 1));

        /* ปัดเพดานขึ้นให้เป็นเลขกลม ๆ กราฟจะได้ไม่มีเศษแปลก ๆ บนเส้นกริดบนสุด */
        if ($fixedMax === null) {
            $magnitude = 10 ** floor(log10(max($max, 1)));
            $max = ceil($max / $magnitude) * $magnitude;
        }

        $inset = 56;
        $span = 600 - ($inset * 2);
        $x = fn (int $index) => $count > 1 ? $inset + ($index * $span / ($count - 1)) : 300;
        $y = fn (?float $value) => $value === null ? null : 220 - (($value / $max) * 200);

        $chartSeries = collect($series)->map(function (array $s) use ($x, $y) {
            $points = collect($s['values'])->map(fn (?float $v, int $i) => $v === null ? null : round($x($i), 2).','.round($y($v), 2))
                ->filter()
                ->implode(' ');

            return [
                'key' => $s['key'],
                'label' => $s['label'],
                'points' => $points,
                /* เก็บ null ไว้ตามตำแหน่งเดิม ไม่ filter ทิ้ง — เดือนที่ไม่มีข้อมูลต้องยังตรงกับ
                   ป้ายเดือนในตาราง/แกน X ถ้า reindex จะเลื่อนหลุดตำแหน่งตั้งแต่เดือนแรกที่ขาดข้อมูล */
                'dots' => collect($s['values'])->map(fn (?float $v, int $i) => $v === null ? null : [
                    'value' => $v,
                    'left' => round(($x($i) / 600) * 100, 3),
                    'top' => round(($y($v) / 240) * 100, 3),
                ])->all(),
            ];
        })->values()->all();

        return [
            'categories' => $categories,
            'grid' => array_map(fn (int $i) => [
                'y' => round(220 - ($i * 200 / 4), 2),
                'is_base' => $i === 0,
            ], [0, 1, 2, 3, 4]),
            'ticks' => array_map(fn (int $i) => self::formatTick($max * $i / 4), [4, 3, 2, 1, 0]),
            'series' => $chartSeries,
        ];
    }

    private static function formatTick(float $value): string
    {
        return $value == floor($value) ? number_format($value, 0) : number_format($value, 1);
    }
}
