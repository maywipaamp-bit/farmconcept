<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicTrackingRoundQrController;
use App\Models\Area;
use App\Models\CohortProfile;
use App\Models\District;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Form;
use App\Models\Option;
use App\Models\Participant;
use App\Models\QrCode;
use App\Models\Question;
use App\Models\SurveyResponse;
use App\Models\TargetGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * QR ทำแบบประเมินติดตามสุขภาพ — เส้นทางของผู้ตอบตั้งแต่สแกนจนส่งคำตอบ
 *
 * ยืนยันตัวตนสองชั้น เบอร์โทร + ชื่อจริง 5 ตัวอักษรแรก
 * ตัวตนอยู่ใน session ไม่ใช่ใน URL — ลิงก์ที่แชร์ต่อจึงไม่พาสิทธิ์ของคนอื่นไปด้วย
 */
class TrackingRoundQrTest extends TestCase
{
    use RefreshDatabase;

    private function qr(): QrCode
    {
        return QrCode::firstOrCreate(
            ['purpose' => 'health', 'activity_id' => null],
            ['token' => 'abcdefghijklmnopqrstuvwx', 'target_url' => '/h/abcdefghijklmnopqrstuvwx', 'is_active' => true]
        );
    }

    private function template(string $name = 'ติดตามครั้งที่หนึ่ง', int $offset = 0): FollowUpRoundTemplate
    {
        return FollowUpRoundTemplate::firstOrCreate(
            ['code' => 'FRT-'.$offset],
            ['name' => $name, 'offset_days' => $offset, 'is_active' => true, 'sort_order' => $offset + 1]
        );
    }

    /** คนที่ลงทะเบียนไว้แล้ว พร้อมใบติดตามหนึ่งใบที่ครบกำหนดวันนี้ */
    private function member(string $code, string $phone, ?string $lineId = null, ?string $due = null, ?string $name = null): FollowUpRound
    {
        $template = $this->template();

        $participant = Participant::create([
            'code' => $code, 'person_code' => $code,
            'name' => $name ?? 'ผู้ร่วม '.$code, 'phone' => $phone, 'line_user_id' => $lineId,
        ]);

        $profile = CohortProfile::create([
            'participant_id' => $participant->id,
            'cohort_code' => 'CHT-'.$code,
            'entry_date' => now()->toDateString(),
        ]);

        return FollowUpRound::create([
            'cohort_profile_id' => $profile->id,
            'template_id' => $template->id,
            'name' => $template->name,
            'offset_days' => 0,
            'due_date' => $due ?? now()->toDateString(),
        ]);
    }

    private function healthForm(): Form
    {
        $form = Form::firstOrCreate(
            ['code' => 'FRM-HEALTH'],
            ['name' => 'แบบติดตามสุขภาพ', 'type' => Form::TYPE_HEALTH_FOLLOW_UP, 'status' => Form::STATUS_ACTIVE]
        );

        Question::firstOrCreate(
            ['form_id' => $form->id, 'sort_order' => 1],
            ['question_type' => 'rating', 'text' => 'สุขภาพโดยรวมของคุณตอนนี้', 'is_required' => true]
        );

        Question::firstOrCreate(
            ['form_id' => $form->id, 'sort_order' => 2],
            ['question_type' => 'text', 'text' => 'สิ่งที่เปลี่ยนไป', 'is_required' => false]
        );

        return $form->fresh('questions');
    }

    /**
     * ยืนยันชื่อจริงต่อจากการกรอกเบอร์
     *
     * เบอร์อย่างเดียวเข้าไม่ได้แล้ว — ทุกเบอร์ที่เจอในฐานต้องผ่านหน้ายืนยันชื่อก่อนเสมอ
     */
    private function confirmName(?string $prefix = null): TestResponse
    {
        $name = Participant::find(session('candidateIds', [])[0] ?? 0)?->name ?? '';

        return $this->post($this->url('/choose'), [
            'name_prefix' => $prefix ?? mb_substr($name, 0, 5),
        ]);
    }

    /** ยืนยันตัวตนครบสองชั้นในบรรทัดเดียว — ใช้ในเทสต์ที่แค่ต้องการสถานะ "เข้าระบบแล้ว" */
    private function signInWithPhone(string $phone): TestResponse
    {
        $this->post($this->url('/verify'), ['phone' => $phone]);

        return $this->confirmName();
    }

    /** พาธสาธารณะไม่มี token แล้ว — QR ติดตามสุขภาพมีอันเดียวทั้งโครงการ */
    private function url(string $suffix = ''): string
    {
        $this->qr();

        return '/health'.$suffix;
    }

    private function area(): Area
    {
        return Area::firstOrCreate(['code' => 'AREA-1'], [
            'name' => 'ชุมชนสวนหลวง',
            'area_type_id' => $this->option('area_type', 'AT-1', 'ชุมชนเมือง')->id,
            'area_group_id' => $this->option('area_group', 'AG-1', 'กลุ่มกรุงเทพ')->id,
            'district_id' => District::firstOrCreate(['province' => 'กรุงเทพมหานคร', 'name' => 'เขตสวนหลวง'])->id,
            'coordinator_name' => 'ลดา',
            'coordinator_phone' => '065-256-4455',
        ]);
    }

    private function option(string $group, string $code, string $label): Option
    {
        return Option::firstOrCreate(
            ['option_group' => $group, 'code' => $code],
            ['label' => $label, 'sort_order' => 1, 'is_active' => true]
        );
    }

