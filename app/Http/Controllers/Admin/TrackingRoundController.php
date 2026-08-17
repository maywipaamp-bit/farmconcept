<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrackingRoundRequest;
use App\Models\FollowUpNote;
use App\Models\Form;
use App\Models\QrCode;
use App\Models\RoundBatch;
use App\Models\RoundBatchMember;
use App\Models\TargetGroup;
use App\Services\TrackingRoundService;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\SvgWriter;
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
            'qr' => $this->healthQrPayload(),
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
        ]);
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

        return view('admin.tracking-rounds.show', ['batch' => $payload]);
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

    /**
     * QR ถาวรของระบบติดตามสุขภาพ — แถวเดียวทั้งระบบ activity_id เป็น NULL
     * URL ไม่มีรหัสคน รหัสรอบ หรือรหัสแบบประเมินอยู่ข้างใน จึงพิมพ์ครั้งเดียวใช้ได้ตลอด
     */
    private function healthQrPayload(): array
    {
        $qr = QrCode::where('purpose', 'health')->whereNull('activity_id')->first();

        /* พาธอ่านออกและบอกต่อทางโทรศัพท์ได้ — ไม่มี token ใน URL แล้ว
           QR ติดตามสุขภาพมีอันเดียวทั้งโครงการ ปลายทางจึงตายตัว */
        $url = $qr ? route('public.tracking-round-qr') : null;

        return [
            'exists' => $qr !== null,
            'url' => $url,
            'scanCount' => $qr?->scan_count ?? 0,
            /* วาดเป็น SVG จริงตั้งแต่ฝั่งเซิร์ฟเวอร์ ไม่ใช่ลายตัวอย่างที่สแกนไม่ติดแบบต้นแบบ
               ใช้ SVG (ไม่ใช่ PNG) เพราะ .fb-qr มีกฎ svg { width:100% } อยู่แล้ว
               และปุ่มดาวน์โหลดฝั่งหน้าจอหยิบ outerHTML ของ <svg> ไปทำไฟล์ได้ตรง ๆ */
            'svg' => $url ? $this->qrSvg($url) : null,
        ];
    }

    private function qrSvg(string $url): string
    {
        $result = (new SvgWriter)->write(
            new EndroidQrCode(
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 512,
                margin: 8,
            ),
            options: [SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true],
        );

        return $result->getString();
    }
}
