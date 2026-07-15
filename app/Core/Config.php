<?php

namespace App\Core;

final class Config
{
    /** @var array<string,mixed> */
    protected static array $items = [];

    public static function load(string $dir): void
    {
        foreach (glob($dir . '/*.php') as $file) {
            static::$items[basename($file, '.php')] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::$items;

        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &static::$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
            } else {
                $ref[$segment] ??= [];
                $ref = &$ref[$segment];
            }
        }
    }
}
