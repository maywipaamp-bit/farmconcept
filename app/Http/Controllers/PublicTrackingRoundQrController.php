<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\FollowUpRound;
use App\Models\Participant;
use App\Models\QrCode;
use App\Models\TargetGroup;
use App\Services\LineLoginService;
use App\Services\TrackingRoundService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * QR ทำแบบประเมินติดตามสุขภาพ — QR เดียวทั้งโครงการ
 *
 * URL ไม่มีรหัสคน รหัสรอบ หรือรหัสแบบประเมินอยู่ข้างใน จึงพิมพ์ครั้งเดียวใช้ได้ตลอด
 * ห้ามทำ QR แยกรายคนเด็ดขาด — นั่นเท่ากับแจกกุญแจของคนนั้นให้ใครก็ได้ที่เห็นกระดาษ
 *
 * แยกเป็นหน้าจอละขั้นตอน (ยืนยันตัวตน → รอบที่ถึงกำหนด → ทำแบบประเมิน)
 * ไม่ใช่หน้าเดียวยาวต่อกัน เพราะผู้ตอบส่วนใหญ่เปิดจากมือถือและทำทีละรอบ
 * แต่ละขั้นมี URL ของตัวเอง กดย้อนกลับแล้วไม่หลุดไปเริ่มใหม่ทั้งหมด
 *
 * ตัวตนของผู้ตอบเก็บใน session ไม่ใช่ใน URL — ถ้าอยู่ใน URL คนที่แชร์ลิงก์ต่อ
 * จะกลายเป็นแชร์สิทธิ์ตอบแทนคนนั้นไปด้วย
 */
class PublicTrackingRoundQrController extends Controller
{
    /** ตัวตนที่ยืนยันแล้วของผู้ตอบ — เก็บแค่ id ของกลุ่มตัวอย่าง ไม่เก็บข้อมูลส่วนตัวลง session */
    public const SESSION_KEY = 'health_survey_participant';

    /** ผู้ถูกประเมิน กรณีกำลังกรอกแทนคนอื่น — คนละคนกับเจ้าของ session */
    public const PROXY_KEY = 'health_survey_proxy_for';

    public function __construct(
        private readonly TrackingRoundService $rounds,
        private readonly LineLoginService $line,
    ) {}

    /** หน้า 1 — ยืนยันตัวตนด้วยเบอร์โทร หรือเข้าด้วย LINE */
    public function landing(Request $request): View|Response|RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        $qr->increment('scan_count');

        /* เคยยืนยันตัวตนไว้แล้วในเครื่องนี้ (หรือเพิ่งกลับมาจาก LINE) ก็ข้ามไปหน้ารอบเลย
           ไม่ต้องให้กรอกเบอร์ซ้ำทุกครั้งที่สแกน */
        if ($this->verifiedParticipant($request) !== null) {
            return redirect()->route('public.tracking-round-qr.dashboard');
        }

