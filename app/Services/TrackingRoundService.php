<?php

namespace App\Services;

use App\Models\CohortProfile;
use App\Models\Consent;
use App\Models\FollowUpNote;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Form;
use App\Models\Participant;
use App\Models\RoundBatch;
use App\Models\RoundBatchMember;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * รอบติดตาม — ตรรกะที่หน้ารายการ · หน้าสร้าง · QR สาธารณะ ใช้ร่วมกัน
 *
 * หลักที่ต้องรักษาไว้:
 * - "คนที่ถึงกำหนด" คือใบติดตามรายคน (ptp_follow_up_rounds) ไม่ใช่ตัวคน
 *   คนหนึ่งคนมีหลายใบ และแต่ละใบครบกำหนดคนละวัน
 * - ชื่อรอบอ่านจากใบของคนนั้น ซึ่ง snapshot มาจาก mst_follow_up_round_templates ตอนสร้าง
 *   ไม่มี "3 เดือน / 6 เดือน" เขียนตายอยู่ในโค้ดชั้นนี้เลย
 * - สถานะการตอบ derive จาก answered_at เสมอ ไม่เก็บสำเนาไว้ที่ตารางสมาชิกรอบ
 */
class TrackingRoundService
{
    public function __construct(
        private readonly LinePushService $push,
        private readonly PersonCodeGenerator $personCodes,
        private readonly SurveyAnswerBuilder $answerBuilder,
    ) {}

    /**
     * ผู้เข้าร่วมลงทะเบียนเป็นกลุ่มตัวอย่างเอง ผ่าน QR
     *
     * ถามเท่าที่คนคนหนึ่งตอบแทนตัวเองได้จริง — ชื่อ เบอร์ เพศ พื้นที่ และความยินยอม
     * กลุ่มเป้าหมายเว้นไว้ให้เจ้าหน้าที่มาจัดทีหลัง เพราะเป็นการจัดกลุ่มเชิงบริหารที่เจ้าตัวไม่รู้
     *
     * รอบติดตามถูกสร้างให้ครบทุกรอบที่เปิดใช้งาน นับจากวันที่ลงทะเบียนเป็นวันฐาน
     */
    public function selfRegister(
        string $name,
        string $phone,
        ?string $lineUserId = null,
        ?string $gender = null,
        ?int $areaId = null,
    ): CohortProfile {
        return DB::transaction(function () use ($name, $phone, $lineUserId, $gender, $areaId): CohortProfile {
            $personCode = $this->personCodes->next(lock: true);

            $participant = Participant::create([
                'code' => $personCode,
                'person_code' => $personCode,
                'name' => $name,
                'phone' => $phone,
                'gender' => $gender,
                'area_id' => $areaId,
                'consent_status' => 'ยินยอม',
                'line_user_id' => $lineUserId,
            ]);

            Consent::create([
                'participant_id' => $participant->id,
                'status' => 'ยินยอม',
                'consent_version' => config('farmconcept.consent_version'),
                'consented_at' => now(),
                /* บอกให้รู้ว่าความยินยอมนี้เจ้าตัวกดเอง ไม่ได้มีเจ้าหน้าที่เป็นพยาน
                   ต่างจากใบยินยอมกระดาษที่แอดมินแนบให้ตอนเพิ่มจากหลังบ้าน */
                'recorded_via' => 'self_qr',
            ]);

            $entryDate = Carbon::today();

            $profile = CohortProfile::create([
                'participant_id' => $participant->id,
                'cohort_code' => $this->personCodes->nextCohortCode(lock: true),
                'entry_date' => $entryDate,
                'source_type' => 'walk_in',
            ]);

            foreach (FollowUpRoundTemplate::active()->get() as $template) {
                FollowUpRound::create([
                    'cohort_profile_id' => $profile->id,
                    'template_id' => $template->id,
                    'name' => $template->name,
                    'offset_days' => $template->offset_days,
                    'due_date' => $entryDate->copy()->addDays($template->offset_days),
                ]);
            }

            return $profile->load('participant');
        });
    }

