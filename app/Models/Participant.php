<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ผู้เข้าร่วมหนึ่งคน = หนึ่งแถวเสมอ (ปม A)
 * สถานะ "เป็นกลุ่มตัวอย่าง" อยู่ที่ cohortProfile แบบ 1:1 — ผู้เข้าร่วมทั่วไปจะไม่มี
 */
class Participant extends Model
{
    use SoftDeletes;

    protected $table = 'ptp_participants';

    protected $guarded = ['id'];

    protected $casts = ['has_caregiver' => 'bool'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function targetGroup(): BelongsTo
    {
        return $this->belongsTo(TargetGroup::class);
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'occupation_id');
    }

    public function sourceChannel(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'source_channel_id');
    }

    /** ช่วงอายุที่กรอกมาโดยตรง — ใช้เมื่อไม่รู้ปีเกิด ดู migration add_age_range_to_ptp_participants */
    public function ageRange(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'age_range_id');
    }

    public function contactChannel(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'contact_channel_id');
    }

    public function cohortProfile(): HasOne
    {
        return $this->hasOne(CohortProfile::class);
    }

    /** ประวัติความยินยอมทั้งหมด ใหม่สุดก่อน — แถวแรกคือฉบับที่มีผลอยู่ */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class)->latest('created_at');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(FollowUpNote::class)->latest('noted_at');
    }

    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    public function isCohortMember(): bool
    {
        return $this->cohortProfile !== null;
    }

    /** อายุ ณ ปีที่กำหนด — คำนวณจาก birth_year เสมอ ไม่เก็บช่วงอายุลงฐาน (ปม F.1) */
    public function ageAt(?int $year = null): ?int
    {
        return $this->birth_year ? ($year ?? (int) date('Y')) - $this->birth_year : null;
    }

    /**
     * ป้ายช่วงอายุสำหรับรายงาน — เกณฑ์อยู่ใน config/farmconcept.php แก้ที่นั่นแล้วรายงานย้อนหลังเปลี่ยนตาม
     *
     * ปีเกิดมาก่อนเสมอเพราะคำนวณใหม่ได้ทุกปี ส่วน age_range_id เป็นค่าที่กรอกทับไว้
     * ณ วันที่เก็บข้อมูล จึงใช้เฉพาะตอนไม่มีปีเกิดให้คำนวณ
     */
    public function ageBand(?int $year = null): ?string
    {
        $age = $this->ageAt($year);

        if ($age === null) {
            return $this->ageRange?->label;
        }

        foreach (config('farmconcept.age_bands') as $band) {
            if ($band['max'] === null || $age <= $band['max']) {
                return $band['label'];
            }
        }

        return null;
    }
}
