<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    /** สถานะที่โค้ดตัดสินใจด้วย — ค่าที่เหลืออ่านจาก mock_data ฝั่งหน้าจอ ยังไม่มี state machine (รอทีมธุรกิจ) */
    public const STATUS_DRAFT = 'ฉบับร่าง';

    public const STATUS_CANCELLED = 'ยกเลิก';

    protected $table = 'act_activities';

    /** ผูก {activity} ใน route กับคอลัมน์ code ไม่ใช่ id — URL จะได้อ่านออกและไม่เปิดเผยลำดับข้อมูล */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    protected $guarded = ['id'];

    protected $casts = [
        'requires_registration' => 'bool',
        'requires_checkin' => 'bool',
        'has_post_survey' => 'bool',
        'has_fee' => 'bool',
        'is_published' => 'bool',
        'is_featured' => 'bool',
        'fee' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'checkin_start_at' => 'datetime',
        'checkin_end_at' => 'datetime',
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(ActivityFormat::class, 'format_id');
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'act_activity_area');
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'act_activity_instructor');
    }

    public function targetGroups(): BelongsToMany
    {
        return $this->belongsToMany(TargetGroup::class, 'act_activity_target_group');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(ActivityRound::class)->orderBy('round_date');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function regFields(): HasMany
    {
        return $this->hasMany(ActivityRegField::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /** แบบฟอร์มที่ผูกไว้ — pivot slot แยก "ตอนลงทะเบียน" (ระบุตัวตน) กับ "หลังจบ" (นิรนาม) */
    public function forms(): BelongsToMany
    {
        return $this->belongsToMany(Form::class, 'evl_form_activity')->withPivot('slot');
    }

    public function satisfactionResponses(): HasMany
    {
        return $this->hasMany(SatisfactionResponse::class);
    }

    /**
     * ชุด relation ที่หน้ารายการกิจกรรมต้องใช้ — เรียกทุกครั้งที่ดึงเป็นลิสต์
     * เพื่อไม่ให้เกิด N+1 ตอนวาดคอลัมน์โปรแกรม/พื้นที่/วิทยากร
     * `registered` ไม่มีคอลัมน์เก็บ จึงใช้ withCount แทน (ดูส่วนที่ 3 ของเอกสาร schema)
     */
    public function scopeForList(Builder $query): Builder
    {
        return $query->with(['program:id,name', 'format:id,name', 'areas:id,name', 'instructors:id,name'])
            ->withCount('registrations');
    }

    /** กิจกรรมที่ผู้ใช้ทั่วไปเห็นได้ — เผยแพร่แล้วและอยู่ในช่วงเวลาที่กำหนด */
    public function scopePublished(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('publish_start_at')->orWhere('publish_start_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('publish_end_at')->orWhere('publish_end_at', '>=', $now));
    }

    /** ที่นั่งคงเหลือ — ต้องเรียกผ่าน scopeForList หรือ loadCount ก่อน ไม่งั้นจะยิง query ต่อแถว */
    public function seatsLeft(): int
    {
        return max(0, $this->capacity - ($this->registrations_count ?? $this->registrations()->count()));
    }
}