    /**
     * แบบประเมินที่ใช้กับรอบนี้
     *
     * รอบที่ถูกดึงเข้ารอบติดตามแล้ว ใช้แบบประเมินที่แอดมินล็อกไว้กับรอบนั้น
     * ที่เหลือใช้แบบติดตามสุขภาพที่เปิดใช้งานอยู่ — คนที่สแกน QR เองโดยยังไม่มีใครเปิดรอบให้
     * ก็ต้องตอบได้ ไม่ใช่เจอหน้าว่าง
     */
    public function formForRound(FollowUpRound $round): ?Form
    {
        $batchForm = RoundBatchMember::where('follow_up_round_id', $round->id)
            ->whereHas('batch', fn ($q) => $q->where('state', '!=', RoundBatch::STATE_CANCELLED))
            ->with('batch.form')
            ->latest('id')
            ->first()?->batch?->form;

        /* ต้องเป็นแบบติดตามสุขภาพที่เปิดใช้งานอยู่เท่านั้น
           แบบที่ถูกปิดไปแล้วหรือเป็นชนิดอื่น (ลงทะเบียน / ความพึงพอใจ) ห้ามโผล่มาให้ตอบ
           ถึงจะเคยผูกไว้กับรอบตอนที่ยังเปิดอยู่ก็ตาม — ไม่งั้นเก็บข้อมูลด้วยแบบที่เลิกใช้แล้ว */
        $usable = fn (?Form $form) => $form
            && $form->type === Form::TYPE_HEALTH_FOLLOW_UP
            && $form->status === Form::STATUS_ACTIVE;

        $form = $usable($batchForm) ? $batchForm : $this->defaultForm();

        return $form?->loadMissing(['questions.options']);
    }

    /**
     * บันทึกคำตอบของรอบหนึ่ง แล้วปิดรอบนั้นให้เป็น "ตอบแล้ว"
     *
     * @param  array<string, mixed>  $answers
     */
    public function submitSurvey(FollowUpRound $round, array $answers, ?Participant $submittedBy = null): SurveyResponse
    {
        return DB::transaction(function () use ($round, $answers, $submittedBy): SurveyResponse {
            $form = $this->formForRound($round);

            if ($form === null) {
                throw ValidationException::withMessages([
                    'answers' => 'ยังไม่มีแบบติดตามสุขภาพที่เปิดใช้งาน กรุณาติดต่อเจ้าหน้าที่',
                ]);
            }

            /* ตรวจคำตอบให้ครบก่อนสร้างระเบียน ถ้าตกข้อบังคับกลางทางจะได้ไม่มีคำตอบครึ่ง ๆ ค้างไว้ */
            $rows = $this->answerBuilder->rowsFor($form, $answers);

            $response = $this->recordResponse($round, $form);

            if ($submittedBy !== null) {
                $response->update(['submitted_by_participant_id' => $submittedBy->id]);
            }

            foreach ($rows as $row) {
                $response->answers()->create($row);
            }

            return $response;
        });
    }

