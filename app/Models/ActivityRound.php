<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityRound extends Model
{
    protected $table = 'act_activity_rounds';

    protected $guarded = ['id'];

    protected $casts = ['round_date' => 'date'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
