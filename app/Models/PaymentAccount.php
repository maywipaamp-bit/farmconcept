<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAccount extends Model
{
    protected $table = 'mst_payment_accounts';

    protected $guarded = ['id', 'active_slot'];

    protected $casts = ['is_active' => 'bool'];

    /** ชื่อสำหรับ audit log ของ MasterDataController */
    public function getNameAttribute(): string
    {
        return trim($this->account_name.' '.$this->account_number);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
