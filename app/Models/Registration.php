<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Registration extends Model
{
    protected $table = 'act_registrations';

    protected $guarded = ['id'];

    protected $casts = [
        'is_manual_entry' => 'bool',
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function activityRound(): BelongsTo
    {
        return $this->belongsTo(ActivityRound::class);
    }

    /** ว่างได้ — Walk-in ที่ยังไม่มี profile (ปม E) จับคู่ทีหลังด้วยเบอร์โทร */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function ageRange(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'age_range_id');
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'occupation_id');
    }

    public function sourceChannel(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'source_channel_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function targetGroup(): BelongsTo
    {
        return $this->belongsTo(TargetGroup::class);
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'act_registration_interests', 'registration_id', 'option_id');
    }

    public function paymentSlips(): HasMany
    {
        return $this->hasMany(PaymentSlip::class)->latest();
    }

    public function checkinLogs(): HasMany
    {
        return $this->hasMany(CheckinLog::class)->latest('performed_at');
    }

    /**
     * ใบเสร็จการตอบแบบประเมิน — บอกได้แค่ว่า "ตอบแล้วหรือยัง"
     * ไม่มีทางไปถึงคำตอบจริง เพราะ evl_satisfaction_responses ไม่มี FK กลับมาที่คน (ปม C)
     */
    public function satisfactionReceipts(): HasMany
    {
        return $this->hasMany(SatisfactionReceipt::class);
    }

    /** คำตอบคำถามเพิ่มเติมในแบบลงทะเบียน เก็บรวมกับคำตอบแบบประเมินผ่าน morph map */
    public function answers(): MorphMany
    {
        return $this->morphMany(Answer::class, 'response');
    }

    public function scopeCheckedIn(Builder $query): Builder
    {
        return $query->whereNotNull('checked_in_at');
    }

    /** ชุด relation ที่หน้ารายชื่อผู้ลงทะเบียนต้องใช้ — กัน N+1 */
    public function scopeForList(Builder $query): Builder
    {
        return $query->with(['activity:id,code,name', 'activityRound:id,round_date,time_start', 'occupation:id,label']);
    }
}
