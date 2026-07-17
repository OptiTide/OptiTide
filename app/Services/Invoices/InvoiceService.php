<?php

namespace App\Services\Invoices;

use App\Core\Database;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Mail\Mail;
use App\Support\Gst;
use App\Support\Money;

/**
 * The single write path for invoices. subtotal/gst/total are NEVER set by hand
 * — recomputeTotals() derives them from the line items under the AU GST
 * INCLUSIVE model (line prices already include 10% GST; total = sum of lines;
 * gst = total / 11; subtotal = total − gst).
 */
final class InvoiceService
{
    public function nextNumber(): string
    {
        $db = Database::instance();

        if (! Setting::firstWhere('key', 'invoice_counter')) {
            try {
                Setting::create(['key' => 'invoice_counter', 'value' => '0']);
            } catch (\Throwable $e) {
                // Created concurrently — fine.
            }
        }

        $keyCol = $db->driver() === 'mysql' ? '`key`' : '"key"';
        $cast = $db->driver() === 'mysql' ? 'CAST(value AS SIGNED)' : 'CAST(value AS INTEGER)';

        // Atomic increment under a row lock: concurrent invoice creation (or the
        // recurring biller) can never mint a duplicate number.
        if ($db->driver() === 'pgsql') {
            $row = $db->selectOne(
                "UPDATE settings SET value = ($cast + 1)::text WHERE $keyCol = ? RETURNING value",
                ['invoice_counter']
            );
            $n = (int) ($row['value'] ?? 0);
        } else {
            $db->affecting("UPDATE settings SET value = $cast + 1 WHERE $keyCol = ?", ['invoice_counter']);
            $n = (int) Setting::get('invoice_counter', '1');
        }

        return 'INV-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Move invoice_counter up to $number's numeric part, never down.
     *
     * Only ever raises: porting an old INV-000007 after you have already issued
     * INV-000200 must not rewind the counter and start minting duplicates. Numbers
     * with no digits (a free-form reference from another system) are ignored rather
     * than treated as zero.
     */
    protected function raiseCounterTo(string $number): void
    {
        if (! preg_match('/(\d+)\s*$/', $number, $m)) {
            return;
        }

        $n = (int) $m[1];
        $current = (int) Setting::get('invoice_counter', '0');

        if ($n > $current) {
            Setting::put('invoice_counter', (string) $n);
        }
    }

    /** Default due date = issue date + the configured payment terms (days). */
    protected function defaultDueDate(): string
    {
        $days = (int) Setting::get('default_payment_terms', 14);

        return date('Y-m-d', strtotime('+' . max(0, $days) . ' days'));
    }

    /**
     * Create an invoice — and if it is born SENT, actually send it.
     *
     * This used to write the row and stop; only send() emailed anything. But four
     * call sites mint an invoice already stamped STATUS_SENT — QuoteService::accept(),
     * Client\OrderController::place(), and both InstallmentRequestController paths —
     * so a client accepting a quote or placing an order got an invoice that SAID
     * "Sent" and never was. No PDF, no pay link, nothing in their inbox.
     *
     * It failed dangerously rather than silently: payments are recorded by hand, so an
     * invoice nobody receives is an invoice nobody pays. RecurringBiller::markOverdue()
     * then flips it to Overdue, applies a LATE FEE, and emails "your invoice is
     * overdue" — the client's FIRST word about a bill they never got — and
     * SuspensionService suspends them 30 days later. Clients were being fined and cut
     * off over invoices we never sent.
     *
     * @param array<string,mixed> $data   client_id, currency, issue_date, due_date, notes, status, payoneer_link
     * @param array<int,array{description:string,quantity:int|float,unit_price_cents:int,service_id?:int|null}> $items
     */
    public function create(array $data, array $items): array
    {
        $invoice = $this->insert($data, $items);

        // notify=false is for PORTING history in from another system: the invoice is
        // a record of something that already happened, and emailing it would send a
        // client a "new" invoice they settled two years ago.
        if (($data['notify'] ?? true) === false) {
            return $invoice;
        }

        if (($invoice['status'] ?? null) === Invoice::STATUS_SENT) {
            // afterCommit, NOT inline. Callers wrap this in their own transaction —
            // OrderController::place() creates the invoices and can then throw
            // DiscountExhaustedException, rolling them back. An inline send would
            // already have emailed the client an invoice that no longer exists. With
            // no transaction open (the admin path) this runs immediately.
            Database::instance()->afterCommit(function () use ($invoice) {
                // A mail failure must never destroy a committed, correct invoice — the
                // admin can resend. email() already returns false (not throws) when the
                // client has no address.
                try {
                    $this->email($invoice);
                } catch (\Throwable $e) {
                    error_log('Invoice ' . ($invoice['number'] ?? '?') . ' created but not emailed: ' . $e->getMessage());
                }
            });
        }

        return $invoice;
    }

    /** The row-writing half of create(): transactional, and deliberately mail-free. */
    private function insert(array $data, array $items): array
    {
        return Database::instance()->transaction(function () use ($data, $items) {
            $currency = $data['currency'] ?? config('company.currency', 'AUD');

            $invoice = Invoice::create([
                // A supplied number is for PORTING: an AU tax invoice number is
                // referenced by your accountant, your BAS and the client's own
                // records, so history has to keep the number it was issued under.
                // Short-circuits, so nextNumber() is not called (and no counter
                // value is burned) when one is given.
                'number'            => $data['number'] ?? $this->nextNumber(),
                // Passed through so ported rows carry their real date; Model::create
                // uses ??= so a null here still becomes now().
                'created_at'        => $data['created_at'] ?? null,
                'client_id'         => $data['client_id'],
                'status'            => $data['status'] ?? Invoice::STATUS_DRAFT,
                'currency'          => $currency,
                'issue_date'        => $data['issue_date'] ?? today(),
                'due_date'          => $data['due_date'] ?? $this->defaultDueDate(),
                'notes'             => $data['notes'] ?? null,
                'payoneer_link'     => $data['payoneer_link'] ?? null,
                'subtotal_cents'    => 0,
                'gst_cents'         => 0,
                'total_cents'       => 0,
                'amount_paid_cents' => 0,
                'api_credit_topup_cents' => (int) ($data['api_credit_topup_cents'] ?? 0),
                // Ported history opts out of the automated chase — see migration 0045.
                'no_auto_chase'     => ! empty($data['no_auto_chase']) ? 1 : 0,
                'public_token'      => str_random(48),
            ]);

            $this->syncItems($invoice['id'], $items, $currency);
            $this->recomputeTotals($invoice['id']);

            // Keep the counter above anything ported in. Without this, importing
            // INV-000001..INV-000150 against a counter still sitting at 0 means the
            // NEXT real invoice mints INV-000001 — which collides with the unique
            // index on invoices.number and 500s, on the first invoice after a port.
            if (isset($data['number'])) {
                $this->raiseCounterTo((string) $data['number']);
            }

            return Invoice::find($invoice['id']);
        });
    }

    public function update(int|string $invoiceId, array $data, array $items): array
    {
        return Database::instance()->transaction(function () use ($invoiceId, $data, $items) {
            $invoice = Invoice::findOrFail($invoiceId);
            $currency = $data['currency'] ?? $invoice['currency'];

            Invoice::updateById($invoiceId, [
                'client_id'     => $data['client_id'] ?? $invoice['client_id'],
                'currency'      => $currency,
                'issue_date'    => $data['issue_date'] ?? $invoice['issue_date'],
                'due_date'      => $data['due_date'] ?? $invoice['due_date'],
                'notes'         => $data['notes'] ?? null,
                'payoneer_link' => $data['payoneer_link'] ?? null,
            ]);

            InvoiceItem::query()->where('invoice_id', $invoiceId)->delete();
            $this->syncItems($invoiceId, $items, $currency);
            $this->recomputeTotals($invoiceId);

            return Invoice::find($invoiceId);
        });
    }

    protected function syncItems(int|string $invoiceId, array $items, string $currency): void
    {
        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unit = (int) ($item['unit_price_cents'] ?? 0);

            if (($item['description'] ?? '') === '' && $unit === 0) {
                continue;
            }

            InvoiceItem::create([
                'invoice_id'       => $invoiceId,
                'service_id'       => $item['service_id'] ?? null,
                'description'      => $item['description'] ?? '',
                'quantity'         => $quantity,
                'unit_price_cents' => $unit,
                'line_total_cents' => (int) round($quantity * $unit),
            ]);
        }
    }

