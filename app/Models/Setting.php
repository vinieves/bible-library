<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting.{$key}");
    }

    public static function getEncrypted(string $key, mixed $default = null): mixed
    {
        $value = static::get($key);

        if (blank($value)) {
            return $default;
        }

        try {
            return decrypt($value);
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function setEncrypted(string $key, mixed $value): void
    {
        if (blank($value)) {
            static::set($key, '');

            return;
        }

        static::set($key, encrypt($value));
    }

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget("setting.{$setting->key}"));
        static::deleted(fn (Setting $setting) => Cache::forget("setting.{$setting->key}"));
    }
}
