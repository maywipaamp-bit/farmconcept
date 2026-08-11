<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $table = 'sys_activity_logs';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * withTrashed เพราะ log ส่วนใหญ่ที่ต้องย้อนดูคือ log ของสิ่งที่ถูกลบไปแล้ว
     * ถ้าไม่ใส่ subject จะ resolve ไม่ได้พอดีในกรณีที่ต้องใช้มากที่สุด
     */
    public function subject(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
