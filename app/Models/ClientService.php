<?php

namespace App\Models;

use App\Core\Model;

/**
 * An active engagement/subscription linking a client to a service. Recurring
 * rows (hosting, retainers) carry a next_invoice_date that the recurring biller
 * advances each cycle.
 */
class ClientService extends Model
{
    protected static string $table = 'client_services';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE    => 'Active',
        self::STATUS_PAUSED    => 'Paused',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('created_at', 'desc')->get();
    }

    /** Recurring engagements due to be invoiced on/before the given date. */
    public static function dueForInvoicing(string $date): array
    {
        return static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->where('billing_type', Service::BILLING_RECURRING)
            ->whereNotNull('next_invoice_date')
            ->where('next_invoice_date', '<=', $date)
            ->get();
    }
}
