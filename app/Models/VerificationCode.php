<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** รหัสยืนยันตัวตนก่อนตอบแบบประเมินสุขภาพ — เก็บเป็น hash ไม่เก็บรหัสดิบ */
class VerificationCode extends Model
{
    protected $table = 'ptp_verification_codes';

    protected $guarded = ['id'];

    protected $hidden = ['code_hash'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', Carbon::now());
    }
}
