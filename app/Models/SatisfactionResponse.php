<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * คำตอบแบบประเมินความพึงพอใจ — นิรนามโดยข้อกำหนด (ปม C)
 *
 * ห้ามเพิ่ม relation หรือคอลัมน์ใดที่ชี้ไปยังคนในคลาสนี้ ไม่ว่ากรณีใด
 * การรู้ว่า "ใครตอบแล้ว" ทำผ่าน SatisfactionReceipt ซึ่งไม่มีเส้นเชื่อมมาที่นี่
 */
class SatisfactionResponse extends Model
{
    protected $table = 'evl_satisfaction_responses';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function activityRound(): BelongsTo
    {
        return $this->belongsTo(ActivityRound::class);
    }

    public function answers(): MorphMany
    {
        return $this->morphMany(Answer::class, 'response');
    }
}
