<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicRegistrationRequest;
use App\Models\Activity;
use App\Models\ConsentDocument;
use App\Models\Form;
use App\Models\Option;
use App\Models\PaymentAccount;
use App\Models\Registration;
use App\Models\SystemSetting;
use App\Services\LineLoginService;
use App\Services\PublicActivityPresenter;
use App\Services\PublicRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    public function __construct(
        private readonly PublicActivityPresenter $presenter,
        private readonly PublicRegistrationService $service,
    ) {
    }

    /** หน้า flow ลงทะเบียน 6 ขั้น — ถ้ากิจกรรมไม่เปิดรับ ส่งกลับหน้ารายละเอียดซึ่งมีการ์ดอธิบายอยู่แล้ว */
    public function page(string $activity): View|RedirectResponse
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
                ->where('evl_form_activity.slot', 'registration')
                ->where('status', Form::STATUS_ACTIVE)
                ->with('fields'),
        ]);

        $form = $this->service->registrationForm($activity);

        if (! $activity->acceptsRegistration() || ! $form) {
            return redirect()->route('public.activities.show', $activity->code);
        }

        $options = Option::query()
            ->whereIn('option_group', ['age_range', 'occupation', 'source_channel'])
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'option_group', 'label'])
            ->groupBy('option_group')
            ->map(fn ($group) => $group->map(fn (Option $option) => [
                'id' => $option->id,
                'label' => $option->label,
            ])->values());

        /* ฟิลด์เสริมเปิด/ปิดตามที่แบบลงทะเบียนของกิจกรรมกำหนด — ไม่มีแถวถือว่าเปิดแบบไม่บังคับ */
        $fieldConfig = $form->fields->keyBy('field_key');
        $fields = collect(['email', 'age_range', 'occupation', 'source_channel'])
            ->mapWithKeys(function (string $key) use ($fieldConfig) {
                $field = $fieldConfig->get($key);

                return [$key => [
                    'enabled' => $field ? $field->is_enabled : true,
                    'required' => $field ? ($field->is_enabled && $field->is_required) : false,
                ]];
            });

        $account = PaymentAccount::query()->where('is_active', true)->first();
        $paymentRequired = $activity->has_fee && (float) $activity->fee > 0;

        $settings = SystemSetting::query()
            ->whereIn('setting_key', ['line_url', 'privacy_policy_url', 'terms_url'])
            ->pluck('setting_value', 'setting_key');

        /* โปรไฟล์ LINE อยู่ใน session ของผู้เข้าชม ไม่ใช่ค่าที่ส่งมากับคำขอ
           เคยลงทะเบียนกิจกรรมนี้ด้วยบัญชีนี้แล้วให้ข้ามไปหน้าจอ "ลงทะเบียนแล้ว" ได้เลย */
        $lineProfile = session(PublicLineLoginController::SESSION_KEY);
        $lineBooking = null;
        $linePrefill = null;
        $lineHistory = [];

        if ($lineProfile) {
            $registrations = $this->service->findByLineUserId($activity, $lineProfile['userId']);

            $lineBooking = $registrations->isNotEmpty()
                ? $this->bookingPayload($activity, $registrations)
                : null;

            $linePrefill = $this->service->lastContactForLineUser($lineProfile['userId']);
            $linePrefill['name'] = $linePrefill['name'] ?: $lineProfile['displayName'];

            /* ประวัติกิจกรรมอื่นที่บัญชี LINE นี้เคยลงทะเบียน — แสดงบนหน้าจอ "ลงทะเบียนแล้ว" */
            $lineHistory = $this->service
                ->historyForLineUser($lineProfile['userId'], $activity->id)
                ->map(fn (Registration $registration) => [
                    'title' => $registration->activity->name,
                    'date' => $this->presenter->thaiDate($registration->activity->start_date) ?: '-',
                    'url' => route('public.activities.show', $registration->activity->code),
                    'paymentLabel' => $registration->payment_status ?: 'ลงทะเบียนแล้ว',
                ])
                ->values()
                ->all();
        }

        return view('public.activities.register', [
            'activity' => $this->presenter->present($activity),
            'lineError' => session('lineError'),
            'config' => [
                'activity' => [
                    'code' => $activity->code,
                    'title' => $activity->name,
                    'image' => $activity->cover_image_path ? '/storage/'.ltrim($activity->cover_image_path, '/') : null,
                    'scheduleLabel' => $this->presenter->present($activity)['scheduleLabel'],
                    'location' => $activity->rounds->first()?->location ?: '-',
                    'fee' => (float) $activity->fee,
                    'isFree' => ! $paymentRequired,
                ],
                'maxSeats' => $this->service->maxSeats($activity),
                'rounds' => $activity->rounds->count() > 1
                    ? $activity->rounds->map(fn ($round) => [
                        'id' => $round->id,
                        'label' => $this->presenter->thaiDate($round->round_date)
                            .' · '.substr((string) $round->time_start, 0, 5)
                            .'–'.substr((string) $round->time_end, 0, 5).' น.',
                        'seatsLeft' => $round->capacity > 0
                            ? max(0, $round->capacity - $round->registrations_count)
                            : null,
                    ])->values()
                    : [],
                'fields' => $fields,
                'options' => [
                    'ageRanges' => $options->get('age_range', collect())->values(),
                    'occupations' => $options->get('occupation', collect())->values(),
                    'sources' => $options->get('source_channel', collect())->values(),
                ],
                'payment' => [
                    'required' => $paymentRequired,
                    'amountPerSeat' => (float) $activity->fee,
                    'bankName' => $account ? config('farmconcept.banks.'.$account->bank_code, $account->bank_code) : null,
                    'accountName' => $account?->account_name,
                    'accountNumber' => $account?->account_number,
                    'qrUrl' => $account?->qr_code_path
                        ? '/storage/'.ltrim($account->qr_code_path, '/')
                        : null,
                ],
                'urls' => [
                    'check' => route('public.activities.registration.check', $activity->code),
                    'store' => route('public.activities.registration.store', $activity->code),
                    'payment' => route('public.activities.registration.payment', $activity->code),
                    'activity' => route('public.activities.show', $activity->code),
                    'home' => route('public.activities'),
                ],
                'links' => [
                    'line' => $settings->get('line_url') ?: null,
                    'privacy' => $settings->get('privacy_policy_url') ?: null,
                    'terms' => $settings->get('terms_url') ?: null,
                ],
                /* เอกสารความยินยอมฉบับที่เปิดใช้งาน (จากหน้าแอดมิน master/consent-documents)
                   กดลิงก์ในบรรทัดยอมรับแล้วเปิดอ่านเป็น popup — terms = เงื่อนไขการเข้าร่วม, pdpa = นโยบายความเป็นส่วนตัว */
                'consentDocs' => [
                    'terms' => $this->consentDoc('terms'),
                    'privacy' => $this->consentDoc('pdpa'),
                ],
                /* เข้าสู่ระบบด้วย LINE — enabled=false เมื่อยังไม่ได้ตั้ง channel ในเซิร์ฟเวอร์
                   หน้าจอจะซ่อนปุ่มไปเลย ไม่ใช่ให้กดแล้วเจอหน้า error */
                'line' => [
                    'enabled' => app(LineLoginService::class)->isConfigured(),
                    'loginUrl' => route('public.line.redirect', $activity->code),
                    'logoutUrl' => route('public.line.logout', $activity->code),
                    'profile' => $lineProfile ? [
                        'displayName' => $lineProfile['displayName'],
                        'pictureUrl' => $lineProfile['pictureUrl'],
                    ] : null,
                    'prefill' => $linePrefill,
                    'booking' => $lineBooking,
                    'history' => $lineHistory,
                ],
            ],
        ]);
    }

    /** ตรวจสิทธิ์ด้วยเบอร์โทรหรืออีเมล — เคยลงทะเบียนแล้วได้รายละเอียดการจองกลับไปแสดง */
    public function check(Request $request, Activity $activity): JsonResponse
    {
        $contact = trim((string) $request->input('contact'));
        $digits = preg_replace('/\D+/', '', $contact) ?: '';
        $isPhone = (bool) preg_match('/^0[689]\d{8}$/', $digits);
        $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;

        if (! $isPhone && ! $isEmail) {
            return response()->json([
                'message' => 'กรุณากรอกเบอร์โทรศัพท์หรืออีเมลให้ถูกต้อง',
            ], 422);
        }

        $registrations = $this->service->findByContact($activity, $isPhone ? $digits : $contact, $isPhone);

        if ($registrations->isNotEmpty()) {
            return response()->json([
                'registered' => true,
                'booking' => $this->bookingPayload($activity, $registrations),
            ]);
        }

        if (! $this->canRegister($activity)) {
            return response()->json([
                'message' => 'กิจกรรมนี้ยังไม่เปิดรับลงทะเบียนหรือปิดรับสมัครแล้ว',
            ], 409);
        }

        return response()->json([
            'registered' => false,
            'contactType' => $isPhone ? 'phone' : 'email',
            'maxSeats' => $this->service->maxSeats($activity),
        ]);
    }

    public function store(
        PublicRegistrationRequest $request,
        Activity $activity,
    ): JsonResponse {
        /* บัญชี LINE อ่านจาก session เท่านั้น ไม่รับจาก payload — ไม่งั้นใครก็ส่ง userId ของคนอื่นมาผูกได้ */
        $registrations = $this->service->register(
            $activity,
            $request->validated(),
            $request->session()->get(PublicLineLoginController::SESSION_KEY),
        );

        return response()->json([
            'message' => 'ลงทะเบียนสำเร็จ '.$registrations->count().' ที่นั่ง',
            'registrationCodes' => $registrations->pluck('code'),
            'names' => $registrations->pluck('name'),
            'booking' => $this->bookingPayload($activity, $registrations),
        ], 201);
    }

    /** แจ้งชำระเงิน + แนบสลิป (ถ้ามี) — อ้างการจองด้วยรหัส REG ที่สุ่มไว้ตอนบันทึก */
    public function payment(Request $request, Activity $activity): JsonResponse
    {
        $data = $request->validate([
            'codes' => ['required', 'array', 'min:1', 'max:5'],
            'codes.*' => ['required', 'string', 'max:24'],
            'slip' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'codes' => 'รหัสการจอง',
            'slip' => 'สลิปการโอน',
        ]);

        $registrations = $this->service->notifyPayment($activity, $data['codes'], $request->file('slip'));

        return response()->json([
            'message' => 'ได้รับแจ้งการชำระเงินแล้ว เจ้าหน้าที่จะตรวจสอบให้ภายใน 1 วันทำการ',
            'booking' => $this->bookingPayload($activity, $registrations),
        ]);
    }

    private function canRegister(Activity $activity): bool
    {
        $activity->loadCount('registrations');

        return $activity->is_published
            && $activity->visibility === 'สาธารณะ'
            && $activity->acceptsRegistration()
            && $this->service->registrationForm($activity) !== null;
    }

    /**
     * ก้อนข้อมูลการจองที่หน้าจอ "ลงทะเบียนแล้ว" และ "สำเร็จ" ใช้ร่วมกัน
     *
     * @param  Collection<int, Registration>  $registrations
     * @return array<string, mixed>
     */
    private function bookingPayload(Activity $activity, Collection $registrations): array
    {
        $activity->loadMissing('rounds', 'instructors');
        $first = $registrations->first();
        $round = $first->activity_round_id
            ? $activity->rounds->firstWhere('id', $first->activity_round_id)
            : $activity->rounds->first();

        $scheduleLabel = $round
            ? collect([
                $this->presenter->thaiDate($round->round_date),
                $round->time_start
                    ? substr((string) $round->time_start, 0, 5)
                        .($round->time_end ? '–'.substr((string) $round->time_end, 0, 5) : '').' น.'
                    : null,
            ])->filter()->join(' · ')
            : $this->presenter->present($activity)['scheduleLabel'];

        return [
            'code' => $first->code,
            'seats' => $registrations->count(),
            'names' => $registrations->pluck('name')->values(),
            'activityTitle' => $activity->name,
            'scheduleLabel' => $scheduleLabel ?: '-',
            'location' => $round?->location ?: '-',
            'seatsLabel' => $registrations->count().' ที่นั่ง ('.$registrations->pluck('name')->join(', ').')',
            'paymentLabel' => $this->paymentLabel($activity, $registrations),
        ];
    }

    /** เอกสารความยินยอมฉบับที่เปิดใช้ของประเภทนั้น — null เมื่อยังไม่มีฉบับ active */
    private function consentDoc(string $type): ?array
    {
        $document = ConsentDocument::query()
            ->where('consent_type', $type)
            ->where('is_active', true)
            ->first();

        return $document ? [
            'title' => $document->title,
            'version' => $document->version,
            'content' => $document->content,
        ] : null;
    }

    /** @param  Collection<int, Registration>  $registrations */
    private function paymentLabel(Activity $activity, Collection $registrations): string
    {
        if (! $activity->has_fee || (float) $activity->fee <= 0) {
            return 'ไม่มีค่าใช้จ่าย';
        }

        $amount = number_format((float) $activity->fee * $registrations->count()).' ฿';

        return match ($registrations->first()->payment_status) {
            'ชำระแล้ว' => 'ชำระแล้ว '.$amount,
            'รอตรวจสอบ' => 'แจ้งชำระแล้ว '.$amount.' · รอตรวจสอบ',
            default => 'ยังไม่ชำระ '.$amount,
        };
    }
}
