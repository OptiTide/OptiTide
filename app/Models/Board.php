<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Board extends Model
{
    protected static string $table = 'boards';

    public static function ordered(): array
    {
        return static::query()->orderBy('position')->orderBy('id')->get();
    }

    public static function byKey(string $key): ?array
    {
        return static::firstWhere('key', $key);
    }

    public static function columns(int|string $boardId): array
    {
        return BoardColumn::query()->where('board_id', $boardId)->orderBy('position')->orderBy('id')->get();
    }

    public static function cards(int|string $boardId): array
    {
        return BoardCard::query()->where('board_id', $boardId)->orderBy('position')->orderBy('id')->get();
    }

    /**
     * Checklist progress for one card.
     *
     * @return array{done:int,total:int,pct:int}
     */
    public static function checklistProgress(int|string $cardId): array
    {
        return static::checklistProgressMap([$cardId])[$cardId] ?? ['done' => 0, 'total' => 0, 'pct' => 0];
    }

    /**
     * Progress for many cards in one aggregate, so rendering a board or the
     * client project list doesn't fire a query per card. Cards with no checklist
     * are absent from the map — callers fall back to the zero shape above.
     *
     * @param  array<int,int|string>  $cardIds
     * @return array<int|string,array{done:int,total:int,pct:int}>
     */
    public static function checklistProgressMap(array $cardIds): array
    {
        if ($cardIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($cardIds), '?'));

        $rows = Database::instance()->select(
            'SELECT card_id, COUNT(*) AS total,'
            . ' COALESCE(SUM(CASE WHEN done = 1 THEN 1 ELSE 0 END), 0) AS done'
            . " FROM board_card_checklist WHERE card_id IN ($placeholders) GROUP BY card_id",
            array_values($cardIds)
        );

        $map = [];
        foreach ($rows as $row) {
            $total = (int) $row['total'];
            $done = (int) $row['done'];
            $map[$row['card_id']] = [
                'done'  => $done,
                'total' => $total,
                'pct'   => $total > 0 ? (int) round($done / $total * 100) : 0,
            ];
        }

        return $map;
    }
}
