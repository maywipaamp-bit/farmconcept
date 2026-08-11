<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'mst_courses';

    protected $guarded = ['id'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'mst_instructor_course');
    }

    /** ใช้ตรวจก่อนลบหลักสูตร — หลักสูตรที่มีกิจกรรมอ้างอิงอยู่ลบทิ้งไม่ได้ */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
