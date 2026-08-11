<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundBatchMember extends Model
{
    protected $table = 'evl_round_batch_members';

    protected $guarded = ['id'];

    protected $casts = ['notified_at' => 'datetime', 'offline_at' => 'datetime'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(RoundBatch::class, 'batch_id');
    }

    public function cohortProfile(): BelongsTo
    {
        return $this->belongsTo(CohortProfile::class);
    }

    public function followUpRound(): BelongsTo
    {
        return $this->belongsTo(FollowUpRound::class);
    }

    public function offlineBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offline_by');
    }

    /** คนที่แจ้งเตือนผ่านระบบไม่ได้ แอดมินต้องติดตามนอกระบบเอง */
    public function scopeUnreachable(Builder $query): Builder
    {
        return $query->whereHas(
            'cohortProfile.participant',
            fn (Builder $q) => $q->whereNull('line_user_id')
        );
    }
}
