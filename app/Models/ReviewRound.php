<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewRound extends Model
{
    protected $table = 'rev_review_rounds';

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'date',
        'due_at' => 'date',
        'project_start' => 'date',
        'project_end' => 'date',
        'is_open' => 'boolean',
    ];

    /** จำนวนวันของโครงการ นับรวมวันแรกและวันสุดท้าย */
    public function projectDays(): ?int
    {
        if (! $this->project_start || ! $this->project_end) {
            return null;
        }

        return $this->project_start->diffInDays($this->project_end) + 1;
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReviewItem::class, 'round_id')->orderBy('sort_order')->orderBy('id');
    }

    /** รอบที่เปิดให้ตรวจอยู่ — หน้าตรวจงานแสดงรอบนี้รอบเดียว */
    public static function current(): ?self
    {
        return static::where('is_open', true)->orderByDesc('round_no')->first();
    }
}
