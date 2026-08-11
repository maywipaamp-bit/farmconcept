<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QR ของระบบ
 *
 * `public` สร้างทุกกิจกรรมเสมอ (เป็นทั้ง QR ประชาสัมพันธ์และ QR ลงทะเบียน — หน้าเดียวกัน)
 * `health` เป็นแถวเดียวทั้งระบบ activity_id เป็น NULL
 *
 * สร้างแถวตอนบันทึกกิจกรรม แต่ is_active = true ต่อเมื่อเผยแพร่แล้ว
 * ปิดสวิตช์ภายหลังให้ตั้ง is_active = false ห้ามลบแถว — QR ที่พิมพ์แจกไปแล้วต้องไม่เจอ 404
 */
class QrCode extends Model
{
    protected $table = 'act_qr_codes';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'bool', 'expires_at' => 'datetime'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
