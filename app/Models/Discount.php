<?php

namespace App\Models;

use App\Core\Model;
use App\Support\Money;

/**
 * A discount: a code a client types, or an automatic sale.
 *
 * Percent values are basis points (2000 = 20%), matching the house convention
 * used by GST and affiliate commission. Fixed values are integer minor units
 * (cents), like every other money column.
 *
 * All the eligibility rules live in App\Services\Billing\DiscountService — this
 * model is only shape + presentation.
 */
class Discount extends Model
{
    protected static string $table = 'discounts';

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENT => 'Percentage off',
        self::TYPE_FIXED   => 'Fixed amount off',
    ];

    public const SCOPE_ALL = 'all';
    public const SCOPE_CATEGORY = 'category';
    public const SCOPE_SERVICE = 'service';

    public const SCOPES = [
        self::SCOPE_ALL      => 'Everything',
        self::SCOPE_CATEGORY => 'One service line',
        self::SCOPE_SERVICE  => 'One package',
    ];

    /** Codes are stored and compared upper-case so "spring20" == "SPRING20". */
    public static function normaliseCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : $code;
    }

    public static function findByCode(string $code): ?array
    {
        $code = self::normaliseCode($code);

        return $code === null ? null : static::firstWhere('code', $code);
    }

    /** Newest first for the admin list. */
    public static function ordered(): array
    {
        return static::query()->orderBy('id', 'desc')->get();
    }

    /**
     * Live automatic sales — active, in-window, no code required. These are the
     * ones the public catalogue advertises.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function liveSales(): array
    {
        $rows = static::query()->where('is_sale', 1)->where('active', 1)->get();

        return array_values(array_filter($rows, fn ($d) => self::inWindow($d) && self::hasUsesLeft($d)));
    }

    /** True when now is inside [starts_at, ends_at] — dates are inclusive. */
    public static function inWindow(array $discount): bool
    {
        $today = today();
        $from = substr((string) ($discount['starts_at'] ?? ''), 0, 10);
        $to = substr((string) ($discount['ends_at'] ?? ''), 0, 10);

        if ($from !== '' && $today < $from) {
            return false;
        }
        // Inclusive: a sale "ending 31 Aug" runs all day on the 31st.
        if ($to !== '' && $today > $to) {
            return false;
        }

        return true;
    }

    public static function hasUsesLeft(array $discount): bool
    {
        $max = $discount['max_uses'] ?? null;

        return $max === null || $max === '' || (int) $discount['uses'] < (int) $max;
    }

    /** "20% off" / "$50 off" — the label shown to clients. */
    public static function label(array $discount): string
    {
        if ($discount['type'] === self::TYPE_PERCENT) {
            return self::percentLabel((int) $discount['value']) . ' off';
        }

        return (new Money((int) $discount['value'], $discount['currency'] ?: 'AUD'))->format() . ' off';
    }

    /** 2000 bps -> "20%", 1250 -> "12.5%" */
    public static function percentLabel(int $basisPoints): string
    {
        return rtrim(rtrim(number_format($basisPoints / 100, 2), '0'), '.') . '%';
    }

    /** Human status for the admin list — why a discount isn't currently working. */
    public static function statusLabel(array $discount): array
    {
        if (! $discount['active']) {
            return ['Off', 'text-bg-secondary'];
        }
        if (! self::inWindow($discount)) {
            $today = today();
            $from = substr((string) ($discount['starts_at'] ?? ''), 0, 10);

            return $from !== '' && $today < $from
                ? ['Scheduled', 'text-bg-info']
                : ['Expired', 'text-bg-dark'];
        }
        if (! self::hasUsesLeft($discount)) {
            return ['Used up', 'text-bg-dark'];
        }

        return $discount['is_sale'] ? ['Live sale', 'text-bg-success'] : ['Live', 'text-bg-success'];
    }
}
