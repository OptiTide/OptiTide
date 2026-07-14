<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use NumberFormatter;
use Stringable;

/**
 * Immutable money value object. Amounts are always integer minor units
 * (cents) to avoid floating-point drift, per the platform's financial rules.
 */
final class Money implements JsonSerializable, Stringable
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency = 'AUD',
    ) {}

    public static function fromMajor(int|float|string $major, string $currency = 'AUD'): self
    {
        return new self((int) round((float) $major * 100), $currency);
    }

    public static function zero(string $currency = 'AUD'): self
    {
        return new self(0, $currency);
    }

    public function major(): float
    {
        return $this->amount / 100;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    /** @param int $basisPoints e.g. 1000 = 10% */
    public function percentage(int $basisPoints): self
    {
        return new self((int) round($this->amount * $basisPoints / 10_000), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function format(string $locale = 'en_AU'): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($this->major(), $this->currency);
    }

    public function jsonSerialize(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on money in different currencies ({$this->currency} vs {$other->currency})."
            );
        }
    }
}
