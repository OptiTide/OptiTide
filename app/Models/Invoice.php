<?php

namespace App\Models;

use App\Core\Model;
use App\Support\Money;

class Invoice extends Model
{
    protected static string $table = 'invoices';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT   => 'Draft',
        self::STATUS_SENT    => 'Sent',
        self::STATUS_PAID    => 'Paid',
        self::STATUS_OVERDUE => 'Overdue',
        self::STATUS_VOID    => 'Void',
    ];

    /** Bootstrap contextual colour for each status badge. */
    public const STATUS_COLORS = [
        self::STATUS_DRAFT   => 'secondary',
        self::STATUS_SENT    => 'info',
        self::STATUS_PAID    => 'success',
        self::STATUS_OVERDUE => 'danger',
        self::STATUS_VOID    => 'dark',
    ];

    public static function total(array $invoice): Money
    {
        return new Money((int) $invoice['total_cents'], $invoice['currency'] ?? 'AUD');
    }

    public static function amountPaid(array $invoice): Money
    {
        return new Money((int) ($invoice['amount_paid_cents'] ?? 0), $invoice['currency'] ?? 'AUD');
    }

    /** Balance due = total − amount paid (the "Balance due" convention). */
    public static function balance(array $invoice): Money
    {
        return static::total($invoice)->subtract(static::amountPaid($invoice));
    }

    public static function isPaid(array $invoice): bool
    {
        return $invoice['status'] === self::STATUS_PAID;
    }

    public static function isPayable(array $invoice): bool
    {
        return in_array($invoice['status'], [self::STATUS_SENT, self::STATUS_OVERDUE], true);
    }

    public static function items(int|string $invoiceId): array
    {
        return InvoiceItem::query()->where('invoice_id', $invoiceId)->orderBy('id')->get();
    }
}
