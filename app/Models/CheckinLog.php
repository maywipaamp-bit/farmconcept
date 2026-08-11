<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * audit log ของการเช็คอิน — ทั้งการเช็คอินและการยกเลิก
 *
 * performed_at ต้องมาจากนาฬิกาเซิร์ฟเวอร์เสมอ ห้ามรับเวลาจากเครื่องหน้างาน
 * เพราะนาฬิกาเครื่องหน้างานตั้งเองได้ ลำดับการเช็คอินจะเพี้ยนทันที
 */
class CheckinLog extends Model
{
    protected $table = 'act_checkin_logs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['performed_at' => 'datetime'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
