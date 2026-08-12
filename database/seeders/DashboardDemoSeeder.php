<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Answer;
use App\Models\CohortProfile;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Form;
use App\Models\Participant;
use App\Models\Question;
use App\Models\SurveyResponse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลตัวอย่างสำหรับแดชบอร์ดภาพรวม
 *
 * ทำไมต้องมี: ตาราง ptp_* กับ evl_* ยังว่างทั้งหมด แผงกลุ่มตัวอย่าง อัตราการตอบ
 * และผลประเมินก่อน/หลัง จึงขึ้น Empty State ทั้งหมด ทั้งที่ตัวหน้าจอทำงานถูกแล้ว
 * seeder นี้สร้างชุดข้อมูลที่ "สอดคล้องกันเอง" เพื่อให้เห็นหน้าจอเต็มรูปแบบ
 *
 * กติกาที่ยึด
 * - **ไม่สุ่ม** ทุกค่ามาจากฟังก์ชันของ index (ดู jitter()) รันซ้ำได้ผลเดิมทุกครั้ง
 *   ถ้าใช้ rand() ตัวเลขบนแดชบอร์ดจะขยับทุกครั้งที่ seed แล้วเทียบภาพก่อน/หลังไม่ได้
 * - **เพิ่มอย่างเดียว ไม่ทับของเดิม** คอลัมน์ในใบลงทะเบียนเติมเฉพาะช่องที่ยังเป็น NULL
 * - **ระบุตัวได้** ผู้เข้าร่วมที่สร้างจากที่นี่ใช้รหัสขึ้นต้น DEMO- ลบออกได้ด้วยคำสั่งเดียว
 *   (ดูวิธีลบใน docs ท้ายคลาสนี้)
 *
 * ไม่สร้างระเบียน ptp_consents ให้ — ตารางนั้นเป็นหลักฐานการยินยอม PDPA ที่ต้องตรวจย้อนหลังได้
 * การปั้นหลักฐานปลอมขึ้นมาไม่ถูกต้องแม้เป็นข้อมูลตัวอย่าง จึงตั้งแค่คอลัมน์สถานะที่ใช้กรองในตาราง
 */
class DashboardDemoSeeder extends Seeder
{
    /** สัดส่วนผู้เข้าร่วมที่ถูกชวนเข้ากลุ่มตัวอย่าง */
    private const COHORT_RATIO = 0.6;

    /** คะแนนเต็มของคำถาม — ต้องตรงกับ config('farmconcept.assessment_score_max') */
    private const SCORE_MAX = 5;

    /**
     * หัวข้อประเมิน 5 ด้าน พร้อมคะแนนเฉลี่ยเป้าหมาย (สเกล 1–SCORE_MAX)
     * before = รอบก่อนเข้าร่วม · after = รอบ 12 เดือน · รอบกลางไล่ระดับให้เอง
     *
     * "การผลิตอาหารไว้บริโภคเอง" ตั้งไว้ต่ำสุดทั้งก่อนและหลังโดยตั้งใจ
     * เพื่อให้แผงผลวิเคราะห์แนวโน้มมีหัวข้อ "ยังต่ำที่สุดหลังเข้าร่วม" ให้ชี้จริง
     */
    private const TOPICS = [
        ['ความรอบรู้ด้านอาหาร', 2.6, 3.9],
        ['ความมั่นคงทางอาหาร', 2.3, 3.6],
        ['การเข้าถึงอาหารปลอดภัย', 2.8, 4.1],
        ['พฤติกรรมการบริโภค', 2.5, 3.8],
        ['การผลิตอาหารไว้บริโภคเอง', 1.9, 3.1],
    ];

    /**
     * อัตราการตอบของแต่ละรอบ — ลดลงตามระยะเวลา ตามที่เกิดขึ้นจริงในงานติดตาม
     * คีย์คือ offset_days ของ template
     */
    private const ANSWER_RATE = [0 => 1.0, 90 => 0.88, 180 => 0.78, 365 => 0.66];

