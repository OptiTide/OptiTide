<?php

namespace App\Services\Payments;

use App\Support\Money;

/**
 * PayID / bank transfer. No API and no automatic settlement webhook — the
 * client pushes an instant NPP/Osko transfer to the business PayID quoting the
 * invoice number, and staff reconcile by recording the payment. Cheapest path,
 * works today, no third-party onboarding.
 */
final class PayIdGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'payid';
    }

    public function label(): string
    {
        return config('payments.gateways.payid.label', 'PayID / Bank transfer');
    }

    public function isEnabled(): bool
    {
        return in_array('payid', (array) config('payments.enabled', []), true)
            && config('payments.gateways.payid.value') !== '';
    }

    public function instructionFor(array $invoice): PaymentInstruction
    {
        $config = config('payments.gateways.payid');
        $balance = new Money(
            (int) $invoice['total_cents'] - (int) ($invoice['amount_paid_cents'] ?? 0),
            $invoice['currency'] ?? 'AUD'
        );

        $typeLabel = match ($config['type']) {
            'email' => 'PayID (email)',
            'abn'   => 'PayID (ABN)',
            default => 'PayID (mobile)',
        };

        $details = [
            $typeLabel        => $config['value'],
            'Account name'    => $config['account_name'],
            'Amount'          => $balance->format(),
            'Reference'       => $invoice['number'],
        ];

        if (! empty($config['bsb']) && ! empty($config['account_number'])) {
            $details['Or BSB / Account'] = $config['bsb'] . ' / ' . $config['account_number'];
        }

        return new PaymentInstruction(
            gateway: 'payid',
            label: $this->label(),
            type: 'bank_transfer',
            details: $details,
            note: 'Please include the reference (' . $invoice['number'] . ') so we can match your payment.',
            available: $config['value'] !== '',
        );
    }
}
