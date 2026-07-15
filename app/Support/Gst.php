<?php

namespace App\Support;

/**
 * Australian GST — treated as INCLUSIVE. Prices already contain 10% GST, so
 * the GST component is backed out of the total (total * bps / (10000 + bps),
 * i.e. total / 11 at 10%), never added on top. If the business is not GST
 * registered the component is zero and the invoice omits the GST line.
 */
final class Gst
{
    public static function isRegistered(): bool
    {
        return (bool) config('company.gst_registered', true);
    }

    public static function basisPoints(): int
    {
        return (int) config('company.gst_basis_points', 1000);
    }

    public static function component(Money $inclusiveTotal): Money
    {
        if (! static::isRegistered()) {
            return Money::zero($inclusiveTotal->currency);
        }

        $bps = static::basisPoints();
        $cents = (int) round($inclusiveTotal->minorUnits * $bps / (10000 + $bps));

        return new Money($cents, $inclusiveTotal->currency);
    }

    public static function rateLabel(): string
    {
        $percent = static::basisPoints() / 100;

        return rtrim(rtrim(number_format($percent, 2), '0'), '.') . '%';
    }
}
