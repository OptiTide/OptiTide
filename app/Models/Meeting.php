<?php

namespace App\Models;

use App\Core\Model;

class Meeting extends Model
{
    protected static string $table = 'meetings';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /** Upcoming (scheduled, future) meetings for a client. */
    public static function upcomingForClient(int|string $clientId): array
    {
        return static::query()
            ->where('client_id', $clientId)
            ->where('status', self::STATUS_SCHEDULED)
            ->where('meeting_at', '>=', date('Y-m-d 00:00:00'))
            ->orderBy('meeting_at', 'asc')->get();
    }

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('meeting_at', 'desc')->get();
    }

    public static function all(string $orderBy = null): array
    {
        return static::query()->orderBy('meeting_at', 'desc')->get();
    }
}