        return view('public.tracking-round.identify', [
            
            'lineEnabled' => $this->line->isConfigured(),
            'projectName' => config('farmconcept.tracking_round.project_name'),
            'assurances' => config('farmconcept.tracking_round.assurances'),
            'disclosures' => config('farmconcept.tracking_round.disclosures'),
        ]);
    }

    /**
     * ยืนยันด้วยเบอร์โทร
     *
     * ใช้เบอร์อย่างเดียวตามที่หน้างานต้องการ — ผู้สูงอายุจำนวนมากไม่ได้พกใบยินยอมมาด้วย
     * แลกมาด้วยความเสี่ยงว่าคนที่รู้เบอร์ของคนอื่นเปิดดูรอบของเขาได้ จึงจำกัดจำนวนครั้งต่อ IP
     * ทางที่ปลอดภัยกว่าคือให้ผูก LINE แล้วเข้าด้วยปุ่ม LINE ซึ่งพิสูจน์ตัวตนได้จริง
     */
    public function verify(Request $request): RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            abort(410);
        }

        $data = $request->validate(
            ['phone' => ['required', 'string', 'max:30']],
            ['phone.required' => 'กรุณากรอกเบอร์โทรศัพท์']
        );

        $key = 'health-verify:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 15)) {
            throw ValidationException::withMessages([
                'phone' => 'ลองหลายครั้งเกินไป กรุณารอสักครู่แล้วลองใหม่',
            ]);
        }

        RateLimiter::hit($key, 600);

        $matches = $this->participantsByPhone($data['phone']);

        /* ไม่พบเบอร์นี้ = คนใหม่ พาไปลงทะเบียนเป็นกลุ่มตัวอย่างเอง ไม่ใช่ตอบว่า "ไม่พบข้อมูล"
           แล้วปล่อยให้เขาไปตามหาเจ้าหน้าที่ ซึ่งส่วนใหญ่แปลว่าเลิกทำไปเลย */
        if ($matches->isEmpty()) {
            return redirect()
                ->route('public.tracking-round-qr.register')
                ->with('prefillPhone', $this->digits($data['phone']));
        }

        RateLimiter::clear($key);

        /* เจอเบอร์แล้วยังเข้าไม่ได้ทันที ต้องยืนยันด้วยชื่อจริงอีกชั้นเสมอ
           เบอร์โทรเป็นสิ่งที่คนอื่นรู้ได้ ถ้าให้ผ่านด้วยเบอร์อย่างเดียวเท่ากับใครก็ตอบแทนได้
           และเบอร์เดียวใช้กันทั้งบ้านเป็นเรื่องปกติ ต้องรู้ให้ชัดว่ากำลังตอบในนามใคร */
        return redirect()
            ->route('public.tracking-round-qr.choose')
            ->with('candidateIds', $matches->pluck('id')->all())
            ->with('candidatePhone', $this->formatPhone($this->digits($data['phone'])))
            /* คงสถานะ "กำลังเชื่อม LINE" ไว้ข้ามหน้ายืนยันชื่อ ไม่งั้นข้อความอธิบายหายกลางทาง */
            ->with('linkLine', $request->session()->get('linkLine'));
    }

    /**
     * กลับมาจาก LINE — บัญชีที่เคยผูกไว้แล้วถือว่ายืนยันตัวตนแล้ว เข้าได้เลย
     *
     * นี่คือเหตุผลของปุ่ม LINE: ผูกครั้งเดียวแล้วไม่ต้องกรอกเบอร์ทุกครั้งที่ทำแบบประเมิน
     * และเป็นการพิสูจน์ตัวตนที่แน่นกว่าเบอร์โทร ซึ่งคนอื่นรู้ได้
     */
    public function lineReturn(Request $request): RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            abort(410);
        }

        $lineUserId = $request->session()->get(PublicLineLoginController::SESSION_KEY)['userId'] ?? null;

        $participant = $lineUserId
            ? Participant::with('cohortProfile')->whereHas('cohortProfile')
                ->where('line_user_id', $lineUserId)->first()
            : null;

        if ($participant !== null) {
            return $this->signIn($request, $participant);
        }

        /* ยังไม่มีใครผูกบัญชี LINE นี้ — ต้องแยกสองกรณีให้ออก คนที่เคยลงทะเบียนด้วยเบอร์ไว้แล้ว
           กับคนที่ยังไม่เคยลงทะเบียนเลย ทั้งคู่ต้องยืนยันเบอร์ก่อน แล้วระบบจะผูก LINE ให้เอง
           ผลักไปหน้าลงทะเบียนตรง ๆ ไม่ได้ เพราะคนกลุ่มแรกจะกรอกเบอร์เดิมแล้วโดนปฏิเสธวนอยู่อย่างนั้น */
        return redirect()
            ->route('public.tracking-round-qr')
            ->with('linkLine', true);
    }

    /**
     * หน้าเลือกชื่อ เมื่อเบอร์เดียวมีหลายคน
     *
     * แสดงชื่อแบบปิดบังเท่านั้น (สมห••• ใ•••) แล้วให้พิมพ์ชื่อจริง 3 ตัวแรกยืนยัน
     * ถ้าโชว์ชื่อเต็มตั้งแต่แรก แค่รู้เบอร์ของบ้านหนึ่งก็อ่านได้ว่ามีใครอยู่ในโครงการบ้าง
     */
    public function choose(Request $request): View|RedirectResponse
    {
        $ids = $request->session()->get('candidateIds', []);

        if ($ids === []) {
            return redirect()->route('public.tracking-round-qr');
        }

        /* คงรายชื่อไว้ข้ามการ refresh — flash หายหลังคำขอเดียว แต่ผู้ใช้กด F5 ได้เสมอ */
        $request->session()->reflash();

        return view('public.tracking-round.choose', [
            'phone' => $request->session()->get('candidatePhone', ''),
            'people' => Participant::with(['area', 'targetGroup'])->whereIn('id', $ids)->orderBy('name')->get(),
            'selected' => (int) $request->session()->get('chooseSelected', 0),
        ]);
    }

    public function chooseSubmit(Request $request): RedirectResponse
    {
        $ids = array_map('intval', $request->session()->get('candidateIds', []));

        /* ต้องเป็นคนที่อยู่ในผลค้นหาของเบอร์ที่เพิ่งยืนยันเท่านั้น
           ไม่งั้นยิง id ของใครก็ได้เข้ามาแล้วสวมสิทธิ์ตอบแทนเขา

           เช็คก่อนตรวจฟอร์ม — ถ้าไปเช็คทีหลัง คนที่ยิง id ของคนอื่นมาโดยไม่กรอกชื่อ
           จะได้ข้อความ "กรุณากรอกชื่อจริง" แทน 403 ซึ่งเท่ากับบอกว่า id นั้นใช้ได้ */
        $requested = (int) $request->input('participant_id');
        abort_if($requested > 0 && ! in_array($requested, $ids, true), 403);

        $data = $request->validate([
            'participant_id' => ['required', 'integer'],
            'name_prefix' => ['required', 'string', 'max:20'],
        ], [
            'participant_id.required' => 'กรุณาเลือกชื่อของคุณ',
            'name_prefix.required' => 'กรุณากรอกชื่อจริง 5 ตัวอักษรแรก',
        ]);

        $participant = Participant::findOrFail($data['participant_id']);

        if (! $participant->matchesNamePrefix($data['name_prefix'])) {
            $request->session()->reflash();

            return back()
                ->with('chooseSelected', $participant->id)
                ->withErrors(['name_prefix' => 'ชื่อจริงไม่ตรงกับรายชื่อที่เลือก']);
        }

        return $this->signIn($request, $participant);
    }

    /** หน้าลงทะเบียนกลุ่มตัวอย่างรายใหม่ — สำหรับผู้เข้าร่วมที่ทำเอง */
    public function register(Request $request): View|Response
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        $request->session()->reflash();

        return view('public.tracking-round.register', [
            
            'phone' => $request->session()->get('prefillPhone', ''),
            'lineName' => $request->session()->get(PublicLineLoginController::SESSION_KEY)['name'] ?? '',
            'genders' => config('farmconcept.genders'),
            /* รายชื่อพื้นที่มาจาก master data ไม่ได้เขียนรายชื่อชุมชนตายไว้ในหน้าจอ */
            'areas' => Area::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function registerSubmit(Request $request): RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            abort(410);
        }

        $request->merge(['phone' => $this->digits($request->input('phone'))]);

        /* ลำดับกฎตรงกับลำดับฟิลด์บนหน้าจอ เพราะหน้านี้แสดง error ทีละข้อความเดียว
           ผู้ใช้จะได้แก้ไล่จากบนลงล่าง ไม่ใช่กระโดดไปมา */
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'regex:/^0[689]\d{8}$/'],
            'gender' => ['required', Rule::in(array_keys(config('farmconcept.genders')))],
            'area_id' => ['required', 'integer', 'exists:mst_areas,id'],
            'consent' => ['required', 'accepted'],
        ], [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex' => 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก',
            'gender.required' => 'กรุณาเลือกเพศ',
            'area_id.required' => 'กรุณาเลือกพื้นที่/ชุมชน',
            'consent.accepted' => 'กรุณายินยอมให้ใช้ข้อมูลเพื่อการวิจัย',
        ]);

        /* เบอร์นี้มีคนใช้อยู่แล้ว = เขาเคยลงทะเบียนไว้ ไม่ต้องสร้างซ้ำ ให้ไปยืนยันตัวตนตามปกติ */
        if ($this->participantsByPhone($data['phone'])->isNotEmpty()) {
            return redirect()
                ->route('public.tracking-round-qr')
                ->withErrors(['phone' => 'เบอร์นี้ลงทะเบียนไว้แล้ว กรุณายืนยันตัวตนด้วยเบอร์โทร']);
        }

        $profile = $this->rounds->selfRegister(
            trim($data['name']),
            $this->formatPhone($data['phone']),
            $request->session()->get(PublicLineLoginController::SESSION_KEY)['userId'] ?? null,
            $data['gender'],
            (int) $data['area_id'],
        );

        return $this->signIn($request, $profile->participant)
            ->with('justRegistered', $profile->participant->person_code);
    }

    /**
     * หน้าหลักของผู้เข้าร่วม
     *
     * ตั้งใจให้เห็น "สิ่งที่ต้องทำตอนนี้" เป็นอย่างแรก แล้วค่อยเป็นข้อมูลประกอบ
     * รอบที่ถึงกำหนดจึงเป็นการ์ดเดียวที่ใช้สีเขียว ที่เหลือเป็นพื้นเทาอ่อนทั้งหมด
     */
    public function dashboard(Request $request): View|Response|RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        $participant = $this->verifiedParticipant($request);

        if ($participant === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        $all = $this->allRoundsFor($participant);
        $due = $this->rounds->dueRoundsFor($participant);

        return view('public.tracking-round.dashboard', [
            
            'participant' => $participant,
            'dueRound' => $due->first(),
            /* ลำดับของรอบในชุดทั้งหมดของคนนี้ — ผู้ตอบเข้าใจ "รอบที่ 2" ง่ายกว่าชื่อรอบลอย ๆ */
            'dueOrder' => $due->first()
                ? $all->pluck('id')->search($due->first()->id) + 1
                : null,
            'answeredRounds' => $all->whereNotNull('answered_at')->count(),
            'totalRounds' => $all->count(),
            /* รอบถัดไปที่ยังไม่เปิด — บอกวันที่เริ่มตอบได้ ไม่ใช่วันครบกำหนด
               ผู้ตอบสนใจว่า "กลับมาได้เมื่อไหร่" ไม่ใช่ "ต้องเสร็จเมื่อไหร่" */
            'nextOpen' => $all->whereNull('answered_at')
                ->first(fn (FollowUpRound $r) => $r->state() === 'ยังไม่ถึงกำหนด'),
            /* ผู้ประสานงานของพื้นที่ก่อน ถ้าพื้นที่นั้นยังไม่ได้กรอกไว้ค่อยใช้เบอร์กลางของโครงการ
               ผู้เข้าร่วมต้องมีคนให้ติดต่อได้เสมอ ไม่ใช่หายไปเพราะข้อมูลพื้นที่ยังไม่ครบ */
            'coordinator' => filled($participant->area?->coordinator_phone)
                ? ['name' => $participant->area->coordinator_name ?: $participant->area->name,
                    'phone' => $participant->area->coordinator_phone]
                : config('farmconcept.tracking_round.coordinator'),
        ]);
    }

    /** รายการรอบทั้งหมดพร้อมสถานะ เปิด / ยังไม่เปิด / เสร็จแล้ว */
    public function roundList(Request $request): View|Response|RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        /* ถ้ากำลังกรอกแทน รายการรอบต้องเป็นของผู้ถูกประเมิน ไม่ใช่ของผู้กรอก */
        $participant = $this->subjectParticipant($request);

        if ($participant === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        return view('public.tracking-round.rounds', [
            
            'participant' => $participant,
            /* ชื่อรอบมาจากใบติดตามของคนนั้นล้วน ๆ ไม่มี "3 เดือน / 6 เดือน" เขียนตายในเส้นทางนี้ */
            'rounds' => $this->allRoundsFor($participant),
            'openIds' => $this->rounds->dueRoundsFor($participant)->pluck('id')->all(),
            'proxyFor' => $this->proxyFor($request),
        ]);
    }

    /** เปิด/ปิดการแจ้งเตือนรอบถัดไปทาง LINE — เป็นค่าของแต่ละคน ไม่ใช่ค่ากลาง */
    public function toggleNotify(Request $request): RedirectResponse
    {
        $participant = $this->verifiedParticipant($request);

        if ($participant === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        $participant->update(['line_notify' => ! $participant->line_notify]);

        return back();
    }

    /** หน้ายืนยันตัวตนของผู้ถูกประเมิน ก่อนกรอกแทน */
    public function proxy(Request $request): View|Response|RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        if ($this->verifiedParticipant($request) === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        return view('public.tracking-round.proxy');
    }

    /**
     * ยืนยันผู้ถูกประเมินแล้วสลับไปทำแบบประเมินให้เขา
     *
     * ต้องยืนยันสองชั้น (เบอร์ + ชื่อจริง 3 ตัวแรก) เพราะการกรอกแทนคือการเขียนคำตอบ
     * ลงในระเบียนของคนอื่น ผิดคนแล้วข้อมูลวิจัยเสียโดยไม่มีใครจับได้
     * ผู้กรอกยังคงเป็นเจ้าของ session เดิม — รอบของผู้กรอกต้องไม่ถูกนับเพิ่ม
     */
    public function proxySubmit(Request $request): RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            abort(410);
        }

        $actor = $this->verifiedParticipant($request);

        if ($actor === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'name_prefix' => ['required', 'string', 'max:20'],
        ], [
            'phone.required' => 'กรุณากรอกเบอร์โทร 10 หลัก',
            'name_prefix.required' => 'กรุณากรอกชื่อจริง 3 ตัวอักษรแรก',
        ]);

        $target = $this->participantsByPhone($data['phone'])
            ->first(fn (Participant $p) => $p->matchesNamePrefix($data['name_prefix']));

        if ($target === null) {
            return back()->withInput()->withErrors([
                'phone' => 'ข้อมูลไม่ตรงกับผู้ถูกประเมินในระบบ กรุณาตรวจสอบอีกครั้ง',
            ]);
        }

        if ($target->id === $actor->id) {
            return redirect()->route('public.tracking-round-qr.rounds');
        }

        $request->session()->put(self::PROXY_KEY, $target->id);

        return redirect()->route('public.tracking-round-qr.rounds');
    }

    /** เลิกกรอกแทน กลับมาเป็นตัวเอง */
    public function proxyStop(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PROXY_KEY);

        return redirect()->route('public.tracking-round-qr.dashboard');
    }

    /** ใบติดตามทั้งหมดของคนนี้ เรียงตามวันครบกำหนด */
    private function allRoundsFor(Participant $participant): Collection
    {
        return $participant->cohortProfile?->rounds()->orderBy('due_date')->get() ?? collect();
    }

    /** หน้า 3 — แบบประเมินของรอบที่เลือก */
    public function survey(Request $request, FollowUpRound $round): View|Response|RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        $participant = $this->subjectParticipant($request);

        if ($participant === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        $round = $this->openRoundFor($participant, $round);

        return view('public.tracking-round.survey', [
            
            'participant' => $participant,
            'round' => $round,
            'form' => $this->rounds->formForRound($round),
            'proxyFor' => $this->proxyFor($request),
        ]);
    }

    public function surveySubmit(Request $request, FollowUpRound $round): RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            abort(410);
        }

        $participant = $this->subjectParticipant($request);

        if ($participant === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        $round = $this->openRoundFor($participant, $round);

        /* คำตอบมาเป็น answer_<id> เพื่อให้ชื่อ input อ่านออกในหน้า HTML
           แปลงกลับเป็นคีย์ id ล้วนก่อนส่งให้ตัวตรวจ ซึ่งใช้ร่วมกับแบบประเมินหลังกิจกรรม */
        $answers = [];

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'answer_')) {
                $answers[substr($key, 7)] = $value;
            }
        }

        /* บันทึกว่าใครเป็นคนกรอก ถ้าไม่ใช่เจ้าตัว — มีผลต่อการตีความข้อมูลวิจัย */
        $proxy = $this->proxyFor($request);

        $this->rounds->submitSurvey($round, $answers, $proxy ? $this->verifiedParticipant($request) : null);

        return redirect()->route('public.tracking-round-qr.done', $round->id);
    }

    /**
     * หน้าสรุปหลังส่งคำตอบ
     *
     * ไม่ใช้ openRoundFor เพราะรอบนี้เพิ่งถูกปิดไป — ตรวจแค่ว่าเป็นรอบของคนที่ยืนยันตัวตนอยู่
     * ไม่งั้นเปิดดูสรุปของคนอื่นได้ด้วยการเดารหัสรอบ
     */
    public function done(Request $request, FollowUpRound $round): View|Response|RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        $participant = $this->subjectParticipant($request);

        if ($participant === null) {
            return redirect()->route('public.tracking-round-qr');
        }

        $profile = $participant->cohortProfile;

        abort_if($profile === null || $round->cohort_profile_id !== $profile->id, 404);

        $response = $round->surveyResponse;

        return view('public.tracking-round.done', [
            'round' => $round,
            /* วันที่ส่งต้องมาจากใบตอบ ไม่ใช่ answered_at ของรอบ — ใบตอบคือหลักฐานที่ผู้ตอบอ้างอิงได้
               ถ้าใบยังไม่มีด้วยเหตุใดก็ตาม ให้ตกไปที่เวลาที่ปิดรอบ */
            'submittedAt' => $response?->submitted_at ?? $round->answered_at,
            'answeredQuestions' => $response?->answers()->distinct('question_id')->count('question_id') ?? 0,
            /* ตัวนับต้องอ้างถึงเจ้าของแบบประเมิน ไม่ใช่ผู้กรอก — การกรอกแทนไม่นับเป็นรอบของผู้กรอก */
            'answeredRounds' => $profile->rounds()->whereNotNull('answered_at')->count(),
            'totalRounds' => $profile->rounds()->count(),
            'remaining' => $this->rounds->dueRoundsFor($participant)->count(),
            'lineLinked' => filled($participant->line_user_id) && $participant->line_notify,
            'proxyFor' => $this->proxyFor($request),
        ]);
    }

    /** ออกจากการยืนยันตัวตนบนเครื่องนี้ — ใช้ตอนทำแทนคนอื่นต่อในเครื่องเดียวกัน */
    public function signOut(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESSION_KEY, self::PROXY_KEY, PublicLineLoginController::SESSION_KEY]);

        return redirect()->route('public.tracking-round-qr');
    }

    /**
     * ผูกตัวตนที่ยืนยันแล้วเข้ากับ session แล้วพาไปหน้ารอบ
     *
     * เก็บเวลาที่ยืนยันไว้ด้วย เพื่อให้หมดอายุตามกติกาของ session ปกติ
     * ไม่ใช่ค้างอยู่ตลอดไปในเครื่องที่คนอื่นหยิบไปใช้ต่อได้
     */
    private function signIn(Request $request, Participant $participant, ): RedirectResponse
    {
        $request->session()->put(self::SESSION_KEY, [
            'id' => $participant->id,
            'at' => now()->toDateTimeString(),
        ]);

        /* เข้าใหม่ = เลิกกรอกแทนของเดิมเสมอ ไม่งั้นสถานะกรอกแทนค้างข้ามคน */
        $request->session()->forget(self::PROXY_KEY);

        /* เข้ามาด้วย LINE แล้วยังไม่เคยผูกบัญชี — ผูกให้ตอนนี้ จะได้รับแจ้งเตือนรอบถัดไปได้
           บัญชี LINE หนึ่งบัญชีผูกได้คนเดียว ผูกซ้อนต้องไม่ทับของเดิม
           และไม่ทับ LINE เดิมของคนนี้ด้วย — ถ้าเขาเคยผูกไว้แล้วแปลว่าเป็นคนละเครื่อง/คนละบัญชี
           ต้องให้เจ้าหน้าที่ตรวจก่อน ไม่ใช่ให้ใครก็ได้ที่รู้เบอร์มาเปลี่ยนปลายทางแจ้งเตือน */
        $lineUserId = $request->session()->get(PublicLineLoginController::SESSION_KEY)['userId'] ?? null;

        $justLinked = $lineUserId
            && blank($participant->line_user_id)
            && ! Participant::where('line_user_id', $lineUserId)->exists();

        if ($justLinked) {
            $participant->update(['line_user_id' => $lineUserId]);
        }

        return redirect()
            ->route('public.tracking-round-qr.dashboard')
            ->with('lineLinked', $justLinked);
    }

    /** เจ้าของ session — คนที่ยืนยันตัวตนไว้ คืน null ถ้ายังไม่ยืนยันหรือระเบียนหายไปแล้ว */
    private function verifiedParticipant(Request $request): ?Participant
    {
        $id = $request->session()->get(self::SESSION_KEY.'.id');

        return $id ? Participant::with(['cohortProfile', 'area'])->find($id) : null;
    }

    /**
     * "เจ้าของแบบประเมิน" ที่กำลังทำอยู่ — คนที่ถูกกรอกแทน ถ้ามี ไม่งั้นคือเจ้าของ session เอง
     *
     * แยกจาก verifiedParticipant() เพราะสองอย่างนี้ต้องไม่ปนกัน: คำตอบต้องลงระเบียนของ
     * ผู้ถูกประเมิน แต่แดชบอร์ด สวิตช์แจ้งเตือน และตัวนับรอบต้องเป็นของผู้กรอกเสมอ
     */
    private function subjectParticipant(Request $request): ?Participant
    {
        $proxyId = $request->session()->get(self::PROXY_KEY);

        if ($proxyId) {
            return Participant::with(['cohortProfile', 'area'])->find($proxyId);
        }

        return $this->verifiedParticipant($request);
    }

    /** กำลังกรอกแทนคนอื่นอยู่หรือไม่ — ใช้แสดงป้ายเตือนค้างไว้ทุกหน้าของรอบนั้น */
    private function proxyFor(Request $request): ?Participant
    {
        $proxyId = $request->session()->get(self::PROXY_KEY);

        return $proxyId ? Participant::find($proxyId) : null;
    }

    /**
     * รอบที่ "เปิดให้ตอบอยู่จริง" ของคนที่ยืนยันตัวตนแล้วเท่านั้น
     *
     * กันสองเรื่องพร้อมกัน: ยิงรหัสรอบของคนอื่นเข้ามาปิดรอบให้เขา
     * และตอบรอบที่ยังไม่ถึงกำหนดข้ามลำดับไปก่อน
     */
    private function openRoundFor(Participant $participant, FollowUpRound $round): FollowUpRound
    {
        $open = $this->rounds->dueRoundsFor($participant)->firstWhere('id', $round->id);

        abort_if($open === null, 404, 'รอบนี้ไม่ได้เปิดให้ตอบอยู่');

        return $open;
    }

    /** @return \Illuminate\Support\Collection<int, Participant> */
    private function participantsByPhone(string $phone): \Illuminate\Support\Collection
    {
        $digits = $this->digits($phone);

        if ($digits === '') {
            return collect();
        }

        /* ข้อมูลเดิมเก็บเบอร์ทั้งแบบมีขีดและไม่มี เทียบเป็นตัวเลขล้วนจึงเจอทั้งสองแบบ
           ตัดผู้ที่ยังไม่ได้เป็นกลุ่มตัวอย่างออก เพราะยังไม่มีรอบให้ตอบ */
        return Participant::with('cohortProfile')
            ->whereNotNull('phone')
            ->whereHas('cohortProfile')
            ->get()
            ->filter(fn (Participant $p) => $this->digits($p->phone) === $digits)
            ->values();
    }

    private function activeQr(): QrCode|Response
    {
        /* QR ติดตามสุขภาพมีแถวเดียวทั้งระบบ (activity_id เป็น NULL) จึงหาจากชนิดได้เลย
           ไม่ต้องรับ token ทาง URL — ดู QrCode ที่อธิบายไว้ตั้งแต่ตอนออกแบบตาราง */
        $qr = QrCode::where('purpose', 'health')->whereNull('activity_id')->firstOrFail();

        if (! $qr->is_active || ($qr->expires_at && $qr->expires_at->isPast())) {
            return response()->view('public.qr-unavailable', ['activity' => null], 410);
        }

        return $qr;
    }

    private function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    /** 0812345678 → 081-234-5678 เก็บรูปแบบเดียวกับข้อมูลเดิมทั้งฐาน */
    private function formatPhone(string $digits): string
    {
        return strlen($digits) === 10
            ? substr($digits, 0, 3).'-'.substr($digits, 3, 3).'-'.substr($digits, 6)
            : $digits;
    }
}
