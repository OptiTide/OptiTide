<?php

namespace App\Services\Payments;

/**
 * Contract every payment provider implements. Adding Stripe, PayTo (Monoova /
 * Zepto / Azupay), GoCardless, etc. means writing one class and listing it in
 * config/payments.php — nothing in the invoice/billing core changes.
 */
interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    public function isEnabled(): bool;

    /** @param array<string,mixed> $invoice a full invoice row */
    public function instructionFor(array $invoice): PaymentInstruction;
}
