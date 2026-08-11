<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ตั้งชื่อ SystemNotification เพื่อไม่ชนกับ Illuminate\Notifications\Notification ของ Laravel */
class SystemNotification extends Model
{
    protected $table = 'sys_notifications';

    protected $guarded = ['id'];

    protected $casts = ['read_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
