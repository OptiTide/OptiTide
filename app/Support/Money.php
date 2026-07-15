<?php

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/**
 * Immutable money value object. Amounts are stored as integer minor units
 * (cents) with an ISO currency code. Never represent money as a float in
 * storage or arithmetic — only convert to dollars for display/entry.
 */
final class Money implements Stringable
{
    public function __construct(
        public readonly int $minorUnits,
        public readonly string $currency = 'AUD'
    ) {
    }

    public static function of(int $minorUnits, string $currency = 'AUD'): self
    {
        return new self($minorUnits, $currency);
    }

    public static function fromDollars(float|int|string $dollars, string $currency = 'AUD'): self
    {
        return new self((int) round(((float) $dollars) * 100), $currency);
    }

    public static function zero(string $currency = 'AUD'): self
    {
        return new self(0, $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->minorUnits * $factor), $this->currency);
    }

    /** Portion of this amount at the given basis points (100 bps = 1%). */
    public function percentage(int $basisPoints): self
    {
        return new self((int) round($this->minorUnits * $basisPoints / 10000), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits < $other->minorUnits;
    }

    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits > $other->minorUnits;
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency && $this->minorUnits === $other->minorUnits;
    }

    public function dollars(): float
    {
        return $this->minorUnits / 100;
    }

    public function format(bool $withSymbol = true): string
    {
        $formatted = number_format($this->minorUnits / 100, 2);

        return $withSymbol ? '$' . $formatted : $formatted;
    }

    public function formatWithCode(): string
    {
        return $this->format() . ' ' . $this->currency;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}."
            );
        }
    }
}
