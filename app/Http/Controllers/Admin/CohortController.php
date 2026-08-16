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
        ]);
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
            'name' => $p->name,
            'phone' => $p->phone ?? '',
            'gender' => $this->genders()[$p->gender] ?? 'ไม่ระบุ',
            'age' => $p->ageBand() ?? 'ไม่ระบุช่วงอายุ',
            'job' => $p->occupation?->label ?? $p->occupation_raw ?? 'ไม่ระบุอาชีพ',
            'source' => $sourceLabels[$cp->source_type] ?? $p->source ?? 'ไม่ระบุแหล่งที่มา',
            'area' => $p->area?->name ?? 'ไม่ระบุพื้นที่',
            'target' => $p->targetGroup?->name ?? 'ไม่ระบุกลุ่ม',
            'line' => ! empty($p->line_user_id),
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
        ];
    }
}
