<?php

namespace App\Core;

/**
 * Minimal .env loader. In containers (Coolify) real environment variables are
 * used directly — no .env file is shipped — so get() falls back to getenv().
 */
final class Env
{
    /** @var array<string,string> */
    protected static array $vars = [];

    public static function load(string $file): void
    {
        if (! is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip a trailing inline comment on unquoted values.
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'" && str_contains($value, ' #')) {
                $value = trim(substr($value, 0, strpos($value, ' #')));
            }

            // Strip matching surrounding quotes.
            $len = strlen($value);
            if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
                $value = substr($value, 1, -1);
            }

            static::$vars[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$vars)) {
            return static::cast(static::$vars[$key]);
        }

        $value = getenv($key);
        if ($value !== false) {
            return static::cast($value);
        }

        return $default;
    }

    public static function set(string $key, string $value): void
    {
        static::$vars[$key] = $value;
    }

    protected static function cast(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}
