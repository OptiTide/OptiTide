<?php

namespace App\Models;

use App\Core\Model;

class CreditTransaction extends Model
{
    protected static string $table = 'credit_transactions';

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('id', 'desc')->get();
    }
}
