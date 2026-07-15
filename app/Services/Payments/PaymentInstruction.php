<?php

namespace App\Services\Payments;

/**
 * A renderable set of instructions telling a client how to pay one invoice
 * with a particular gateway. Deliberately presentation-agnostic — the pay
 * page and the invoice PDF both render the same object.
 */
final class PaymentInstruction
{
    /**
     * @param 'bank_transfer'|'link' $type
     * @param array<string,string>   $details  ordered label => value rows
     */
    public function __construct(
        public string $gateway,
        public string $label,
        public string $type,
        public array $details = [],
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public ?string $note = null,
        public bool $available = true,
    ) {
    }
}
