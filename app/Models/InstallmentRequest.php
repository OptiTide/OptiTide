<?php

namespace App\Models;

use App\Core\Model;

class InstallmentRequest extends Model
{
    protected static string $table = 'installment_requests';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    public static function pending(): array
    {
        return static::query()->where('status', self::STATUS_PENDING)->orderBy('id', 'desc')->get();
    }
}
