<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * คำตอบรายข้อ ใช้ร่วมกันทั้งแบบประเมินความพึงพอใจและแบบติดตามสุขภาพ
 * response_type เก็บเป็น 'satisfaction' / 'survey' ตาม morph map ใน AppServiceProvider
 * ไม่ใช้ชื่อคลาสเต็มเป็นค่าในฐาน เพื่อให้ย้าย namespace ได้โดยข้อมูลไม่พัง
 */
class Answer extends Model
{
    protected $table = 'evl_answers';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function response(): MorphTo
    {
        return $this->morphTo();
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
