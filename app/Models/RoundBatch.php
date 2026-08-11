<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** รอบติดตามที่แอดมินเปิดจริง — เลือกช่วงวันครบกำหนดแล้วดึงคนที่ถึงกำหนดในช่วงนั้น */
class RoundBatch extends Model
{
    protected $table = 'evl_round_batches';

    protected $guarded = ['id'];

    protected $casts = ['due_from' => 'date', 'due_to' => 'date'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(RoundBatchMember::class, 'batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
