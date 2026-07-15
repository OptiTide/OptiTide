<?php

namespace App\Models;

use App\Core\Model;

class Service extends Model
{
    protected static string $table = 'services';

    public const BILLING_ONE_OFF = 'one_off';
    public const BILLING_RECURRING = 'recurring';

    public const INTERVAL_MONTHLY = 'monthly';
    public const INTERVAL_QUARTERLY = 'quarterly';
    public const INTERVAL_YEARLY = 'yearly';

    public const INTERVALS = [
        self::INTERVAL_MONTHLY   => 'Monthly',
        self::INTERVAL_QUARTERLY => 'Quarterly',
        self::INTERVAL_YEARLY    => 'Yearly',
    ];

    public static function active(): array
    {
        return static::query()->where('active', 1)->orderBy('name')->get();
    }

    public static function intervalMonths(?string $interval): int
    {
        return match ($interval) {
            self::INTERVAL_YEARLY    => 12,
            self::INTERVAL_QUARTERLY => 3,
            default                  => 1,
        };
    }
}
