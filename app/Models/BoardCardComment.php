<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * A comment on a delivery card. Comments are EVENTS — newest first, so the
 * latest word on a card is what you land on.
 *
 * Internal notes are filtered in the QUERY, never in a template: the client
 * portal must never receive an internal row it merely declines to print.
 */
class BoardCardComment extends Model
{
    protected static string $table = 'board_card_comments';

    public static function forCard(int|string $cardId, bool $includeInternal = true): array
    {
        return static::forCards([$cardId], $includeInternal)[$cardId] ?? [];
    }

    /**
     * Comments for many cards at once, grouped by card_id, so a board or the
     * client project list doesn't fire a query per card.
     *
     * @param  array<int,int|string>  $cardIds
     * @return array<int|string,array<int,array<string,mixed>>>
     */
    public static function forCards(array $cardIds, bool $includeInternal = true): array
    {
        if ($cardIds === []) {
            return [];
        }

        $query = static::query()->whereIn('card_id', array_values($cardIds));

        if (! $includeInternal) {
            $query->where('is_internal', 0);
        }

        $grouped = [];
        foreach ($query->orderBy('id', 'desc')->get() as $row) {
            $grouped[$row['card_id']][] = $row;
        }

        return $grouped;
    }

    /**
     * Comment count per card for a board face, without loading every body.
     *
     * @param  array<int,int|string>  $cardIds
     * @return array<int|string,int>
     */
    public static function countsForCards(array $cardIds, bool $includeInternal = true): array
    {
        if ($cardIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($cardIds), '?'));
        $sql = 'SELECT card_id, COUNT(*) AS total FROM board_card_comments'
            . " WHERE card_id IN ($placeholders)";

        if (! $includeInternal) {
            $sql .= ' AND is_internal = 0';
        }

        $rows = Database::instance()->select($sql . ' GROUP BY card_id', array_values($cardIds));

        return array_map('intval', array_column($rows, 'total', 'card_id'));
    }
}
