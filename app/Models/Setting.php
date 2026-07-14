<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Simple cached key/value site settings. Values are opaque strings — for the
 * analytics IDs we ONLY ever store a format-validated ID (e.g. "G-ABC123"),
 * never a raw <script> tag, and re-validate at render (see the <x-analytics>
 * component). Never interpolate a Setting value into markup without validation.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(
            "setting:{$key}",
            fn () => static::query()->where('key', $key)->value('value'),
        ) ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}
