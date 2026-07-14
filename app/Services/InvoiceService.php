<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceIssued;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    /**
     * The GST **component** contained in a GST-inclusive amount (cents). AU
     * prices are GST-inclusive, so GST is backed out of the total the customer
     * pays — `amount * rate / (1 + rate)` — not added on top (which would make
     * an issued invoice exceed the Stripe-charged amount). Returns 0 when the
     * supplier isn't GST-registered.
     */
    public static function gstComponent(int $inclusiveAmount): int
    {
        if (! config('company.gst_registered', true)) {
            return 0;
        }

        $bps = config('company.gst_basis_points', 1000);

        return (int) round($inclusiveAmount * $bps / (10_000 + $bps));
    }

    /**
     * Sync the invoice's total (= sum of GST-inclusive line items), the GST
     * component within it, and the ex-GST subtotal so the stored figures always
     * match the itemised lines and never exceed what the client is charged.
     */
    public function recomputeTotals(Invoice $invoice): void
    {
        $total = (int) $invoice->items()->sum('total');
        $tax = self::gstComponent($total);

        $invoice->forceFill([
            'subtotal' => $total - $tax,
            'tax' => $tax,
            'total' => $total,
        ])->save();

        // Keep line items denominated in the invoice's currency — the repeater
        // has no per-item currency field, so without this a non-AUD invoice
        // would render item lines in AUD and totals in the chosen currency.
        $invoice->items()->update(['currency' => $invoice->currency]);
    }

    /**
     * Issue a draft invoice to the client: mark it sent, default the due date,
     * and email them a copy. No-op if it isn't a draft.
     */
    public function send(Invoice $invoice, int $dueInDays = 14): void
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            return;
        }

        $this->recomputeTotals($invoice);

        $invoice->forceFill([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
            'due_date' => $invoice->due_date ?? now()->addDays($dueInDays)->toDateString(),
        ])->save();

        Mail::to($invoice->user->email)->send(new InvoiceIssued($invoice->fresh()));
    }
}
