<?php

namespace App\Core;

/**
 * Small cache with three drivers: file (default, persistent), redis, and array
 * (per-request). TTL is in seconds; 0 means "forever". increment() gives a
 * fixed-window counter used by RateLimiter.
 */
final class Cache
{
    /** @var array<string,array{expires:int,value:mixed}> */
    protected static array $memory = [];

    public static function driver(): string
    {
        $driver = config('cache.driver', 'file');

        // Fail safe to file if redis is configured but unreachable.
        if ($driver === 'redis' && ! Redis::available()) {
            return 'file';
        }

        return $driver;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return match (static::driver()) {
            'redis' => static::redisGet($key, $default),
            'array' => static::arrayGet($key, $default),
            default => static::fileGet($key, $default),
        };
    }

    public static function put(string $key, mixed $value, int $ttl = 0): void
    {
        match (static::driver()) {
            'redis' => static::redisPut($key, $value, $ttl),
            'array' => static::arrayPut($key, $value, $ttl),
            default => static::filePut($key, $value, $ttl),
        };
    }

    public static function has(string $key): bool
    {
        return static::get($key, null) !== null;
    }

    public static function forget(string $key): void
    {
        match (static::driver()) {
            'redis' => (function () use ($key) { Redis::client()->del([$key]); })(),
            'array' => (function () use ($key) { unset(static::$memory[$key]); })(),
            default => (function () use ($key) { @unlink(static::file($key)); })(),
        };
    }

    /** Fixed-window increment; sets the TTL on the first hit only. Returns the new count. */
    public static function increment(string $key, int $by = 1, int $ttl = 0): int
    {
        if (static::driver() === 'redis') {
            $client = Redis::client();
            $value = (int) $client->incrby($key, $by);
            if ($value === $by && $ttl > 0) {
                $client->expire($key, $ttl);
            }

            return $value;
        }

        // file / array: read-modify-write preserving the original expiry.
        $existing = static::rawEntry($key);
        $count = (int) ($existing['value'] ?? 0) + $by;
        $expires = $existing['expires'] ?? ($ttl > 0 ? time() + $ttl : 0);

        static::driver() === 'array'
            ? static::$memory[$key] = ['expires' => $expires, 'value' => $count]
            : @file_put_contents(static::file($key), serialize(['expires' => $expires, 'value' => $count]));

        return $count;
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = static::get($key, null);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        static::put($key, $value, $ttl);

        return $value;
    }

    // --- redis ---------------------------------------------------------------

    protected static function redisGet(string $key, mixed $default): mixed
    {
        $raw = Redis::client()->get($key);

        return $raw === null ? $default : unserialize($raw);
    }

    protected static function redisPut(string $key, mixed $value, int $ttl): void
    {
        $raw = serialize($value);
        $ttl > 0 ? Redis::client()->setex($key, $ttl, $raw) : Redis::client()->set($key, $raw);
    }

    // --- array ---------------------------------------------------------------

    protected static function arrayGet(string $key, mixed $default): mixed
    {
        $entry = static::$memory[$key] ?? null;
        if (! $entry || static::expired($entry)) {
            unset(static::$memory[$key]);

            return $default;
        }

        return $entry['value'];
    }

    protected static function arrayPut(string $key, mixed $value, int $ttl): void
    {
        static::$memory[$key] = ['expires' => $ttl > 0 ? time() + $ttl : 0, 'value' => $value];
    }

    // --- file ----------------------------------------------------------------

    protected static function fileGet(string $key, mixed $default): mixed
    {
        $entry = static::rawEntry($key);

        return $entry === null ? $default : $entry['value'];
    }

    protected static function filePut(string $key, mixed $value, int $ttl): void
    {
        @file_put_contents(static::file($key), serialize([
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value'   => $value,
        ]));
    }

    /** @return array{expires:int,value:mixed}|null a live (non-expired) entry */
    protected static function rawEntry(string $key): ?array
    {
        if (static::driver() === 'array') {
            $entry = static::$memory[$key] ?? null;
        } else {
            $path = static::file($key);
            if (! is_file($path)) {
                return null;
            }
            $entry = @unserialize((string) file_get_contents($path)) ?: null;
        }

        if (! is_array($entry) || static::expired($entry)) {
            static::forget($key);

            return null;
        }

        return $entry;
    }

    protected static function expired(array $entry): bool
    {
        return ! empty($entry['expires']) && $entry['expires'] < time();
    }

    protected static function file(string $key): string
    {
        $dir = base_path(config('cache.path', 'storage/framework/cache'));
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir . '/' . sha1(config('cache.prefix', 'cache:') . $key) . '.cache';
    }
}
