<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewItem extends Model
{
    protected $table = 'rev_review_items';

    protected $guarded = ['id'];

    protected $casts = ['due_date' => 'date'];

    /**
     * สถานะที่ใช้ได้ พร้อมโทนสีของป้าย
     *
     * ลำดับคือลำดับการทำงานจริง: ยังไม่เริ่ม → ส่งให้ตรวจ → กำลังแก้ตามที่คอมเมนต์ → จบ
     */
    public const STATUSES = [
        'รอพัฒนา' => 'badge-neutral',
        'ตรวจได้' => 'badge-success',
        'ตรวจแล้ว' => 'badge-info',
        'ระหว่างแก้งาน' => 'badge-warning',
        'เสร็จสิ้น' => 'badge-primary',
    ];

    /** เปิดดูได้เฉพาะหน้าที่ส่งตรวจแล้ว — "รอพัฒนา" ยังไม่มีของให้ดู */
    public function isOpenForReview(): bool
    {
        return $this->status !== 'รอพัฒนา';
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(ReviewRound::class, 'round_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReviewComment::class, 'item_id')->orderBy('id');
    }
}
