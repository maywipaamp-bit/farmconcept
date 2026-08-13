<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $table = 'evl_form_fields';

    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'bool',
        'is_required' => 'bool',
        'sort_order' => 'int',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
