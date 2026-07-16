<?php

namespace App\Models;

use App\Core\Model;
use App\Support\Money;

class Quote extends Model
{
    protected static string $table = 'quotes';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_DRAFT    => 'Draft',
        self::STATUS_SENT     => 'Sent',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_EXPIRED  => 'Expired',
    ];

    /** Bootstrap contextual colour for each status badge. */
    public const STATUS_COLORS = [
        self::STATUS_DRAFT    => 'secondary',
        self::STATUS_SENT     => 'info',
        self::STATUS_ACCEPTED => 'success',
        self::STATUS_DECLINED => 'danger',
        self::STATUS_EXPIRED  => 'dark',
    ];

    public static function total(array $quote): Money
    {
        return new Money((int) $quote['total_cents'], $quote['currency'] ?? 'AUD');
    }

    public static function subtotal(array $quote): Money
    {
        return new Money((int) $quote['subtotal_cents'], $quote['currency'] ?? 'AUD');
    }

    public static function gst(array $quote): Money
    {
        return new Money((int) $quote['gst_cents'], $quote['currency'] ?? 'AUD');
    }

    public static function discount(array $quote): Money
    {
        return new Money((int) ($quote['discount_cents'] ?? 0), $quote['currency'] ?? 'AUD');
    }

    /**
     * True once the expiry DATE has passed. Compared date-only so a quote
     * expiring today is still acceptable for the whole of that day — the client
     * shouldn't lose the deal to a clock they can't see.
     */
    public static function hasExpired(array $quote): bool
    {
        $expires = (string) ($quote['expires_at'] ?? '');

        if ($expires === '') {
            return false;
        }

        return substr($expires, 0, 10) < today();
    }

    /** Only a live, sent quote can be accepted. */
    public static function isAcceptable(array $quote): bool
    {
        return $quote['status'] === self::STATUS_SENT && ! static::hasExpired($quote);
    }

    public static function isConverted(array $quote): bool
    {
        return ! empty($quote['converted_invoice_id']);
    }

    /**
     * The status to SHOW. A sent quote whose expiry has passed reads "Expired"
     * even before the row is stamped — the badge must never invite a client to
     * accept something isAcceptable() will refuse.
     */
    public static function displayStatus(array $quote): string
    {
        if ($quote['status'] === self::STATUS_SENT && static::hasExpired($quote)) {
            return self::STATUS_EXPIRED;
        }

        return $quote['status'];
    }

    /** @return array<int,array<string,mixed>> */
    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('id', 'desc')->get();
    }

    /**
     * Line items in id order — they're a sequence the client reads top to
     * bottom, not a stream of events.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function items(int|string $quoteId): array
    {
        return QuoteItem::query()->where('quote_id', $quoteId)->orderBy('id')->get();
    }
}
