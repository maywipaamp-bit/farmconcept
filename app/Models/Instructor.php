<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructor extends Model
{
    protected $table = 'mst_instructors';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'bool',
        'search_tags' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function expertises(): HasMany
    {
        return $this->hasMany(InstructorExpertise::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'mst_instructor_course');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'act_activity_instructor');
    }

    /* คนที่แก้ล่าสุด — ตารางรายการแสดงคู่กับวันเวลา จะได้รู้ว่าต้องไปถามใคร */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
