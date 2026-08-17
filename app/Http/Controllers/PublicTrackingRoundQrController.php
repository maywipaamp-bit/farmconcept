<?php

namespace App\Http\Controllers;

use App\Models\ConsentDocument;
use App\Models\FollowUpRound;
use App\Models\Option;
use App\Models\Participant;
use App\Models\QrCode;
use App\Models\TargetGroup;
use App\Services\LineLoginService;
use App\Services\SurveyAnswerBuilder;
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
            [
                /* ช่องเดียวรับทั้งเบอร์และอีเมล — 160 เท่าความยาวคอลัมน์อีเมล */
                'phone' => ['required', 'string', 'max:160'],
                /* ไม่บังคับ — กล่องเชื่อม LINE ส่งมาแต่เบอร์ และคนที่จำรหัสไม่ได้ตอนนั้น
                   ยังต้องเข้าต่อได้ โดยไปถามรหัสที่หน้าถัดไปแทน */
                'person_code' => ['nullable', 'string', 'max:30'],
            ],
            ['phone.required' => 'กรุณากรอกเบอร์โทรศัพท์หรืออีเมล']
        );

        $key = 'health-verify:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 15)) {
            throw ValidationException::withMessages([
                'phone' => 'ลองหลายครั้งเกินไป กรุณารอสักครู่แล้วลองใหม่',
            ]);
        }

        RateLimiter::hit($key, 600);

        $matches = $this->participantsByContact($data['phone']);

        if ($matches->isEmpty()) {
            /* อีเมลที่หาไม่เจอ พาไปหน้าลงทะเบียนไม่ได้ เพราะฟอร์มนั้นรับเฉพาะเบอร์
               ส่งไปก็ไปติดอยู่ตรงนั้นต่อไม่ได้ — บอกตรง ๆ ว่าไม่พบ พร้อมทางออกที่ทำได้จริง */
            if (str_contains($data['phone'], '@')) {
                return back()->withInput()->withErrors([
                    'phone' => 'ไม่พบอีเมลนี้ในระบบ — ลองใช้เบอร์โทรศัพท์แทน หรือติดต่อเจ้าหน้าที่เพื่อเพิ่มอีเมลให้',
                ]);
            }

            /* ไม่พบเบอร์นี้ = คนใหม่ พาไปลงทะเบียนเป็นกลุ่มตัวอย่างเอง ไม่ใช่ตอบว่า "ไม่พบข้อมูล"
               แล้วปล่อยให้เขาไปตามหาเจ้าหน้าที่ ซึ่งส่วนใหญ่แปลว่าเลิกทำไปเลย */
            return redirect()
                ->route('public.tracking-round-qr.register')
                ->with('prefillPhone', $this->digits($data['phone']))
                /* บอกเหตุผลที่ถูกพามาหน้านี้ ไม่งั้นคนที่ตั้งใจแค่เข้าสู่ระบบจะงงว่าทำไมเจอฟอร์มลงทะเบียน */
                ->with('phoneNotFound', true);
        }

        RateLimiter::clear($key);

        $ids = $matches->pluck('id')->all();
        $typed = trim((string) ($data['person_code'] ?? ''));

        /* กรอกรหัสมาพร้อมเบอร์ในจอเดียวแล้ว ก็ให้เข้าได้เลย ไม่ต้องเด้งไปถามซ้ำอีกจอ */
        if ($typed !== '') {
            $matched = $this->matchesCode($ids, $typed);

            if ($matched->count() === 1) {
                return $this->signIn($request, $matched->first());
            }
        }

        /* เจอเบอร์แล้วยังเข้าไม่ได้ทันที ต้องยืนยันด้วยรหัสบุคคลอีกชั้นเสมอ
           เบอร์โทรเป็นสิ่งที่คนอื่นรู้ได้ ถ้าให้ผ่านด้วยเบอร์อย่างเดียวเท่ากับใครก็ตอบแทนได้
           และเบอร์เดียวใช้กันทั้งบ้านเป็นเรื่องปกติ ต้องรู้ให้ชัดว่ากำลังตอบในนามใคร */
        $redirect = redirect()
            ->route('public.tracking-round-qr.choose')
            ->with('candidateIds', $ids)
            /* อีเมลแสดงตามที่พิมพ์มา — จับเป็นเบอร์แล้ว digits() จะเหลือแต่ตัวเลขในอีเมล กลายเป็นค่าที่อ่านไม่ออก */
            ->with('candidatePhone', str_contains($data['phone'], '@')
                ? trim($data['phone'])
                : $this->formatPhone($this->digits($data['phone'])))
            /* คงสถานะ "กำลังเชื่อม LINE" ไว้ข้ามหน้ายืนยันรหัส ไม่งั้นข้อความอธิบายหายกลางทาง */
            ->with('linkLine', $request->session()->get('linkLine'));

        /* พิมพ์รหัสมาแล้วแต่ไม่ตรง ต้องบอกเหตุผลที่จอถัดไป ไม่ใช่ให้เจอช่องเปล่าโดยไม่รู้ว่าพลาดตรงไหน */
        return $typed === ''
            ? $redirect
            : $redirect->withErrors(['name_prefix' => 'รหัสบุคคลไม่ตรงกับข้อมูลของเบอร์นี้ กรุณาตรวจสอบอีกครั้ง']);
    }

    /**
     * คนในเบอร์นั้นที่ตรงกับรหัสบุคคลที่พิมพ์เข้ามา
     *
     * ตรงมากกว่าหนึ่งคนแปลว่าเดาไม่ได้ ต้องไม่เลือกให้ — เดาผิดคือคำตอบลงระเบียนผิดคนโดยไม่มีใครรู้
     *
     * @param  array<int>  $ids
     * @return \Illuminate\Support\Collection<int, Participant>
     */
    private function matchesCode(array $ids, string $typed): \Illuminate\Support\Collection
    {
        return Participant::whereIn('id', $ids)->get()
            ->filter(fn (Participant $p) => $p->matchesNamePrefix($typed))
            ->values();
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

        /* บัญชี LINE นี้ยังไม่ถูกผูกกับใคร และคนกดล็อกอินค้างอยู่แล้ว (มาจากสวิตช์บนแดชบอร์ด)
           — ผูกให้ทันทีแล้วพากลับแดชบอร์ด ไม่ใช่ไล่ไปกรอกเบอร์ซ้ำเหมือนคนแปลกหน้า
           การผูกและเงื่อนไขกันผูกซ้อนอยู่ใน signIn() ที่เดียว */
        $current = $this->verifiedParticipant($request);

        if ($current !== null) {
            return $this->signIn($request, $current);
        }

        /* ยังไม่มีใครผูกบัญชี LINE นี้ — ต้องแยกสองกรณีให้ออก คนที่เคยลงทะเบียนด้วยเบอร์ไว้แล้ว
           กับคนที่ยังไม่เคยลงทะเบียนเลย ทั้งคู่ต้องยืนยันเบอร์ก่อน แล้วระบบจะผูก LINE ให้เอง
           ผลักไปหน้าลงทะเบียนตรง ๆ ไม่ได้ เพราะคนกลุ่มแรกจะกรอกเบอร์เดิมแล้วโดนปฏิเสธวนอยู่อย่างนั้น */
        return redirect()
            ->route('public.tracking-round-qr')
            ->with('linkLine', true);
    }

    /**
     * ยืนยันตัวตนชั้นที่สอง — พิมพ์ชื่อจริง 5 ตัวอักษรแรก
     *
     * หน้านี้ไม่แสดงรายชื่อในเบอร์นั้นเลย แม้แบบปิดบัง ผู้ตอบพิมพ์ชื่อตัวเองจากความจำ
     * ถ้ายื่นรายชื่อให้เลือก แค่รู้เบอร์ของบ้านหนึ่งก็อ่านได้ว่ามีใครอยู่ในโครงการบ้าง
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
        ]);
    }

    public function chooseSubmit(Request $request): RedirectResponse
    {
        $ids = array_map('intval', $request->session()->get('candidateIds', []));

        /* หมดอายุกลางทาง (กดค้างไว้นาน / เปิดจากแท็บเก่า) ต้องกลับไปกรอกเบอร์ใหม่
           ไม่ใช่ให้ชื่ออย่างเดียวผ่านเข้ามาโดยไม่มีเบอร์คุมอยู่ */
        if ($ids === []) {
            return redirect()->route('public.tracking-round-qr');
        }

        $data = $request->validate(
            ['name_prefix' => ['required', 'string', 'max:20']],
            ['name_prefix.required' => 'กรุณากรอกรหัสบุคคล หรือชื่อที่ลงทะเบียนไว้']
        );

        /* จับคู่เองจากรหัสที่พิมพ์ ไม่รับ id จากฟอร์ม — ฟอร์มไม่เคยรู้ว่ามีใครอยู่ในเบอร์นี้
           จึงไม่มีทางยิง id ของคนอื่นเข้ามาสวมสิทธิ์ได้ตั้งแต่ต้น */
        $matches = $this->matchesCode($ids, $data['name_prefix']);

        $request->session()->reflash();

        if ($matches->isEmpty()) {
            return back()->withErrors(['name_prefix' => 'ชื่อไม่ตรงกับที่ลงทะเบียนไว้ของเบอร์นี้ กรุณาตรวจสอบอีกครั้ง']);
        }

        /* เบอร์เดียวมีสองคนที่ชื่อขึ้นต้นเหมือนกัน — เดาให้ไม่ได้ เพราะเดาผิดคือคำตอบลงผิดคน */
        if ($matches->count() > 1) {
            return back()->withErrors([
                'name_prefix' => 'เบอร์นี้มีมากกว่าหนึ่งชื่อที่ขึ้นต้นแบบนี้ กรุณาติดต่อเจ้าหน้าที่',
            ]);
        }

        return $this->signIn($request, $matches->first());
    }

    /** หน้าลงทะเบียนกลุ่มตัวอย่างรายใหม่ — สำหรับผู้เข้าร่วมที่ทำเอง */
    public function register(Request $request): View|Response
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            return $qr;
        }

        /* ไม่ reflash — เบอร์ที่เติมให้กับข้อความแจ้งควรอยู่แค่ครั้งแรกที่ถูกพามา
           กดรีเฟรชแล้วต้องหาย ส่วนกรณี validation error ฟอร์มเติมกลับด้วย old() อยู่แล้ว */
        return view('public.tracking-round.register', [
            'phone' => $request->session()->get('prefillPhone', ''),
            /* หน้าลงทะเบียนกลุ่มตัวอย่างให้เลือกแค่หญิง/ชาย — ชุดเต็มใน config ยังใช้ที่หน้าอื่นตามเดิม */
            'genders' => collect(config('farmconcept.genders'))->only(['female', 'male'])->all(),
            /* ตัวเลือกช่วงอายุมาจาก master data ชุดเดียวกับแบบลงทะเบียนกิจกรรม */
            'ageRanges' => Option::group('age_range')->active()->get(['id', 'label']),
            /* เอกสารความยินยอมฉบับที่เปิดใช้อยู่ (แก้ที่ admin/master/consent-documents)
               ไม่ hardcode ข้อความไว้ในหน้า เพราะเนื้อหาความยินยอมเปลี่ยนได้และต้องตรวจย้อนหลังได้ว่าใครยอมรับฉบับไหน */
            'consentDocs' => collect([
                'cohort' => 'cohort_data',
                'privacy' => 'pdpa',
            ])->map(fn (string $type) => $this->consentDoc($type))->all(),
        ]);
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

    public function registerSubmit(Request $request): RedirectResponse
    {
        $qr = $this->activeQr();

        if ($qr instanceof Response) {
            abort(410);
        }

        $request->merge(['phone' => $this->digits($request->input('phone'))]);

        /* ลำดับกฎตรงกับลำดับฟิลด์บนหน้าจอ เพราะหน้านี้แสดง error ทีละข้อความเดียว
           ผู้ใช้จะได้แก้ไล่จากบนลงล่าง ไม่ใช่กระโดดไปมา
           ไม่รับชื่อ — ระบบออกรหัสบุคคลให้เป็นชื่อในระบบแทน */
        $data = $request->validate([
            'phone' => ['required', 'regex:/^0[689]\d{8}$/'],
            /* รับเฉพาะค่าที่หน้าจอนี้มีให้เลือกจริง ไม่ใช่ชุดเต็มของระบบ */
            'gender' => ['required', Rule::in(['female', 'male'])],
            'age_range_id' => ['required', 'integer',
                Rule::exists('mst_options', 'id')->where('option_group', 'age_range')->where('is_active', true)],
            'consent' => ['required', 'accepted'],
        ], [
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex' => 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก',
            'gender.required' => 'กรุณาเลือกเพศ',
            'age_range_id.required' => 'กรุณาเลือกช่วงอายุ',
            'consent.accepted' => 'กรุณายินยอมให้ใช้ข้อมูลเพื่อการวิจัย',
        ]);

        /* เบอร์นี้มีคนใช้อยู่แล้ว = เขาเคยลงทะเบียนไว้ ไม่ต้องสร้างซ้ำ ให้ไปยืนยันตัวตนตามปกติ */
        if ($this->participantsByContact($data['phone'])->isNotEmpty()) {
            return redirect()
                ->route('public.tracking-round-qr')
                ->withErrors(['phone' => 'เบอร์นี้ลงทะเบียนไว้แล้ว กรุณายืนยันตัวตนด้วยเบอร์โทร']);
        }

        $profile = $this->rounds->selfRegister(
            $this->formatPhone($data['phone']),
            $request->session()->get(PublicLineLoginController::SESSION_KEY)['userId'] ?? null,
            $data['gender'],
            (int) $data['age_range_id'],
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

        /* ป้ายบนหัวแสดงรหัสกลุ่มตัวอย่าง — โหลดล่วงหน้าเพราะระบบปิด lazy loading */
        $participant->loadMissing('cohortProfile');

        return view('public.tracking-round.dashboard', [
            'participant' => $participant,
            /* หน้าหลักแสดงไทม์ไลน์เต็มชุดแทนการ์ดรอบเดียว จึงใช้ข้อมูลชุดเดียวกับหน้ารายการรอบ */
            'rounds' => $this->allRoundsFor($participant),
            'openIds' => $this->rounds->openRoundsFor($participant)->pluck('id')->all(),
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
            'openIds' => $this->rounds->openRoundsFor($participant)->pluck('id')->all(),
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

        /* ยังไม่เชื่อม LINE — เปิดสวิตช์ไปข้อความก็ส่งไม่ถึง พาไปเชื่อมเลยแทนที่จะ toggle ค่าเปล่า ๆ
           กลับมาจาก LINE แล้ว line_notify เป็นค่าเริ่มต้น (เปิด) อยู่แล้ว ตรงกับความตั้งใจของคนกด */
        if (blank($participant->line_user_id)) {
            return redirect()->route('public.tracking-round-qr.line');
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
            /* 160 เท่าความยาวคอลัมน์อีเมล — ช่องเดียวรับได้ทั้งเบอร์และอีเมล */
            'phone' => ['required', 'string', 'max:160'],
            'name_prefix' => ['required', 'string', 'max:20'],
        ], [
            'phone.required' => 'กรุณากรอกเบอร์โทรหรืออีเมลผู้ถูกประเมิน',
            'name_prefix.required' => 'กรุณากรอกรหัสบุคคลผู้ถูกประเมิน',
        ]);

        $target = $this->participantsByContact($data['phone'])
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

        $form = $this->rounds->formForRound($round);

        return view('public.tracking-round.survey', [
            'participant' => $participant,
            'round' => $round,
            'form' => $form,
            'proxyFor' => $this->proxyFor($request),
            /* เนื้อหาเอกสารความยินยอมของคำถามชนิด consent — ชุดเดียวกับแบบประเมินหลังกิจกรรม */
            'consentDocs' => app(SurveyAnswerBuilder::class)->consentDocsFor($form),
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

        /* หน้านี้คือใบยืนยันการส่ง ไม่ใช่หน้าทั่วไป — รอบที่ยังไม่ได้ตอบต้องเข้าไม่ได้
           ไม่งั้นพิมพ์ URL ตรง ๆ ก็ได้หน้า "ส่งแบบประเมินแล้ว" ทั้งที่ยังไม่ได้ส่งอะไรเลย */
        abort_if($round->answered_at === null, 404);

        $response = $round->surveyResponse;

        return view('public.tracking-round.done', [
            'round' => $round,
            /* วันที่ส่งต้องมาจากใบตอบ ไม่ใช่ answered_at ของรอบ — ใบตอบคือหลักฐานที่ผู้ตอบอ้างอิงได้
               ถ้าใบยังไม่มีด้วยเหตุใดก็ตาม ให้ตกไปที่เวลาที่ปิดรอบ */
            'submittedAt' => $response?->submitted_at ?? $round->answered_at,
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

        /* ผูกไม่ได้เพราะ LINE นี้เป็นของคนอื่น — ต้องบอกผู้ใช้ตรง ๆ ไม่ใช่เงียบ
           ไม่งั้นเขาจะกดเชื่อมวนไปเรื่อย ๆ โดยไม่รู้ว่าติดอะไร */
        /* withTrashed สำคัญ — unique index ที่ฐานข้อมูลนับแถวที่ soft delete ไปแล้วด้วย
           ถ้าเช็กด้วย scope ปกติจะมองไม่เห็นเจ้าของเดิมที่ถูกลบไป แล้วสั่ง update ทับ
           ได้ 500 duplicate entry คาหน้าผู้ใช้แทนที่จะบอกว่าเชื่อมไม่ได้ */
        $ownedByOther = $lineUserId
            && Participant::withTrashed()
                ->where('line_user_id', $lineUserId)->where('id', '!=', $participant->id)->exists();

        $justLinked = $lineUserId && blank($participant->line_user_id) && ! $ownedByOther;

        if ($justLinked) {
            $participant->update(['line_user_id' => $lineUserId]);
        }

        return redirect()
            ->route('public.tracking-round-qr.dashboard')
            ->with('lineLinked', $justLinked)
            ->with('lineConflict', $ownedByOther && blank($participant->line_user_id));
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
     * และข้ามลำดับไปตอบรอบหลังก่อนที่จะตอบรอบก่อนหน้าให้ครบ
     */
    private function openRoundFor(Participant $participant, FollowUpRound $round): FollowUpRound
    {
        $open = $this->rounds->openRoundsFor($participant)->firstWhere('id', $round->id);

        abort_if($open === null, 404, 'รอบนี้ไม่ได้เปิดให้ตอบอยู่');

        return $open;
    }

    /** @return \Illuminate\Support\Collection<int, Participant> */
    private function participantsByContact(string $contact): \Illuminate\Support\Collection
    {
        $contact = trim($contact);

        if ($contact === '') {
            return collect();
        }

        /* ช่องเดียวรับได้ทั้งเบอร์และอีเมล — แยกทางด้วย @ ซึ่งไม่มีทางอยู่ในเบอร์โทร
           มีไว้เป็นทางเลือกให้คนที่ไม่สะดวกใช้เบอร์ ไม่ได้มาแทนเบอร์ */
        if (str_contains($contact, '@')) {
            /* เทียบแบบไม่สนตัวพิมพ์และช่องว่างหัวท้าย ข้อมูลที่เจ้าหน้าที่คีย์เข้ามามีทั้งสองแบบ */
            return Participant::with('cohortProfile')
                ->whereHas('cohortProfile')
                ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($contact)])
                ->get();
        }

        $digits = $this->digits($contact);

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
