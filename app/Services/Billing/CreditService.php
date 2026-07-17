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
        if (empty($invoice['client_id'])) {
            return 0;
        }
        $clientId = $invoice['client_id'];

        // Deduct, log, and record-as-payment in ONE transaction, so a mid-way failure
        // can never leave credit spent but the invoice unpaid (or the reverse).
        // recordPayment nests via savepoint; its receipt afterCommit fires only if
        // this outer transaction commits.
        return Database::instance()->transaction(function () use ($invoiceId, $clientId, $actorId) {
            $invoice = Invoice::findOrFail($invoiceId);
            $credit = (int) (Client::find($clientId)['credit_cents'] ?? 0);
            $balance = (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
            $apply = min($credit, max(0, $balance));

            if ($apply <= 0) {
                return 0;
            }

            // Atomic deduct via compare-and-swap, NOT read-then-write. Two concurrent
            // applies of the same credit each read $100 and each deducted (clamped at
            // 0, so the balance never went negative) — but each then recorded a $100
            // payment, crediting $200 of invoices against $100 of real credit. The CAS
            // lets exactly one win; the loser sees 0 rows affected and applies nothing.
            // Same shape as the discount-redeem guard.
            $deducted = Database::instance()->affecting(
                'UPDATE clients SET credit_cents = credit_cents - ? WHERE id = ? AND credit_cents >= ?',
                [$apply, $clientId, $apply]
            );

            if ($deducted === 0) {
                return 0; // lost the race; empty transaction commits, caller gets 0
            }

            CreditTransaction::create([
                'client_id'    => $clientId,
                'amount_cents' => -$apply,
                'type'         => 'applied',
                'reason'       => 'Applied to invoice ' . $invoice['number'],
                'invoice_id'   => $invoiceId,
                'recorded_by'  => $actorId,
            ]);

            (new InvoiceService())->recordPayment($invoiceId, $apply, 'credit', 'Account credit', today(), $actorId);

            return $apply;
        });
    }
}
