<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * รายการตัวเลือกแบนทั้งหมดอยู่ตารางเดียว แยกด้วย option_group (ปม F.3)
 * group ที่ใช้: age_range · occupation · source_channel · interest · cohort_source · contact_channel · note_kind · purchase_channel
 */
class Option extends Model
{
    protected $table = 'mst_options';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'bool'];

    public function getNameAttribute(): string
    {
        return $this->label;
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('option_group', $group)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