    private function group(): TargetGroup
    {
        return TargetGroup::firstOrCreate(
            ['code' => 'TG-1'],
            ['name' => 'วัยทำงาน', 'is_active' => true, 'sort_order' => 1]
        );
    }

    /** ฟอร์มลงทะเบียนไม่เก็บชื่อ — ระบบใช้รหัสบุคคลเป็นชื่อในระบบแทน */
    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'phone' => '0899999999',
            'gender' => 'female',
            'age_range_id' => $this->option('age_range', 'AGE-1', '60 ปีขึ้นไป')->id,
            'consent' => 1,
        ], $overrides);
    }

    public function test_identify_screen_asks_for_the_phone_only(): void
    {
        $this->get($this->url())
            ->assertOk()
            ->assertSee('เบอร์โทรศัพท์')
            ->assertSee('เข้าสู่ระบบประเมินสุขภาวะ')
            ->assertSee('เข้าสู่ระบบด้วย LINE')
            /* รายละเอียดโครงการซ่อนหลังตัวกด แต่ต้องอยู่ในหน้านี้เสมอ
               เพราะเป็นข้อมูลที่ต้องบอกก่อนขอข้อมูลส่วนบุคคล */
            ->assertSee('เกี่ยวกับโครงการและการใช้ข้อมูล')
            ->assertSee('แบบประเมินชุดนี้ใช้เพื่อรวบรวมข้อมูล')
            /* รายการติ๊กถูกสามข้อถูกตัดออก เนื้อความซ้ำกับคำชี้แจงด้านล่างอยู่แล้ว */
            ->assertDontSee('ข้อมูลของคุณเป็นความลับ เสนอผลเป็นภาพรวมเท่านั้น')
            /* หน้านี้เหลือเฉพาะสิ่งที่ต้องกด — ตัวช่วยเหลือย้ายไปหน้าแดชบอร์ด */
            ->assertDontSee('ผู้ประสานงานโครงการ')
            ->assertDontSee('เบอร์เดียวใช้ได้หลายคนในครัวเรือน')
            /* รหัสบุคคลบนใบยินยอมถูกตัดออกจากขั้นยืนยันแล้ว */
            ->assertDontSee('รหัสบุคคลบนใบยินยอม');
    }

    public function test_verifying_a_known_phone_asks_for_the_name_before_letting_you_in(): void
    {
        $round = $this->member('P0001', '081-234-5678');

        /* เจอเบอร์แล้วยังเข้าไม่ได้ทันที — เบอร์เป็นสิ่งที่คนอื่นรู้ได้ ต้องยืนยันชื่อจริงอีกชั้น */
        $this->post($this->url('/verify'), ['phone' => '0812345678'])
            ->assertRedirect($this->url('/choose'));

        /* กรอกเบอร์อย่างเดียวยังเข้าแดชบอร์ดไม่ได้ */
        $this->get($this->url('/home'))->assertRedirect($this->url());

        $this->post($this->url('/verify'), ['phone' => '0812345678']);

        $this->get($this->url('/choose'))
            ->assertOk()
            ->assertSee('ยืนยันตัวตน')
            ->assertSee('รหัสบุคคล');

        $this->confirmName()->assertRedirect($this->url('/home'));

        /* หน้าแรกหลังยืนยันคือแดชบอร์ด — เห็นไทม์ไลน์ครบทุกรอบและกดทำรอบที่ถึงคิวได้ทันที */
        $this->get($this->url('/home'))
            ->assertOk()
            ->assertSee('รอบแบบประเมิน')
            ->assertSee($round->name)
            ->assertSee('ทำได้เลย')
            ->assertSee('เริ่มทำ');

        $this->get($this->url('/rounds'))
            ->assertOk()
            ->assertSee('รอบแบบประเมิน')
            ->assertSee($round->name)
            ->assertSee('ทำได้เลย')
            ->assertSee('เริ่มทำ');
    }

    public function test_typing_the_person_code_with_the_phone_signs_in_from_one_screen(): void
    {
        /* คนที่เจ้าหน้าที่เพิ่มให้จากหลังบ้านมีชื่อจริงกับรหัสคนละค่ากัน
           รหัสบุคคลต้องใช้เข้าระบบได้ ไม่ใช่รับแต่ชื่อตามที่หน้าจอไม่ได้บอก */
        $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');

        $this->post($this->url('/verify'), ['phone' => '0812345678', 'person_code' => 'P0001'])
            ->assertRedirect($this->url('/home'));

        $this->get($this->url('/home'))->assertOk()->assertSee('รอบแบบประเมิน');
    }

    /** รหัสบุคคลต้องตรงทั้งรหัส — พิมพ์แค่ต้นรหัสแล้วชนหลายคนพร้อมกันต้องไม่ผ่าน */
    public function test_a_partial_person_code_is_not_enough(): void
    {
        $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');
        $this->member('P0002', '081-234-5678', null, null, 'มานี รักเรียน');

        $this->post($this->url('/verify'), ['phone' => '0812345678', 'person_code' => 'P000'])
            ->assertRedirect($this->url('/choose'));

        $this->get($this->url('/home'))->assertRedirect($this->url());
    }

    public function test_a_wrong_person_code_does_not_get_in_and_says_why(): void
    {
        $this->member('P0001', '081-234-5678', null, null, 'P0001');

        $this->post($this->url('/verify'), ['phone' => '0812345678', 'person_code' => 'P9999'])
            ->assertRedirect($this->url('/choose'))
            ->assertSessionHasErrors('name_prefix');

        $this->get($this->url('/home'))->assertRedirect($this->url());
    }

    /** อีเมลเป็นทางเลือกในช่องเดียวกับเบอร์ — สำหรับคนที่ไม่สะดวกใช้เบอร์ */
    public function test_an_email_works_in_the_same_field_as_the_phone(): void
    {
        $round = $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');
        $round->cohortProfile->participant->update(['email' => 'Somchai@Example.com']);

        /* เทียบแบบไม่สนตัวพิมพ์ใหญ่เล็กและช่องว่างหัวท้าย ข้อมูลที่คีย์เข้ามามีทั้งสองแบบ */
        $this->post($this->url('/verify'), ['phone' => '  somchai@example.com  ', 'person_code' => 'P0001'])
            ->assertRedirect($this->url('/home'));

        $this->get($this->url('/home'))->assertOk();
    }

    public function test_an_unknown_email_says_so_instead_of_pushing_to_registration(): void
    {
        $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');

        /* ฟอร์มลงทะเบียนรับเฉพาะเบอร์ ส่งคนที่กรอกอีเมลไปที่นั่นก็ไปต่อไม่ได้ */
        $this->post($this->url('/verify'), ['phone' => 'nobody@example.com'])
            ->assertSessionHasErrors('phone');

        $this->get($this->url('/home'))->assertRedirect($this->url());
    }

    public function test_someone_without_a_cohort_profile_is_not_found_by_email(): void
    {
        Participant::create([
            'code' => 'P9999', 'person_code' => 'P9999',
            'name' => 'ยังไม่เป็นกลุ่มตัวอย่าง', 'phone' => '082-000-0000',
            'email' => 'outsider@example.com',
        ]);

        $this->post($this->url('/verify'), ['phone' => 'outsider@example.com'])
            ->assertSessionHasErrors('phone');
    }

    public function test_rounds_screen_cannot_be_opened_without_verifying_first(): void
    {
        $this->member('P0001', '081-234-5678');

        $this->get($this->url('/rounds'))->assertRedirect($this->url());
    }

    public function test_the_identity_screen_never_shows_who_is_on_that_phone(): void
    {
        $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');
        $this->member('P0002', '0812345678', null, null, 'มานี รักเรียน');

        $this->post($this->url('/verify'), ['phone' => '081-234-5678'])
            ->assertRedirect($this->url('/choose'));

        /* ห้ามยื่นรายชื่อให้เลือก แม้แบบปิดบัง — แค่รู้เบอร์ของบ้านหนึ่ง
           ไม่ควรอ่านได้ว่ามีใครอยู่ในโครงการบ้าง ผู้ตอบต้องพิมพ์ชื่อตัวเองจากความจำ */
        $this->followingRedirects()
            ->post($this->url('/verify'), ['phone' => '081-234-5678'])
            ->assertOk()
            ->assertSee('ยืนยันตัวตน')
            ->assertSee('รหัสบุคคล')
            ->assertDontSee('สมชาย')
            ->assertDontSee('มานี')
            ->assertDontSee('สมช')
            ->assertDontSee('มาน');
    }

    public function test_the_typed_name_must_match_before_you_get_in(): void
    {
        $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');
        $this->member('P0002', '081-234-5678', null, null, 'มานี รักเรียน');

        $this->post($this->url('/verify'), ['phone' => '081-234-5678']);

        /* กรอกผิด = ยังไม่ผ่าน และต้องยังไม่ถูกยืนยันตัวตน */
        $this->post($this->url('/choose'), ['name_prefix' => 'ผิดจัง'])
            ->assertSessionHasErrors('name_prefix');

        /* ระบบเป็นฝ่ายจับคู่ว่าชื่อนี้คือใครในเบอร์นั้น ฟอร์มไม่ได้ส่ง id มาเลย */
        $this->post($this->url('/choose'), ['name_prefix' => 'สมชา'])
            ->assertRedirect($this->url('/home'));

        /* แดชบอร์ดไม่แสดงชื่อ — หน้าจอเปิดในที่สาธารณะได้ ใช้รหัสกลุ่มตัวอย่างแทน */
        $this->get($this->url('/home'))->assertOk()->assertDontSee('สมชาย ใจดี');
    }

    public function test_a_name_from_a_different_phone_cannot_get_in(): void
    {
        $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');
        $this->member('P0002', '089-999-9999', null, null, 'อรทัย พากเพียร');

        $this->post($this->url('/verify'), ['phone' => '0812345678']);

        /* ชื่อจริงของคนอื่นที่อยู่คนละเบอร์ ต้องเข้าไม่ได้ — สองชั้นต้องตรงกันทั้งคู่ */
        $this->post($this->url('/choose'), ['name_prefix' => 'อรทัย'])
            ->assertSessionHasErrors('name_prefix');

        $this->get($this->url('/home'))->assertRedirect($this->url());
    }

    public function test_two_people_on_one_phone_with_the_same_opening_letters_are_not_guessed(): void
    {
        $this->member('P0001', '081-234-5678', null, null, 'สมชาย ใจดี');
        $this->member('P0002', '081-234-5678', null, null, 'สมชาย ใจงาม');

        $this->post($this->url('/verify'), ['phone' => '0812345678']);

        /* เดาให้ไม่ได้ เพราะเดาผิดแปลว่าคำตอบลงระเบียนผิดคนโดยไม่มีใครรู้ */
        $this->post($this->url('/choose'), ['name_prefix' => 'สมชา'])
            ->assertSessionHasErrors('name_prefix');

        $this->get($this->url('/home'))->assertRedirect($this->url());
    }

    public function test_an_unknown_phone_is_taken_to_self_registration(): void
    {
        $this->template();

        $this->post($this->url('/verify'), ['phone' => '0899999999'])
            ->assertRedirect($this->url('/register'));

        $this->followingRedirects()
            ->post($this->url('/verify'), ['phone' => '0899999999'])
            ->assertOk()
            ->assertSee('ลงทะเบียนกลุ่มตัวอย่าง')
            /* เติมเบอร์ที่เพิ่งกรอกให้ ไม่ต้องพิมพ์ซ้ำ */
            ->assertSee('0899999999');
    }

    public function test_self_registration_creates_a_cohort_member_with_rounds_and_consent(): void
    {
        $this->template('ก่อนเข้าร่วม', 0);
        $this->template('3 เดือน', 90);

        $this->post($this->url('/register'), $this->registerPayload())
            ->assertRedirect($this->url('/home'));

        $participant = Participant::firstOrFail();

        /* รหัสรูปแบบเดียวกับที่แอดมินเพิ่มจากหลังบ้าน — ตัวออกรหัสตัวเดียวกัน */
        $this->assertSame('P0001', $participant->person_code);
        /* ไม่เก็บชื่อ — รหัสบุคคลถูกใช้เป็นชื่อในระบบ และเป็นกุญแจยืนยันตัวตนชั้นที่สอง */
        $this->assertSame('P0001', $participant->name);
        $this->assertSame('089-999-9999', $participant->phone);

        /* เพศกับช่วงอายุถูกบันทึกจริง ไม่ได้ปล่อยว่างให้เจ้าหน้าที่มาตามเก็บทีหลัง */
        $this->assertSame('female', $participant->gender);
        $this->assertSame($this->option('age_range', 'AGE-1', '60 ปีขึ้นไป')->id, $participant->age_range_id);

        $this->assertSame(
            ['ก่อนเข้าร่วม', '3 เดือน'],
            $participant->cohortProfile->rounds->sortBy('offset_days')->pluck('name')->all()
        );

        $this->assertDatabaseHas('ptp_consents', [
            'participant_id' => $participant->id,
            'status' => 'ยินยอม',
            'recorded_via' => 'self_qr',
        ]);

        $this->followingRedirects()->get($this->url('/home'))
            ->assertOk()
            /* แดชบอร์ดทักทายด้วยรหัสบุคคล — รหัสเดียวกับที่ใช้เข้าระบบ
               และต้องบอกรหัสหลังลงทะเบียนหนึ่งครั้ง เพราะเป็นกุญแจเข้าระบบครั้งถัดไป */
            ->assertSee('P0001')
            ->assertSee('รหัสบุคคลของคุณคือ');
    }

    public function test_dashboard_shows_the_whole_timeline(): void
    {
        $this->template('ก่อนเข้าร่วม', 0);
        $this->post($this->url('/register'), $this->registerPayload());

        /* หน้าหลักแสดงไทม์ไลน์เต็มชุด ไม่ใช่การ์ดรอบเดียวแล้วต้องกดไปอีกหน้าเพื่อดูภาพรวม */
        $this->get($this->url('/home'))
            ->assertOk()
            ->assertSee('รอบแบบประเมิน')
            ->assertSee('รอบที่ 1')
            ->assertSee('ก่อนเข้าร่วม')
            ->assertSee('ทำได้เลย')
            ->assertSee('เริ่มทำ')
            ->assertSee('0/1')
            ->assertSee('แจ้งเตือนรอบถัดไปผ่าน LINE')
            ->assertSee('ทำแทนคนอื่น')
            /* คำชี้แจงการใช้ข้อมูลอยู่ที่หน้าเข้าสู่ระบบเท่านั้น หน้าหลักเหลือเฉพาะสิ่งที่ต้องทำ */
            ->assertDontSee('ข้อมูลของคุณเป็นความลับ')
            ->assertDontSee('อ่านคำชี้แจง')
            /* ชื่อโครงการไม่ต้องแสดงในหน้านี้ — อ่านได้จากหน้าเข้าสู่ระบบ */
            ->assertDontSee('โครงการพัฒนาพื้นที่ต้นแบบ');
    }

    /** ไม่มีปุ่มเชื่อม LINE แยกแล้ว — สวิตช์แจ้งเตือนคือทางเข้าเดียว กดตอนยังไม่เชื่อมจะพาไปเชื่อมเลย */
    public function test_the_notify_switch_sends_unlinked_people_to_line_first(): void
    {
        $round = $this->member('P0001', '081-234-5678');
        $participant = $round->cohortProfile->participant;
        $this->signInWithPhone('0812345678');

        /* แดชบอร์ดไม่มีปุ่มชวนเชื่อมแล้ว */
        $this->get($this->url('/home'))->assertOk()->assertDontSee('เชื่อมต่อ LINE');

        /* กดสวิตช์ตอนยังไม่เชื่อม — ถูกพาไปหน้าเชื่อม LINE และค่าแจ้งเตือนต้องไม่ถูกสลับ */
        $before = $participant->fresh()->line_notify;
        $this->post($this->url('/notify'))->assertRedirect($this->url('/line'));
        $this->assertSame($before, $participant->fresh()->line_notify);
    }

    public function test_notification_switch_belongs_to_the_person_and_can_be_turned_off(): void
    {
        $round = $this->member('P0001', '081-234-5678', 'U-line-1');
        $participant = $round->cohortProfile->participant;

        $this->signInWithPhone('0812345678');

        $this->assertTrue($participant->fresh()->line_notify, 'ค่าเริ่มต้นต้องเปิด');

        $this->post($this->url('/notify'));
        $this->assertFalse($participant->fresh()->line_notify);

        $this->post($this->url('/notify'));
        $this->assertTrue($participant->fresh()->line_notify);
    }

    public function test_self_registration_requires_name_phone_and_consent(): void
    {
        $this->template();

        $this->post($this->url('/register'), [])
            ->assertSessionHasErrors(['phone', 'gender', 'age_range_id', 'consent']);

        $this->assertSame(0, Participant::count());
    }

    /** กดสวิตช์เชื่อม LINE จากแดชบอร์ดขณะล็อกอินอยู่ — ขากลับต้องผูกให้ทันที ไม่ไล่ไปกรอกเบอร์ซ้ำ */
    public function test_line_return_links_the_signed_in_person_without_asking_for_the_phone_again(): void
    {
        $round = $this->member('P0001', '081-234-5678');
        $participant = $round->cohortProfile->participant;
        $this->signInWithPhone('0812345678');

        /* จำลองกลับมาจาก LINE ด้วยบัญชีที่ยังไม่เคยผูกกับใคร */
        $this->withSession([
            \App\Http\Controllers\PublicLineLoginController::SESSION_KEY => [
                'userId' => 'U-new-line', 'displayName' => 'ผู้ใช้ LINE', 'pictureUrl' => null, 'email' => null,
            ],
        ])->get($this->url('/line/return'))
            ->assertRedirect($this->url('/home'));

        $this->assertSame('U-new-line', $participant->fresh()->line_user_id);
    }

    /** เชื่อม LINE ไว้แล้วมาลงทะเบียนคนใหม่ต่อ — ต้องไม่ล้มเพราะ LINE ถูกผูกกับคนแรกไปแล้ว */
    public function test_registering_a_second_person_with_a_lingering_line_session_does_not_crash(): void
    {
        $this->template();
        $this->member('P0001', '081-234-5678', 'U-line-1');

        $this->withSession([
            \App\Http\Controllers\PublicLineLoginController::SESSION_KEY => [
                'userId' => 'U-line-1', 'displayName' => 'ผู้ใช้ LINE', 'pictureUrl' => null, 'email' => null,
            ],
        ])->post($this->url('/register'), $this->registerPayload())
            ->assertRedirect($this->url('/home'));

        /* คนใหม่ถูกสร้างสำเร็จโดยไม่ผูก LINE — บัญชีเดิมยังอยู่กับคนแรก */
        $new = Participant::where('phone', '089-999-9999')->firstOrFail();
        $this->assertNull($new->line_user_id);
        $this->assertSame('U-line-1', Participant::where('code', 'P0001')->firstOrFail()->line_user_id);
    }

    public function test_self_registration_refuses_a_phone_that_already_exists(): void
    {
        $this->member('P0001', '081-234-5678');

        $this->post($this->url('/register'), $this->registerPayload(['phone' => '0812345678']))
            ->assertRedirect($this->url())
            ->assertSessionHasErrors('phone');

        $this->assertSame(1, Participant::count());
    }

    public function test_answering_the_survey_records_the_answers_and_closes_the_round(): void
    {
        $form = $this->healthForm();
        $round = $this->member('P0001', '081-234-5678');
        $rating = $form->questions->firstWhere('question_type', 'rating');
        $text = $form->questions->firstWhere('question_type', 'text');

        $this->signInWithPhone('0812345678');

        $this->get($this->url('/rounds/'.$round->id.'/survey'))
            ->assertOk()
            ->assertSee($round->name)
            ->assertSee('สุขภาพโดยรวมของคุณตอนนี้');

        $this->post($this->url('/rounds/'.$round->id.'/survey'), [
            'answer_'.$rating->id => 4,
            'answer_'.$text->id => 'กินผักมากขึ้น',
        ])->assertRedirect($this->url('/rounds/'.$round->id.'/done'));

        /* ใบยืนยันการส่ง — ต้องอ้างอิงได้ว่าตอบรอบไหน เมื่อไร */
        $this->get($this->url('/rounds/'.$round->id.'/done'))
            ->assertOk()
            ->assertSee('ส่งแบบประเมินแล้ว')
            ->assertSee('ขอบคุณในการร่วมตอบแบบสอบถาม')
            ->assertSee('วันที่ส่ง')
            ->assertSee($round->name)
            /* ใบยืนยันต้องมีทางออกกลับหน้าหลักเสมอ — เทียบแค่คำว่า "หน้าหลัก"
               ไม่ผูกกับถ้อยคำเต็มของปุ่ม เพราะข้อความปุ่มยังปรับกันอยู่ ("กลับหน้าหลัก" / "กลับไปหน้าหลัก")
               สิ่งที่ต้องไม่หายคือทางออก ไม่ใช่การสะกดคำ */
            ->assertSee('หน้าหลัก');

        $this->assertNotNull($round->fresh()->answered_at, 'ใบติดตามต้องถูกปิดว่าตอบแล้ว');

        $response = SurveyResponse::where('cohort_round_id', $round->id)->firstOrFail();
        $this->assertSame($form->id, $response->form_id);

        $this->assertDatabaseHas('evl_answers', [
            'response_type' => 'survey', 'response_id' => $response->id,
            'question_id' => $rating->id, 'score' => 4,
        ]);
        $this->assertDatabaseHas('evl_answers', [
            'response_type' => 'survey', 'response_id' => $response->id,
            'question_id' => $text->id, 'text_value' => 'กินผักมากขึ้น',
        ]);
    }

    /**
     * เคสจริงที่พัง — คนที่มีรอบค้างมากกว่าหนึ่งใบส่งแบบประเมินไม่ได้เลย
     *
     * ตัวกัน lazy loading จะติดอาวุธก็ต่อเมื่อ hydrate ได้โมเดลมากกว่าหนึ่งตัว
     * เทสต์เดิมมีใบเดียวจึงผ่านตลอดทั้งที่ของจริงพัง — เทสต์นี้ต้องมีสองใบเสมอ
     */
    public function test_someone_with_more_than_one_due_round_can_still_submit(): void
    {
        $form = $this->healthForm();
        $round = $this->member('P0001', '081-234-5678');
        $rating = $form->questions->firstWhere('question_type', 'rating');

        /* ใบที่สองของคนเดียวกัน ครบกำหนดเหมือนกัน — offset ต้องต่างกันเพราะติด unique */
        $second = $this->template('ติดตามครั้งที่สอง', 30);
        FollowUpRound::create([
            'cohort_profile_id' => $round->cohort_profile_id,
            'template_id' => $second->id,
            'name' => $second->name,
            'offset_days' => 30,
            'due_date' => now()->toDateString(),
        ]);

        $this->signInWithPhone('0812345678');

        $this->post($this->url('/rounds/'.$round->id.'/survey'), ['answer_'.$rating->id => 3])
            ->assertRedirect($this->url('/rounds/'.$round->id.'/done'));

        $this->assertNotNull($round->fresh()->answered_at);
    }

    public function test_a_required_question_left_blank_is_rejected(): void
    {
        $form = $this->healthForm();
        $round = $this->member('P0001', '081-234-5678');

        $this->signInWithPhone('0812345678');

        $this->post($this->url('/rounds/'.$round->id.'/survey'), [
            'answer_'.$form->questions->firstWhere('question_type', 'text')->id => 'ตอบแต่ข้อไม่บังคับ',
        ])->assertSessionHasErrors();

        $this->assertNull($round->fresh()->answered_at);
        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_a_round_that_is_already_answered_disappears_and_cannot_be_answered_again(): void
    {
        $this->healthForm();
        $round = $this->member('P0001', '081-234-5678');

        $this->signInWithPhone('0812345678');
        $round->update(['answered_at' => now()]);

        /* รอบที่ตอบแล้วยังอยู่ในไทม์ไลน์ แต่เปลี่ยนเป็นป้าย "ทำแล้ว" กดทำซ้ำไม่ได้ */
        $this->get($this->url('/rounds'))
            ->assertOk()
            ->assertSee('ทำแล้ว')
            ->assertDontSee('เริ่มทำ');

        $this->get($this->url('/rounds/'.$round->id.'/survey'))->assertNotFound();
    }

    public function test_a_round_belonging_to_someone_else_cannot_be_answered(): void
    {
        $this->healthForm();
        $mine = $this->member('P0001', '081-234-5678');
        $theirs = $this->member('P0002', '089-999-9999');

        $this->signInWithPhone('0812345678');

        $this->get($this->url('/rounds/'.$theirs->id.'/survey'))->assertNotFound();
        $this->assertNotNull($mine->fresh());
    }

    public function test_the_receipt_screen_is_not_reachable_before_answering(): void
    {
        $this->healthForm();
        $round = $this->member('P0001', '081-234-5678');

        $this->signInWithPhone('0812345678');

        /* พิมพ์ URL ตรง ๆ ต้องไม่ได้หน้า "ส่งแบบประเมินแล้ว" ทั้งที่ยังไม่ได้ส่งอะไร */
        $this->get($this->url('/rounds/'.$round->id.'/done'))->assertNotFound();
    }

    public function test_a_round_due_far_ahead_can_still_be_answered_now(): void
    {
        $this->healthForm();
        $round = $this->member('P0001', '081-234-5678', null, now()->addYear()->toDateString());

        $this->signInWithPhone('0812345678');

        /* ไม่ต้องรอวันครบกำหนด คนที่พร้อมตอบก่อนไม่ควรถูกกันไว้เฉย ๆ */
        $this->get($this->url('/rounds'))
            ->assertOk()
            ->assertSee('ทำได้เลย')
            ->assertSee('เริ่มทำ');

        $this->get($this->url('/rounds/'.$round->id.'/survey'))->assertOk();
    }

    public function test_a_later_round_stays_locked_until_the_earlier_one_is_answered(): void
    {
        $form = $this->healthForm();
        $rating = $form->questions->firstWhere('question_type', 'rating');
        $first = $this->member('P0001', '081-234-5678');

        $template = $this->template('ติดตามครั้งที่สอง', 30);
        $second = FollowUpRound::create([
            'cohort_profile_id' => $first->cohort_profile_id,
            'template_id' => $template->id,
            'name' => $template->name,
            'offset_days' => 30,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->signInWithPhone('0812345678');

        /* ข้ามลำดับไม่ได้ — คำตอบแต่ละรอบต้องเทียบก่อน–หลังกันได้ ข้ามรอบแล้วชุดข้อมูลใช้ไม่ได้ */
        $this->get($this->url('/rounds/'.$second->id.'/survey'))->assertNotFound();

        /* รอบที่ล็อกแสดงป้าย "ยังไม่เปิด" เฉย ๆ — คำอธิบายเงื่อนไขถูกตัดออกตามคำขอ */
        $this->get($this->url('/rounds'))
            ->assertOk()
            ->assertDontSee('ทำรอบก่อนหน้าให้ครบก่อน');

        $this->post($this->url('/rounds/'.$first->id.'/survey'), ['answer_'.$rating->id => 4])
            ->assertRedirect($this->url('/rounds/'.$first->id.'/done'));

        /* ตอบใบแรกเสร็จ ใบถัดไปเปิดทันที ไม่ต้องรอวันครบกำหนดเช่นกัน */
        $this->get($this->url('/rounds/'.$second->id.'/survey'))->assertOk();
    }

    public function test_signing_out_clears_the_verified_identity(): void
    {
        $this->member('P0001', '081-234-5678');

        $this->signInWithPhone('0812345678');
        $this->post($this->url('/sign-out'))->assertRedirect($this->url());

        $this->get($this->url('/rounds'))->assertRedirect($this->url());
    }

    public function test_scanning_again_after_verifying_skips_straight_to_the_rounds(): void
    {
        $this->member('P0001', '081-234-5678');

        $this->signInWithPhone('0812345678');

        /* เคยยืนยันแล้วในเครื่องนี้ ไม่ต้องกรอกเบอร์ซ้ำทุกครั้งที่สแกน */
        $this->get($this->url())->assertRedirect($this->url('/home'));
    }

    public function test_a_line_account_already_linked_signs_in_without_typing_a_phone(): void
    {
        $round = $this->member('P0001', '081-234-5678', 'U-line-1');

        $this->withSession(['line_profile' => ['userId' => 'U-line-1', 'name' => 'สมชาย']])
            ->get($this->url('/line/return'))
            ->assertRedirect($this->url('/home'));

        $this->withSession([
            'line_profile' => ['userId' => 'U-line-1', 'name' => 'สมชาย'],
            PublicTrackingRoundQrController::SESSION_KEY => ['id' => $round->cohortProfile->participant_id],
        ])->get($this->url('/rounds'))->assertOk()->assertSee($round->name);
    }

    public function test_a_line_account_not_linked_yet_is_asked_to_confirm_the_phone_first(): void
    {
        $this->template();

        /* พาไปยืนยันเบอร์ ไม่ใช่ผลักไปลงทะเบียนใหม่ — คนที่เคยลงทะเบียนด้วยเบอร์ไว้แล้ว
           จะกรอกเบอร์เดิมแล้วโดนปฏิเสธวนอยู่อย่างนั้น ผูก LINE ไม่ได้เลย */
        $this->withSession(['line_profile' => ['userId' => 'U-new', 'name' => 'คนใหม่']])
            ->get($this->url('/line/return'))
            ->assertRedirect($this->url());

        $this->withSession(['line_profile' => ['userId' => 'U-new', 'name' => 'คนใหม่']])
            ->followingRedirects()
            ->get($this->url('/line/return'))
            ->assertOk()
            ->assertSee('เชื่อม LINE กับบัญชีของคุณ');
    }

    public function test_an_existing_phone_gets_its_line_linked_after_confirming_the_phone(): void
    {
        $round = $this->member('P0001', '081-234-5678');
        $participant = $round->cohortProfile->participant;

        $this->assertNull($participant->line_user_id);

        /* เข้าด้วย LINE → ยังไม่มีใครผูก → ยืนยันเบอร์ → ระบบผูกให้เอง */
        $this->withSession(['line_profile' => ['userId' => 'U-new', 'name' => 'สมชาย']])
            ->post($this->url('/verify'), ['phone' => '0812345678']);

        $this->confirmName()->assertRedirect($this->url('/home'));

        $this->assertSame('U-new', $participant->fresh()->line_user_id);
    }

    public function test_linking_never_takes_over_a_line_account_that_belongs_to_someone_else(): void
    {
        $this->member('P0001', '081-234-5678');
        $other = $this->member('P0002', '089-999-9999', 'U-taken');

        /* บัญชี LINE นี้เป็นของคนอื่นอยู่แล้ว — ห้ามย้ายมาผูกกับคนที่เพิ่งยืนยันเบอร์
           ไม่งั้นใครที่รู้เบอร์ของคนอื่นก็ดึงปลายทางแจ้งเตือนมาเข้าตัวเองได้ */
        $this->withSession(['line_profile' => ['userId' => 'U-taken', 'name' => 'ใครก็ไม่รู้']])
            ->post($this->url('/verify'), ['phone' => '0812345678']);

        $this->confirmName()->assertRedirect($this->url('/home'));

        $this->assertNull(Participant::where('person_code', 'P0001')->firstOrFail()->line_user_id);
        $this->assertSame('U-taken', $other->cohortProfile->participant->fresh()->line_user_id);
    }

    /**
     * เคสจริงที่พังบนเซิร์ฟเวอร์ — เจ้าของ LINE เดิมถูกลบไปแล้ว (soft delete)
     *
     * unique index ที่ฐานข้อมูลนับแถวที่ soft delete ด้วย แต่ scope ปกติของ Eloquent มองไม่เห็น
     * ตัวเช็ก "LINE นี้เป็นของคนอื่นไหม" จึงตอบว่าว่าง แล้วสั่ง update ทับจนได้
     * SQLSTATE[23000] Duplicate entry คาหน้าผู้ใช้
     */
    public function test_a_line_account_left_by_a_deleted_person_does_not_crash_the_link(): void
    {
        $this->member('P0001', '081-234-5678');
        $deleted = $this->member('P0002', '089-999-9999', 'U-taken');
        $deleted->cohortProfile->participant->delete();

        $this->withSession(['line_profile' => ['userId' => 'U-taken', 'name' => 'ใครก็ไม่รู้']])
            ->post($this->url('/verify'), ['phone' => '0812345678']);

        /* ต้องเข้าระบบได้ตามปกติ แค่ผูก LINE ไม่ได้ — ไม่ใช่ 500 */
        $this->confirmName()->assertRedirect($this->url('/home'));

        $this->assertNull(Participant::where('person_code', 'P0001')->firstOrFail()->line_user_id);
    }

    public function test_linking_never_replaces_a_line_account_the_person_already_has(): void
    {
        $round = $this->member('P0001', '081-234-5678', 'U-original');

        /* เจ้าตัวผูกไว้แล้วด้วยบัญชีหนึ่ง มีคนเข้าด้วยอีกบัญชีแล้วยืนยันเบอร์เดียวกัน
           ต้องไม่เปลี่ยนปลายทางแจ้งเตือนให้เงียบ ๆ — เรื่องนี้ต้องให้เจ้าหน้าที่ตรวจ */
        $this->withSession(['line_profile' => ['userId' => 'U-second', 'name' => 'อีกเครื่อง']])
            ->post($this->url('/verify'), ['phone' => '0812345678']);

        $this->confirmName()->assertRedirect($this->url('/home'));

        $this->assertSame('U-original', $round->cohortProfile->participant->fresh()->line_user_id);
    }

    public function test_registering_while_signed_in_with_line_links_the_account(): void
    {
        $this->template();

        $this->withSession(['line_profile' => ['userId' => 'U-new', 'name' => 'คนใหม่']])
            ->post($this->url('/register'), $this->registerPayload(['name' => 'คนใหม่']))
            ->assertRedirect($this->url('/home'));

        $this->assertSame('U-new', Participant::firstOrFail()->line_user_id);
    }

    public function test_filling_in_for_someone_else_records_the_answers_under_their_name(): void
    {
        $form = $this->healthForm();
        $rating = $form->questions->firstWhere('question_type', 'rating');
        $mine = $this->member('P0001', '081-234-5678');
        $theirs = $this->member('P0002', '089-999-9999');

        $actor = $mine->cohortProfile->participant;
        $subject = $theirs->cohortProfile->participant;

        $this->signInWithPhone('0812345678');

        /* ต้องยืนยันสองชั้น เบอร์ + ชื่อจริง 3 ตัวแรก ไม่งั้นกรอกลงระเบียนผิดคน */
        $this->post($this->url('/proxy'), ['phone' => '0899999999', 'name_prefix' => 'ผิด'])
            ->assertSessionHasErrors('phone');

        $this->post($this->url('/proxy'), ['phone' => '0899999999', 'name_prefix' => 'ผู้ร่'])
            ->assertRedirect($this->url('/rounds'));

        /* ป้ายกรอกแทนต้องค้างอยู่ทุกหน้า ไม่งั้นเผลอคิดว่าตอบของตัวเอง */
        $this->get($this->url('/rounds'))->assertOk()->assertSee('กำลังกรอกแทน '.$subject->name);
        $this->get($this->url('/rounds/'.$theirs->id.'/survey'))->assertOk()->assertSee('กำลังกรอกแทน');

        $this->post($this->url('/rounds/'.$theirs->id.'/survey'), ['answer_'.$rating->id => 3])
            ->assertRedirect($this->url('/rounds/'.$theirs->id.'/done'));

        /* ใบยืนยันต้องบอกชัดว่าคำตอบลงในนามใคร ไม่งั้นเผลอคิดว่าตอบของตัวเอง */
        $this->get($this->url('/rounds/'.$theirs->id.'/done'))
            ->assertOk()
            ->assertSee('ตอบในนามของ')
            ->assertSee($subject->name);

        /* คำตอบลงระเบียนของผู้ถูกประเมิน และบันทึกว่าใครเป็นคนกรอก */
        $this->assertNotNull($theirs->fresh()->answered_at);
        $this->assertDatabaseHas('evl_survey_responses', [
            'cohort_round_id' => $theirs->id,
            'participant_id' => $subject->id,
            'submitted_by_participant_id' => $actor->id,
        ]);

        /* รอบของผู้กรอกต้องไม่ถูกนับเพิ่ม */
        $this->assertNull($mine->fresh()->answered_at);
    }

    public function test_stopping_proxy_returns_to_your_own_dashboard(): void
    {
        $this->member('P0001', '081-234-5678');
        $this->member('P0002', '089-999-9999');

        $this->signInWithPhone('0812345678');
        $this->post($this->url('/proxy'), ['phone' => '0899999999', 'name_prefix' => 'ผู้ร่']);

        $this->post($this->url('/proxy/stop'))->assertRedirect($this->url('/home'));

        /* กลับมาเป็นแดชบอร์ดของตัวเอง — ดูจากรหัสบุคคลในคำทักทาย เพราะหน้านี้ไม่แสดงชื่อแล้ว */
        $this->get($this->url('/home'))->assertOk()->assertSee('P0001')->assertDontSee('P0002');
    }

    public function test_proxy_requires_being_signed_in_first(): void
    {
        $this->member('P0002', '089-999-9999');

        $this->get($this->url('/proxy'))->assertRedirect($this->url());
    }

    public function test_an_inactive_qr_is_refused(): void
    {
        $this->qr()->update(['is_active' => false]);

        $this->get($this->url())->assertStatus(410);
    }
}