    public function run(): void
    {
        $occupations = DB::table('mst_options')
            ->where('option_group', 'occupation')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $channels = DB::table('mst_options')
            ->where('option_group', 'source_channel')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $areas = DB::table('mst_areas')->orderBy('id')->pluck('id')->all();
        $targetGroups = DB::table('mst_target_groups')->orderBy('id')->pluck('id')->all();

        if ($occupations === [] || $areas === [] || $targetGroups === []) {
            $this->command?->warn('  ข้ามไป — ต้องรัน MasterDataSeeder ก่อน');

            return;
        }

        DB::transaction(function () use ($occupations, $channels, $areas, $targetGroups) {
            $this->fillRegistrationProfile($occupations, $channels, $areas, $targetGroups);
            $participants = $this->syncParticipants($occupations, $channels, $areas, $targetGroups);
            $this->syncExtraCourseActivities($participants, $areas);
            $cohort = $this->syncCohort($participants);
            $form = $this->syncForm();
            $this->syncRoundsAndAnswers($cohort, $form);
        });

        $this->report();
    }

    /* ==========================================================================
       1) เติมข้อมูลโปรไฟล์ให้ใบลงทะเบียนที่กรอกไม่ครบ
       ========================================================================== */

    /**
     * ใบลงทะเบียน 122 ใบมี gender กับ birth_year อยู่แล้ว แต่ occupation / area /
     * target_group / source_channel ยังว่างทั้งหมด แผง "จำแนกตามอาชีพ" จึงไม่มีอะไรแสดง
     *
     * เติมเฉพาะช่องที่เป็น NULL — ใบที่มีคนกรอกไว้แล้วไม่ถูกแก้
     *
     * @param  array<int, int>  $occupations
     * @param  array<int, int>  $channels
     * @param  array<int, int>  $areas
     * @param  array<int, int>  $targetGroups
     */
    private function fillRegistrationProfile(array $occupations, array $channels, array $areas, array $targetGroups): void
    {
        $rows = DB::table('act_registrations')
            ->orderBy('id')
            ->get(['id', 'phone', 'birth_year', 'occupation_id', 'area_id', 'target_group_id', 'source_channel_id']);

        /* ใบที่ผูกกับผู้เข้าร่วมชุด DEMO- คือของที่ seeder นี้เคยเติมไว้เอง เขียนทับได้
           ใบที่คนกรอกมาจริงหรือผูกกับผู้เข้าร่วมชุดอื่นจะเติมเฉพาะช่องที่ยังว่าง */
        $ownIds = DB::table('act_registrations')
            ->join('ptp_participants', 'ptp_participants.id', '=', 'act_registrations.participant_id')
            ->where('ptp_participants.code', 'like', 'DEMO-%')
            ->pluck('act_registrations.id')
            ->all();

        $own = array_fill_keys($ownIds, true);

        foreach ($rows->values() as $index => $row) {
            $update = [];
            $mine = isset($own[$row->id]);

            /* กระจายอาชีพแบบถ่วงน้ำหนัก ไม่ใช่วนเท่ากันทุกตัวเลือก
               ข้อมูลจริงมีอาชีพยอดนิยมกระจุกอยู่ไม่กี่อัน กราฟจึงควรมีความชันให้เห็น */
            if ($row->occupation_id === null || $mine) {
                $update['occupation_id'] = $occupations[$this->weightedPick(count($occupations), $index)];
            }

            if ($row->target_group_id === null || $mine) {
                $update['target_group_id'] = $this->targetGroupFor($row->birth_year, $index, $targetGroups);
            }

            /* พื้นที่ถ่วงน้ำหนักเหมือนอาชีพ — ถ้าวนเท่ากันทุกพื้นที่ แท่งซ้อนในแถว 5
               จะยาวเท่ากันหมด ซึ่งไม่ได้บอกอะไรและไม่เหมือนข้อมูลจริง */
            if ($row->area_id === null || $mine) {
                $update['area_id'] = $areas[$this->weightedPick(count($areas), $index + 3)];
            }

            if (($row->source_channel_id === null || $mine) && $channels !== []) {
                $update['source_channel_id'] = $channels[$this->jitter($index, 7) % count($channels)];
            }

            if ($update !== []) {
                DB::table('act_registrations')->where('id', $row->id)->update($update);
            }
        }
    }

