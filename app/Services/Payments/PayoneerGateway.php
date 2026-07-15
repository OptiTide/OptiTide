<?php

namespace App\Services\Payments;

/**
 * Payoneer "request a payment" links.
 *
 *  - mode=manual (default): a staff member pastes the Payoneer request link on
 *    the invoice; we surface it as a pay button. Works today, no API access.
 *  - mode=api: mint the link programmatically once Payoneer API credentials are
 *    provisioned. Stubbed here and fail-closed to the stored link, so enabling
 *    the mode without wiring the call never breaks the pay page.
 *
 * Payoneer has no native recurring billing, so hosting/retainers are handled by
 * generating a fresh invoice (and link) each cycle — see RecurringBiller.
 */
final class PayoneerGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'payoneer';
    }

    public function label(): string
    {
        return config('payments.gateways.payoneer.label', 'Payoneer');
    }

    public function isEnabled(): bool
    {
        return in_array('payoneer', (array) config('payments.enabled', []), true);
    }

    public function instructionFor(array $invoice): PaymentInstruction
    {
        $link = $this->resolveLink($invoice);

        return new PaymentInstruction(
            gateway: 'payoneer',
            label: $this->label(),
            type: 'link',
            details: [],
            actionUrl: $link,
            actionText: 'Pay with Payoneer',
            note: $link
                ? 'You will be taken to Payoneer to complete payment by card or bank.'
                : 'A Payoneer payment link has not been attached to this invoice yet.',
            available: $link !== null,
        );
    }

    protected function resolveLink(array $invoice): ?string
    {
        $mode = config('payments.gateways.payoneer.mode', 'manual');

        if ($mode === 'api') {
            $minted = $this->mintApiLink($invoice);
            if ($minted !== null) {
                return $minted;
            }
        }

        $stored = $invoice['payoneer_link'] ?? '';

        return $stored !== '' ? $stored : null;
    }

    /**
     * Placeholder for the Payoneer Payment Requests API. Returns null (so the
     * flow falls back to a stored link) until real credentials + endpoint are
     * wired. Kept isolated so the integration is a single-method change.
     */
    protected function mintApiLink(array $invoice): ?string
    {
        $apiKey = config('payments.gateways.payoneer.api_key');
        $programId = config('payments.gateways.payoneer.program_id');

        if (! $apiKey || ! $programId) {
            return null;
        }

        // TODO: POST to the Payoneer Payment Requests API and return the URL.
        logger('Payoneer API mode is enabled but the API call is not yet implemented.', [
            'invoice' => $invoice['number'] ?? null,
        ]);

        return null;
    }
}