    /**
     * คนที่ "ถึงกำหนดติดตาม" ในช่วงที่ระบุ
     *
     * เงื่อนไขตามที่หน้าสร้างรอบต้องการ:
     *   1. วันครบกำหนดอยู่ในช่วง due_from – due_to
     *   2. กลุ่มเป้าหมายของคนนั้นตรงกับที่เลือก (ไม่เลือก = ทุกกลุ่ม)
     *   3. ยังไม่ตอบ (answered_at เป็น null) = สถานะ "รอถึงกำหนด"
     *
     * เพิ่มอีกข้อที่ไม่ได้อยู่ในเงื่อนไขข้างบนแต่จำเป็น: ตัดใบที่ถูกดึงเข้ารอบอื่น
     * ที่ยังไม่ถูกยกเลิกไปแล้ว ไม่งั้นคนเดียวกันจะได้ข้อความแจ้งเตือนของรอบเดิมสองครั้ง
     * และตาราง evl_round_batch_members ก็มี unique (batch_id, follow_up_round_id) อยู่แล้ว
     * ซึ่งเป็นกติกาเดียวกันในระดับรอบเดียว
     *
     * @param  array{from: string, to: string, targetGroupIds?: array<int>, page?: int, pageSize?: int, excludeBatchId?: int|null}  $filters
     */
    public function eligible(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 10)));

        $query = $this->eligibleQuery($filters);

        /* ดึงเฉพาะ id ของทั้งชุดก่อน เพื่อให้หน้าจอรู้ยอดรวมและ "เลือกทั้งหมด" ได้
           โดยไม่ต้องโหลดทุกแถวมาแสดง — แถวจริงโหลดเฉพาะหน้าที่ขอ */
        $allIds = (clone $query)->orderBy('ptp_follow_up_rounds.due_date')
            ->orderBy('ptp_follow_up_rounds.id')
            ->pluck('ptp_follow_up_rounds.id');

        $notifiableIds = (clone $query)
            ->whereNotNull('ptp_participants.line_user_id')
            ->pluck('ptp_follow_up_rounds.id');

        $rows = (clone $query)
            ->with(['cohortProfile.participant.area', 'cohortProfile.participant.targetGroup'])
            ->orderBy('ptp_follow_up_rounds.due_date')
            ->orderBy('ptp_follow_up_rounds.id')
            ->forPage($page, $pageSize)
            ->select('ptp_follow_up_rounds.*')
            ->get();

        return [
            'total' => $allIds->count(),
            'page' => $page,
            'pageSize' => $pageSize,
            'rows' => $rows->map(fn (FollowUpRound $r) => $this->toMemberPayload($r))->values(),
            'allIds' => $allIds->values(),
            'notifiableIds' => $notifiableIds->values(),
        ];
    }

    /** query กลางของ "ใบที่ถึงกำหนด" — ใช้ทั้งตอนนับ ตอนแบ่งหน้า และตอนตรวจตอนบันทึก */
    private function eligibleQuery(array $filters): Builder
    {
        $targetGroupIds = array_filter((array) ($filters['targetGroupIds'] ?? []));

        return FollowUpRound::query()
            ->join('ptp_cohort_profiles', 'ptp_cohort_profiles.id', '=', 'ptp_follow_up_rounds.cohort_profile_id')
            ->join('ptp_participants', 'ptp_participants.id', '=', 'ptp_cohort_profiles.participant_id')
            ->whereNull('ptp_participants.deleted_at')
            ->whereNull('ptp_cohort_profiles.stopped_at')
            ->whereNull('ptp_follow_up_rounds.answered_at')
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $q) => $q->whereDate('ptp_follow_up_rounds.due_date', '>=', $filters['from'])
            )
            ->when(
                filled($filters['to'] ?? null),
                fn (Builder $q) => $q->whereDate('ptp_follow_up_rounds.due_date', '<=', $filters['to'])
            )
            ->when(
                $targetGroupIds !== [],
                fn (Builder $q) => $q->whereIn('ptp_participants.target_group_id', $targetGroupIds)
            )
            ->whereNotExists(function ($q) use ($filters): void {
                $q->select(DB::raw(1))
                    ->from('evl_round_batch_members')
                    ->join('evl_round_batches', 'evl_round_batches.id', '=', 'evl_round_batch_members.batch_id')
                    ->whereColumn('evl_round_batch_members.follow_up_round_id', 'ptp_follow_up_rounds.id')
                    ->where('evl_round_batches.state', '!=', RoundBatch::STATE_CANCELLED)
                    ->when(
                        filled($filters['excludeBatchId'] ?? null),
                        fn ($sub) => $sub->where('evl_round_batches.id', '!=', $filters['excludeBatchId'])
                    );
            })
            ->select('ptp_follow_up_rounds.*');
    }

    /**
     * สร้างรอบติดตาม
     *
     * บันทึกร่าง (state = รอเริ่ม) กับสร้างจริง ต่างกันแค่ "ส่งแจ้งเตือนไหม"
     * สมาชิกถูกบันทึกเหมือนกันทั้งสองแบบ เพื่อให้เปิดร่างกลับมาแล้วรายชื่อยังอยู่
     *
     * @param  array<int>  $followUpRoundIds
     */
    public function create(array $data, array $followUpRoundIds, bool $notify): RoundBatch
    {
        return DB::transaction(function () use ($data, $followUpRoundIds, $notify): RoundBatch {
            $batch = RoundBatch::create([
                'code' => $this->nextCode(),
                'name' => $data['name'],
                'due_from' => $data['due_from'],
                'due_to' => $data['due_to'],
                'form_id' => $data['form_id'],
                'notification_template' => $data['notification_template'] ?: $this->defaultTemplate(),
                'state' => $notify ? RoundBatch::STATE_RUNNING : RoundBatch::STATE_DRAFT,
                'created_by' => auth()->id(),
            ]);

            $batch->targetGroups()->sync($data['target_group_ids'] ?? []);

            /* ตรวจซ้ำด้วย query เดิมใต้ transaction — ระหว่างที่แอดมินติ๊กรายชื่อค้างไว้
               อาจมีคนตอบแบบประเมินไปแล้ว หรือถูกดึงเข้ารอบอื่น ต้องไม่หลุดเข้ามา */
            $eligible = $this->eligibleQuery([
                'from' => $data['due_from'],
                'to' => $data['due_to'],
                'targetGroupIds' => $data['target_group_ids'] ?? [],
            ])->whereIn('ptp_follow_up_rounds.id', $followUpRoundIds)->get();

            foreach ($eligible as $round) {
                RoundBatchMember::create([
                    'batch_id' => $batch->id,
                    'cohort_profile_id' => $round->cohort_profile_id,
                    'follow_up_round_id' => $round->id,
                ]);
            }

            return $batch->refresh();
        });
    }

    /**
     * ส่งแจ้งเตือนของรอบนี้
     *
     * ส่งเฉพาะคนที่มี line_user_id — คนที่ไม่มีถูกบันทึกเป็น "ไม่มีช่องทางแจ้งเตือน"
     * แล้วไปโผล่ในกลุ่ม "ต้องติดตามเอง" ของหน้ารายละเอียด ไม่ใช่เงียบหายไป
     *
     * ส่งซ้ำได้เฉพาะคนที่ยังส่งไม่สำเร็จ คนที่ส่งไปแล้วจะไม่โดนข้อความซ้ำ
     *
     * @return array{sent: int, failed: int, noChannel: int, lineConfigured: bool}
     */
    public function notify(RoundBatch $batch): array
    {
        $batch->loadMissing(['members.cohortProfile.participant', 'members.followUpRound']);

        $result = ['sent' => 0, 'failed' => 0, 'noChannel' => 0, 'lineConfigured' => $this->push->isConfigured()];

        foreach ($batch->members as $member) {
            if ($member->notify_result === RoundBatchMember::RESULT_SENT) {
                $result['sent']++;

                continue;
            }

            $participant = $member->cohortProfile?->participant;
            $lineUserId = $participant?->line_user_id;

            if (blank($lineUserId)) {
                $member->update([
                    'notify_channel' => RoundBatchMember::CHANNEL_NONE,
                    'notify_result' => RoundBatchMember::RESULT_NO_CHANNEL,
                ]);
                $result['noChannel']++;

                continue;
            }

            $message = $this->fillTemplate($batch->notification_template, $participant, $member->followUpRound);
            $ok = $this->push->pushText($lineUserId, $message);

            $member->update([
                'notified_at' => now(),
                'notify_channel' => RoundBatchMember::CHANNEL_LINE,
                'notify_result' => $ok ? RoundBatchMember::RESULT_SENT : RoundBatchMember::RESULT_FAILED,
            ]);

            /* ทุกครั้งที่ส่ง ต้องมีร่องรอยในประวัติของคนนั้น ไม่งั้นแอดมินที่เปิดหน้ากลุ่มตัวอย่าง
               จะไม่รู้ว่าเคยตามไปแล้วกี่ครั้ง แล้วโทรตามซ้ำ */
            FollowUpNote::create([
                'participant_id' => $participant->id,
                'source' => 'ระบบแจ้งเตือน',
                'kind' => 'แจ้งเตือน LINE',
                'noted_at' => now(),
                'body' => 'ส่งแจ้งเตือน'.$member->followUpRound->name.' · '
                    .($ok ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ').' (รอบ '.$batch->name.')',
                'created_by' => auth()->id(),
            ]);

            $ok ? $result['sent']++ : $result['failed']++;
        }

        if ($batch->state === RoundBatch::STATE_DRAFT) {
            $batch->update(['state' => RoundBatch::STATE_RUNNING]);
        }

        return $result;
    }

    /**
     * บันทึกว่าคนนี้ตอบแบบประเมินของรอบติดตามใบนี้แล้ว
     *
     * stamp answered_at ที่ใบติดตามรายคนที่เดียว — สมาชิกของทุกรอบที่อ้างใบนี้
     * จะกลายเป็น "ตอบแล้ว" ตามทันที เพราะอ่านจากใบเดียวกัน ไม่ได้เก็บสำเนาไว้คนละที่
     *
     * เรียกซ้ำด้วยใบเดิมไม่สร้างคำตอบซ้ำ — evl_survey_responses.cohort_round_id เป็น unique
     */
    public function recordResponse(FollowUpRound $round, ?Form $form = null, ?Carbon $submittedAt = null): SurveyResponse
    {
        return DB::transaction(function () use ($round, $form, $submittedAt): SurveyResponse {
            $submittedAt ??= now();

            /* เผื่อผู้เรียกส่งใบที่ยังไม่ได้โหลดโปรไฟล์มา — เมธอดนี้ถูกเรียกจากหลายทาง
               จะไปพึ่งให้ทุกทางโหลดมาให้ครบไม่ได้ */
            $round->loadMissing('cohortProfile');

            $response = SurveyResponse::firstOrCreate(
                ['cohort_round_id' => $round->id],
                [
                    'form_id' => ($form ?? $this->defaultForm())?->id,
                    'participant_id' => $round->cohortProfile->participant_id,
                    'submitted_at' => $submittedAt,
                ]
            );

            if ($round->answered_at === null) {
                $round->update(['answered_at' => $submittedAt]);
            }

            return $response;
        });
    }

    /** แทนค่าตัวแปรในข้อความ — ชุดตัวแปรอยู่ที่ config('farmconcept.tracking_round.placeholders') */
    public function fillTemplate(?string $template, Participant $participant, FollowUpRound $round): string
    {
        return strtr($template ?: $this->defaultTemplate(), [
            '{ชื่อ}' => $participant->name,
            /* ชื่อรอบมาจากใบของคนนั้น ซึ่ง snapshot มาจากหน้าตั้งค่ารอบประเมิน — ไม่ได้เขียนตายไว้ */
            '{รอบ}' => $round->name,
            '{วันครบกำหนด}' => $this->thaiDate($round->due_date),
        ]);
    }

    public function defaultTemplate(): string
    {
        return config('farmconcept.tracking_round.default_message');
    }

    /**
     * รอบที่ถึงกำหนดของคนหนึ่งคน — ใช้หลังยืนยันตัวตนจาก QR
     *
     * แสดงเฉพาะใบที่ "เปิดให้ตอบแล้ว" ตามช่วงติดตามของใบนั้น (ครบกำหนด −7 ถึง +14 วัน)
     * ใบที่ยังไม่ถึงกำหนดต้องไม่โผล่ ไม่งั้นคนจะตอบล่วงหน้าข้ามรอบ
     */
    public function dueRoundsFor(Participant $participant): Collection
    {
        $profile = $participant->cohortProfile;

        if ($profile === null || $profile->isStopped()) {
            return collect();
        }

        /* โหลด cohortProfile มาด้วย — ปลายทางของรอบพวกนี้คือการบันทึกคำตอบ ซึ่งต้องใช้ participant_id
           ถ้าไม่โหลดมา พอมีรอบที่ถึงกำหนดมากกว่าหนึ่ง ตัวกัน lazy loading จะทำงานแล้วส่งคำตอบไม่ได้เลย */
        return $profile->rounds()
            ->with('cohortProfile')
            ->whereNull('answered_at')
            ->orderBy('due_date')
            ->get()
            ->filter(fn (FollowUpRound $r) => in_array($r->state(), ['รอติดตาม', 'เกินกำหนด'], true))
            ->values();
    }

    /** ข้อมูลหนึ่งแถวของ "คนในรอบ" — รูปแบบเดียวกันทั้งหน้าสร้างและหน้ารายละเอียด */
    public function toMemberPayload(FollowUpRound $round, ?RoundBatchMember $member = null): array
    {
        /* ตอนเรียกจากหน้ารายละเอียด โปรไฟล์ถูก eager load ไว้ที่ member ไม่ใช่ที่ใบติดตาม
           หยิบจาก member ก่อนจึงไม่ไป lazy load ซ้ำทีละแถว */
        $participant = ($member?->cohortProfile ?? $round->cohortProfile)->participant;

        return [
            'id' => $round->id,
            'memberId' => $member?->id,
            'pid' => $participant->person_code ?? $participant->code,
            'cohortId' => $round->cohort_profile_id,
            'name' => $participant->name,
            'phone' => $participant->phone ?? '',
            'area' => $participant->area?->name ?? 'ไม่ระบุพื้นที่',
            'target' => $participant->targetGroup?->name ?? 'ไม่ระบุกลุ่ม',
            /* ชื่อรอบ join มาจากใบของคนนั้น ไม่ได้ hardcode ที่หน้าจอหรือที่นี่ */
            'round' => $round->name,
            'due' => $round->due_date?->toDateString(),
            'line' => filled($participant->line_user_id),
            'state' => $round->state(),
            'answered' => $round->answered_at !== null,
            'notifyResult' => $member?->notify_result,
            'notifyChannel' => $member?->notify_channel,
            'notifiedAt' => $member?->notified_at?->toDateTimeString(),
            'responseStatus' => $member?->responseStatus() ?? ($round->answered_at ? 'ตอบแล้ว' : 'ยังไม่ตอบ'),

            /* ผลติดตามนอกระบบของคนที่แจ้งเตือนไม่ได้ — แอดมินคีย์เอง */
            'offlineKind' => $member?->offline_kind,
            'offlineNote' => $member?->offline_note,
            'offlineAt' => $member?->offline_at?->toDateTimeString(),
            'offlineBy' => $member?->offlineBy?->name,
        ];
    }

    /** ตัวเลขสรุปของรอบ — คนที่ยังไม่ผูก LINE ไม่ถูกนับเป็นแจ้งเตือนได้ */
    public function statsOf(RoundBatch $batch): array
    {
        $members = $batch->members;

        $notifiable = $members->filter(
            fn (RoundBatchMember $m) => filled($m->cohortProfile?->participant?->line_user_id)
        )->count();

        return [
            'total' => $members->count(),
            'answered' => $members->filter(fn (RoundBatchMember $m) => $m->hasAnswered())->count(),
            'notifiable' => $notifiable,
            'unreachable' => $members->count() - $notifiable,
        ];
    }

    /**
     * แบบติดตามสุขภาพที่ใช้อยู่จริง
     *
     * ถ้ามีเปิดใช้งานอยู่หลายชุด (เช่นทำสำเนาไว้แก้) ให้ใช้ "ชุดที่เผยแพร่ล่าสุด"
     * ไม่ใช่ id ต่ำสุด — คนที่ทำสำเนาใหม่แล้วปิดของเก่า ย่อมตั้งใจให้ใช้ชุดใหม่
     * เรียงด้วย id ต่ำสุดจะได้ชุดเก่าค้างอยู่โดยไม่มีอะไรเตือน
     */
    private function defaultForm(): ?Form
    {
        return Form::where('type', Form::TYPE_HEALTH_FOLLOW_UP)
            ->where('status', Form::STATUS_ACTIVE)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    private function thaiDate(?Carbon $date): string
    {
        if ($date === null) {
            return '—';
        }

        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $date->day.' '.$months[$date->month - 1].' '.($date->year + 543);
    }

    /** เลขสูงสุดจากตัวเลขท้ายรหัส เหตุผลเดียวกับ MasterDataController::runningCode() */
    private function nextCode(): string
    {
        $running = RoundBatch::where('code', 'like', 'RBT-%')
            ->lockForUpdate()
            ->pluck('code')
            ->map(fn (string $code) => (int) Str::afterLast($code, '-'))
            ->max() ?? 0;

        return 'RBT-'.str_pad((string) ($running + 1), 4, '0', STR_PAD_LEFT);
    }
}
