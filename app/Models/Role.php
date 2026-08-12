<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'usr_roles';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'bool'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usr_role_user');
    }

    public function menuPermissions(): HasMany
    {
        return $this->hasMany(RoleMenuPermission::class);
    }

    /* คนที่แก้ล่าสุด — ตารางรายการแสดงคู่กับวันเวลา จะได้รู้ว่าต้องไปถามใคร */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** ใช้ relation ที่โหลดมาแล้ว ไม่ยิง query ใหม่ต่อการเรียกหนึ่งครั้ง */
    public function allowsMenu(string $menuKey): bool
    {
        return (bool) $this->menuPermissions->firstWhere('menu_key', $menuKey)?->is_allowed;
    }
}
