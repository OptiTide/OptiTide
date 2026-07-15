<?php

namespace App\Models;

use App\Core\Model;

class ClientApp extends Model
{
    protected static string $table = 'client_apps';

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('name')->get();
    }
}
