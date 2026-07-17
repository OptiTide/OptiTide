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
        $due = static::query()
            ->where('status', self::STATUS_ACTIVE)
            ->where('billing_type', Service::BILLING_RECURRING)
            ->whereNotNull('next_invoice_date')
            ->where('next_invoice_date', '<=', $date)
            ->get();

        // Stop at the end date. Filtered here rather than in SQL because ends_at is
        // NULL for every open-ended engagement, and a NULL comparison in SQL would
        // silently drop them all — which would quietly stop ALL recurring billing.
        // Without this the end date would be decorative: an engagement that finished
        // last year would keep issuing invoices forever.
        return array_values(array_filter(
            $due,
            fn (array $e) => empty($e['ends_at']) || (string) $e['ends_at'] >= $date
        ));
    }
}
