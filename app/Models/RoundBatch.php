<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * รอบติดตามที่แอดมินเปิดจริง — เลือกช่วงวันครบกำหนดแล้วดึงคนที่ถึงกำหนดในช่วงนั้น
 *
 * `state` เก็บเฉพาะสถานะที่เป็น "การตัดสินใจของคน" — รอเริ่ม (ร่าง) · กำลังดำเนินการ · ยกเลิกแล้ว
 * ส่วน "เสร็จสิ้น" คำนวณจากสมาชิกที่ตอบครบ ไม่เขียนทับลงคอลัมน์
 * ไม่งั้นรอบที่ตอบครบแล้วจะค้างเป็น "กำลังดำเนินการ" จนกว่าจะมีใครไปกดปิด
 */
class RoundBatch extends Model
{
    public const STATE_DRAFT = 'รอเริ่ม';

    public const STATE_RUNNING = 'กำลังดำเนินการ';

    public const STATE_DONE = 'เสร็จสิ้น';

    public const STATE_CANCELLED = 'ยกเลิกแล้ว';

    /** ลำดับเดียวกับแท็บบนหน้ารายการ */
    public const STATES = [self::STATE_RUNNING, self::STATE_DRAFT, self::STATE_DONE, self::STATE_CANCELLED];

    protected $table = 'evl_round_batches';

    protected $guarded = ['id'];

    protected $casts = ['due_from' => 'date', 'due_to' => 'date', 'answer_due_date' => 'date'];

    /**
     * เส้นตายที่ผู้ตอบต้องตอบภายใน — ของรอบนี้ก่อน ถ้าไม่ได้กำหนดค่อยใช้วันครบกำหนดของใบรายคน
     *
     * แยกจาก due_from/due_to ซึ่งเป็นช่วงที่ใช้กรองว่าใครเข้ารอบนี้ ไม่ใช่เส้นตาย
     * รอบเก่าที่สร้างก่อนมีคอลัมน์นี้จึงยังทำงานเหมือนเดิมโดยไม่ต้องไล่แก้ข้อมูลย้อนหลัง
     */
    public function answerDueFor(?FollowUpRound $round): ?\Illuminate\Support\Carbon
    {
        return $this->answer_due_date ?? $round?->due_date;
    }

    /** URL อ้างรหัสรอบ ไม่ใช่ id — ลิงก์ที่ส่งต่อกันจึงอ่านออกและไม่เปิดเผยจำนวนรอบทั้งระบบ */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** กลุ่มเป้าหมายที่รอบนี้ตั้งใจครอบคลุม — ต่างจากสมาชิกจริงที่แอดมินติ๊กเลือกทีหลัง */
    public function targetGroups(): BelongsToMany
    {
        return $this->belongsToMany(TargetGroup::class, 'evl_round_batch_target_group', 'batch_id', 'target_group_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(RoundBatchMember::class, 'batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('state', '!=', self::STATE_CANCELLED);
    }

    /**
     * สถานะที่แสดงบนหน้าจอ — "เสร็จสิ้น" มาจากการนับ ไม่ใช่จากคอลัมน์
     * ต้อง eager load members.followUpRound ก่อนเรียก ไม่งั้นได้ N+1
     */
    public function displayState(): string
    {
        if (in_array($this->state, [self::STATE_CANCELLED, self::STATE_DRAFT], true)) {
            return $this->state;
        }

        $members = $this->members;

        return $members->isNotEmpty() && $members->every(fn (RoundBatchMember $m) => $m->hasAnswered())
            ? self::STATE_DONE
            : self::STATE_RUNNING;
    }
}
