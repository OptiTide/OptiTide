<?php

namespace App\Core;

/**
 * Persistent, server-side rate limiter backed by Cache (file or redis). Because
 * the counter lives server-side keyed by IP — not in the client's session — a
 * cookieless/rotating-session attacker cannot reset it.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return static::attempts($key) >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds = 60): int
    {
        return Cache::increment(static::key($key), 1, $decaySeconds);
    }

    public static function attempts(string $key): int
    {
        return (int) Cache::get(static::key($key), 0);
    }

    public static function clear(string $key): void
    {
        Cache::forget(static::key($key));
    }

    protected static function key(string $key): string
    {
        return 'ratelimit:' . sha1($key);
    }
}
