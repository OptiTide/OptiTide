<?php

namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    protected static string $table = 'payments';

    public const METHODS = [
        'payid'    => 'PayID / Bank transfer',
        'payoneer' => 'Payoneer',
        'manual'   => 'Manual / Other',
    ];

    public static function forInvoice(int|string $invoiceId): array
    {
        return static::query()->where('invoice_id', $invoiceId)->orderBy('paid_at', 'desc')->get();
    }

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('paid_at', 'desc')->get();
    }
}
