<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'mst_areas';

    protected $guarded = ['id'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'act_activity_area');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
