<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CohortRequest;
use App\Models\Area;
use App\Models\CohortProfile;
use App\Models\Consent;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Participant;
use App\Models\TargetGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CohortController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $profiles = CohortProfile::with([
            'participant.area',
            'participant.targetGroup',
            'participant.occupation',
            'rounds',
        ])
        ->orderByDesc('entry_date')
        ->orderByDesc('id')
        ->get();

        $areas = Area::all();
        $targetGroups = TargetGroup::all();
        $templates = FollowUpRoundTemplate::where('is_active', true)->orderBy('offset_days')->get();

        $memberPayloads = $profiles->map(fn (CohortProfile $cp) => $this->toMemberPayload($cp));

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
            'targetGroups' => $targetGroups,
            'templates' => $templates,
        ]);
    }

    public function store(CohortRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $nextPId = (Participant::max('id') ?? 0) + 1;
            $pCode = 'PID-' . str_pad((string) $nextPId, 4, '0', STR_PAD_LEFT);

            $genderMap = [
                'ชาย' => 'male',
                'หญิง' => 'female',
                'อื่น ๆ' => 'other',
                'ไม่ระบุ' => 'undisclosed',
            ];
            $genderEnum = $genderMap[$validated['gender'] ?? ''] ?? null;

            $participant = Participant::create([
                'code' => $pCode,
                'person_code' => $pCode,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'gender' => $genderEnum,
                'area_id' => $validated['area_id'],
                'target_group_id' => $validated['target_group_id'] ?? null,
                'occupation_id' => $validated['occupation_id'] ?? null,
                'source_channel_id' => $validated['source_channel_id'] ?? null,
                'consent_status' => 'ยินยอม',
            ]);

            $entryDate = Carbon::parse($validated['entry_date']);
            $nextCId = (CohortProfile::max('id') ?? 0) + 1;
            $cCode = 'CHT-' . str_pad((string) $nextCId, 4, '0', STR_PAD_LEFT);

            $cohortProfile = CohortProfile::create([
                'participant_id' => $participant->id,
                'cohort_code' => $cCode,
                'entry_date' => $entryDate,
                'source_type' => 'manual',
            ]);

            Consent::create([
                'participant_id' => $participant->id,
                'status' => 'ยินยอม',
                'consented_at' => now(),
                'recorded_via' => 'admin_cohort',
                'recorded_by' => auth()->id(),
            ]);

            $templates = FollowUpRoundTemplate::where('is_active', true)->orderBy('offset_days')->get();
            foreach ($templates as $tpl) {
                $dueDate = $entryDate->copy()->addDays($tpl->offset_days);

                FollowUpRound::create([
                    'cohort_profile_id' => $cohortProfile->id,
                    'template_id' => $tpl->id,
                    'name' => $tpl->name,
                    'offset_days' => $tpl->offset_days,
                    'due_date' => $dueDate,
                    'answered_at' => $tpl->offset_days === 0 ? $entryDate : null,
                ]);
            }

            $cohortProfile->load(['participant.area', 'participant.targetGroup', 'rounds']);

            $evalLink = url('/evaluations/start?code=' . $cCode);
            $lineBindLink = url('/line/bind?code=' . $cCode);

            return response()->json([
                'success' => true,
                'message' => 'เพิ่มกลุ่มตัวอย่างสำเร็จ',
                'data' => $this->toMemberPayload($cohortProfile),
                'evalLink' => $evalLink,
                'lineBindLink' => $lineBindLink,
            ]);
        });
    }

    public function show(CohortProfile $cohortProfile): View|JsonResponse
    {
        $cohortProfile->load([
            'participant.area',
            'participant.targetGroup',
            'participant.occupation',
            'participant.consents',
            'participant.notes.author',
            'rounds.surveyResponse',
        ]);

        $payload = $this->toMemberPayload($cohortProfile);

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
            'data' => $this->toMemberPayload($cohortProfile),
        ]);
    }

    private function toMemberPayload(CohortProfile $cp): array
    {
        $p = $cp->participant;
        $today = Carbon::today();

        $genderThai = match ($p->gender) {
            'male' => 'ชาย',
            'female' => 'หญิง',
            'other' => 'อื่น ๆ',
            default => 'ไม่ระบุ',
        };

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
            'pid' => $p->code ?? $p->person_code ?? 'PID-' . $p->id,
            'name' => $p->name,
            'phone' => $p->phone ?? '',
            'gender' => $genderThai,
            'age' => $p->ageBand() ?? 'ไม่ระบุช่วงอายุ',
            'job' => $p->occupation?->name ?? $p->occupation_raw ?? 'เกษียณ / ไม่ได้ทำงาน',
            'source' => $p->source ?? 'สมัครสมาชิก',
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
            'rounds' => $roundStates,
        ];
    }
}