    public function recomputeTotals(int|string $invoiceId): array
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $grossCents = InvoiceItem::query()->where('invoice_id', $invoiceId)->sum('line_total_cents');

        // The discount comes off the GST-INCLUSIVE figure and GST is then
        // re-derived from what's left. Discounting the ex-GST subtotal instead
        // would remit GST on money we never collected. Clamped to the gross so
        // a stale/oversized discount can never produce a negative invoice.
        $discount = max(0, min((int) ($invoice['discount_cents'] ?? 0), $grossCents));
        $netCents = $grossCents - $discount;

        $net = new Money($netCents, $invoice['currency']);
        $gst = Gst::component($net);
        $subtotal = $net->subtract($gst);

        // Any active late fee (GST-free) rides on top of the line-item total, so
        // re-editing an overdue invoice never silently drops the penalty.
        $lateFee = (int) ($invoice['late_fee_cents'] ?? 0);

        Invoice::updateById($invoiceId, [
            'discount_cents' => $discount,
            'subtotal_cents' => $subtotal->minorUnits,
            'gst_cents'      => $gst->minorUnits,
            'total_cents'    => $net->minorUnits + $lateFee,
        ]);

        return Invoice::find($invoiceId);
    }

    /** Issue a draft: Draft → Sent, stamp dates, email the client the PDF. */
    public function send(int|string $invoiceId): array
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if ($invoice['status'] === Invoice::STATUS_DRAFT) {
            Invoice::updateById($invoiceId, [
                'status'     => Invoice::STATUS_SENT,
                'issue_date' => $invoice['issue_date'] ?: today(),
                'due_date'   => $invoice['due_date'] ?: $this->defaultDueDate(),
            ]);
            $invoice = Invoice::find($invoiceId);
        }

        $this->email($invoice);

        return $invoice;
    }

    public function email(array $invoice): bool
    {
        $client = \App\Models\Client::find($invoice['client_id']);
        if (! $client || empty($client['email'])) {
            return false;
        }

        $pdf = (new InvoicePdf())->render($invoice['id']);

        return Mail::to($client['email'], $client['business_name'] ?? null)
            ->subject('Invoice ' . $invoice['number'] . ' from ' . config('company.legal_name'))
            ->view('emails.invoice', [
                'invoice' => $invoice,
                'client'  => $client,
                'payUrl'  => url('pay/' . $invoice['public_token']),
            ])
            ->attach($pdf, $invoice['number'] . '.pdf', 'application/pdf')
            ->send();
    }

    /**
     * Record a payment against an invoice and reconcile status. Runs in a
     * transaction so amount_paid and status never drift apart.
     */
    public function recordPayment(int|string $invoiceId, int $amountCents, string $method, ?string $reference, ?string $paidAt, int|string|null $recordedBy): array
    {
        $result = Database::instance()->transaction(function () use ($invoiceId, $amountCents, $method, $reference, $paidAt, $recordedBy) {
            $invoice = Invoice::findOrFail($invoiceId);
            $wasPaid = $invoice['status'] === Invoice::STATUS_PAID;

            $payment = Payment::create([
                'invoice_id'   => $invoiceId,
                'client_id'    => $invoice['client_id'],
                'amount_cents' => $amountCents,
                'currency'     => $invoice['currency'],
                'method'       => $method,
                'reference'    => $reference,
                'paid_at'      => $paidAt ?: now(),
                'recorded_by'  => $recordedBy,
            ]);

            $paid = (int) $invoice['amount_paid_cents'] + $amountCents;
            $status = $invoice['status'];

            if ($paid >= (int) $invoice['total_cents'] && $status !== Invoice::STATUS_VOID) {
                $status = Invoice::STATUS_PAID;
            }

            Invoice::updateById($invoiceId, [
                'amount_paid_cents' => $paid,
                'status'            => $status,
                'paid_at'           => $status === Invoice::STATUS_PAID ? ($paidAt ?: now()) : $invoice['paid_at'],
            ]);

            return ['payment' => $payment, 'becamePaid' => (! $wasPaid && $status === Invoice::STATUS_PAID)];
        });

        // When an invoice first becomes fully paid, credit any referrer of the
        // client. Best-effort + outside the payment transaction so a referral
        // hiccup never blocks recording the payment.
        if ($result['becamePaid']) {
            try {
                (new \App\Services\Referrals\CommissionService())->recordForPaidInvoice(Invoice::find($invoiceId));
            } catch (\Throwable $e) {
                // ignore
            }

            // If this invoice was an API-credit top-up, grant the credit now.
            // Idempotent via the invoice reference so redelivery never double-grants.
            try {
                $inv = Invoice::find($invoiceId);
                $topup = (int) ($inv['api_credit_topup_cents'] ?? 0);
                if ($inv && $topup > 0 && ! empty($inv['client_id'])) {
                    (new \App\Services\Api\ApiCreditService())->topUp(
                        $inv['client_id'],
                        $topup,
                        'Credit purchase — invoice ' . $inv['number'],
                        'invoice:' . $invoiceId,
                    );
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // A payment may clear a suspended client's balance — lift the hold and
        // resume their services. Best-effort.
        try {
            $inv = Invoice::find($invoiceId);
            if ($inv && ! empty($inv['client_id'])) {
                (new \App\Services\Billing\SuspensionService())->reactivateIfCleared($inv['client_id']);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $result['payment'];
    }

    public function void(int|string $invoiceId): void
    {
        Invoice::updateById($invoiceId, ['status' => Invoice::STATUS_VOID]);
    }
}
