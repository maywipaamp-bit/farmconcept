<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * หน่วยงานที่ร่วมดำเนินงานในพื้นที่
 *
 * แยกเป็นตารางแทนการเก็บเป็นข้อความหรือ JSON ในแถวพื้นที่ เพราะต้องตอบคำถาม
 * "หน่วยงานนี้ร่วมกี่พื้นที่" ซึ่งเป็นการจัดกลุ่มข้ามแถว — JSON ทำได้แต่ต้องใช้
 * JSON_TABLE ซึ่งช้าและ index ไม่ได้ และไม่ได้กันชื่อที่พิมพ์ต่างกันอยู่ดี
 */
class PartnerOrg extends Model
{
    protected $table = 'mst_partner_orgs';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'bool'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'mst_area_partner_org');
    }
}
