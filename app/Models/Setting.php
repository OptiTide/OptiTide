<?php

namespace App\Models;

use App\Core\Model;

/** Simple key/value store for editable-in-app settings. */
class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::firstWhere('key', $key);

        return $row['value'] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        if (static::firstWhere('key', $key)) {
            static::query()->where('key', $key)->update(['value' => $value, 'updated_at' => now()]);
        } else {
            static::create(['key' => $key, 'value' => $value]);
        }
    }

    /** @return array<string,string> */
    public static function map(): array
    {
        $map = [];
        foreach (static::all() as $row) {
            $map[$row['key']] = $row['value'];
        }

        return $map;
    }
}
