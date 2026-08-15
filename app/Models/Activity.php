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

    /** อีเวนท์กับกิจกรรมอยู่ตารางเดียวกัน แยกด้วยคอลัมน์ type */
    public const TYPE_EVENT = 'อีเว้นท์';

    public const TYPE_ACTIVITY = 'กิจกรรม';

    /** ข่าวสาร/ประชาสัมพันธ์ — แสดงเฉพาะภาพ ชื่อเรื่อง คำอธิบาย ไม่มีราคา/ลงทะเบียน */
    public const TYPE_NEWS = 'ข่าวสาร';

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
        'survey_start_at' => 'datetime',
        'survey_end_at' => 'datetime',
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
        'registration_start_at' => 'datetime',
        'registration_end_at' => 'datetime',
    ];

    /** อีเวนท์ที่กิจกรรมนี้อยู่ภายใต้ — ว่างได้ แปลว่าเป็นกิจกรรมเดี่ยว */
    public function parentEvent(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'parent_event_id');
    }

    /** กิจกรรมที่อยู่ในอีเวนท์นี้ */
    public function childActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'parent_event_id');
    }

    public function isEvent(): bool
    {
        return $this->type === self::TYPE_EVENT;
    }

    public function isNews(): bool
    {
        return $this->type === self::TYPE_NEWS;
    }

    /** อีเวนท์ที่เลือกเป็นแม่ได้ — ต้องเป็นอีเวนท์ และไม่ใช่ตัวเอง */
    public function scopeSelectableEvents(Builder $query, ?int $excludeId = null): Builder
    {
        return $query->where('type', self::TYPE_EVENT)
            ->when($excludeId, fn (Builder $q) => $q->whereKeyNot($excludeId))
            ->orderBy('name');
    }

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

    /* คนที่แก้ล่าสุด — ตารางรายการแสดงคู่กับวันเวลา จะได้รู้ว่าต้องไปถามใคร */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    /** ข้อมูลที่อนุญาตให้หน้าเว็บไซต์สาธารณะแสดง พร้อม relation ที่หน้า list/detail ใช้ */
    public function scopeForPublicListing(Builder $query): Builder
    {
        return $query->published()
            ->where('visibility', 'สาธารณะ')
            ->where('public_sort_order', '>', 0)
            ->with([
                'format:id,name,icon',
                'rounds:id,activity_id,round_date,time_start,time_end,location',
                'instructors:id,name',
            ])
            ->withCount('registrations');
    }

    /** ที่นั่งคงเหลือ — ต้องเรียกผ่าน scopeForList หรือ loadCount ก่อน ไม่งั้นจะยิง query ต่อแถว */
    public function seatsLeft(): int
    {
        return max(0, $this->capacity - ($this->registrations_count ?? $this->registrations()->count()));
    }

    /** เปิดปุ่มลงทะเบียนเฉพาะช่วงที่กำหนด และต้องยังมีที่นั่งถ้าระบุจำนวนรับไว้ */
    public function acceptsRegistration(): bool
    {
        $now = now();

        return $this->requires_registration
            && $this->status !== self::STATUS_CANCELLED
            && (! $this->registration_start_at || $this->registration_start_at->lte($now))
            && (! $this->registration_end_at || $this->registration_end_at->gte($now))
            && ($this->capacity === 0 || $this->seatsLeft() > 0);
    }

    /** เปิดให้ผู้ลงทะเบียนยืนยันตัวตนเฉพาะช่วง Check-in ที่กิจกรรมกำหนด */
    public function acceptsCheckin(): bool
    {
        $now = now();

        return $this->requires_checkin
            && $this->is_published
            && $this->status !== self::STATUS_CANCELLED
            && (! $this->checkin_start_at || $this->checkin_start_at->lte($now))
            && (! $this->checkin_end_at || $this->checkin_end_at->gte($now));
    }

    /** แบบประเมินหลังจบเปิดตามช่วงเวลาที่กิจกรรมกำหนด */
    public function acceptsPostSurvey(): bool
    {
        $now = now();

        return $this->has_post_survey
            && $this->is_published
            && $this->status !== self::STATUS_CANCELLED
            && (! $this->survey_start_at || $this->survey_start_at->lte($now))
            && (! $this->survey_end_at || $this->survey_end_at->gte($now));
    }
}
