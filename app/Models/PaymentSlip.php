<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * สลิปการชำระเงิน — หนึ่งการลงทะเบียนมีได้หลายสลิป
 * สลิปที่ถูกปฏิเสธต้องแนบใหม่ได้ และประวัติเดิมต้องอยู่ครบ
 *
 * file_path ชี้ไฟล์ที่เก็บนอก public/ ต้องเสิร์ฟผ่าน route ที่ตรวจสิทธิ์เสมอ
 */
class PaymentSlip extends Model
{
    protected $table = 'act_payment_slips';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'transferred_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'รอตรวจสอบ');
    }
}
