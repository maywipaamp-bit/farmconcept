<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ใบเสร็จ "คนนี้ตอบแบบประเมินแล้ว" — ไว้ทวงคนที่ยังไม่ตอบและกันตอบซ้ำ
 *
 * ห้ามมี relation หรือคอลัมน์ใดเชื่อมไปยัง SatisfactionResponse
 * ถ้ามีเมื่อไหร่ ความนิรนามของแบบประเมินหายทันที (ปม C.4)
 *
 * กิจกรรมที่ไม่เปิดลงทะเบียนจะไม่มีแถวที่นี่ — ยอมรับว่าไม่มีอัตราการตอบ (ข้อ 7.8)
 */
class SatisfactionReceipt extends Model
{
    protected $table = 'evl_satisfaction_receipts';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
