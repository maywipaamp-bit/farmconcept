<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CohortProfile;
use App\Models\Registration;
use App\Support\ChartMath;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * รายชื่อผู้เข้าร่วมกิจกรรมทั้งหมด (admin/reports/people)
 *
 * ตอบสองคำถามที่ตารางรายกิจกรรมตอบไม่ได้ เพราะที่นั่นนับเป็น "ครั้งที่ลงทะเบียน" ไม่ใช่ "คน":
 *   1. คนที่เคยมาร่วมกิจกรรมกับเราทั้งหมดมีกี่คน และในนั้นเป็นกลุ่มตัวอย่างกี่คน
 *   2. แต่ละคนมาร่วมกี่กิจกรรมแล้ว (คนหน้าใหม่ vs คนที่กลับมาซ้ำ)
 *
 * ยึด "เบอร์โทร/อีเมล" เป็นตัวชี้ตัวคน ไม่ใช่ participant_id
 * ---------------------------------------------------------------
 * เพราะคนคนเดียวกันมี participant ได้หลายแถวจริง — ลงทะเบียนเองผ่าน QR ครั้งหนึ่ง
 * แล้วเจ้าหน้าที่เพิ่มให้หน้างานอีกครั้ง ระบบจะสร้างคนละแถว ถ้านับตาม participant_id
 * คนคนเดียวจะถูกนับเป็นสองคนและ "จำนวนกิจกรรมที่เคยมา" ของเขาจะถูกหารครึ่ง
 * เบอร์โทรเป็นค่าที่ผู้ใช้กรอกเองทุกครั้งและซ้ำกันได้ยาก จึงเป็นตัวรวมที่ตรงกับความจริงมากกว่า
 */
class ReportPeopleController extends Controller
{
    /** เกินจำนวนนี้ถือว่าเป็นคนที่กลับมาซ้ำ ไม่ใช่คนหน้าใหม่ */
    private const REPEAT_FROM = 2;

