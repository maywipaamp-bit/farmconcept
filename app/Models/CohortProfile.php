<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CohortProfile extends Model
{
    protected $table = 'ptp_cohort_profiles';

    protected $guarded = ['id'];

    protected $casts = ['entry_date' => 'date', 'stopped_at' => 'datetime'];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /** คนนี้เข้ากลุ่มตัวอย่างผ่านการลงทะเบียนกิจกรรมครั้งไหน (ถ้ามี) */
    public function sourceRegistration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'source_registration_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(FollowUpRound::class)->orderBy('offset_days');
    }

    public function isStopped(): bool
    {
        return $this->stopped_at !== null;
    }
}
