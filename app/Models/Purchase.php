<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $table = 'ptp_purchases';

    protected $guarded = ['id'];

    protected $casts = ['order_date' => 'date', 'amount' => 'decimal:2'];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'channel_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /** เจ้าหน้าที่ที่คีย์รายการนี้ — หน้ารายละเอียดกลุ่มตัวอย่างต้องบอกได้ว่าใครบันทึก */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
