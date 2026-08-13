<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    public const TYPE_REGISTRATION = 'registration';

    public const TYPE_POST_ACTIVITY = 'post_activity';

    public const TYPE_HEALTH_FOLLOW_UP = 'health_follow_up';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const TYPES = [
        self::TYPE_REGISTRATION,
        self::TYPE_POST_ACTIVITY,
        self::TYPE_HEALTH_FOLLOW_UP,
    ];

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $table = 'evl_forms';

    protected $guarded = ['id'];

    protected $casts = [
        'is_anonymous' => 'bool',
        'max_participants' => 'int',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'evl_form_activity')->withPivot('slot');
    }

    public function satisfactionResponses(): HasMany
    {
        return $this->hasMany(SatisfactionResponse::class);
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** เฉพาะชุดที่เปิดใช้งาน — ฉบับร่างยังแก้อยู่และนำไปผูกกับกิจกรรมไม่ได้ */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function hasResponses(): bool
    {
        if ($this->satisfactionResponses()->exists() || $this->surveyResponses()->exists()) {
            return true;
        }

        return Answer::query()
            ->where('response_type', 'registration')
            ->whereIn('question_id', $this->questions()->select('id'))
            ->exists();
    }
}
