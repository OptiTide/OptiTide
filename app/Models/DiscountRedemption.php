<?php

namespace App\Models;

use App\Core\Model;

/**
 * One recorded use of a discount. Backs the per-client usage limit and gives a
 * real trail of what was given away, to whom, and on which invoice.
 */
class DiscountRedemption extends Model
{
    protected static string $table = 'discount_redemptions';

    /** @return array<int,array<string,mixed>> newest first */
    public static function forDiscount(int|string $discountId): array
    {
        return static::query()
            ->where('discount_id', $discountId)
            ->orderBy('id', 'desc')
            ->get();
    }

    /** Total given away under a discount, in cents. */
    public static function totalGiven(int|string $discountId): int
    {
        return (int) static::query()->where('discount_id', $discountId)->sum('amount_cents');
    }
}
