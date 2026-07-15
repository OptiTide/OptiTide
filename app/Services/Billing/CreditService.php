<?php

namespace App\Services\Billing;

use App\Core\Database;
use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\Invoice;
use App\Services\Invoices\InvoiceService;

/**
 * Client account credit. Credit is a prepaid balance held against the client
 * that can be applied to invoices as a "credit" payment (which flows through the
 * normal invoice reconciliation). Every change is logged to credit_transactions.
 */
final class CreditService
{
    /** Add (or, with a negative amount, deduct) credit. Returns the new balance in cents. */
    public function add(int|string $clientId, int $amountCents, string $type, ?string $reason, int|string|null $recordedBy, int|string|null $invoiceId = null): int
    {
        return Database::instance()->transaction(function () use ($clientId, $amountCents, $type, $reason, $recordedBy, $invoiceId) {
            $client = Client::findOrFail($clientId);
            $new = max(0, (int) ($client['credit_cents'] ?? 0) + $amountCents);
            Client::updateById($clientId, ['credit_cents' => $new]);

            CreditTransaction::create([
                'client_id'   => $clientId,
                'amount_cents' => $amountCents,
                'type'        => $type,
                'reason'      => $reason,
                'invoice_id'  => $invoiceId,
                'recorded_by' => $recordedBy,
            ]);

            return $new;
        });
    }

    /**
     * Apply available credit to an invoice's outstanding balance. Records it as a
     * payment (method 'credit') so status/commission/reactivation all reconcile.
     * Returns the cents applied.
     */
    public function applyToInvoice(int|string $invoiceId, int|string|null $actorId): int
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $client = $invoice['client_id'] ? Client::find($invoice['client_id']) : null;
        if (! $client) {
            return 0;
        }

        $credit = (int) ($client['credit_cents'] ?? 0);
        $balance = (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
        $apply = min($credit, max(0, $balance));
        if ($apply <= 0) {
            return 0;
        }

        // Deduct + log first, then record the payment (its own transaction).
        $this->add($client['id'], -$apply, 'applied', 'Applied to invoice ' . $invoice['number'], $actorId, $invoiceId);
        (new InvoiceService())->recordPayment($invoiceId, $apply, 'credit', 'Account credit', today(), $actorId);

        return $apply;
    }
}
