<?php

namespace App\Models;

use App\Core\Model;

class Commission extends Model
{
    protected static string $table = 'commissions';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';

    public const STATUSES = [
        self::STATUS_PENDING  => 'Pending',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_PAID     => 'Paid',
    ];

    public static function forReferrer(int|string $referrerId): array
    {
        return static::query()->where('referrer_id', $referrerId)->orderBy('id', 'desc')->get();
    }
}
