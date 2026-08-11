<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $table = 'evl_forms';

    protected $guarded = ['id'];

    protected $casts = ['is_anonymous' => 'bool'];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
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

    /** เฉพาะชุดที่เผยแพร่แล้ว — ฉบับร่างยังแก้อยู่ ผูกกับกิจกรรมไปก่อนไม่ได้ */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'เผยแพร่แล้ว');
    }
}
