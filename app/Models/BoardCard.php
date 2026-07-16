<?php

namespace App\Models;

use App\Core\Model;

class BoardCard extends Model
{
    protected static string $table = 'board_cards';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW    => 'Low',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH   => 'High',
        self::PRIORITY_URGENT => 'Urgent',
    ];

    /** Bootstrap badge class per priority. */
    public const PRIORITY_BADGES = [
        self::PRIORITY_LOW    => 'text-bg-secondary',
        self::PRIORITY_NORMAL => 'badge-soft',
        self::PRIORITY_HIGH   => 'text-bg-warning',
        self::PRIORITY_URGENT => 'text-bg-danger',
    ];

    /** Next position at the bottom of a column. */
    public static function nextPosition(int|string $columnId): int
    {
        return count(static::query()->where('column_id', $columnId)->get());
    }

    /**
     * The client-facing card query: their own cards, and only the ones left
     * client-visible. Internal delivery detail never reaches a client, so the
     * client_visible filter lives HERE — a template that forgets to check is
     * then still safe, because the row was never loaded.
     */
    public static function forClient(int|string $clientId): array
    {
        return static::query()
            ->where('client_id', $clientId)
            ->where('client_visible', 1)
            ->orderBy('board_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public static function priorityLabel(?string $priority): string
    {
        return self::PRIORITIES[$priority] ?? self::PRIORITIES[self::PRIORITY_NORMAL];
    }

    public static function priorityBadge(?string $priority): string
    {
        return self::PRIORITY_BADGES[$priority] ?? self::PRIORITY_BADGES[self::PRIORITY_NORMAL];
    }
}