    /**
     * กลุ่มเป้าหมาย — อิงอายุเป็นหลัก ไม่ใช่สุ่มแยกจากปีเกิด
     * ไม่งั้นจะมี "ผู้สูงอายุ" ที่เกิดปี 2004 ซึ่งอ่านแล้วขัดกันเอง
     *
     * ยกเว้น "กลุ่มเปราะบาง" ที่นิยามครอบทุกช่วงวัย (age_range = "ทุกช่วงวัย")
     * จึงแทรกให้ประมาณหนึ่งในเจ็ด เพื่อให้โดนัทในแถว 3 มีครบทั้งสี่กลุ่มตามที่ออกแบบไว้
     *
     * ลำดับ id: เด็กและเยาวชน · วัยทำงาน · ผู้สูงอายุ · กลุ่มเปราะบาง
     */
    private function targetGroupFor(?int $birthYear, int $index, array $targetGroups): int
    {
        if (count($targetGroups) >= 4 && $this->jitter($index, 23) % 7 === 3) {
            return $targetGroups[3];
        }

        $age = $birthYear ? (int) now()->year - $birthYear : 40;

        $slot = match (true) {
            $age <= 18 => 0,
            $age >= 60 => 2,
            default => 1,
        };

        return $targetGroups[min($slot, count($targetGroups) - 1)];
    }

    /* ==========================================================================
       2) ผู้เข้าร่วม (ptp_participants) — หนึ่งคนต่อหนึ่งเบอร์โทร
       ========================================================================== */

