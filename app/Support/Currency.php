<?php

namespace App\Support;

use App\Core\Session;

/**
 * Storefront display-currency helper. Base prices are AUD minor units; this
 * converts them to whatever currency the visitor has chosen (persisted in the
 * session). AUD is always the settlement currency — this only affects display.
 */
final class Currency
{
    private const SESSION_KEY = 'display_currency';

    /** @return string[] */
    public static function supported(): array
    {
        return (array) config('currencies.supported', ['AUD']);
    }

    public static function base(): string
    {
        return strtoupper((string) config('currencies.base', 'AUD'));
    }

    public static function default(): string
    {
        $d = strtoupper((string) config('currencies.default', 'AUD'));

        return self::isSupported($d) ? $d : self::base();
    }

    public static function isSupported(string $code): bool
    {
        return in_array(strtoupper(trim($code)), self::supported(), true);
    }

    /** The currency the visitor is currently viewing prices in. */
    public static function current(): string
    {
        $code = strtoupper((string) Session::get(self::SESSION_KEY, self::default()));

        return self::isSupported($code) ? $code : self::default();
    }

    public static function set(string $code): bool
    {
        $code = strtoupper(trim($code));
        if (! self::isSupported($code)) {
            return false;
        }
        Session::put(self::SESSION_KEY, $code);

        return true;
    }

    public static function rate(string $code): float
    {
        $rates = (array) config('currencies.rates', []);

        return (float) ($rates[strtoupper($code)] ?? 1.0);
    }

    public static function symbol(?string $code = null): string
    {
        $code = strtoupper($code ?: self::current());
        $symbols = (array) config('currencies.symbols', []);

        return (string) ($symbols[$code] ?? '$');
    }

    /** Is the visitor viewing a non-settlement currency (so we should note it)? */
    public static function isConverted(): bool
    {
        return self::current() !== self::base();
    }

    /** Convert a base-currency (AUD) minor-unit amount into the current display currency. */
    public static function fromBase(int $baseMinorUnits): Money
    {
        $code = self::current();
        $converted = (int) round($baseMinorUnits * self::rate($code));

        return new Money($converted, $code);
    }

    /** Formatted price string for the catalogue, e.g. "US$726.00" or "A$1,100.00". */
    public static function display(int $baseMinorUnits, bool $withCode = false): string
    {
        $money = self::fromBase($baseMinorUnits);
        $text = self::symbol($money->currency) . number_format($money->minorUnits / 100, 2);

        return $withCode ? $text . ' ' . $money->currency : $text;
    }
}
