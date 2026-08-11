<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * คำตอบแบบติดตามสุขภาพ — ระบุตัวตนได้และต้องได้
 * ระบบต้องรู้ว่าใครยังไม่ตอบรอบไหนเพื่อไปตามตัว จึงต่างจากแบบประเมินความพึงพอใจ
 */
class SurveyResponse extends Model
{
    protected $table = 'evl_survey_responses';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function cohortRound(): BelongsTo
    {
        return $this->belongsTo(FollowUpRound::class, 'cohort_round_id');
    }

    public function answers(): MorphMany
    {
        return $this->morphMany(Answer::class, 'response');
    }
}
