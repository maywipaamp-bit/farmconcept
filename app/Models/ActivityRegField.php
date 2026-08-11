<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ชั้นที่ 2 ของแบบลงทะเบียน — กิจกรรมเลือกได้แค่ "ถามหรือไม่" และ "บังคับหรือไม่"
 * ตัวเลือกของแต่ละฟิลด์มาจาก master กลาง กิจกรรมตั้งชื่อตัวเลือกเองไม่ได้
 * ไม่งั้นรายงานรวมข้ามกิจกรรมไม่ได้ (ดู docs/database-schema-proposal.md ส่วนที่ 8)
 */
class ActivityRegField extends Model
{
    protected $table = 'act_activity_reg_fields';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['is_enabled' => 'bool', 'is_required' => 'bool'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
