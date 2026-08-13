<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFormat;
use App\Models\Form;
use App\Services\PublicRegistrationService;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function show(Request $request, string $activity, PublicRegistrationService $registrationService): View
    {
        $activity = Activity::forPublicListing()
            ->where('code', $activity)
            ->firstOrFail();

        $activity->load([
            'rounds' => fn ($query) => $query
                ->withCount('registrations')
                ->orderBy('round_date')
                ->orderBy('time_start'),
            'forms' => fn ($query) => $query
                ->whereIn('evl_form_activity.slot', ['registration', 'post_survey'])
                ->where('status', Form::STATUS_ACTIVE)
                ->with(['questions.options']),
        ]);

        $registrationForm = $activity->forms->first(fn (Form $form) => $form->pivot->slot === 'registration'
            && $form->type === Form::TYPE_REGISTRATION);
        $postSurveyForm = $activity->forms->first(fn (Form $form) => $form->pivot->slot === 'post_survey'
            && $form->type === Form::TYPE_POST_ACTIVITY);
        $canRegister = $activity->acceptsRegistration() && $registrationForm !== null;
        $checkinRequested = $request->query('action') === 'checkin';
        $postSurveyRequested = $request->query('action') === 'post-survey';

        return view('public.activities.show', [
            'activity' => $this->present($activity),
            'registration' => [
                'enabled' => $canRegister,
                'open' => $canRegister && $request->query('action') === 'registration',
                'maxSeats' => $registrationService->maxSeats($activity),
                'checkPhoneUrl' => route('public.activities.registration.check-phone', $activity->code),
                'storeUrl' => route('public.activities.registration.store', $activity->code),
                'rounds' => $activity->rounds->map(fn ($round) => [
                    'id' => $round->id,
                    'label' => $this->thaiDate($round->round_date)
                        .' · '.substr((string) $round->time_start, 0, 5)
                        .'–'.substr((string) $round->time_end, 0, 5).' น.',
                    'seatsLeft' => $round->capacity > 0
                        ? max(0, $round->capacity - $round->registrations_count)
                        : null,
                ])->values(),
            ],
            'checkin' => [
                'requested' => $checkinRequested,
                'enabled' => $checkinRequested && $activity->acceptsCheckin(),
                'lookupUrl' => route('public.activities.checkin.lookup', $activity->code),
                'storeUrl' => route('public.activities.checkin.store', $activity->code),
            ],
            'postSurvey' => [
                'requested' => $postSurveyRequested,
                'enabled' => $postSurveyRequested && $activity->acceptsPostSurvey() && $postSurveyForm !== null,
                'form' => $postSurveyForm,
                'storeUrl' => route('public.activities.post-survey.store', $activity->code),
            ],
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
            'requiresRegistration' => $activity->requires_registration,
            'requiresCheckin' => $activity->requires_checkin,
            'hasPostSurvey' => $activity->has_post_survey,
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
