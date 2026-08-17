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

        /* แบบประเมินย้ายไปหน้าของตัวเองแล้ว — ลิงก์เดิม ?action=post-survey ต้องยังใช้ได้
           (QR ที่พิมพ์แจกไปแล้วชี้มาที่นี่ผ่าน /s/{token} เรียกคืนไม่ได้) */
        if ($request->query('action') === 'post-survey') {
            return redirect()->route('public.activities.survey', $activity->code);
        }

        $registrationForm = $activity->forms->first(fn (Form $form) => $form->pivot->slot === 'registration'
            && $form->type === Form::TYPE_REGISTRATION);
        $canRegister = $activity->acceptsRegistration() && $registrationForm !== null;
        $checkinRequested = $request->query('action') === 'checkin';

        return view('public.activities.show', [
            'activity' => $this->present($activity),
            'registration' => [
                'enabled' => $canRegister,
                'closed' => $canRegister ? null : $this->registrationClosedReason($activity, $registrationForm),
                'registerUrl' => route('public.activities.register', $activity->code),
            ],
            'checkin' => [
                'requested' => $checkinRequested,
                'enabled' => $checkinRequested && $activity->acceptsCheckin(),
                'lookupUrl' => route('public.activities.checkin.lookup', $activity->code),
                'storeUrl' => route('public.activities.checkin.store', $activity->code),
            ],
        ]);
    }

    /**
     * เหตุผลที่ยังลงทะเบียนไม่ได้ — ไล่ตามเงื่อนไขของ acceptsRegistration() ทีละข้อ
     *
     * ของเดิมขึ้นข้อความเดียวว่า "ยังไม่เปิดรับลงทะเบียน" ทุกกรณี ซึ่งบอกผิดเมื่อจริง ๆ แล้ว
     * ปิดรับไปแล้วหรือที่นั่งเต็ม ผู้ใช้จึงไม่รู้ว่าควรรอ ควรเลิกรอ หรือควรติดต่อเจ้าหน้าที่
     *
     * @return array{title: string, message: string}
     */
    private function registrationClosedReason(Activity $activity, ?Form $registrationForm): array
    {
        $now = now();

        if ($activity->status === Activity::STATUS_CANCELLED) {
            return ['title' => 'กิจกรรมนี้ถูกยกเลิก', 'message' => 'ติดตามกิจกรรมอื่นได้ที่หน้ารายการกิจกรรม'];
        }

        if ($activity->registration_start_at && $activity->registration_start_at->gt($now)) {
            return [
                'title' => 'ยังไม่เปิดรับลงทะเบียน',
                'message' => 'จะเปิดรับสมัครวันที่ '.$this->presenter->thaiDateTime($activity->registration_start_at),
            ];
        }

        if ($activity->registration_end_at && $activity->registration_end_at->lt($now)) {
            return [
                'title' => 'ปิดรับลงทะเบียนแล้ว',
                'message' => 'ปิดรับสมัครเมื่อ '.$this->presenter->thaiDateTime($activity->registration_end_at),
            ];
        }

        if ($activity->capacity > 0 && $activity->seatsLeft() <= 0) {
            return ['title' => 'ที่นั่งเต็มแล้ว', 'message' => 'ขออภัย กิจกรรมนี้มีผู้ลงทะเบียนครบจำนวนแล้ว'];
        }

        /* ไม่มีแบบฟอร์มผูกไว้ = ผู้ดูแลยังตั้งค่าไม่ครบ ไม่ใช่ความผิดของผู้ใช้ จึงไม่ลงรายละเอียดทางเทคนิค */
        if (! $registrationForm) {
            return ['title' => 'ยังไม่เปิดรับลงทะเบียน', 'message' => 'กิจกรรมนี้ยังไม่เปิดให้ลงทะเบียนผ่านเว็บไซต์'];
        }

        return ['title' => 'ยังไม่เปิดรับลงทะเบียน', 'message' => 'กิจกรรมนี้ยังไม่อยู่ในช่วงรับสมัคร'];
    }

    private function present(Activity $activity): array
    {
        return $this->presenter->present($activity);
    }
}
