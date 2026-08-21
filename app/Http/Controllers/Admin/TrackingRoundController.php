<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrackingRoundRequest;
use App\Models\FollowUpNote;
use App\Models\Form;
use App\Models\RoundBatch;
use App\Models\RoundBatchMember;
use App\Models\TargetGroup;
use App\Services\TrackingRoundService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * รอบติดตาม (/admin/tracking-rounds)
 *
 * ใช้ตาราง evl_round_batches / evl_round_batch_members ที่มีอยู่แล้ว
 * ตรรกะทั้งหมดอยู่ที่ TrackingRoundService — ที่นี่ทำแค่แปลง request/response
 */
class TrackingRoundController extends Controller
{
    public function __construct(private readonly TrackingRoundService $rounds) {}

    public function index(Request $request): View|JsonResponse
    {
        $batches = RoundBatch::with(['form:id,code,name', 'members.cohortProfile.participant:id,line_user_id', 'members.followUpRound'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (RoundBatch $b) => $this->toBatchPayload($b));

        if ($request->expectsJson()) {
            return response()->json(['rows' => $batches]);
        }

        return view('admin.tracking-rounds.index', [
            'batches' => $batches,
            'forms' => $this->healthForms(),
            'states' => RoundBatch::STATES,
        ]);
    }

    public function create(): View
    {
        return view('admin.tracking-rounds.create', [
            'forms' => $this->healthForms(),
            'targetGroups' => TargetGroup::where('is_active', true)->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn (TargetGroup $t) => ['value' => $t->id, 'label' => $t->name])->values(),
            'defaultMessage' => $this->rounds->defaultTemplate(),
            'placeholders' => config('farmconcept.tracking_round.placeholders'),
            'today' => now()->toDateString(),
            /* ค่าตั้งต้นของช่วง "ครบกำหนด" — ทั้งเดือนปัจจุบัน ไม่ใช่วันนี้ถึงวันนี้
               ช่วงวันเดียวมักค้นได้ 0 คน แล้วหน้าจอก็ไม่มีอะไรบอกว่าควรขยายเป็นเท่าไร
               คนสร้างรอบจึงเดาไปเรื่อย ๆ ทั้งเดือนเป็นหน่วยที่ตรงกับวิธีทำงานจริงมากกว่า
               และครอบคลุมคนที่เลยกำหนดไปแล้วต้นเดือนด้วย */
            'monthStart' => now()->startOfMonth()->toDateString(),
            'monthEnd' => now()->endOfMonth()->toDateString(),        ]);
    }

