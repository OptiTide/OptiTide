<?php

namespace App\Models;

use App\Core\Model;

class BoardColumn extends Model
{
    protected static string $table = 'board_columns';

    public static function nextPosition(int|string $boardId): int
    {
        $max = static::query()->where('board_id', $boardId)->get();

        return count($max);
    }
}
