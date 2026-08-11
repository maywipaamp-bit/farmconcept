<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityFormat extends Model
{
    protected $table = 'mst_activity_formats';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'bool'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'format_id');
    }
}
