<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFormat;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicActivityController extends Controller
{
    public function page(): View
    {
        $categories = ActivityFormat::active()
            ->orderBy('id')
            ->get(['name', 'icon'])
            ->map(fn (ActivityFormat $format) => [
                'name' => $format->name,
                'icon' => $format->icon,
            ])
            ->values();

        $activities = Activity::forPublicListing()
            ->orderBy('public_sort_order')
            ->orderBy('start_date')
            ->get()
            ->map(fn (Activity $activity) => $this->present($activity))
            ->values();

        return view('public.activities.index', compact('activities', 'categories'));
    }

    public function index(): JsonResponse
    {
        $activities = Activity::forPublicListing()
            ->orderBy('public_sort_order')
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'activities' => $activities->map(fn (Activity $activity) => $this->present($activity)),
        ]);
    }

    public function show(string $activity): View
    {
        $activity = Activity::forPublicListing()
            ->where('code', $activity)
            ->firstOrFail();

        return view('public.activities.show', [
            'activity' => $this->present($activity),
        ]);
    }

    private function present(Activity $activity): array
    {
        $round = $activity->rounds->first();
        $date = $round?->round_date ?? $activity->start_date;
        $startTime = $round?->time_start ? substr((string) $round->time_start, 0, 5) : null;
        $endTime = $round?->time_end ? substr((string) $round->time_end, 0, 5) : null;
        $timeLabel = $startTime
            ? $startTime.($endTime ? '–'.$endTime : '').' น.'
            : null;

        return [
            'code' => $activity->code,
            'title' => $activity->name,
            'description' => $activity->description,
            'type' => $activity->type,
            'category' => $activity->format?->name,
            'categoryIcon' => $activity->format?->icon,
            'image' => $activity->cover_image_path ? '/storage/'.ltrim($activity->cover_image_path, '/') : null,
            'date' => $date?->toDateString(),
            'dateLabel' => $this->thaiDate($date),
            'startTime' => $startTime,
            'endTime' => $endTime,
            'timeLabel' => $timeLabel,
            'scheduleLabel' => collect([$this->thaiDate($date), $timeLabel])->filter()->join(' · '),
            'location' => $round?->location ?: '-',
            'speaker' => $activity->instructors->pluck('name')->join(', ') ?: ($activity->organizer ?: '-'),
            'fee' => (float) $activity->fee,
            'isFree' => ! $activity->has_fee,
            'priceLabel' => $activity->has_fee
                ? number_format((float) $activity->fee, 0).' บาท / ท่าน'
                : 'เข้าร่วมฟรี',
            'isFeatured' => $activity->is_featured,
            'registrationStartAt' => $activity->registration_start_at?->toIso8601String(),
            'registrationEndAt' => $activity->registration_end_at?->toIso8601String(),
            'registrationDeadlineLabel' => $activity->registration_end_at
                ? 'ปิดรับสมัคร '.$this->thaiDateTime($activity->registration_end_at)
                : null,
            'canRegister' => $activity->acceptsRegistration(),
            'seatsLeft' => $activity->seatsLeft(),
            'sortOrder' => $activity->public_sort_order,
        ];
    }

    private function thaiDate(?CarbonInterface $date): ?string
    {
        if (! $date) {
            return null;
        }

        $days = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
        $months = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $days[$date->dayOfWeek].' '
            .$date->day.' '.$months[$date->month].' '
            .str_pad((string) (($date->year + 543) % 100), 2, '0', STR_PAD_LEFT);
    }

    private function thaiDateTime(CarbonInterface $date): string
    {
        return $this->thaiDate($date).' · '.$date->format('H:i').' น.';
    }
}
