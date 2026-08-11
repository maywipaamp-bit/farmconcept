<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * master ของ "ระยะห่างเป็นวัน" ที่ใช้คำนวณวันครบกำหนดรายคน
 *
 * รอบของแต่ละคน (FollowUpRound) snapshot ค่า name/offset_days ไปแล้ว
 * แก้ template ที่นี่จึงมีผลเฉพาะคนที่เพิ่มใหม่ ไม่ขยับวันครบกำหนดของคนเก่า
 */
class FollowUpRoundTemplate extends Model
{
    protected $table = 'mst_follow_up_round_templates';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'bool', 'line_notify' => 'bool'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(FollowUpRound::class, 'template_id');
    }
}
