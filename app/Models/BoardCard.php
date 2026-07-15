<?php

namespace App\Models;

use App\Core\Model;

class BoardCard extends Model
{
    protected static string $table = 'board_cards';

    /** Next position at the bottom of a column. */
    public static function nextPosition(int|string $columnId): int
    {
        return count(static::query()->where('column_id', $columnId)->get());
    }
}