    public function index(): View
    {
        /* นับเฉพาะคนที่เช็คอินแล้วเท่านั้น — หน้านี้ตอบว่า "ใครมาร่วมกิจกรรมจริง"
           คนที่ลงทะเบียนไว้แล้วไม่มา ไม่ควรถูกนับเป็นฐานผู้เข้าร่วมของโครงการ
           (ดูใครลงทะเบียนไว้บ้างได้ที่แท็บลงทะเบียนของแต่ละกิจกรรม) */
        $registrations = Registration::query()
            ->whereNotNull('checked_in_at')
            ->with([
                'activity:id,code,name,start_date',
                'area:id,name',
                'participant:id,phone,email',
            ])
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->get([
                'id', 'activity_id', 'participant_id', 'name', 'phone', 'email',
                'area_id', 'registered_at', 'checked_in_at',
            ]);

        /* กลุ่มตัวอย่างที่ยังไม่ถอนตัว — เก็บทั้ง participant_id และเบอร์/อีเมลของ participant นั้น
           เพื่อให้จับคู่ได้แม้แถวลงทะเบียนจะชี้ไป participant คนละแถวกับที่ถูกตั้งเป็นกลุ่มตัวอย่าง */
        $cohort = CohortProfile::query()
            ->whereNull('stopped_at')
            ->with('participant:id,phone,email')
            ->get(['id', 'participant_id', 'entry_date']);

        $cohortByParticipant = $cohort->keyBy('participant_id');
        $cohortKeys = $cohort
            ->flatMap(fn (CohortProfile $p) => array_filter([
                $this->phoneKey($p->participant?->phone),
                $this->emailKey($p->participant?->email),
            ]))
            ->flip();

        $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $thaiDate = fn ($d) => $d
            ? $d->day.' '.$thMonths[$d->month - 1].' '.($d->year + 543)
            : '';

        /* จัดกลุ่มไว้เพื่อ "นับครั้ง" เท่านั้น ไม่ได้ยุบแถวในตาราง
           ตารางแสดงทุกครั้งที่มาร่วม ส่วนตัวเลข "เคยมาแล้วกี่ครั้ง" ต้องมองข้ามแถวจึงคำนวณจากตรงนี้ */
        $byIdentity = $registrations->groupBy(fn (Registration $r) => $this->identityKey($r));

        /* จำนวนกิจกรรมที่แต่ละคนเคยมา — นับเป็นกิจกรรม ไม่ใช่แถวลงทะเบียน
           คนเดียวจองหลายที่นั่งในกิจกรรมเดียวต้องนับเป็นมาหนึ่งครั้ง */
        $visitsByIdentity = $byIdentity->map(
            fn (Collection $rows) => $rows->pluck('activity_id')->filter()->unique()->count()
        );

        /* เป็นกลุ่มตัวอย่างหรือไม่ — ตัดสินระดับ "คน" แล้วเอาไปแปะทุกแถวของคนนั้น
           คิดครั้งเดียวต่อคน ไม่ใช่ทุกแถว เพราะต้องดู participant_id ของทุกครั้งที่เขาเคยมา */
        $cohortByIdentity = $byIdentity->map(function (Collection $rows) use ($cohortByParticipant, $cohortKeys) {
            $profile = $rows->pluck('participant_id')->filter()->unique()
                ->map(fn ($id) => $cohortByParticipant->get($id))
                ->filter()
                ->first();

            if ($profile) {
                return $profile;
            }

            /* ไม่เจอจาก participant_id ให้ลองจับจากเบอร์/อีเมลอีกชั้น */
            $matched = $rows->first(fn (Registration $r) => $cohortKeys->has($this->phoneKey($r->phone))
                || $cohortKeys->has($this->emailKey($r->email)));

            return $matched ? true : null;
        });

        /* ประวัติกิจกรรมของแต่ละคน — ใช้ใน popup ตอนกดตัวเลข "เคยมา N ครั้ง" */
        $historyByIdentity = $byIdentity->map(fn (Collection $rows) => $rows
            ->unique('activity_id')
            ->sortByDesc(fn (Registration $r) => $r->activity?->start_date?->toDateString() ?? '')
            ->map(fn (Registration $r) => [
                'name' => $r->activity?->name ?? '(กิจกรรมถูกลบแล้ว)',
                'date' => $thaiDate($r->activity?->start_date),
            ])
            ->values()
            ->all());

        /* หนึ่งแถว = หนึ่งครั้งที่มาร่วมกิจกรรม ไม่ยุบรวมตามเบอร์/อีเมล
           คนที่มาสามกิจกรรมจึงมีสามแถว และทุกแถวบอกว่าเขาเคยมาแล้วกี่ครั้ง */
        $people = $registrations->map(function (Registration $r) use (
            $visitsByIdentity, $cohortByIdentity, $historyByIdentity, $thaiDate
        ) {
            $key = $this->identityKey($r);
            $profile = $cohortByIdentity->get($key);

            return [
                'name' => $r->name ?: '(ไม่ระบุชื่อ)',
                'phone' => $r->phone ?: '',
                'email' => $r->email ?: '',
                'area' => $r->area?->name ?: '',
                /* กิจกรรมของแถวนี้ — ของเดิมไม่มีเพราะหนึ่งแถวคือหนึ่งคน ตอนนี้ต้องบอกว่ามาร่วมงานไหน */
                'activityName' => $r->activity?->name ?? '(กิจกรรมถูกลบแล้ว)',
                'activityDate' => $thaiDate($r->activity?->start_date),
                'joinedAt' => $thaiDate($r->checked_in_at)
                    .($r->checked_in_at ? ' · '.$r->checked_in_at->format('H:i').' น.' : ''),
                'activities' => $visitsByIdentity->get($key, 1),
                'isCohort' => $profile !== null,
                'cohortSince' => ($profile instanceof CohortProfile && $profile->entry_date)
                    ? $thaiDate($profile->entry_date)
                    : '',
                'history' => $historyByIdentity->get($key, []),
            ];
        })->values();

        /* ตัวเลขสรุปยังมองเป็น "คน" ไม่ใช่ "แถว" — คำถามที่หน้านี้ตอบคือฐานคนของโครงการ
           ส่วนตารางด้านล่างเป็นรายครั้งตามที่หน้างานต้องการไล่ดู */
        $peopleCount = $byIdentity->count();
        $cohortCount = $cohortByIdentity->filter()->count();
        $repeatCount = $visitsByIdentity->filter(fn (int $n) => $n >= self::REPEAT_FROM)->count();
        $summary = [
            'total' => $peopleCount,
            'cohort' => $cohortCount,
            'cohortPct' => $peopleCount > 0 ? (int) round($cohortCount / $peopleCount * 100) : 0,
            'repeat' => $repeatCount,
            'repeatPct' => $peopleCount > 0 ? (int) round($repeatCount / $peopleCount * 100) : 0,
            /* จำนวนแถวในตาราง = จำนวนครั้งที่มีคนมาร่วมกิจกรรม */
            'registrations' => $people->count(),
        ];

        /* การกระจายจำนวนครั้งที่มา — บอกว่าฐานคนของเราเป็นคนหน้าใหม่ล้วนหรือมีคนกลับมาซ้ำจริง

           สีกำหนดเองไม่ใช้ลำดับอัตโนมัติ (rank) เพราะชุดสีลำดับจบที่แดง ซึ่งจะไปตกที่
           "มา 5 ครั้งขึ้นไป" — กลุ่มที่ดีที่สุดของโครงการกลับถูกระบายเป็นสีเตือน อ่านกลับด้านทันที
           ไล่ฟ้า → ส้ม → เขียวอ่อน → เขียวเข้ม ตามทิศทางที่ดีขึ้นจริง
           และไม่ใช้เทากับช่วงแรก เพราะช่วงนั้นมักเป็นก้อนใหญ่สุด วงจะกลายเป็นเทาเกือบทั้งวง */
        $buckets = collect([
            ['label' => 'มาครั้งเดียว', 'count' => $visitsByIdentity->filter(fn (int $n) => $n === 1)->count(), 'tone' => 'info'],
            ['label' => 'มา 2 ครั้ง', 'count' => $visitsByIdentity->filter(fn (int $n) => $n === 2)->count(), 'tone' => 'warning'],
            ['label' => 'มา 3–4 ครั้ง', 'count' => $visitsByIdentity->filter(fn (int $n) => $n >= 3 && $n <= 4)->count(), 'tone' => 'success'],
            ['label' => 'มา 5 ครั้งขึ้นไป', 'count' => $visitsByIdentity->filter(fn (int $n) => $n >= 5)->count(), 'tone' => 'primary'],
        ]);

        /* แยกกลุ่มตัวอย่างกับคนทั่วไป — คำถามแรกของหน้านี้คือ "ฐานคนที่มาร่วมงานเป็นใคร"
           เป็นการแบ่งที่ครอบคลุมทุกคนพอดี ไม่ทับกัน จึงอ่านเป็นสัดส่วนของวงเดียวได้ตรง ๆ */
        $groups = [
            ['label' => 'กลุ่มตัวอย่าง', 'count' => $cohortCount, 'tone' => 'primary'],
            ['label' => 'กลุ่มทั่วไป', 'count' => $peopleCount - $cohortCount, 'tone' => 'info'],
        ];

        return view('admin.reports.people', [
            'people' => $people,
            'summary' => $summary,
            /* keepEmpty — ช่วงที่ยังไม่มีใครถึงต้องอยู่ใน legend ต่อ
               "ยังไม่มีใครมาครบ 5 ครั้ง" คือข้อมูลที่โครงการต้องเห็น ไม่ใช่ช่องว่างที่ตัดทิ้งได้ */
            'frequency' => ChartMath::donut($buckets->all(), keepEmpty: true),
            'byGroup' => ChartMath::donut($groups, keepEmpty: true),
        ]);
    }

