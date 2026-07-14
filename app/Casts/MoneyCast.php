<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

/**
 * Casts an integer minor-units column into a Money object, reading the
 * currency from a sibling column (default `currency`).
 *
 * Usage: 'price' => MoneyCast::class or MoneyCast::class.':currency_column'
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(protected string $currencyColumn = 'currency') {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        // Defaulting here would silently mislabel a non-AUD row's currency on
        // partial selects, so require the sibling column to be hydrated.
        if (! array_key_exists($this->currencyColumn, $attributes)) {
            throw new RuntimeException(sprintf(
                'Cannot cast %s::%s to Money: sibling column [%s] was not selected. Include it in the query.',
                $model::class,
                $key,
                $this->currencyColumn,
            ));
        }

        return new Money((int) $value, $attributes[$this->currencyColumn]);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            if ($value->amount < 0) {
                throw new InvalidArgumentException("The {$key} attribute cannot be negative.");
            }

            // Several money columns share one currency column per row; letting
            // one assignment rewrite it would silently re-denominate the rest.
            $existing = $attributes[$this->currencyColumn] ?? null;

            if ($existing !== null && $existing !== $value->currency) {
                throw new InvalidArgumentException(
                    "Cannot assign {$value->currency} to {$key}: row currency is {$existing}. "
                    ."Change the {$this->currencyColumn} column explicitly first."
                );
            }

            return [$key => $value->amount, $this->currencyColumn => $value->currency];
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            if ((int) $value < 0) {
                throw new InvalidArgumentException("The {$key} attribute cannot be negative.");
            }

            return [$key => (int) $value];
        }

        throw new InvalidArgumentException(
            "The {$key} attribute accepts an App\Support\Money instance or non-negative integer minor units."
        );
    }
}
