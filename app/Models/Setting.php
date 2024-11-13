<?php

namespace App\Models;

use ElipZis\Cacheable\Models\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use Cacheable;

    protected static $cached = [];

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    protected $primaryKey = 'key';

    protected static function booted()
    {
        static::created(function (self $setting) {
            static::$cached[$setting->key] = $setting;
        });

        static::updated(function (self $setting) {
            static::$cached[$setting->key] = $setting;
        });

        static::deleted(function (self $setting) {
            unset(static::$cached[$setting->key]);
        });

        static::saved(function (self $setting) {
            static::$cached[$setting->key] = $setting;
        });
    }

    public static function rememberCache($key, $defaultValue)
    {
        if (! isset(static::$cached[$key])) {
            static::$cached[$key] = static::where('key', $key)->first();
            if (! static::$cached[$key]) {
                static::$cached[$key] = new static([
                    'key' => $key,
                    'value' => $defaultValue,
                ]);
            }
        }

        return static::$cached[$key];
    }
}
