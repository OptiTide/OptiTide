<?php

namespace App\Services\Payments;

/**
 * Skrill Quick Checkout. When a merchant Skrill email is configured, the client
 * gets a "Pay with Skrill" button that hands off to Skrill's hosted page
 * (an auto-submitting form — see PayController::skrill). Config-gated: without a
 * merchant email it renders as unavailable rather than a broken button.
 */
final class SkrillGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'skrill';
    }

    public function label(): string
    {
        return config('payments.gateways.skrill.label', 'Skrill');
    }

    public function isEnabled(): bool
    {
        return in_array('skrill', (array) config('payments.enabled', []), true);
    }

    public function instructionFor(array $invoice): PaymentInstruction
    {
        $configured = trim((string) config('payments.gateways.skrill.merchant_email', '')) !== '';
        $token = $invoice['public_token'] ?? '';

        return new PaymentInstruction(
            gateway: 'skrill',
            label: $this->label(),
            type: 'link',
            details: [],
            actionUrl: $configured && $token !== '' ? url('pay/' . $token . '/skrill') : null,
            actionText: 'Pay with Skrill',
            note: $configured
                ? 'You will be taken to Skrill to pay securely by card, wallet or bank.'
                : 'Skrill isn\'t set up yet — please use PayID, or contact us.',
            available: $configured && $token !== '',
        );
    }
}
