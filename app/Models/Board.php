<?php

namespace App\Models;

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
}
