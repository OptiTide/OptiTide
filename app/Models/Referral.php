<?php

namespace App\Models;

use App\Core\Model;

class Referral extends Model
{
    protected static string $table = 'referrals';

    public static function forReferrer(int|string $referrerId): array
    {
        return static::query()->where('referrer_id', $referrerId)->orderBy('id', 'desc')->get();
    }
}
