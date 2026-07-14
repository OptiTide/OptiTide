<?php

namespace App\Exceptions;

use App\Enums\OrderState;
use DomainException;

class InvalidStateTransition extends DomainException
{
    public static function make(string $orderNumber, OrderState $from, OrderState $to): self
    {
        return new self(
            "Order {$orderNumber} cannot transition from [{$from->value}] to [{$to->value}]."
        );
    }
}
