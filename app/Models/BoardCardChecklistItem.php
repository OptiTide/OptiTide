<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * One tick-box on a delivery card. A checklist is a SEQUENCE, so every read is
 * ordered by position — never by id or insertion order.
 */
class BoardCardChecklistItem extends Model
{
    protected static string $table = 'board_card_checklist';

    public static function forCard(int|string $cardId): array
    {
        return static::query()->where('card_id', $cardId)->orderBy('position')->orderBy('id')->get();
    }

    /**
     * Bottom of the list. MAX(position) + 1 rather than a row count: deleting a
     * middle item leaves a count that collides with an existing position.
     */
    public static function nextPosition(int|string $cardId): int
    {
        $row = Database::instance()->selectOne(
            'SELECT COALESCE(MAX(position), -1) + 1 AS next FROM board_card_checklist WHERE card_id = ?',
            [$cardId]
        );

        return (int) ($row['next'] ?? 0);
    }
}
