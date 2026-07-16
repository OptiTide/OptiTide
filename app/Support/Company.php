<?php

namespace App\Support;

/**
 * Formatting helpers for the company identity set in admin Settings
 * (/admin/settings), so the same details render the same way everywhere —
 * site footer, contact page, invoice PDF and structured data.
 */
final class Company
{
    /**
     * The business address as a single line, e.g.
     * "12 Example St, Brisbane QLD 4000". Empty string when nothing is set —
     * callers decide their own fallback rather than printing a stray comma.
     */
    public static function addressLine(bool $withCountry = false): string
    {
        $a = config('company.address', []);

        $street = trim((string) ($a['line1'] ?? ''));
        $region = trim(implode(' ', array_filter([
            $a['locality'] ?? null,
            $a['region'] ?? null,
            $a['postcode'] ?? null,
        ])));

        $parts = array_filter([$street, $region]);
        if ($withCountry && ! empty($a['country'])) {
            $parts[] = $a['country'];
        }

        return implode(', ', $parts);
    }

    /** The address line, or a sensible default when none is configured. */
    public static function addressLineOr(string $fallback = 'Australia-wide'): string
    {
        return self::addressLine() ?: $fallback;
    }

    /** True when enough of an address is set to be worth showing. */
    public static function hasAddress(): bool
    {
        return self::addressLine() !== '';
    }

    /**
     * Where the team sits, as a validated IANA timezone. Falls back to Perth
     * rather than throwing — a typo'd setting must never 500 a public page.
     */
    public static function timezone(): \DateTimeZone
    {
        $tz = (string) (config('company.timezone') ?: 'Australia/Perth');

        try {
            return new \DateTimeZone($tz);
        } catch (\Exception $e) {
            return new \DateTimeZone('Australia/Perth');
        }
    }

    /** The current time in the office. */
    public static function officeNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', self::timezone());
    }

    /** "AWST" — the office's current abbreviation (handles DST where it applies). */
    public static function timezoneAbbr(): string
    {
        return self::officeNow()->format('T');
    }

    /** "UTC+8" / "UTC+10:30" — the office's current offset. */
    public static function utcOffsetLabel(): string
    {
        $seconds = self::officeNow()->getOffset();
        $sign = $seconds < 0 ? '-' : '+';
        $seconds = abs($seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return 'UTC' . $sign . $hours . ($minutes ? ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');
    }
}
