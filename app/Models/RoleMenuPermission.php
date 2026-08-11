<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleMenuPermission extends Model
{
    protected $table = 'usr_role_menu_permissions';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['is_allowed' => 'bool'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