    /**
     * คีย์ที่ใช้รวมแถวลงทะเบียนของคนเดียวกัน
     *
     * เบอร์โทรมาก่อนเพราะเป็นช่องบังคับกรอกของทุกช่องทาง ส่วนอีเมลเป็นช่องเสริม
     * ไม่มีทั้งคู่ (walk-in ที่กรอกแต่ชื่อ) ให้แยกเป็นคนละคนไว้ก่อน — เดารวมด้วยชื่อ
     * จะทำให้คนชื่อซ้ำกันถูกยุบเป็นคนเดียว ซึ่งผิดมากกว่าการนับเกิน
     */
    private function identityKey(Registration $registration): string
    {
        $phone = $this->phoneKey($registration->phone);

        if ($phone !== null) {
            return $phone;
        }

        $email = $this->emailKey($registration->email);

        if ($email !== null) {
            return $email;
        }

        return 'reg:'.$registration->id;
    }

    /** เบอร์โทรเหลือเฉพาะตัวเลข — "081-234-5678" กับ "0812345678" ต้องเป็นคนเดียวกัน */
    private function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? 'phone:'.$digits : null;
    }

    private function emailKey(?string $email): ?string
    {
        $clean = mb_strtolower(trim((string) $email));

        return $clean !== '' ? 'email:'.$clean : null;
    }
}
