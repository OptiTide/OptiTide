<?php

namespace App\Models;

use App\Core\Model;

class HostingAccount extends Model
{
    protected static string $table = 'hosting_accounts';

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('domain')->get();
    }
}
