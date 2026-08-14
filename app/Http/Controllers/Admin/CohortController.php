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
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CohortController extends Controller
{
    /**
     * เพศเก็บเป็น enum ในตาราง ไม่ได้แยกเป็นตาราง master
     * เพราะเป็นชุดค่าที่ปิดตายตามมาตรฐานข้อมูล ไม่ใช่รายการที่แอดมินเพิ่มเองได้
     * แผนที่นี้เป็นแหล่งเดียวของคำแปล — หน้าจอกับ payload ใช้ตัวเดียวกัน
     */
    private const GENDERS = [
        'female' => 'หญิง',
        'male' => 'ชาย',
        'other' => 'อื่น ๆ',
        'undisclosed' => 'ไม่ระบุ',
    ];

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

        return response()->json([
            'success' => true,
            'path' => $file->store(CohortRequest::CONSENT_DIR, 'local'),
            'name' => $file->getClientOriginalName(),
        ]);
    }

    public function store(CohortRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $entryDate = Carbon::parse($validated['entry_date'])->startOfDay();
        $rounds = $request->selectedRounds($entryDate->toDateString());

        $payload = DB::transaction(function () use ($validated, $entryDate, $rounds) {
            /* ตรวจซ้ำอีกครั้งใต้ transaction — ระหว่างที่แอดมินกรอกฟอร์มค้างไว้
               อาจมีคนอื่นบันทึกรหัสเดียวกันไปแล้ว ปล่อยให้ unique index ระเบิดเป็น 500 ไม่ได้ */
            $personCode = $this->reserveUnusedPersonCode($validated['person_code']);

            $participant = Participant::create([
                /* code กับ person_code ตั้งค่าเท่ากันสำหรับคนที่เพิ่มจากหน้านี้
                   เพื่อให้รหัสที่แอดมินเห็นตอนกดรันเลข ตรงกับรหัสที่โผล่ในตารางเป๊ะ */
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
                'cohort_code' => $this->nextCohortCode(),
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
            'nextPersonCode' => $this->nextPersonCode(),
            /* ฟอร์มต่อรหัสบุคคลท้าย prefix นี้เอง จะได้ไม่ต้องประกอบ URL เองสองที่ */
            'assessmentLinkBase' => self::assessmentLink(''),
            'genders' => collect(self::GENDERS)->map(fn (string $label, string $value) => [
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

    /** รหัสบุคคลถัดไปที่ยังไม่มีใครใช้ — รูปแบบ PID-0001 */
    private function nextPersonCode(): string
    {
        return $this->personCodeFrom($this->highestPersonCodeNumber() + 1);
    }

    private function personCodeFrom(int $running): string
    {
        return 'PID-'.str_pad((string) $running, 4, '0', STR_PAD_LEFT);
    }

    /**
     * เลขสูงสุดจาก "ตัวเลขท้ายรหัส" ไม่ใช่ max() ของข้อความ
     * เหตุผลเดียวกับ MasterDataController::runningCode() — PID-9 กับ PID-0010 เทียบเป็นข้อความแล้วผิด
     */
    private function highestPersonCodeNumber(bool $lock = false): int
    {
        $query = Participant::withTrashed()->where('person_code', 'like', 'PID-%');

        if ($lock) {
            $query->lockForUpdate();
        }

        return (int) $query->pluck('person_code')
            ->map(fn (string $code) => (int) Str::afterLast($code, '-'))
            ->max();
    }

    /**
     * รหัสที่ฟอร์มส่งมา ถ้ามีคนใช้ไปแล้วระหว่างที่กรอกฟอร์มค้างไว้ ให้ออกเลขถัดไปให้แทน
     * ดีกว่าปฏิเสธทั้งฟอร์มแล้วให้กรอกใหม่ทั้งหมด เพราะรหัสไม่ใช่ค่าที่แอดมินตั้งใจพิมพ์เอง
     */
    private function reserveUnusedPersonCode(string $requested): string
    {
        $taken = Participant::withTrashed()
            ->where(fn ($q) => $q->where('person_code', $requested)->orWhere('code', $requested))
            ->lockForUpdate()
            ->exists();

        return $taken ? $this->personCodeFrom($this->highestPersonCodeNumber(lock: true) + 1) : $requested;
    }

    private function nextCohortCode(): string
    {
        $running = CohortProfile::where('cohort_code', 'like', 'CHT-%')
            ->lockForUpdate()
            ->pluck('cohort_code')
            ->map(fn (string $code) => (int) Str::afterLast($code, '-'))
            ->max() ?? 0;

        return 'CHT-'.str_pad((string) ($running + 1), 4, '0', STR_PAD_LEFT);
    }

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
        $nextRoundName = null;

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

            if (! $nextRoundName && $state !== 'ตอบแล้ว' && ! $cp->isStopped()) {
                $nextRoundName = $r->name;
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
            'pid' => $p->code ?? $p->person_code ?? 'PID-'.$p->id,
            'cohortCode' => $cp->cohort_code,
            'name' => $p->name,
            'phone' => $p->phone ?? '',
            'gender' => self::GENDERS[$p->gender] ?? 'ไม่ระบุ',
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
            'nextRound' => $nextRoundName ?? 'ครบกำหนดแล้ว',
            /* ลิงก์ผูกกับรหัสบุคคล ไม่ใช่รหัสกลุ่มตัวอย่าง เพราะฟอร์มต้องแสดงลิงก์นี้
               ให้คัดลอกได้ตั้งแต่ก่อนกดบันทึก ตอนนั้นยังไม่มี cohort_code */
            'assessmentLink' => self::assessmentLink($p->person_code),
            'rounds' => $roundStates,
        ];
    }
}
