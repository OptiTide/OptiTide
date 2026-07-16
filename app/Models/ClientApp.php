<?php

namespace App\Models;

use App\Core\Model;
use App\Support\Money;

/**
 * An app we host for a client (Coolify-deployed), optionally billed.
 *
 * An app never bills on its own. Recurring apps point at the client_services
 * engagement that RecurringBiller already invoices — the engagement owns the
 * price, interval and schedule, so there is exactly one source of truth and no
 * way to double-bill. One-off apps carry an agreed price that staff raise on an
 * invoice by hand, the same as a one_off engagement. See migration 0036.
 */
class ClientApp extends Model
{
    protected static string $table = 'client_apps';

    public const BILLING_NONE = 'none';
    public const BILLING_ONE_OFF = 'one_off';
    public const BILLING_RECURRING = 'recurring';

    public const BILLING_TYPES = [
        self::BILLING_NONE      => 'Not Billed',
        self::BILLING_ONE_OFF   => 'One-off Charge',
        self::BILLING_RECURRING => 'Part of an Engagement',
    ];

    /** Suffix per engagement interval, for a "$50.00/mo" style label. */
    protected const INTERVAL_SUFFIX = [
        Service::INTERVAL_MONTHLY   => '/mo',
        Service::INTERVAL_QUARTERLY => '/qtr',
        Service::INTERVAL_YEARLY    => '/yr',
    ];

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('name')->get();
    }

    /**
     * Is anything actually going to charge for this app? Strict on purpose: a
     * recurring app with no engagement is billed by nothing, and a one-off with
     * no price is not a charge. Both must read as "not billed" rather than
     * showing the client a figure nobody will ever invoice.
     */
    public static function isBilled(array $app): bool
    {
        return match ($app['billing_type'] ?? self::BILLING_NONE) {
            self::BILLING_RECURRING => ! empty($app['engagement_id']),
            self::BILLING_ONE_OFF   => (int) ($app['price_cents'] ?? 0) > 0,
            default                 => false,
        };
    }

    /**
     * What this app costs, e.g. "$50.00/mo" or "$1,500.00 once". Empty string
     * when nothing bills it — callers render nothing at all rather than "$0.00".
     *
     * A recurring app's price lives on the engagement, so pass that row in;
     * without it we return '' rather than guess, since a stale mirrored price on
     * the app row would quietly contradict the invoice the client receives.
     */
    public static function priceLabel(array $app, ?array $engagement = null): string
    {
        if (! static::isBilled($app)) {
            return '';
        }

        if (($app['billing_type'] ?? null) === self::BILLING_RECURRING) {
            if ($engagement === null) {
                return '';
            }

            $money = new Money((int) $engagement['price_cents'], $engagement['currency'] ?: 'AUD');

            return $money->format() . (static::INTERVAL_SUFFIX[$engagement['interval'] ?? ''] ?? '');
        }

        return (new Money((int) $app['price_cents'], $app['currency'] ?: 'AUD'))->format() . ' once';
    }

    /**
     * The engagements the given apps link to, keyed by id, so a caller can
     * resolve prices without a query per app.
     *
     * @param  array<int,array>  $apps
     * @return array<int|string,array>
     */
    public static function engagementMap(array $apps): array
    {
        $ids = array_values(array_unique(array_filter(array_column($apps, 'engagement_id'))));
        if ($ids === []) {
            return [];
        }

        $map = [];
        foreach (ClientService::query()->whereIn('id', $ids)->get() as $engagement) {
            $map[$engagement['id']] = $engagement;
        }

        return $map;
    }
}
