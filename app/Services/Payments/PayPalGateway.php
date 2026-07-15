<?php

namespace App\Services\Payments;

/**
 * PayPal via a PayPal.Me link (no API needed): the client is sent to
 * paypal.com/paypalme/{handle}/{amount} pre-filled with the invoice balance, and
 * can pay with their PayPal balance or any card. Config-gated on the handle.
 */
final class PayPalGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return config('payments.gateways.paypal.label', 'PayPal');
    }

    public function isEnabled(): bool
    {
        return in_array('paypal', (array) config('payments.enabled', []), true);
    }

    public function instructionFor(array $invoice): PaymentInstruction
    {
        $handle = trim((string) config('payments.gateways.paypal.me_handle', ''));
        $balance = max(0, (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents']);
        $amount = number_format($balance / 100, 2, '.', '');

        $url = $handle !== ''
            ? 'https://www.paypal.com/paypalme/' . rawurlencode($handle) . '/' . $amount
            : null;

        return new PaymentInstruction(
            gateway: 'paypal',
            label: $this->label(),
            type: 'link',
            details: [],
            actionUrl: $url,
            actionText: 'Pay with PayPal',
            note: $url
                ? 'Pay securely with your PayPal balance or any credit/debit card.'
                : 'PayPal isn\'t set up yet — please use PayID, or contact us.',
            available: $url !== null,
        );
    }
}
