<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'mst_areas';

    protected $guarded = ['id'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    /* ---------- ข้อมูลอ้างอิง ----------
       เก็บเป็น id ไม่ใช่ข้อความ เปลี่ยนชื่อตัวเลือกแล้วรายงานย้อนหลังเปลี่ยนตามทันที
       จังหวัดไม่เก็บซ้ำ — อ่านผ่าน district เอา ไม่งั้นแก้จังหวัดแต่ลืมแก้อำเภอจะขัดกันเอง */

    public function areaType(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'area_type_id');
    }

    public function areaGroup(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'area_group_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function partnerOrgs(): BelongsToMany
    {
        return $this->belongsToMany(PartnerOrg::class, 'mst_area_partner_org');
    }

    /* คนที่แก้ล่าสุด — ตารางรายการแสดงคู่กับวันเวลา จะได้รู้ว่าต้องไปถามใคร */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ---------- ความสัมพันธ์อื่น ---------- */

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

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
