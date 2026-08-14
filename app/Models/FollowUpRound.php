<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * รอบติดตามของ "คนหนึ่งคน"
 *
 * name และ offset_days เป็น snapshot ตอนสร้าง ห้ามอ่านสดจาก template
 * ไม่งั้นแอดมินแก้จำนวนวันเมื่อไหร่ วันครบกำหนดของคนที่ตอบไปแล้วจะขยับทั้งกระดาน
 * สถานะไม่มีคอลัมน์เก็บ — คำนวณจาก due_date เทียบวันนี้เสมอ
 */
class FollowUpRound extends Model
{
    protected $table = 'ptp_follow_up_rounds';

    protected $guarded = ['id'];

    protected $casts = ['due_date' => 'date', 'answered_at' => 'datetime'];

    public function cohortProfile(): BelongsTo
    {
        return $this->belongsTo(CohortProfile::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FollowUpRoundTemplate::class, 'template_id');
    }

    public function surveyResponse(): HasOne
    {
        return $this->hasOne(SurveyResponse::class, 'cohort_round_id');
    }

    /** ใบนี้ถูกดึงเข้ารอบติดตามไหนไปแล้วบ้าง — ใช้กันไม่ให้ถูกดึงซ้ำสองรอบพร้อมกัน */
    public function batchMembers(): HasMany
    {
        return $this->hasMany(RoundBatchMember::class, 'follow_up_round_id');
    }

    /** วันแรกที่เปิดให้ตอบ — รอบ offset 0 ทำในวันที่เข้ากลุ่มเลย ไม่มีช่วงให้รอ */
    public function windowStart(): Carbon
    {
        return $this->offset_days === 0
            ? $this->due_date->copy()
            : $this->due_date->copy()->subDays(config('farmconcept.follow_up.window_days_before'));
    }

    public function windowEnd(): Carbon
    {
        return $this->offset_days === 0
            ? $this->due_date->copy()
            : $this->due_date->copy()->addDays(config('farmconcept.follow_up.window_days_after'));
    }

    public function state(?Carbon $today = null): string
    {
        $today = $today ?? Carbon::today();

        return match (true) {
            $this->answered_at !== null => 'ตอบแล้ว',
            $today->lt($this->windowStart()) => 'ยังไม่ถึงกำหนด',
            $today->lte($this->windowEnd()) => 'รอติดตาม',
            default => 'เกินกำหนด',
        };
    }
}