    /**
     * สร้างระเบียนคนจากใบลงทะเบียนที่มีอยู่ แล้วผูก participant_id กลับไปที่ใบลงทะเบียน
     *
     * ใบลงทะเบียนเดิมมี participant_id เป็น NULL ทั้งหมด (กรอกหน้างาน ยังไม่ผูกตัวตน)
     * เมื่อผูกแล้ว การนับ "จำนวนคน" ของแดชบอร์ดจะใช้ participant_id ซึ่งแม่นกว่าเบอร์โทร
     *
     * @return Collection<int, Participant>
     */
    private function syncParticipants(array $occupations, array $channels, array $areas, array $targetGroups)
    {
        /* จัดกลุ่มใบลงทะเบียนตามเบอร์โทร — ใบแรกสุดเป็นตัวแทนข้อมูลของคนนั้น */
        $groups = DB::table('act_registrations')
            ->orderBy('id')
            ->get(['id', 'name', 'phone', 'email', 'gender', 'birth_year', 'occupation_id', 'area_id', 'target_group_id', 'source_channel_id'])
            ->groupBy('phone');

        $participants = collect();
        $index = 0;

        foreach ($groups as $phone => $rows) {
            $first = $rows->first();
            $index++;

            $participant = Participant::updateOrCreate(
                ['person_code' => 'DEMO-PSN-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT)],
                [
                    'code' => 'DEMO-PTP-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'name' => $first->name,
                    'phone' => $phone,
                    'email' => $first->email,
                    'gender' => $first->gender,
                    'birth_year' => $first->birth_year,
                    'occupation_id' => $first->occupation_id,
                    'area_id' => $first->area_id,
                    'target_group_id' => $first->target_group_id,
                    'source_channel_id' => $first->source_channel_id,
                    'status' => 'ใช้งานอยู่',
                    'project_status' => 'เข้าร่วม',
                    /* คอลัมน์นี้มีไว้กรองในตารางเท่านั้น หลักฐานจริงอยู่ที่ ptp_consents
                       ซึ่ง seeder นี้ไม่สร้างให้ (ดูเหตุผลในหัวคลาส) */
                    'consent_status' => 'ยินยอมแล้ว',
                ]
            );

            /* ผูกทุกใบของเบอร์นี้เข้ากับคนคนเดียว */
            DB::table('act_registrations')
                ->whereIn('id', $rows->pluck('id'))
                ->whereNull('participant_id')
                ->update(['participant_id' => $participant->id]);

            $participants->push($participant);
        }

        return $participants;
    }

    /* ==========================================================================
       2.5) กิจกรรมเพิ่มเพื่อให้กราฟหลักสูตรมีอันดับให้เรียงจริง
       ========================================================================== */

    /**
     * กิจกรรมตัวอย่าง 2 รายการที่ผูกหลักสูตรซึ่งยังไม่มีใครเรียน
     *
     * ทำไมต้องมี: กราฟ "หลักสูตรที่มีผู้เข้าร่วมสูงสุด" ต้องแสดง 5 อันดับ
     * แต่ระบบมีกิจกรรมที่ผูกหลักสูตรและมีผู้ลงทะเบียนอยู่แค่ 4 หลักสูตร
     * จึงเป็น "อันดับ" ไม่ได้จริงเพราะแสดงทั้งหมดที่มี
     *
     * ใช้ **ผู้เข้าร่วมเดิม** ลงทะเบียนเพิ่ม ไม่สร้างคนใหม่
     * ยอด "ผู้เข้าร่วมทั้งหมด" จึงยังเป็น 47 คนเท่าเดิม เปลี่ยนแค่ว่าคนหนึ่งเรียนหลายหลักสูตร
     * ซึ่งเป็นพฤติกรรมจริงของโครงการอยู่แล้ว
     *
     * ไม่แตะกิจกรรมที่มีอยู่เดิมเลย — ใช้รหัสขึ้นต้น DEMO-ACT- แยกออกจาก ACT-2026-xxx
     *
     * @param  Collection<int, Participant>  $participants
     * @param  array<int, int>  $areas
     */
    private function syncExtraCourseActivities($participants, array $areas): void
    {
        /* หลักสูตรที่ยังไม่มีกิจกรรมไหนผูกอยู่ — เลือกจาก master data ที่มีจริง
           ตัวเลขคือจำนวนผู้ลงทะเบียน ตั้งให้ต่ำกว่าสามอันดับแรกเดิม (47/32/30)
           กราฟจึงเรียงลำดับลดหลั่นจริง ไม่ใช่แท่งเท่ากันหมด */
        $plan = [
            ['Cooking Workshop ลดหวาน มัน เค็ม', 26, 2],
            ['วางแผนมื้ออาหารสุขภาพ', 18, 1],
        ];

        foreach ($plan as $slot => [$courseName, $seats, $areaCount]) {
            $course = DB::table('mst_courses')->where('name', $courseName)->first();

            if (! $course) {
                $this->command?->warn("  ข้ามกิจกรรมของหลักสูตร \"{$courseName}\" — ไม่มีในฐานข้อมูล");

                continue;
            }

            $code = 'DEMO-ACT-'.str_pad((string) ($slot + 1), 2, '0', STR_PAD_LEFT);
            $start = now()->subMonths(2 + $slot)->startOfDay();

            $activity = Activity::updateOrCreate(['code' => $code], [
                'name' => $courseName,
                'description' => 'กิจกรรมตัวอย่างสำหรับแดชบอร์ด — สร้างโดย DashboardDemoSeeder',
                'type' => Activity::TYPE_ACTIVITY,
                'participant_type' => 'บุคคลทั่วไป',
                'program_id' => $course->program_id,
                'course_id' => $course->id,
                'status' => 'ดำเนินการเสร็จสิ้น',
                'capacity' => $seats + 4,
                'requires_registration' => true,
                'requires_checkin' => true,
                'has_post_survey' => true,
                'is_published' => true,
                'start_date' => $start->toDateString(),
                'end_date' => $start->toDateString(),
            ]);

            /* ผูกพื้นที่ให้ต่างจำนวนกัน — treemap "พื้นที่ที่จัดกิจกรรมมากที่สุด"
               จะได้มีกล่องขนาดต่างกันจริง ไม่ใช่กล่องเท่ากันทุกพื้นที่ */
            $activity->areas()->sync(array_slice($areas, 0, min($areaCount, count($areas))));

            $this->syncDemoRegistrations($activity, $participants->take($seats), $slot);
        }
    }

    /**
     * ใบลงทะเบียนของกิจกรรมตัวอย่าง — คัดลอกโปรไฟล์มาจากผู้เข้าร่วมคนนั้นตรง ๆ
     *
     * @param  Collection<int, Participant>  $people
     */
    private function syncDemoRegistrations(Activity $activity, $people, int $slot): void
    {
        foreach ($people->values() as $index => $person) {
            DB::table('act_registrations')->updateOrInsert(
                ['code' => 'DEMO-REG-'.$slot.'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'activity_id' => $activity->id,
                    'participant_id' => $person->id,
                    'name' => $person->name,
                    'phone' => $person->phone,
                    'email' => $person->email,
                    'gender' => $person->gender,
                    'birth_year' => $person->birth_year,
                    'occupation_id' => $person->occupation_id,
                    'area_id' => $person->area_id,
                    'target_group_id' => $person->target_group_id,
                    'source_channel_id' => $person->source_channel_id,
                    'payment_status' => 'ไม่มีค่าใช้จ่าย',
                    /* กิจกรรมจบแล้ว ส่วนใหญ่จึงเข้าร่วมจริง เหลือส่วนน้อยที่ลงชื่อแล้วไม่มา */
                    'checkin_status' => $this->jitter($index, $slot + 31) % 10 < 8 ? 'เข้าร่วมแล้ว' : 'ยังไม่เข้าร่วม',
                    'registered_at' => $activity->start_date->copy()->subDays(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /* ==========================================================================
       3) กลุ่มตัวอย่าง (ptp_cohort_profiles)
       ========================================================================== */

    /**
     * ชวนผู้เข้าร่วมส่วนหนึ่งเข้ากลุ่มตัวอย่าง
     *
     * วันเข้าร่วมไล่จากเก่าไปใหม่ให้ครอบคลุมทุกช่วง — คนที่เข้านานแล้วจะครบกำหนดทั้ง 4 รอบ
     * คนที่เพิ่งเข้าจะครบแค่รอบแรก ทำให้อัตราการตอบของแต่ละรอบมีฐานต่างกันเหมือนของจริง
     *
     * @param  Collection<int, Participant>  $participants
     * @return Collection<int, CohortProfile>
     */
    private function syncCohort($participants)
    {
        $take = (int) round($participants->count() * self::COHORT_RATIO);
        $chosen = $participants->take($take)->values();
        $cohort = collect();

        foreach ($chosen as $index => $participant) {
            /* ไล่ย้อนหลังทีละ 20 วัน คนสุดท้ายอยู่ราว 19 เดือนก่อน
               ช่วงนี้ทำให้ประมาณ 40% ของกลุ่มมีรอบ 12 เดือนถึงกำหนดแล้ว */
            $entry = now()->subDays(30 + $index * 20)->startOfDay();

            $cohort->push(CohortProfile::updateOrCreate(
                ['participant_id' => $participant->id],
                [
                    'cohort_code' => 'DEMO-CHT-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'entry_date' => $entry->toDateString(),
                    'source_type' => $index % 3 === 0 ? 'referral' : 'walk_in',
                ]
            ));
        }

        return $cohort;
    }

    /* ==========================================================================
       4) แบบติดตามสุขภาพ (evl_forms + evl_questions)
       ========================================================================== */

    /**
     * แบบฟอร์มหนึ่งชุด คำถามหนึ่งข้อต่อหนึ่งหัวข้อประเมิน
     *
     * คอลัมน์ dimension คือสิ่งที่แดชบอร์ดใช้จัดกลุ่มคะแนนรายด้าน
     * ถ้าเว้นว่างไว้ แผงผลประเมินก่อน/หลังจะไม่มีหัวข้อให้เทียบเลย
     */
    private function syncForm(): Form
    {
        $form = Form::updateOrCreate(
            ['code' => 'DEMO-FRM-01'],
            [
                'name' => 'แบบติดตามสุขภาพและพฤติกรรมการบริโภค',
                'type' => 'แบบติดตามสุขภาพ',
                'status' => 'เปิดใช้งาน',
                /* ระบุตัวตนได้และต้องได้ — ต่างจากแบบประเมินความพึงพอใจที่เป็นนิรนาม */
                'is_anonymous' => false,
            ]
        );

        foreach (self::TOPICS as $order => [$dimension]) {
            Question::updateOrCreate(
                ['form_id' => $form->id, 'dimension' => $dimension],
                [
                    'sort_order' => $order + 1,
                    'question_type' => 'rating',
                    'text' => 'ระดับ'.$dimension.'ของท่านในช่วง 3 เดือนที่ผ่านมา',
                    'is_required' => true,
                ]
            );
        }

        return $form;
    }

    /* ==========================================================================
       5) รอบติดตาม + คำตอบ
       ========================================================================== */

    /**
     * สร้างรอบติดตามของแต่ละคนตาม template แล้วใส่คำตอบให้รอบที่ "ตอบแล้ว"
     *
     * name กับ offset_days เป็น snapshot ตาม migration — ห้าม join กลับไปอ่าน template สด
     * ไม่งั้นพอแอดมินแก้จำนวนวัน วันครบกำหนดของคนที่ตอบไปแล้วจะขยับทั้งกระดาน
     *
     * @param  Collection<int, CohortProfile>  $cohort
     */
    private function syncRoundsAndAnswers($cohort, Form $form): void
    {
        $templates = FollowUpRoundTemplate::where('is_active', true)
            ->orderBy('offset_days')
            ->get();

        $questions = Question::where('form_id', $form->id)->orderBy('sort_order')->get();
        $today = now()->startOfDay();

        foreach ($cohort as $personIndex => $profile) {
            $entry = Carbon::parse($profile->entry_date);

            foreach ($templates as $template) {
                $due = $entry->copy()->addDays($template->offset_days);

                /* รอบที่ยังไม่ถึงกำหนดไม่ควรมีอยู่ในระบบ — คนที่เพิ่งเข้าเดือนก่อน
                   ไม่ควรถูกนับเป็น "ยังไม่ตอบ" ของรอบ 12 เดือน */
                if ($due->greaterThan($today)) {
                    continue;
                }

                $rate = self::ANSWER_RATE[$template->offset_days] ?? 0.7;
                $answered = $this->jitter($personIndex, $template->offset_days + 3) % 100 < (int) round($rate * 100);

                $round = FollowUpRound::updateOrCreate(
                    ['cohort_profile_id' => $profile->id, 'offset_days' => $template->offset_days],
                    [
                        'template_id' => $template->id,
                        'name' => $template->name,
                        'due_date' => $due->toDateString(),
                        'answered_at' => $answered ? $due->copy()->addDays(2)->setTime(10, 30) : null,
                    ]
                );

                if (! $answered) {
                    /* เปลี่ยนใจจากตอบแล้วเป็นยังไม่ตอบตอนรันซ้ำ ต้องล้างคำตอบเก่าทิ้งด้วย
                       ไม่งั้นจะมีคำตอบค้างอยู่ทั้งที่รอบบอกว่ายังไม่ตอบ */
                    $this->clearResponse($round);

                    continue;
                }

                $this->syncResponse($round, $profile, $form, $questions, $template->offset_days, $personIndex);
            }
        }
    }

    /**
     * คำตอบหนึ่งชุดต่อหนึ่งรอบ — คะแนนแต่ละด้านไล่ขึ้นตามระยะเวลาที่ติดตาม
     *
     * @param  Collection<int, Question>  $questions
     */
    private function syncResponse(FollowUpRound $round, CohortProfile $profile, Form $form, $questions, int $offsetDays, int $personIndex): void
    {
        $response = SurveyResponse::updateOrCreate(
            ['cohort_round_id' => $round->id],
            [
                'form_id' => $form->id,
                'participant_id' => $profile->participant_id,
                'submitted_at' => $round->answered_at,
            ]
        );

        /* ความคืบหน้าของรอบ: 0 = ก่อนเข้าร่วม, 1 = ครบ 12 เดือน */
        $progress = min(1.0, $offsetDays / 365);

        foreach ($questions->values() as $topicIndex => $question) {
            [, $before, $after] = self::TOPICS[$topicIndex];
            $target = $before + ($after - $before) * $progress;

            Answer::updateOrCreate(
                [
                    'response_type' => 'survey',
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                ],
                ['score' => $this->scoreNear($target, $this->jitter($personIndex, $topicIndex + $offsetDays))]
            );
        }
    }

    private function clearResponse(FollowUpRound $round): void
    {
        $response = SurveyResponse::where('cohort_round_id', $round->id)->first();

        if (! $response) {
            return;
        }

        Answer::where('response_type', 'survey')->where('response_id', $response->id)->delete();
        $response->delete();
    }

    /* ==========================================================================
       ตัวช่วย — ทุกตัวเป็นฟังก์ชันของ input ไม่มีการสุ่ม
       ========================================================================== */

    /**
     * คะแนนจำนวนเต็มที่เฉลี่ยแล้วเข้าใกล้ค่าเป้าหมาย
     *
     * evl_answers.score เป็นจำนวนเต็ม เก็บ 2.6 ตรง ๆ ไม่ได้
     * จึงกระจายเป็น 2 กับ 3 ตามสัดส่วนของทศนิยม ค่าเฉลี่ยของกลุ่มจึงลงที่ 2.6
     */
    private function scoreNear(float $target, int $seed): int
    {
        $base = (int) floor($target);
        $bump = ($seed % 100) < (int) round(($target - $base) * 100) ? 1 : 0;

        return max(1, min(self::SCORE_MAX, $base + $bump));
    }

    /**
     * ตัวเลขกระจายแบบคาดเดาได้จากสองอินพุต
     *
     * ใช้แทน rand() ทุกจุด เพื่อให้ seed ซ้ำได้ผลเดิม — ตัวเลขบนแดชบอร์ดจะนิ่ง
     * เทียบภาพก่อน/หลังแก้โค้ดได้ และ bug ที่เจอครั้งหนึ่งจะเจอซ้ำได้อีก
     */
    private function jitter(int $a, int $b): int
    {
        return abs(crc32($a.':'.$b));
    }

    /**
     * เลือก index แบบถ่วงน้ำหนัก — index ต้น ๆ ถูกเลือกบ่อยกว่าท้าย ๆ
     * ทำให้กราฟอาชีพมีความชัน ไม่ใช่แท่งเท่ากันหมดซึ่งดูไม่เหมือนข้อมูลจริง
     */
    private function weightedPick(int $count, int $seed): int
    {
        /* น้ำหนัก count, count-1, ... 1 รวมเป็น count*(count+1)/2 */
        $total = $count * ($count + 1) / 2;
        $point = $this->jitter($seed, 11) % (int) $total;

        for ($i = 0; $i < $count; $i++) {
            $weight = $count - $i;

            if ($point < $weight) {
                return $i;
            }

            $point -= $weight;
        }

        return $count - 1;
    }

    private function report(): void
    {
        $this->command?->info(sprintf(
            '  ผู้เข้าร่วม %d · กลุ่มตัวอย่าง %d · รอบติดตาม %d (ตอบแล้ว %d) · คำตอบ %d ข้อ',
            Participant::count(),
            CohortProfile::count(),
            FollowUpRound::count(),
            FollowUpRound::whereNotNull('answered_at')->count(),
            Answer::where('response_type', 'survey')->count(),
        ));
    }
}
