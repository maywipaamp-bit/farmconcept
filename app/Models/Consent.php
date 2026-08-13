<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ระเบียนความยินยอม PDPA — append-only
 *
 * เปลี่ยนสถานะเมื่อไหร่ให้สร้างแถวใหม่ ห้ามแก้แถวเดิม
 * ไม่งั้นพิสูจน์ย้อนหลังไม่ได้ว่า ณ วันที่เก็บข้อมูล เขายินยอมไว้จริง
 */
class Consent extends Model
{
    protected $table = 'ptp_consents';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['consented_at' => 'date'];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ConsentDocument::class, 'consent_document_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
