<?php

namespace App\Models;

use App\Core\Model;

class Client extends Model
{
    protected static string $table = 'clients';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE    => 'Active',
        self::STATUS_SUSPENDED => 'Suspended',
        self::STATUS_ARCHIVED  => 'Archived',
    ];

    public static function active(): array
    {
        return static::query()->where('status', self::STATUS_ACTIVE)->orderBy('business_name')->get();
    }

    public static function fullAddress(array $client): string
    {
        return trim(implode(', ', array_filter([
            $client['address_line1'] ?? null,
            $client['address_locality'] ?? null,
            trim(($client['address_region'] ?? '') . ' ' . ($client['address_postcode'] ?? '')),
            $client['address_country'] ?? null,
        ])), ', ');
    }
}
