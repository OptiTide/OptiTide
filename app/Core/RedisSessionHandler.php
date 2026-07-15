<?php

namespace App\Core;

use SessionHandlerInterface;

/** Stores PHP sessions in Redis with a sliding TTL. */
final class RedisSessionHandler implements SessionHandlerInterface
{
    protected int $ttl;
    protected string $prefix = 'session:';

    public function __construct()
    {
        $this->ttl = ((int) config('session.lifetime', 120)) * 60;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        $value = Redis::client()->get($this->prefix . $id);

        return $value === null ? '' : (string) $value;
    }

    public function write($id, $data): bool
    {
        Redis::client()->setex($this->prefix . $id, $this->ttl, $data);

        return true;
    }

    public function destroy($id): bool
    {
        Redis::client()->del([$this->prefix . $id]);

        return true;
    }

    public function gc($max_lifetime): int
    {
        // Redis expires keys itself via the TTL set in write().
        return 0;
    }
}
