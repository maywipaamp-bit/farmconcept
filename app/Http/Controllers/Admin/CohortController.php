<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CohortRequest;
use App\Models\Area;
use App\Models\CohortProfile;
use App\Models\Consent;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Option;
use App\Models\Participant;
use App\Models\TargetGroup;
use App\Services\PersonCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CohortController extends Controller
{
    public function __construct(private readonly PersonCodeGenerator $personCodes) {}

    /** คำแปลของเพศอยู่ที่ config('farmconcept.genders') ที่เดียว — ใช้ร่วมกับหน้าที่ผู้เข้าร่วมกรอกเอง */
    private function genders(): array
    {
        return config('farmconcept.genders');
    }

    /** แหล่งที่มาของกลุ่มตัวอย่าง เก็บเป็น "รหัส" ลง ptp_cohort_profiles.source_type ไม่ใช่ id */
    private const SOURCE_GROUP = 'cohort_source';

    public function index(Request $request): View|JsonResponse
    {
        $profiles = CohortProfile::with([
            'participant.area',
            'participant.targetGroup',
            'participant.occupation',
            'participant.ageRange',
            /* ใบยินยอมบอกว่าระเบียนนี้มาทางไหน (ทำเอง / เจ้าหน้าที่คีย์) — โหลดมาด้วย
               ไม่งั้นทุกแถวยิง query แยกใบละครั้ง และตัวกัน lazy loading จะฟ้องทันที */
            'participant.consents',
            'rounds',
        ])
        ->orderByDesc('entry_date')
        ->orderByDesc('id')
        ->get();

        $areas = Area::all();
        $templates = FollowUpRoundTemplate::active()->get();
        $sourceLabels = $this->sourceLabels();

        $memberPayloads = $profiles->map(fn (CohortProfile $cp) => $this->toMemberPayload($cp, $sourceLabels));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $memberPayloads,
                'areas' => $areas->pluck('name'),
                'rounds' => $templates->pluck('name'),
            ]);
        }

        return view('admin.cohort.index', [
            'members' => $memberPayloads,
            'areas' => $areas,
            'templates' => $templates,
            'lookups' => $this->lookupPayload(),
        ]);
    }

    /**
     * ตัวเลือกทั้งหมดที่ฟอร์มต้องใช้ + รหัสบุคคลถัดไป
     *
     * ปุ่ม "รันเลข" เรียก endpoint นี้ทุกครั้งแทนที่จะนับต่อจากรายการบนหน้าจอ
     * เพราะหน้าจอเห็นเฉพาะแถวที่โหลดมาตอนเปิดหน้า ถ้ามีคนอื่นเพิ่มระหว่างนั้น
     * เลขที่ได้จะซ้ำกับของเขาทันที
     */
    public function lookups(): JsonResponse
    {
        return response()->json($this->lookupPayload());
    }

    /**
     * รับไฟล์ใบยินยอมก่อนกดบันทึก แล้วคืน path กลับไปให้ฟอร์มถือไว้
     *
     * เก็บบน disk local ไม่ใช่ public — ใบยินยอมมีชื่อ เบอร์โทร และลายเซ็นจริง
     * ไฟล์ที่อัปโหลดแล้วแต่ไม่ได้กดบันทึกจะค้างอยู่ ต้องมีงานตามเก็บกวาดภายหลัง
     */
    public function uploadConsent(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'file.required' => 'กรุณาเลือกไฟล์ใบยินยอม',
            'file.mimes' => 'รองรับเฉพาะไฟล์ PDF, JPG และ PNG',
            'file.max' => 'ไฟล์ต้องมีขนาดไม่เกิน 10 MB',
        ], ['file' => 'ไฟล์ใบยินยอม']);

        $file = $request->file('file');
        $path = $this->storeConsentFile($file);

        if (! $path) {
            throw ValidationException::withMessages([
                'file' => 'ไม่สามารถบันทึกไฟล์ใบยินยอมได้ กรุณาตรวจสอบไฟล์แล้วลองใหม่อีกครั้ง',
            ]);
        }

        return response()->json([
            'success' => true,
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ]);
    }

    private function storeConsentFile(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf');
        $path = CohortRequest::CONSENT_DIR.'/'.Str::random(40).'.'.$extension;
        $temporaryPath = $file->getRealPath() ?: $file->getPathname();

        if ($temporaryPath && file_exists($temporaryPath)) {
            $contents = @file_get_contents($temporaryPath);
            if ($contents !== false && Storage::disk('local')->put($path, $contents)) {
                return $path;
            }
        }

        try {
            return $file->store(CohortRequest::CONSENT_DIR, 'local') ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function store(CohortRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $entryDate = Carbon::parse($validated['entry_date'])->startOfDay();
        $rounds = $request->selectedRounds($entryDate->toDateString());

        $payload = DB::transaction(function () use ($validated, $entryDate, $rounds) {
            /* ออกรหัสตอนบันทึก ไม่ใช่ตอนเปิดฟอร์ม — ระหว่างที่แอดมินกรอกฟอร์มค้างไว้
               อาจมีคนอื่นบันทึกไปหลายคนแล้ว รหัสที่จองไว้ล่วงหน้าจึงชนกันได้เสมอ
               ออกตรงนี้ใต้ transaction ที่ล็อกแถวไว้แล้ว จะไม่มีช่องให้ชนอีก */
            $personCode = $this->personCodes->next(lock: true);

            $participant = Participant::create([
                /* code กับ person_code ตั้งค่าเท่ากันสำหรับคนที่เพิ่มจากหน้านี้
                   เพื่อให้รหัสที่โผล่ในตารางกับรหัสบนใบยินยอมเป็นตัวเดียวกัน */
                'code' => $personCode,
                'person_code' => $personCode,
                'name' => $validated['name'],
                'phone' => $this->formatPhone($validated['phone']),
                'gender' => $validated['gender'],
                'age_range_id' => $validated['age_range_id'] ?? null,
                'occupation_id' => $validated['occupation_id'] ?? null,
                'area_id' => $validated['area_id'],
                'target_group_id' => $validated['target_group_id'],
                'consent_status' => 'ยินยอม',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $stopped = ($validated['status'] ?? null) === 'ยุติการติดตาม';

            $cohortProfile = CohortProfile::create([
                'participant_id' => $participant->id,
                'cohort_code' => $this->personCodes->nextCohortCode(lock: true),
                'entry_date' => $entryDate,
                'source_type' => $validated['source_code'],
                /* สถานะอื่นทั้งหมด derive จากวันครบกำหนด มีแค่ "ยุติการติดตาม" ที่เป็นการตัดสินใจของคน
                   จึงเป็นสถานะเดียวที่มีคอลัมน์เก็บ */
                'stopped_at' => $stopped ? now() : null,
                'stopped_reason' => $stopped ? 'ตั้งค่าตอนเพิ่มกลุ่มตัวอย่าง' : null,
                'stopped_by' => $stopped ? auth()->id() : null,
            ]);

            Consent::create([
                'participant_id' => $participant->id,
                'status' => 'ยินยอม',
                'consent_version' => config('farmconcept.consent_version'),
                'consented_at' => now(),
                'file_path' => $validated['consent_file_path'] ?? null,
                'recorded_via' => 'admin_cohort',
                'recorded_by' => auth()->id(),
            ]);

            foreach ($rounds as $round) {
                FollowUpRound::create([
                    'cohort_profile_id' => $cohortProfile->id,
                    'template_id' => $round['template_id'],
                    'name' => $round['name'],
                    'offset_days' => $round['offset_days'],
                    'due_date' => $round['due_date'],
                ]);
            }

            $cohortProfile->load([
                'participant.area', 'participant.targetGroup', 'participant.occupation',
                'participant.ageRange', 'rounds',
            ]);

            return $this->toMemberPayload($cohortProfile, $this->sourceLabels());
        });

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มกลุ่มตัวอย่างสำเร็จ',
            'data' => $payload,
            'evalLink' => $payload['assessmentLink'],
            'lineBindLink' => url('/line/bind?code='.$payload['cohortCode']),
        ]);
    }

    /**
     * แก้ไขกลุ่มตัวอย่างที่มีอยู่แล้ว
     *
     * รหัสบุคคลกับรหัสกลุ่มตัวอย่างแก้ไม่ได้ — สองค่านี้ถูกพิมพ์ลงใบยินยอมและใช้เข้าระบบไปแล้ว
     * เปลี่ยนเมื่อไรคนที่ถือกระดาษอยู่ก็เข้าไม่ได้ทันที
     *
     * ใบติดตามที่ตอบไปแล้วห้ามแตะ ทั้งวันครบกำหนดและการถอดรอบออก — คำตอบผูกกับใบนั้น
     * แก้วันที่ย้อนหลังเท่ากับเปลี่ยนความหมายของข้อมูลที่เก็บมาแล้ว
     */
    public function update(CohortRequest $request, CohortProfile $cohortProfile): JsonResponse
    {
        $validated = $request->validated();
        $entryDate = Carbon::parse($validated['entry_date'])->startOfDay();
        $rounds = $request->selectedRounds($entryDate->toDateString());

        $payload = DB::transaction(function () use ($validated, $entryDate, $rounds, $cohortProfile) {
            $participant = $cohortProfile->participant;

            $participant->update([
                'name' => $validated['name'],
                'phone' => $this->formatPhone($validated['phone']),
                'gender' => $validated['gender'],
                'age_range_id' => $validated['age_range_id'] ?? null,
                'occupation_id' => $validated['occupation_id'] ?? null,
                'area_id' => $validated['area_id'],
                'target_group_id' => $validated['target_group_id'],
                'updated_by' => auth()->id(),
            ]);

            $stopped = ($validated['status'] ?? null) === 'ยุติการติดตาม';

            $cohortProfile->update([
                'entry_date' => $entryDate,
                'source_type' => $validated['source_code'],
                /* เคยยุติแล้วกลับมาติดตามต่อได้ ล้างเหตุผลเดิมทิ้งไปพร้อมกัน
                   ไม่งั้นเหลือเหตุผลค้างอยู่กับคนที่กลับมาติดตามแล้ว อ่านแล้วเข้าใจผิด */
                'stopped_at' => $stopped ? ($cohortProfile->stopped_at ?? now()) : null,
                'stopped_reason' => $stopped ? ($cohortProfile->stopped_reason ?? 'ตั้งค่าตอนแก้ไขกลุ่มตัวอย่าง') : null,
                'stopped_by' => $stopped ? ($cohortProfile->stopped_by ?? auth()->id()) : null,
            ]);

            $this->syncRounds($cohortProfile, $rounds);

            $cohortProfile->load([
                'participant.area', 'participant.targetGroup', 'participant.occupation',
                'participant.ageRange', 'participant.consents', 'rounds',
            ]);

            return $this->toMemberPayload($cohortProfile, $this->sourceLabels());
        });

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขกลุ่มตัวอย่างเรียบร้อย',
            'data' => $payload,
        ]);
    }

    /**
     * ปรับชุดใบติดตามให้ตรงกับที่แอดมินเลือก โดยไม่แตะใบที่ตอบไปแล้ว
     *
     * @param  array<int, array{template_id: int, name: string, offset_days: int, due_date: string}>  $rounds
     */
    private function syncRounds(CohortProfile $cohortProfile, array $rounds): void
    {
        $existing = $cohortProfile->rounds()->get()->keyBy('template_id');
        $keep = [];

        foreach ($rounds as $round) {
            $current = $existing->get($round['template_id']);
            $keep[] = $round['template_id'];

            /* ใบที่ตอบไปแล้วปล่อยไว้เหมือนเดิมทุกอย่าง ไม่ใช่แค่ไม่ลบ */
            if ($current?->answered_at !== null) {
                continue;
            }

            $attributes = [
                'name' => $round['name'],
                'offset_days' => $round['offset_days'],
                'due_date' => $round['due_date'],
            ];

            $current
                ? $current->update($attributes)
                : FollowUpRound::create($attributes + [
                    'cohort_profile_id' => $cohortProfile->id,
                    'template_id' => $round['template_id'],
                ]);
        }

        $cohortProfile->rounds()
            ->whereNotIn('template_id', $keep)
            ->whereNull('answered_at')
            ->delete();
    }

    /**
     * ลบกลุ่มตัวอย่าง — ใช้กับระเบียนที่คีย์ผิดเท่านั้น
     *
     * มีคำตอบแล้วลบไม่ได้ ต้องใช้ "ยุติการติดตาม" แทน — คำตอบที่เก็บมาเป็นข้อมูลวิจัย
     * ลบเจ้าของคำตอบทิ้งแล้วชุดข้อมูลที่เหลือจะอ้างถึงคนที่ไม่มีอยู่
     */
    public function destroy(CohortProfile $cohortProfile): JsonResponse
    {
        $answered = $cohortProfile->rounds()->whereNotNull('answered_at')->count();

        if ($answered > 0) {
            return response()->json([
                'success' => false,
                'message' => 'ลบไม่ได้ — มีคำตอบแบบประเมินแล้ว '.$answered.' รอบ กรุณาใช้ "ยุติการติดตาม" แทน',
            ], 422);
        }

        DB::transaction(function () use ($cohortProfile) {
            $cohortProfile->rounds()->delete();
            $cohortProfile->delete();

            /* คืนบัญชี LINE ให้ว่างก่อนลบ — unique index ของ line_user_id นับแถวที่ soft delete ด้วย
               ไม่ล้างไว้ เจ้าของบัญชี LINE ตัวจริงจะเชื่อมไม่ได้อีกเลยเพราะติดแถวที่ถูกลบไปแล้ว
               ต่างจากรหัสบุคคลกับเบอร์ที่ต้องคงไว้ (ดูเหตุผลด้านล่าง) เพราะบัญชี LINE
               ไม่ใช่ข้อมูลระบุตัวตนในงานวิจัย เป็นแค่ปลายทางแจ้งเตือน */
            $cohortProfile->participant?->update(['line_user_id' => null]);

            /* ผู้เข้าร่วมถูก soft delete ไว้ ไม่ล้างทิ้งจริง — รหัสบุคคลกับเบอร์มี unique index
               ถ้าลบออกจริงแล้วมีคนใช้รหัสเดิมซ้ำ ประวัติเก่าจะย้ายไปติดคนใหม่โดยไม่มีใครรู้ */
            $cohortProfile->participant?->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'ลบกลุ่มตัวอย่างเรียบร้อย',
        ]);
    }

    public function show(CohortProfile $cohortProfile): View|JsonResponse
    {
        $cohortProfile->load([
            'participant.area',
            'participant.targetGroup',
            'participant.occupation',
            'participant.ageRange',
            'participant.consents',
            /* FollowUpNote ใช้ชื่อ createdBy ไม่ใช่ author — ของเดิมเขียนผิดไว้
               แต่ยังไม่ระเบิดเพราะไม่มีใครมีบันทึกติดตามเลย ตอนนี้รอบติดตามเขียนบันทึกให้แล้ว
               หน้านี้จึงจะ 500 ทันทีที่เปิดดูคนที่เคยถูกตามไป */
            'participant.notes.createdBy',
            'participant.consents',
            'rounds.surveyResponse',
        ]);

        $payload = $this->toMemberPayload($cohortProfile, $this->sourceLabels());

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        }

        return view('admin.cohort.detail', [
            'member' => $payload,
            'cohortProfile' => $cohortProfile,
            'tabs' => $this->detailTabs($cohortProfile),
        ]);
    }

    /**
     * ข้อมูลของแท็บใต้หน้ารายละเอียด — ประวัติกิจกรรม · แบบประเมิน · การซื้อสินค้า · การติดตาม
     *
     * ทุกแท็บอ่านจากตารางที่มีอยู่แล้ว ไม่มีการเก็บสำเนาไว้ที่โปรไฟล์
     * สถานะจึงตรงกับต้นทางเสมอ ไม่ต้องมีงาน sync ที่พังเงียบ ๆ ได้
     *
     * @return array<string, mixed>
     */
    private function detailTabs(CohortProfile $cohortProfile): array
    {
        $participant = $cohortProfile->participant;

        $registrations = $participant->registrations()
            ->with(['activity.program', 'activity.course'])
            /* กิจกรรมที่ถูกลบไปแล้วไม่ต้องโชว์เป็นแถวว่าง */
            ->whereHas('activity')
            ->orderBy('registered_at')
            ->get();

        $purchases = $participant->purchases()
            ->with(['items', 'channel', 'createdBy:id,name'])
            ->orderBy('order_date')
            ->get();

        $rounds = $cohortProfile->rounds->sortBy('offset_days')->values();
        $answered = $rounds->whereNotNull('answered_at')->count();

        return [
            /* ข้อมูลทั่วไปเรียงเป็นคู่ "ป้าย : ค่า" ตามลำดับที่เจ้าหน้าที่อ่านจริง
               ประกอบใน controller ไม่ใช่ใน view เพราะบางค่าต้องรวมจากหลายที่ (ความยินยอม, ตอบครบ) */
            /* ไม่แสดงชื่อ–นามสกุล (คำสั่งทีม) — กลุ่มตัวอย่างเป็นข้อมูลนิรนาม อ้างด้วยรหัสบุคคล */
            'info' => [
                ['รหัสบุคคล', $participant->person_code ?: '—'],
                ['เบอร์โทร', $participant->phone ?: '—'],
                ['เพศ', $this->genders()[$participant->gender] ?? 'ไม่ระบุ'],
                ['ช่วงอายุ', $participant->ageBand() ?? 'ไม่ระบุ'],
                ['อาชีพ', $participant->occupation?->label ?? $participant->occupation_raw ?? 'ไม่ระบุ'],
                ['พื้นที่', $participant->area?->name ?? 'ไม่ระบุ'],
                ['กลุ่มเป้าหมาย', $participant->targetGroup?->name ?? 'ไม่ระบุ'],
                ['แหล่งที่มา', $this->sourceLabels()[$cohortProfile->source_type] ?? 'ไม่ระบุ'],
                ['วันที่เข้ากลุ่มตัวอย่าง', $cohortProfile->entry_date?->toDateString()],
                ['ความยินยอม', $this->consentLabel($participant)],
                ['LINE', filled($participant->line_user_id) ? 'ผูกแล้ว' : 'ยังไม่ผูก'],
                ['ตอบครบ', $answered.' จาก '.$rounds->count().' รอบ'],
            ],

            'entryDate' => $cohortProfile->entry_date?->toDateString(),

            /* ช่วงติดตามคำนวณจากใบของคนนั้น ไม่ใช่ค่ากลางของระบบ — วันครบกำหนดของแต่ละคนต่างกัน
               เจ้าหน้าที่ต้องรู้ว่า "ตอบได้ตั้งแต่วันไหนถึงวันไหน" ไม่ใช่แค่วันครบกำหนดลอย ๆ */
            'timeline' => $rounds->map(fn (FollowUpRound $r) => [
                'name' => $r->name,
                'state' => $cohortProfile->isStopped() ? 'ยุติการติดตาม' : $r->state(),
                'due' => $r->due_date?->toDateString(),
                'from' => $r->windowStart()->toDateString(),
                'to' => $r->windowEnd()->toDateString(),
                /* รอบแรก (offset 0) ทำในวันที่เข้ากลุ่มเลย ไม่มีช่วงให้รอ จึงไม่ต้องบอกช่วง */
                'hasWindow' => $r->offset_days !== 0,
                'answeredAt' => $r->answered_at?->toDateString(),
            ])->values(),

            'activities' => $registrations->map(fn ($r) => [
                'date' => $r->activity->start_date,
                'name' => $r->activity->name,
                'program' => collect([$r->activity->program?->name, $r->activity->course?->name])
                    ->filter()->join(' · ') ?: '—',
                'venue' => $r->activity->organizer ?: '—',
                /* เช็คอินแล้วคือ "เข้าร่วม" จริง ไม่ใช่แค่ลงชื่อไว้ — สองอย่างนี้ต่างกันในรายงาน */
                'status' => $r->checked_in_at ? 'เข้าร่วม' : ($r->payment_status ?: 'ลงทะเบียนแล้ว'),
                'joined' => $r->checked_in_at !== null,
            ])->values(),

            'purchases' => $purchases->map(fn ($p) => [
                'date' => $p->order_date,
                'items' => $p->items->map(fn ($i) => trim($i->product_name.' '.$i->quantity))->join(' · ') ?: '—',
                'store' => $p->store_name ?: '—',
                'channel' => $p->channel?->label ?? '—',
                'status' => $p->status ?: '—',
                'paid' => $p->status === 'ชำระแล้ว',
                'amount' => (float) $p->amount,
                'by' => $p->createdBy?->name ?? 'ระบบ',
            ])->values(),
            'purchaseTotal' => (float) $purchases->sum('amount'),

            'notes' => $participant->notes->sortBy('noted_at')->map(fn ($n) => [
                'at' => $n->noted_at,
                'source' => $n->source,
                'kind' => $n->kind,
                'body' => $n->body,
                'by' => $n->createdBy?->name ?? 'ระบบอัตโนมัติ',
            ])->values(),
        ];
    }

    public function stopFollowUp(Request $request, CohortProfile $cohortProfile): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $cohortProfile->update([
            'stopped_at' => now(),
            'stopped_reason' => $request->input('reason'),
            'stopped_by' => auth()->id(),
        ]);

        $cohortProfile->load(['participant.area', 'participant.targetGroup', 'rounds']);

        return response()->json([
            'success' => true,
            'message' => 'ยุติการติดตามเรียบร้อย',
            'data' => $this->toMemberPayload($cohortProfile, $this->sourceLabels()),
        ]);
    }

    /**
     * ยกเลิกการเชื่อม LINE — คืนบัญชีให้ว่าง เพื่อผูกกับบัญชีใหม่หรือคนอื่นได้
     *
     * ใช้เมื่อคนนั้นทำบัญชี LINE หาย เปลี่ยนบัญชี หรือกดเชื่อมผิดคน (บัญชี LINE หนึ่งบัญชี
     * ผูกได้กับคนเดียวเท่านั้น — ไม่ล้างของเดิมก่อน จะเชื่อมให้คนใหม่ไม่ได้เลย)
     *
     * ไม่กระทบรอบติดตามหรือคำตอบที่มีอยู่แล้ว กระทบแค่ปลายทางแจ้งเตือนเท่านั้น
     */
    public function unlinkLine(CohortProfile $cohortProfile): JsonResponse
    {
        $participant = $cohortProfile->participant;

        abort_if($participant === null, 404);

        if (blank($participant->line_user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'คนนี้ยังไม่ได้เชื่อม LINE',
            ], 422);
        }

        $participant->update([
            'line_user_id' => null,
            'line_notify' => false,
            'line_display_name' => null,
            'line_picture_url' => null,
        ]);

        $cohortProfile->load(['participant.area', 'participant.targetGroup', 'rounds']);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการเชื่อม LINE เรียบร้อย',
            'data' => $this->toMemberPayload($cohortProfile, $this->sourceLabels()),
        ]);
    }

    /** ตัวเลือกทุกช่องของฟอร์ม — หน้าจอไม่มีรายการไหน hardcode ไว้เอง */
    private function lookupPayload(): array
    {
        $options = Option::query()
            ->active()
            ->whereIn('option_group', ['age_range', 'occupation', self::SOURCE_GROUP])
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'option_group', 'code', 'label'])
            ->groupBy('option_group');

        $byGroup = fn (string $group) => ($options[$group] ?? collect())
            ->map(fn (Option $o) => ['value' => $o->id, 'label' => $o->label])->values();

        return [
            'today' => now()->toDateString(),
            /* รหัสถัดไปที่ระบบจะออกให้ — ไว้ให้ดูเฉย ๆ ไม่ได้จองไว้
               รหัสจริงออกตอนกดบันทึกเสมอ ดู CohortController::store() */
            'nextPersonCode' => $this->personCodes->next(),
            'genders' => collect($this->genders())->map(fn (string $label, string $value) => [
                'value' => $value, 'label' => $label,
            ])->values(),
            'ageRanges' => $byGroup('age_range'),
            'occupations' => $byGroup('occupation'),
            'areas' => Area::orderBy('name')->get(['id', 'name'])
                ->map(fn (Area $a) => ['value' => $a->id, 'label' => $a->name])->values(),
            'targetGroups' => TargetGroup::where('is_active', true)->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn (TargetGroup $t) => ['value' => $t->id, 'label' => $t->name])->values(),
            /* แหล่งที่มาส่งเป็น code เพราะ ptp_cohort_profiles.source_type เก็บรหัส ไม่ใช่ FK */
            'sources' => ($options[self::SOURCE_GROUP] ?? collect())
                ->map(fn (Option $o) => ['value' => $o->code, 'label' => $o->label])->values(),
            'statuses' => CohortRequest::STATUSES,
            /* รอบติดตามมาจากหน้าตั้งค่ารอบประเมินล้วน ๆ — ไม่มี 3/6/12 เขียนตายที่ไหนเลย
               checked บอกว่ารอบไหนถูกติ๊กไว้ให้ตั้งแต่เปิดฟอร์ม */
            'followUpRounds' => FollowUpRoundTemplate::active()->get()
                ->map(fn (FollowUpRoundTemplate $t) => [
                    'value' => $t->id,
                    'label' => $t->name,
                    'offsetDays' => $t->offset_days,
                    'checked' => true,
                ])->values(),
        ];
    }

    public static function assessmentLink(string $personCode): string
    {
        return url('/evaluations/start?person='.$personCode);
    }

    /* ตัวออกรหัสอยู่ที่ PersonCodeGenerator — ใช้ร่วมกับการลงทะเบียนตัวเองผ่าน QR
       จะได้ไม่มีรหัสสองรูปแบบในระบบเดียวกัน */


    /** 0812345678 → 081-234-5678 เก็บรูปแบบเดียวกับข้อมูลเดิมทั้งฐาน */
    private function formatPhone(string $digits): string
    {
        return strlen($digits) === 10
            ? substr($digits, 0, 3).'-'.substr($digits, 3, 3).'-'.substr($digits, 6)
            : $digits;
    }

    /** แผนที่ code → ชื่อไทยของแหล่งที่มา ดึงครั้งเดียวต่อคำขอ ไม่ให้ join รายแถว */
    private function sourceLabels(): Collection
    {
        return Option::group(self::SOURCE_GROUP)->pluck('label', 'code');
    }

    /**
     * ระเบียนนี้ใครเป็นคนสร้าง — ผู้เข้าร่วมทำเอง หรือเจ้าหน้าที่คีย์ให้
     *
     * ใบยินยอมบอกได้ตรงที่สุดเพราะบันทึกไว้ตอนสร้างว่ามาทางไหน (recorded_via)
     * ไม่มีใบยินยอมค่อยดูว่ามีเจ้าหน้าที่เป็นผู้สร้างไหม — ทำเองผ่าน QR ไม่มีใครล็อกอินอยู่
     * ไม่มีสัญญาณทั้งคู่ (ข้อมูลเก่าที่นำเข้ามา) ให้ตอบว่าไม่ระบุ ดีกว่าเดาแล้วรายงานผิด
     */
    /** ความยินยอม — บอกด้วยว่ามีไฟล์ใบยินยอมแนบไว้ไหม เพราะเป็นหลักฐานที่ต้องหาให้เจอตอนตรวจ */
    private function consentLabel(Participant $p): string
    {
        if ($p->consent_status !== 'ยินยอม') {
            return $p->consent_status ?: 'ยังไม่ยืนยัน';
        }

        $consent = $p->relationLoaded('consents')
            ? $p->consents->sortByDesc('id')->first()
            : $p->consents()->latest('id')->first();

        return filled($consent?->file_path) ? 'ได้รับแล้ว · มีเอกสารแนบ' : 'ได้รับแล้ว';
    }

    private function createdViaLabel(Participant $p): string
    {
        $via = $p->relationLoaded('consents')
            ? $p->consents->sortByDesc('id')->first()?->recorded_via
            : $p->consents()->latest('id')->value('recorded_via');

        return match ($via) {
            'self_qr' => 'ลงทะเบียนเอง',
            'registration' => 'ลงทะเบียนกิจกรรม',
            'admin_cohort' => 'แอดมินสร้าง',
            default => $p->created_by ? 'แอดมินสร้าง' : 'ไม่ระบุ',
        };
    }

    private function toMemberPayload(CohortProfile $cp, Collection $sourceLabels): array
    {
        $p = $cp->participant;
        $today = Carbon::today();

        $rounds = $cp->rounds->sortBy('offset_days')->values();
        $roundStates = [];
        $nextRound = null;

        foreach ($rounds as $r) {
            $state = $cp->isStopped() ? 'ยุติการติดตาม' : $r->state($today);

            $roundStates[] = [
                'id' => (string) $r->id,
                'name' => $r->name,
                'short' => $r->name,
                'offsetDays' => $r->offset_days,
                'dueDate' => $r->due_date?->toDateString(),
                'due' => $r->due_date?->toDateString(),
                'at' => $r->answered_at?->toDateString(),
                'answeredAt' => $r->answered_at?->toDateString(),
                'state' => $state,
            ];

            /* รอบถัดไป = รอบแรกที่ยังไม่ตอบ เรียงตามจำนวนวัน
               เก็บทั้งชื่อและวันครบกำหนด เพราะหน้ารายการต้องบอกว่า "ต้องตามภายในเมื่อไหร่"
               ไม่ใช่แค่ชื่อรอบลอย ๆ ที่ยังต้องเปิดเข้าไปดูวันอีกที */
            if ($nextRound === null && $state !== 'ตอบแล้ว' && ! $cp->isStopped()) {
                $nextRound = end($roundStates);
            }
        }

        $allAnswered = count($roundStates) > 0 && collect($roundStates)->every(fn ($r) => $r['state'] === 'ตอบแล้ว');
        $hasOverdue = collect($roundStates)->some(fn ($r) => $r['state'] === 'เกินกำหนด');

        $overallStatus = match (true) {
            $cp->isStopped() => 'หลุดการติดตาม',
            $allAnswered => 'ติดตามครบ',
            $hasOverdue => 'เกินกำหนด',
            default => 'กำลังติดตาม',
        };

        return [
            'id' => (string) $cp->id,
            'db_id' => $cp->id,
            'pid' => $p->code ?? $p->person_code ?? PersonCodeGenerator::PREFIX.$p->id,
            'cohortCode' => $cp->cohort_code,
            /* ไม่มีคีย์ name ระดับบนสุด — หน้ารายการ/รายละเอียดอ้างด้วยรหัสบุคคลเท่านั้น (คำสั่งทีม)
               ชื่อเหลืออยู่ที่ edit.name ที่เดียว เพราะฟอร์มแก้ไขยังมีช่องชื่อให้กรอก */
            'phone' => $p->phone ?? '',
            'gender' => $this->genders()[$p->gender] ?? 'ไม่ระบุ',
            'age' => $p->ageBand() ?? 'ไม่ระบุช่วงอายุ',
            'job' => $p->occupation?->label ?? $p->occupation_raw ?? 'ไม่ระบุอาชีพ',
            'source' => $sourceLabels[$cp->source_type] ?? $p->source ?? 'ไม่ระบุแหล่งที่มา',
            'area' => $p->area?->name ?? 'ไม่ระบุพื้นที่',
            'target' => $p->targetGroup?->name ?? 'ไม่ระบุกลุ่ม',
            'line' => ! empty($p->line_user_id),
            'email' => $p->email ?: '',
            'createdVia' => $this->createdViaLabel($p),
            'consent' => $p->consent_status === 'ยินยอม',
            'base' => $cp->entry_date?->toDateString(),
            'entryDate' => $cp->entry_date?->toDateString(),
            'status' => $overallStatus,
            'stopped' => $cp->isStopped(),
            'stoppedReason' => $cp->stopped_reason,
            'nextRound' => $nextRound['name'] ?? 'ครบกำหนดแล้ว',
            'nextRoundDue' => $nextRound['dueDate'] ?? null,
            'nextRoundState' => $nextRound['state'] ?? null,
            /* ลิงก์ผูกกับรหัสบุคคล ไม่ใช่รหัสกลุ่มตัวอย่าง เพราะฟอร์มต้องแสดงลิงก์นี้
               ให้คัดลอกได้ตั้งแต่ก่อนกดบันทึก ตอนนั้นยังไม่มี cohort_code */
            'assessmentLink' => self::assessmentLink($p->person_code),
            'rounds' => $roundStates,
            /* ค่าดิบสำหรับเติมฟอร์มแก้ไข — ช่องอื่นในนี้เป็นข้อความสำหรับแสดงผลแล้ว
               (เพศเป็น "ชาย" ไม่ใช่ "male") เอากลับเข้า <select> ไม่ได้ */
            'edit' => [
                'name' => $p->name,
                'phone' => preg_replace('/\D+/', '', (string) $p->phone),
                'gender' => $p->gender,
                'ageRangeId' => $p->age_range_id,
                'occupationId' => $p->occupation_id,
                'areaId' => $p->area_id,
                'targetGroupId' => $p->target_group_id,
                'sourceCode' => $cp->source_type,
                'entryDate' => $cp->entry_date?->toDateString(),
                'status' => $cp->isStopped() ? 'ยุติการติดตาม' : 'กำลังติดตาม',
                /* ใบที่ตอบแล้วต้องล็อกไม่ให้ถอดออกหรือแก้วันครบกำหนด — คำตอบผูกกับใบนั้น */
                'rounds' => $rounds->map(fn (FollowUpRound $r) => [
                    'templateId' => $r->template_id,
                    'dueDate' => $r->due_date?->toDateString(),
                    'answered' => $r->answered_at !== null,
                ])->values(),
            ],
        ];
    }
}
