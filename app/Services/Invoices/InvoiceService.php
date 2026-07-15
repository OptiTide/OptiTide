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

    /** Default due date = issue date + the configured payment terms (days). */
    protected function defaultDueDate(): string
    {
        $days = (int) Setting::get('default_payment_terms', 14);

        return date('Y-m-d', strtotime('+' . max(0, $days) . ' days'));
    }

    /**
     * @param array<string,mixed> $data   client_id, currency, issue_date, due_date, notes, status, payoneer_link
     * @param array<int,array{description:string,quantity:int|float,unit_price_cents:int,service_id?:int|null}> $items
     */
    public function create(array $data, array $items): array
    {
        return Database::instance()->transaction(function () use ($data, $items) {
            $currency = $data['currency'] ?? config('company.currency', 'AUD');

            $invoice = Invoice::create([
                'number'            => $this->nextNumber(),
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
                'public_token'      => str_random(48),
            ]);

            $this->syncItems($invoice['id'], $items, $currency);
            $this->recomputeTotals($invoice['id']);

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
        $totalCents = InvoiceItem::query()->where('invoice_id', $invoiceId)->sum('line_total_cents');

        $total = new Money($totalCents, $invoice['currency']);
        $gst = Gst::component($total);
        $subtotal = $total->subtract($gst);

        Invoice::updateById($invoiceId, [
            'subtotal_cents' => $subtotal->minorUnits,
            'gst_cents'      => $gst->minorUnits,
            'total_cents'    => $total->minorUnits,
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
        }

        return $result['payment'];
    }

    public function void(int|string $invoiceId): void
    {
        Invoice::updateById($invoiceId, ['status' => Invoice::STATUS_VOID]);
    }
}