    /**
     * รายชื่อที่ถึงกำหนดในช่วงที่ระบุ
     *
     * แบ่งหน้าที่ฝั่งเซิร์ฟเวอร์เสมอ — หน้าจอถือแค่ "id ที่ติ๊กไว้" ไม่เคยถือรายชื่อทั้งหมด
     */
    public function eligibleMembers(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'target_group_ids' => ['array'],
            'target_group_ids.*' => ['integer', 'exists:mst_target_groups,id'],
            'page' => ['integer', 'min:1'],
            'page_size' => ['integer', 'min:1', 'max:100'],
        ], [
            'from.required' => 'กรุณาระบุวันครบกำหนดเริ่มต้น',
            'to.required' => 'กรุณาระบุวันครบกำหนดสิ้นสุด',
            'to.after_or_equal' => 'วันสิ้นสุดต้องไม่อยู่ก่อนวันเริ่มต้น',
        ]);

        return response()->json($this->rounds->eligible([
            'from' => $filters['from'],
            'to' => $filters['to'],
            'targetGroupIds' => $filters['target_group_ids'] ?? [],
            'page' => $filters['page'] ?? 1,
            'pageSize' => $filters['page_size'] ?? 10,
        ]));
    }

    /**
     * บันทึกร่าง หรือ สร้างรอบแล้วส่งแจ้งเตือนทันที
     *
     * ต่างกันแค่ค่า notify — สมาชิกถูกบันทึกเหมือนกันทั้งสองแบบ
     * ร่างจึงเปิดกลับมาแล้วรายชื่อยังอยู่ ไม่ต้องค้นหาใหม่
     */
    public function store(TrackingRoundRequest $request): JsonResponse
    {
        $data = $request->validated();
        $notify = $request->boolean('notify');

        $batch = $this->rounds->create($data, $data['follow_up_round_ids'], $notify);
        $result = $notify ? $this->rounds->notify($batch) : null;

        $batch->load(['form', 'members.cohortProfile.participant', 'members.followUpRound']);

        return response()->json([
            'success' => true,
            'message' => $notify ? $this->notifyMessage($result) : 'บันทึกรอบติดตามเป็นฉบับร่างแล้ว',
            'data' => $this->toBatchPayload($batch),
            'notify' => $result,
            'redirect' => route('admin.tracking-rounds.show', $batch),
        ]);
    }

    public function show(RoundBatch $trackingRound): View|JsonResponse
    {
        $trackingRound->load([
            'form', 'targetGroups',
            'members.cohortProfile.participant.area',
            'members.cohortProfile.participant.targetGroup',
            'members.followUpRound',
            'members.offlineBy:id,name',
        ]);

        $payload = $this->toBatchPayload($trackingRound) + [
            'members' => $trackingRound->members
                ->map(fn (RoundBatchMember $m) => $this->rounds->toMemberPayload($m->followUpRound, $m))
                ->sortBy('due')->values(),
            'targetGroups' => $trackingRound->targetGroups->pluck('name'),
            'template' => $trackingRound->notification_template,
        ];

        if (request()->expectsJson()) {
            return response()->json(['data' => $payload]);
        }

        return view('admin.tracking-rounds.show', [
            'batch' => $payload,
            /* โครงคำถามของแบบประเมินที่รอบนี้ใช้ — popup "คีย์คำตอบ" วาดจากชุดนี้
               ส่งมาพร้อมหน้าเพราะเป็นชุดเดียวทั้งรอบ ยิงซ้ำทุกครั้งที่เปิด popup ไม่ได้อะไรเพิ่ม */
            'formQuestions' => $this->formQuestions($trackingRound),
        ]);
    }

    /** ส่งแจ้งเตือนซ้ำ — คนที่ส่งสำเร็จไปแล้วจะไม่โดนข้อความซ้ำ */
    public function sendNotify(RoundBatch $trackingRound): JsonResponse
    {
        if ($trackingRound->state === RoundBatch::STATE_CANCELLED) {
            return response()->json(['message' => 'รอบนี้ถูกยกเลิกแล้ว ส่งแจ้งเตือนไม่ได้'], 422);
        }

        $result = $this->rounds->notify($trackingRound);

        $trackingRound->load(['form', 'members.cohortProfile.participant', 'members.followUpRound']);

        return response()->json([
            'success' => true,
            'message' => $this->notifyMessage($result),
            'notify' => $result,
            'data' => $this->toBatchPayload($trackingRound),
        ]);
    }

    /**
     * ส่งแจ้งเตือนให้คนเดียวในรอบนี้
     *
     * ส่งซ้ำได้แม้เคยส่งสำเร็จแล้ว ต่างจากการส่งทั้งรอบที่ข้ามคนที่ส่งไปแล้ว
     * เพราะแอดมินกดปุ่มนี้ทีละคนโดยเห็นสถานะของคนนั้นอยู่ตรงหน้า จึงตั้งใจส่งซ้ำจริง
     */
    public function sendNotifyMember(RoundBatch $trackingRound, RoundBatchMember $member): JsonResponse
    {
        if ($trackingRound->state === RoundBatch::STATE_CANCELLED) {
            return response()->json(['message' => 'รอบนี้ถูกยกเลิกแล้ว ส่งแจ้งเตือนไม่ได้'], 422);
        }

        /* กันยิงรหัสสมาชิกของรอบอื่นเข้ามา — สองค่านี้มาจาก URL ทั้งคู่ */
        abort_if($member->batch_id !== $trackingRound->id, 404);

        $outcome = $this->rounds->notifyMember($trackingRound, $member);

        $trackingRound->load(['form', 'members.cohortProfile.participant', 'members.followUpRound']);

        return response()->json([
            'success' => $outcome === 'sent',
            'message' => match ($outcome) {
                'sent' => 'ส่งแจ้งเตือนเรียบร้อย',
                'noChannel' => 'คนนี้ยังไม่ผูก LINE จึงส่งแจ้งเตือนไม่ได้',
                'badLink' => 'ยังส่งไม่ได้ — ลิงก์แบบประเมินในการ์ดชี้ไปที่โดเมนที่เปิดได้เฉพาะบนเครื่องนี้ '
                    .'ให้ส่งจากเซิร์ฟเวอร์จริง หรือตั้ง HEALTH_PUBLIC_URL ให้เป็นที่อยู่เว็บที่เปิดจากมือถือได้',
                default => 'ส่งแจ้งเตือนไม่สำเร็จ กรุณาลองใหม่',
            },
            'outcome' => $outcome,
            'data' => $this->toBatchPayload($trackingRound),
        ]);
    }

    /**
     * บันทึกผลติดตามนอกระบบของคนที่แจ้งเตือนไม่ได้
     *
     * เขียนลง evl_round_batch_members (offline_*) และเปิดบันทึกในประวัติของคนนั้นด้วย
     * จะได้ไม่ต้องคีย์ซ้ำสองที่ และคนที่เปิดหน้ากลุ่มตัวอย่างเห็นว่าตามไปแล้ว
     */
    public function offlineLog(Request $request, RoundBatch $trackingRound, RoundBatchMember $member): JsonResponse
    {
        abort_unless($member->batch_id === $trackingRound->id, 404);

        $data = $request->validate([
            'kind' => ['required', Rule::in(config('farmconcept.tracking_round.offline_kinds'))],
            'note' => ['required', 'string', 'max:500'],
        ], [
            'kind.in' => 'วิธีติดตามไม่ถูกต้อง',
            'note.required' => 'กรุณากรอกผลการติดตาม',
        ]);

        $member->update([
            'offline_kind' => $data['kind'],
            'offline_note' => $data['note'],
            'offline_at' => now(),
            'offline_by' => auth()->id(),
        ]);

        $member->load(['cohortProfile.participant.area', 'cohortProfile.participant.targetGroup', 'followUpRound', 'offlineBy']);

        FollowUpNote::create([
            'participant_id' => $member->cohortProfile->participant_id,
            'source' => 'แอดมินติดตาม',
            'kind' => $data['kind'],
            'noted_at' => now(),
            'body' => $data['note'].' (รอบ '.$trackingRound->name.')',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกผลการติดตามแล้ว',
            'data' => $this->rounds->toMemberPayload($member->followUpRound, $member),
        ]);
    }

    public function cancel(RoundBatch $trackingRound): JsonResponse
    {
        $trackingRound->update(['state' => RoundBatch::STATE_CANCELLED]);

        $trackingRound->load(['form', 'members.cohortProfile.participant', 'members.followUpRound']);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกรอบติดตามแล้ว',
            'data' => $this->toBatchPayload($trackingRound),
        ]);
    }

    /** @param  array{sent: int, failed: int, noChannel: int, lineConfigured: bool}  $result */
    private function notifyMessage(array $result): string
    {
        /* ลิงก์ในการ์ดเปิดจากมือถือไม่ได้ = ไม่ได้ส่งสักคน บอกสาเหตุตรง ๆ ไปเลย
           ต่อรายละเอียดอื่นท้ายข้อความจะกลบสิ่งเดียวที่แอดมินต้องไปแก้ */
        if (($result['badLink'] ?? 0) > 0) {
            return 'ยังส่งไม่ได้ — ลิงก์แบบประเมินในการ์ดชี้ไปที่โดเมนที่เปิดได้เฉพาะบนเครื่องนี้ '
                .'ให้ส่งจากเซิร์ฟเวอร์จริง หรือตั้ง HEALTH_PUBLIC_URL ให้เป็นที่อยู่เว็บที่เปิดจากมือถือได้';
        }

        $parts = ['ส่งสำเร็จ '.$result['sent'].' คน'];

        if ($result['failed'] > 0) {
            $parts[] = 'ส่งไม่สำเร็จ '.$result['failed'].' คน'
                .($result['lineConfigured'] ? '' : ' (ยังไม่ได้ตั้งค่า LINE Messaging API)');
        }

        if ($result['noChannel'] > 0) {
            $parts[] = 'ต้องติดตามเอง '.$result['noChannel'].' คน';
        }

        return implode(' · ', $parts);
    }

    private function toBatchPayload(RoundBatch $batch): array
    {
        $stats = $this->rounds->statsOf($batch);

        return [
            'id' => $batch->code,
            'code' => $batch->code,
            'name' => $batch->name,
            'from' => $batch->due_from?->toDateString(),
            'to' => $batch->due_to?->toDateString(),
            /* เส้นตายการตอบของทั้งรอบ — ว่างได้ แปลว่าใช้วันครบกำหนดของใบรายคน */
            'answerDue' => $batch->answer_due_date?->toDateString(),
            'form' => $batch->form?->name ?? 'ยังไม่ระบุแบบประเมิน',
            'formId' => $batch->form_id,
            'state' => $batch->displayState(),
            'cancelled' => $batch->state === RoundBatch::STATE_CANCELLED,
            'createdAt' => $batch->created_at?->toDateString(),
            'url' => route('admin.tracking-rounds.show', $batch),
        ] + $stats;
    }

    /**
     * บันทึกคำตอบที่แอดมินคีย์จากกระดาษ
     *
     * มีไว้เพราะกลุ่มตัวอย่างส่วนหนึ่งทำแบบประเมินในแอปเองไม่ได้ (ไม่มีสมาร์ตโฟน อ่านจอไม่ไหว)
     * เจ้าหน้าที่ลงพื้นที่เก็บด้วยกระดาษแล้วต้องมีที่คีย์กลับเข้าระบบ ไม่งั้นคนกลุ่มนี้จะค้าง
     * สถานะ "ยังไม่ตอบ" ตลอดไป และหายไปจากรายงานสุขภาพกลุ่มตัวอย่างทั้งที่มีข้อมูลอยู่จริง
     *
     * บันทึกผ่าน submitSurvey() ตัวเดียวกับฝั่งผู้ตอบ — กติกาการตรวจคำตอบจึงเป็นชุดเดียวกัน
     * ทั้งข้อบังคับตอบ ความถูกต้องของตัวเลือก และการกันใบเปล่า
     *
     * ร่องรอยว่าใครเป็นคนคีย์เก็บที่ ptp_follow_up_notes ไม่ใช่ที่ตัวใบคำตอบ
     * เพราะคอลัมน์ submitted_by_participant_id ของใบคำตอบเก็บได้แต่ id ของกลุ่มตัวอย่าง
     * ไม่ใช่ id ของผู้ใช้ระบบ — จะเก็บที่ใบต้องเพิ่มคอลัมน์ใหม่ ซึ่งยังไม่ได้รับอนุญาตให้ทำ
     */
    public function recordAnswers(Request $request, RoundBatch $trackingRound, RoundBatchMember $member): JsonResponse
    {
        abort_unless($member->batch_id === $trackingRound->id, 404);

        if ($trackingRound->state === RoundBatch::STATE_CANCELLED) {
            return response()->json(['message' => 'รอบนี้ถูกยกเลิกแล้ว คีย์คำตอบไม่ได้'], 422);
        }

        $member->loadMissing(['followUpRound', 'cohortProfile']);
        $round = $member->followUpRound;

        /* ตอบไปแล้วต้องไม่ให้คีย์ทับ — คำตอบชุดเดิมคือข้อมูลวิจัยที่อ้างอิงไปแล้ว
           แก้ของเดิมเป็นคนละเรื่องกับการคีย์ของที่ยังไม่มี ต้องมีขั้นตอนของตัวเอง */
        if ($round->answered_at !== null) {
            return response()->json(['message' => 'รอบนี้มีคำตอบอยู่แล้ว คีย์ซ้ำไม่ได้'], 422);
        }

        /* คำตอบมาเป็น answer_<id> เหมือนฝั่งผู้ตอบ แปลงกลับเป็นคีย์ id ล้วนก่อนส่งให้ตัวตรวจ */
        $answers = [];

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'answer_')) {
                $answers[substr($key, 7)] = $value;
            }
        }

        $this->rounds->submitSurvey($round, $answers);

        FollowUpNote::create([
            'participant_id' => $member->cohortProfile->participant_id,
            'source' => 'แอดมินคีย์แทน',
            'kind' => 'คีย์คำตอบจากกระดาษ',
            'noted_at' => now(),
            'body' => 'คีย์คำตอบแบบประเมินแทนผู้ตอบ (รอบ '.$trackingRound->name.')',
            'created_by' => auth()->id(),
        ]);

        $member->load(['cohortProfile.participant.area', 'cohortProfile.participant.targetGroup', 'followUpRound', 'offlineBy']);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกคำตอบแล้ว',
            'data' => $this->rounds->toMemberPayload($member->followUpRound->fresh(), $member),
        ]);
    }

    /**
     * โครงคำถามของแบบประเมินที่รอบนี้ใช้ — รูปแบบเดียวกับที่ฝั่งผู้ตอบเห็น
     *
     * หัวข้อคั่น (section) ส่งไปด้วย เพราะ popup ต้องวาดหัวข้อให้ตรงกับกระดาษที่เจ้าหน้าที่ถืออยู่
     * ไม่งั้นไล่คีย์ตามลำดับแล้วหลงว่าอยู่ส่วนไหนของแบบสอบถาม
     */
    private function formQuestions(RoundBatch $batch): array
    {
        /* ผ่าน formForRound() ไม่ใช่อ่าน $batch->form ตรง ๆ — ตัวนั้นกันแบบที่ถูกปิดไปแล้ว
           หรือเป็นชนิดอื่นออก และมีทางสำรองไปแบบเริ่มต้นให้ ต้องเป็นชุดเดียวกับที่ผู้ตอบเห็น
           ไม่งั้นแอดมินคีย์ตามคำถามชุดหนึ่ง แต่ระบบตรวจด้วยอีกชุด */
        $round = $batch->members->first()?->followUpRound;
        $form = $round ? $this->rounds->formForRound($round) : null;

        if ($form === null) {
            return [];
        }

        return $form->questions->map(fn ($q) => [
            'id' => $q->id,
            'type' => $q->question_type,
            'text' => $q->text,
            'required' => (bool) $q->is_required,
            'options' => $q->options->map(fn ($o) => ['id' => $o->id, 'label' => $o->label])->values(),
        ])->values()->all();
    }

    /** รอบติดตามล็อกไว้ที่แบบประเมินชนิดติดตามสุขภาพเท่านั้น — ชนิดอื่นใช้กับรอบไม่ได้ */
    private function healthForms()
    {
        return Form::where('type', Form::TYPE_HEALTH_FOLLOW_UP)
            ->where('status', Form::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Form $f) => ['value' => $f->id, 'label' => $f->name])
            ->values();
    }
}
