<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CohortProfile;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * รายชื่อผู้เข้าร่วมกิจกรรมทั้งหมด (admin/activities/people)
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
class ActivityPeopleController extends Controller
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

        $people = $registrations
            ->groupBy(fn (Registration $r) => $this->identityKey($r))
            ->map(function (Collection $rows) use ($cohortByParticipant, $cohortKeys, $thaiDate) {
                /* แถวแรกคือครั้งล่าสุด (เรียง desc มาแล้ว) — ใช้ชื่อและช่องทางติดต่อชุดล่าสุด
                   ของเก่าอาจสะกดชื่อไม่เหมือนกันหรือใช้เบอร์เดิมที่เลิกใช้ไปแล้ว */
                $latest = $rows->first();

                /* นับเป็น "กิจกรรม" ไม่ใช่ "แถวลงทะเบียน" — คนเดียวจองหลายที่นั่งในกิจกรรมเดียว
                   ต้องนับเป็นมาหนึ่งครั้ง ไม่ใช่หลายครั้ง
                   ทุกแถวที่มาถึงตรงนี้เช็คอินแล้วทั้งหมด (กรองที่คิวรี) จึงเป็นจำนวนกิจกรรมที่มาจริง */
                $activityIds = $rows->pluck('activity_id')->filter()->unique();

                $profile = $rows->pluck('participant_id')->filter()->unique()
                    ->map(fn ($id) => $cohortByParticipant->get($id))
                    ->filter()
                    ->first();

                /* ไม่เจอจาก participant_id ให้ลองจับจากเบอร์/อีเมลอีกชั้น */
                $isCohort = $profile !== null
                    || $cohortKeys->has($this->phoneKey($latest->phone))
                    || $cohortKeys->has($this->emailKey($latest->email));

                /* ช่วงเวลาที่มาร่วมจริง คิดจากเวลาเช็คอิน ไม่ใช่เวลาที่ลงทะเบียนไว้ */
                $dates = $rows->pluck('checked_in_at')->filter()->sort();

                return [
                    'name' => $latest->name ?: '(ไม่ระบุชื่อ)',
                    'phone' => $latest->phone ?: '',
                    'email' => $latest->email ?: '',
                    'area' => $rows->pluck('area.name')->filter()->first() ?: '',
                    'activities' => $activityIds->count(),
                    'isCohort' => $isCohort,
                    'cohortSince' => $profile?->entry_date ? $thaiDate($profile->entry_date) : '',
                    'firstJoined' => $thaiDate($dates->first()),
                    'lastJoined' => $thaiDate($dates->last()),
                    /* รายการกิจกรรมที่คนนี้มาร่วมจริง — ใช้ใน popup ประวัติ ไม่ต้องยิงคำขอเพิ่มตอนกด */
                    'history' => $rows
                        ->unique('activity_id')
                        ->sortByDesc(fn (Registration $r) => $r->activity?->start_date?->toDateString() ?? '')
                        ->map(fn (Registration $r) => [
                            'name' => $r->activity?->name ?? '(กิจกรรมถูกลบแล้ว)',
                            'date' => $thaiDate($r->activity?->start_date),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            /* คนที่มาบ่อยที่สุดอยู่บน แล้วเรียงตามชื่อเมื่อจำนวนเท่ากัน */
            ->sortBy([
                fn (array $a, array $b) => $b['activities'] <=> $a['activities'],
                fn (array $a, array $b) => strcmp($a['name'], $b['name']),
            ])
            ->values();

        $cohortCount = $people->where('isCohort', true)->count();
        $repeatCount = $people->where('activities', '>=', self::REPEAT_FROM)->count();

        $summary = [
            'total' => $people->count(),
            'cohort' => $cohortCount,
            'cohortPct' => $people->count() > 0 ? (int) round($cohortCount / $people->count() * 100) : 0,
            'repeat' => $repeatCount,
            'repeatPct' => $people->count() > 0 ? (int) round($repeatCount / $people->count() * 100) : 0,
            'registrations' => $registrations->count(),
        ];

        /* การกระจายจำนวนครั้งที่มา — บอกว่าฐานคนของเราเป็นคนหน้าใหม่ล้วนหรือมีคนกลับมาซ้ำจริง */
        $buckets = collect([
            ['label' => 'มาครั้งเดียว', 'count' => $people->where('activities', 1)->count()],
            ['label' => 'มา 2 ครั้ง', 'count' => $people->where('activities', 2)->count()],
            ['label' => 'มา 3–4 ครั้ง', 'count' => $people->whereBetween('activities', [3, 4])->count()],
            ['label' => 'มา 5 ครั้งขึ้นไป', 'count' => $people->where('activities', '>=', 5)->count()],
        ]);
        $maxBucket = max(1, $buckets->max('count'));

        return view('admin.activities.people', [
            'people' => $people,
            'summary' => $summary,
            'frequency' => $buckets->map(fn (array $b) => $b + [
                'pct' => (int) round($b['count'] / $maxBucket * 100),
            ])->all(),
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
