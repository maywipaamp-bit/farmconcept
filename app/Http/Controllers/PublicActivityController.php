<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFormat;
use App\Models\Form;
use App\Services\PublicActivityPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicActivityController extends Controller
{
    public function __construct(private readonly PublicActivityPresenter $presenter)
    {
    }

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

    public function show(Request $request, string $activity): View|RedirectResponse
    {
        /* ลิงก์เดิม ?action=registration (รวมถึง QR รุ่นแรก) ส่งต่อไปหน้าลงทะเบียนแบบใหม่ */
        if ($request->query('action') === 'registration') {
            return redirect()->route('public.activities.register', ['activity' => $activity]);
        }

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
                'registerUrl' => route('public.activities.register', $activity->code),
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
        return $this->presenter->present($activity);
    }
}
