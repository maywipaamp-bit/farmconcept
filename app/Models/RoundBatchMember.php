<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * สมาชิกหนึ่งคนในรอบติดตามหนึ่งรอบ = รอบติดตามรายคนหนึ่งใบ (ptp_follow_up_rounds)
 *
 * "ตอบแล้วหรือยัง" ไม่มีคอลัมน์เก็บที่นี่ — อ่านจาก answered_at ของใบนั้นเสมอ
 * เพราะคนตอบผ่าน QR ได้โดยไม่ผ่านรอบนี้ ถ้าเก็บสำเนาไว้สองที่จะมีวันที่ไม่ตรงกัน
 * แล้วไม่มีใครรู้ว่าฝั่งไหนถูก — ดูเหตุผลเดียวกันใน FollowUpRound
 */
class RoundBatchMember extends Model
{
    public const CHANNEL_LINE = 'line';

    public const CHANNEL_NONE = 'none';

    public const RESULT_SENT = 'ส่งสำเร็จ';

    public const RESULT_FAILED = 'ส่งไม่สำเร็จ';

    public const RESULT_NO_CHANNEL = 'ไม่มีช่องทางแจ้งเตือน';

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

    public function hasAnswered(): bool
    {
        return $this->followUpRound?->answered_at !== null;
    }

    /** สถานะการตอบของสมาชิกคนนี้ — derive จากใบติดตามรายคน ไม่ได้เก็บซ้ำ */
    public function responseStatus(): string
    {
        return $this->hasAnswered() ? 'ตอบแล้ว' : 'ยังไม่ตอบ';
    }
}
