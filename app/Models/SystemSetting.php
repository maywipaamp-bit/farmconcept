<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'sys_settings';

    protected $guarded = ['id'];

    public static function value(string $key, ?string $default = null): ?string
    {
        return static::query()->where('setting_key', $key)->value('setting_value') ?? $default;
    }
}
