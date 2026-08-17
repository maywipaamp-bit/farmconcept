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
        string $phone,
        ?string $lineUserId = null,
        ?string $gender = null,
        ?int $ageRangeId = null,
    ): CohortProfile {
        /* บัญชี LINE หนึ่งผูกได้กับคนเดียว (unique ที่ฐานข้อมูล) — ถ้า LINE ใน session
           ถูกผูกกับคนอื่นไว้แล้ว เช่น ลงทะเบียนให้คนในบ้านต่อกันโดยไม่สลับบัญชี
           ให้ลงทะเบียนคนใหม่แบบไม่ผูก LINE แทนที่จะล้มทั้งรายการด้วย duplicate key
           (withTrashed เพราะ unique index นับรวมแถวที่ soft delete ด้วย) */
        if ($lineUserId !== null && Participant::withTrashed()->where('line_user_id', $lineUserId)->exists()) {
            $lineUserId = null;
        }

        return DB::transaction(function () use ($phone, $lineUserId, $gender, $ageRangeId): CohortProfile {
            $personCode = $this->personCodes->next(lock: true);

            /* ไม่เก็บชื่อ — ใช้รหัสบุคคลเป็นชื่อในระบบ โครงสร้างตารางคงเดิม
               รหัสนี้ยังทำหน้าที่ชั้นยืนยันตัวตนคู่กับเบอร์ตอนเข้าระบบ (matchesNamePrefix) */
            $participant = Participant::create([
                'code' => $personCode,
                'person_code' => $personCode,
                'name' => $personCode,
                'phone' => $phone,
                'gender' => $gender,
                'age_range_id' => $ageRangeId,
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
     * @param  array{from: string, to: string, targetGroupIds?: array<int>, page?: int, pageSize?: int}  $filters
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
            /* ใบที่เคยอยู่ในรอบอื่นแล้ว "ยังเลือกซ้ำได้" — ตั้งใจให้เปิดรอบใหม่ตามคนเดิมซ้ำได้
               (เตือนครั้งที่สองของใบเดียวกัน) ตัวกันข้อความถล่มคือเงื่อนไข answered_at ด้านบน:
               คนที่ตอบแล้วหลุดจากรายชื่อเอง และ notify() ในรอบเดียวกันก็ไม่ส่งซ้ำคนที่ส่งสำเร็จแล้ว */
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
                /* ไม่กำหนดก็ปล่อยว่าง — การ์ดแจ้งเตือนจะไปใช้วันครบกำหนดของใบรายคนแทน */
                'answer_due_date' => $data['answer_due_date'] ?? null,
                'form_id' => $data['form_id'],
                'notification_template' => $data['notification_template'] ?: $this->defaultTemplate(),
                /* ตั้งใจส่งแต่ลิงก์ในการ์ดยังเปิดจากมือถือไม่ได้ = ไม่มีใครได้รับอะไร
                   เก็บเป็นร่างไว้ก่อน แอดมินตั้งค่าเสร็จแล้วกดส่งจากหน้ารายละเอียดได้เลย
                   ไม่ต้องเลือกรายชื่อใหม่ทั้งชุด */
                'state' => $notify && $this->publicLinkReady()
                    ? RoundBatch::STATE_RUNNING
                    : RoundBatch::STATE_DRAFT,
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
    /**
     * ส่งแจ้งเตือนให้คนเดียว แล้วบันทึกผลลงใบสมาชิกกับประวัติของคนนั้น
     *
     * แยกออกมาเพราะแอดมินต้องส่งรายคนได้จากหน้ารายละเอียด — การส่งซ้ำทั้งรอบ
     * ยิงถึงคนที่ตอบไปแล้วด้วย ซึ่งเป็นการรบกวนที่ไม่มีเหตุผล
     *
     * @return 'sent'|'failed'|'noChannel'|'badLink'  ผลที่เกิดขึ้นจริง ใช้เป็นคีย์นับในสรุปของ notify()
     */
    public function notifyMember(RoundBatch $batch, RoundBatchMember $member): string
    {
        /* กันตั้งแต่ต้นทาง ไม่แตะสถานะของสมาชิกเลย — ครั้งนี้ถือว่ายังไม่ได้ส่ง
           กดส่งใหม่หลังตั้งค่าลิงก์ถูกแล้วต้องยิงถึงคนนี้ได้เหมือนเดิม */
        if (! $this->publicLinkReady()) {
            return 'badLink';
        }

        $member->loadMissing(['cohortProfile.participant', 'followUpRound']);

        $participant = $member->cohortProfile?->participant;
        $lineUserId = $participant?->line_user_id;

        if (blank($lineUserId)) {
            $member->update([
                'notify_channel' => RoundBatchMember::CHANNEL_NONE,
                'notify_result' => RoundBatchMember::RESULT_NO_CHANNEL,
            ]);

            return 'noChannel';
        }

        $message = $this->fillTemplate($batch->notification_template, $participant, $member->followUpRound, $batch);
        /* ส่งเป็นการ์ดมีปุ่มกดเสมอ — รอบกับวันสุดท้ายที่ตอบได้ขึ้นบนการ์ดเป็นโครงสร้างอยู่แล้ว
           แอดมินจึงไม่ต้องพะวงว่าลืมใส่ตัวแปรในข้อความ */
        $ok = $this->push->pushSurveyInvite(
            $lineUserId,
            $message,
            $member->followUpRound->name,
            $this->thaiDate($batch->answerDueFor($member->followUpRound)),
            $this->healthUrl(),
        );

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

        return $ok ? 'sent' : 'failed';
    }

    public function notify(RoundBatch $batch): array
    {
        $batch->loadMissing(['members.cohortProfile.participant', 'members.followUpRound']);

        $result = [
            'sent' => 0, 'failed' => 0, 'noChannel' => 0, 'badLink' => 0,
            'lineConfigured' => $this->push->isConfigured(),
            'publicLink' => $this->publicLinkReady(),
        ];

        foreach ($batch->members as $member) {
            if ($member->notify_result === RoundBatchMember::RESULT_SENT) {
                $result['sent']++;

                continue;
            }

            $outcome = $this->notifyMember($batch, $member);
            $result[$outcome]++;
        }

        /* ลิงก์ยังใช้ไม่ได้ = ยังไม่ได้เริ่มส่งจริง ห้ามเลื่อนรอบออกจากสถานะร่าง
           ไม่งั้นแอดมินจะเห็นว่ารอบนี้ "กำลังดำเนินการ" ทั้งที่ไม่มีใครได้รับอะไรเลย */
        if ($batch->state === RoundBatch::STATE_DRAFT && $result['badLink'] === 0) {
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
    public function fillTemplate(?string $template, Participant $participant, FollowUpRound $round, ?RoundBatch $batch = null): string
    {
        return strtr($template ?: $this->defaultTemplate(), [
            '{ชื่อ}' => $participant->name,
            /* ชื่อรอบมาจากใบของคนนั้น ซึ่ง snapshot มาจากหน้าตั้งค่ารอบประเมิน — ไม่ได้เขียนตายไว้ */
            '{รอบ}' => $round->name,
            /* เส้นตายของรอบมาก่อนวันครบกำหนดของใบรายคน ถ้าแอดมินกำหนดไว้
               รอบเก่าที่ไม่ได้กำหนดยังได้วันเดิมเหมือนก่อนมีคอลัมน์นี้ */
            '{วันครบกำหนด}' => $this->thaiDate($batch?->answerDueFor($round) ?? $round->due_date),
            '{ลิงก์}' => $this->healthUrl(),
        ]);
    }

    public function defaultTemplate(): string
    {
        return config('farmconcept.tracking_round.default_message');
    }

    /**
     * ลิงก์หน้าแบบประเมิน — ปลายทางของปุ่มบนการ์ดและตัวแปร {ลิงก์}
     *
     * ยึดค่าคงที่ ไม่ใช่ host ของคำขอ เพราะข้อความถูกส่งนอกบริบท HTTP ได้ (คิวรัน/สั่งจาก CLI)
     * คนที่เชื่อม LINE แล้วเปิดลิงก์นี้จะกดปุ่ม LINE เข้าได้เลยโดยไม่ต้องกรอกเบอร์
     *
     * ปกติใช้ APP_URL แต่เปิดให้ตั้ง HEALTH_PUBLIC_URL ทับได้ เพราะบนเครื่องพัฒนา
     * APP_URL คือโดเมนที่มีแต่เครื่องนั้นเปิดได้ (เช่น .test ของ Herd) — ส่งการ์ดออกไป
     * แล้วปุ่มบนมือถือจะกดไม่ติด ตั้งค่านี้ชี้ไปเซิร์ฟเวอร์จริงจะทดสอบจากเครื่องพัฒนาได้
     */
    public function healthUrl(): string
    {
        $base = config('farmconcept.tracking_round.public_url') ?: config('app.url');

        return rtrim((string) $base, '/').'/health';
    }

    /**
     * ลิงก์ที่จะส่งออกไป เปิดจากมือถือได้จริงหรือไม่
     *
     * โดเมนของเครื่องพัฒนา (.test ของ Herd, localhost, เลข IP ในวง) เปิดได้แต่บนเครื่องนั้น
     * ส่งการ์ดที่มีลิงก์แบบนี้ออกไปคือส่งปุ่มที่กดแล้วไม่ไปไหน แล้วยัง stamp notified_at
     * ทับไว้ด้วย ทำให้ส่งซ้ำทั้งรอบไม่ยิงถึงคนนั้นอีก — เสียโอกาสแจ้งเตือนไปเลยหนึ่งครั้ง
     */
    public function publicLinkReady(): bool
    {
        $host = parse_url($this->healthUrl(), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        return ! preg_match('/(^localhost$|\.test$|\.local$|\.localhost$)/i', $host);
    }

    /**
     * รอบที่ตอบได้ตอนนี้ของคนหนึ่งคน — ใช้หลังยืนยันตัวตนจาก QR
     *
     * เปิดให้ตอบทันทีโดยไม่ต้องรอวันครบกำหนด คนที่พร้อมตอบก่อนกำหนดไม่ควรถูกกันไว้เฉย ๆ
     * แต่ต้องไล่ตามลำดับรอบ ห้ามข้ามไปตอบรอบหลังก่อน เพราะคำตอบแต่ละรอบต้องเทียบก่อน–หลังกันได้
     * ข้ามรอบเมื่อไรชุดข้อมูลของคนนั้นก็เทียบไม่ได้อีกเลย
     *
     * คืนเป็น Collection เพื่อให้ฝั่งเรียกเช็ค "มีรอบให้ทำไหม" ได้ด้วยรูปแบบเดิม
     * แต่โดยกติกาแล้วจะมีได้มากสุดหนึ่งใบเสมอ
     */
    public function openRoundsFor(Participant $participant): Collection
    {
        $profile = $participant->cohortProfile;

        if ($profile === null || $profile->isStopped()) {
            return collect();
        }

        /* โหลด cohortProfile มาด้วย — ปลายทางของใบนี้คือการบันทึกคำตอบ ซึ่งต้องใช้ participant_id */
        return $profile->rounds()
            ->with('cohortProfile')
            ->whereNull('answered_at')
            ->orderBy('due_date')
            ->orderBy('offset_days')
            ->limit(1)
            ->get();
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
            /* รหัสกลุ่มตัวอย่าง (CHT-xxxx) คือรหัสที่ใช้แทนตัวตนในบริบทงานวิจัย
               หน้าผลติดตามและ export ต้องอ้างรหัสนี้ ไม่ใช่ชื่อหรือรหัสบุคคล */
            'cohortCode' => ($member?->cohortProfile ?? $round->cohortProfile)->cohort_code,
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
