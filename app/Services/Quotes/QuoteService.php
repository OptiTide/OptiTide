<?php

namespace App\Services\Quotes;

use App\Core\Database;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Setting;
use App\Services\Invoices\InvoiceService;
use App\Services\Mail\Mail;
use App\Support\Gst;
use App\Support\Money;

/**
 * The single write path for quotes. subtotal/gst/total are NEVER set by hand —
 * recomputeTotals() derives them from the line items under the AU GST INCLUSIVE
 * model (line prices already include 10% GST; total = sum of lines − discount;
 * gst = total / 11; subtotal = total − gst). Accepting converts the quote to an
 * invoice exactly once — see accept().
 */
final class QuoteService
{
    public function nextNumber(): string
    {
        $db = Database::instance();

        if (! Setting::firstWhere('key', 'quote_counter')) {
            try {
                Setting::create(['key' => 'quote_counter', 'value' => '0']);
            } catch (\Throwable $e) {
                // Created concurrently — fine.
            }
        }

        $keyCol = $db->driver() === 'mysql' ? '`key`' : '"key"';
        $cast = $db->driver() === 'mysql' ? 'CAST(value AS SIGNED)' : 'CAST(value AS INTEGER)';

        // Atomic increment under a row lock: concurrent quote creation can never
        // mint a duplicate number.
        if ($db->driver() === 'pgsql') {
            $row = $db->selectOne(
                "UPDATE settings SET value = ($cast + 1)::text WHERE $keyCol = ? RETURNING value",
                ['quote_counter']
            );
            $n = (int) ($row['value'] ?? 0);
        } else {
            $db->affecting("UPDATE settings SET value = $cast + 1 WHERE $keyCol = ?", ['quote_counter']);
            $n = (int) Setting::get('quote_counter', '1');
        }

        return 'QUO-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    /** Default validity window for a new quote. */
    public function defaultExpiry(): string
    {
        $days = (int) Setting::get('quote_valid_days', 30);

        return date('Y-m-d', strtotime('+' . max(1, $days) . ' days'));
    }

    /**
     * @param array<string,mixed> $data   client_id, currency, issue_date, expires_at, notes, terms, status, discount_cents, discount_label
     * @param array<int,array{description:string,quantity:int|float,unit_price_cents:int,service_id?:int|null}> $items
     */
    public function create(array $data, array $items): array
    {
        return Database::instance()->transaction(function () use ($data, $items) {
            $currency = $data['currency'] ?? config('company.currency', 'AUD');

            $quote = Quote::create([
                'number'         => $this->nextNumber(),
                'client_id'      => $data['client_id'],
                'status'         => $data['status'] ?? Quote::STATUS_DRAFT,
                'currency'       => $currency,
                'issue_date'     => $data['issue_date'] ?? today(),
                'expires_at'     => $data['expires_at'] ?? $this->defaultExpiry(),
                'notes'          => $data['notes'] ?? null,
                'terms'          => $data['terms'] ?? null,
                'discount_cents' => (int) ($data['discount_cents'] ?? 0),
                'discount_label' => $data['discount_label'] ?? null,
                'subtotal_cents' => 0,
                'gst_cents'      => 0,
                'total_cents'    => 0,
                'public_token'   => str_random(48),
            ]);

            $this->replaceItems($quote['id'], $items, $currency);
            $this->recomputeTotals($quote['id']);

            return Quote::find($quote['id']);
        });
    }

    public function update(int|string $quoteId, array $data, array $items): array
    {
        return Database::instance()->transaction(function () use ($quoteId, $data, $items) {
            $quote = Quote::findOrFail($quoteId);
            $currency = $data['currency'] ?? $quote['currency'];

            Quote::updateById($quoteId, [
                'client_id'      => $data['client_id'] ?? $quote['client_id'],
                'currency'       => $currency,
                'issue_date'     => $data['issue_date'] ?? $quote['issue_date'],
                'expires_at'     => $data['expires_at'] ?? $quote['expires_at'],
                'notes'          => $data['notes'] ?? null,
                'terms'          => $data['terms'] ?? null,
                'discount_cents' => (int) ($data['discount_cents'] ?? 0),
                'discount_label' => $data['discount_label'] ?? null,
            ]);

            $this->replaceItems($quoteId, $items, $currency);
            $this->recomputeTotals($quoteId);

            return Quote::find($quoteId);
        });
    }

    /** Swap the whole line-item set — the form posts the sequence, not a delta. */
    public function replaceItems(int|string $quoteId, array $items, ?string $currency = null): void
    {
        QuoteItem::query()->where('quote_id', $quoteId)->delete();

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unit = (int) ($item['unit_price_cents'] ?? 0);

            if (($item['description'] ?? '') === '' && $unit === 0) {
                continue;
            }

            QuoteItem::create([
                'quote_id'         => $quoteId,
                'service_id'       => $item['service_id'] ?? null,
                'description'      => $item['description'] ?? '',
                'quantity'         => $quantity,
                'unit_price_cents' => $unit,
                'line_total_cents' => (int) round($quantity * $unit),
            ]);
        }
    }

    public function recomputeTotals(int|string $quoteId): array
    {
        $quote = Quote::findOrFail($quoteId);
        $grossCents = QuoteItem::query()->where('quote_id', $quoteId)->sum('line_total_cents');

        // The discount comes off the GST-INCLUSIVE figure and GST is then
        // re-derived from what's left, so the quote states the GST actually
        // collectable on the discounted price. Clamped to the gross so an
        // oversized discount can never quote a negative total.
        $discount = max(0, min((int) ($quote['discount_cents'] ?? 0), $grossCents));
        $netCents = $grossCents - $discount;

        $net = new Money($netCents, $quote['currency']);
        $gst = Gst::component($net);
        $subtotal = $net->subtract($gst);

        Quote::updateById($quoteId, [
            'discount_cents' => $discount,
            'subtotal_cents' => $subtotal->minorUnits,
            'gst_cents'      => $gst->minorUnits,
            'total_cents'    => $net->minorUnits,
        ]);

        return Quote::find($quoteId);
    }

    /** Issue a draft: Draft → Sent, stamp the issue date, email the client. */
    public function send(int|string $quoteId): array
    {
        $quote = Quote::findOrFail($quoteId);

        if ($quote['status'] === Quote::STATUS_DRAFT) {
            Quote::updateById($quoteId, [
                'status'     => Quote::STATUS_SENT,
                'issue_date' => $quote['issue_date'] ?: today(),
                'expires_at' => $quote['expires_at'] ?: $this->defaultExpiry(),
            ]);
            $quote = Quote::find($quoteId);
        }

        // A mail outage must never lose the quote: it is already issued and the
        // public link works whether or not the email lands, so the send is
        // recorded and the failure only logged.
        try {
            $this->email($quote);
        } catch (\Throwable $e) {
            logger('Quote email failed', ['quote' => $quote['number'], 'error' => $e->getMessage()]);
        }

        return $quote;
    }

    public function email(array $quote): bool
    {
        $client = Client::find($quote['client_id']);
        if (! $client || empty($client['email'])) {
            return false;
        }

        $pdf = (new QuotePdf())->render($quote['id']);

        return Mail::to($client['email'], $client['business_name'] ?? null)
            ->subject('Quote ' . $quote['number'] . ' from ' . config('company.legal_name'))
            ->view('emails.quote-sent', [
                'quote'     => $quote,
                'client'    => $client,
                'acceptUrl' => url('quote/' . $quote['public_token']),
            ])
            ->attach($pdf, $quote['number'] . '.pdf', 'application/pdf')
            ->send();
    }

    /**
     * Accept a quote and convert it to an invoice. Idempotent and race-safe:
     * two clicks (or a client and the public link at once) both land here, and
     * only the request that flips sent → accepted converts. The loser reads back
     * the invoice the winner created instead of double-billing the client.
     *
     * The compare-and-swap claim runs as the FIRST statement inside the
     * transaction rather than before it. Concurrency is still exclusive — a
     * second UPDATE blocks on the row lock and then re-evaluates
     * `status = 'sent'` to 0 rows — but rolling back now also releases the
     * claim, so a mid-flight failure leaves a re-acceptable quote rather than
     * one stranded as "accepted" with no invoice to show for it.
     *
     * @return array|null the resulting invoice, or null if the quote is not
     *                    acceptable and never converted
     */
    public function accept(int|string $quoteId): ?array
    {
        $quote = Quote::findOrFail($quoteId);

        // An expired, declined or already-accepted quote never converts. The CAS
        // below guards the status but not the expiry window, and expiry is
        // date-based so it can't meaningfully race with the claim.
        if (! Quote::isAcceptable($quote)) {
            return $this->convertedInvoice($quote);
        }

        return Database::instance()->transaction(function () use ($quoteId) {
            // The claim is the FIRST statement in the transaction, deliberately:
            // it takes the write lock outright, so a racing writer queues behind
            // it rather than dying on a read snapshot it can no longer upgrade.
            $claimed = Database::instance()->affecting(
                'UPDATE quotes SET status = ?, accepted_at = ?, updated_at = ? WHERE id = ? AND status = ?',
                [Quote::STATUS_ACCEPTED, now(), now(), $quoteId, Quote::STATUS_SENT]
            );

            if ($claimed === 0) {
                return $this->convertedInvoice(Quote::find($quoteId));
            }

            $quote = Quote::findOrFail($quoteId);

            $items = array_map(fn (array $item) => [
                'description'      => $item['description'],
                'quantity'         => $item['quantity'],
                'unit_price_cents' => (int) $item['unit_price_cents'],
                'service_id'       => $item['service_id'] ?? null,
            ], Quote::items($quoteId));

            // due_date is left to InvoiceService, which applies the house
            // payment terms (the `default_payment_terms` setting) from today —
            // so a quote-born invoice is dated exactly like a hand-made one.
            $invoice = (new InvoiceService())->create([
                'client_id'  => $quote['client_id'],
                'currency'   => $quote['currency'],
                'issue_date' => today(),
                'notes'      => $quote['notes'] ?? null,
                'status'     => Invoice::STATUS_SENT,
            ], $items);

            // InvoiceService::create() takes no discount (the column defaults to
            // 0), so the accepted discount is written on and the totals
            // re-derived — the invoice must total exactly what the client
            // accepted, GST included.
            if ((int) ($quote['discount_cents'] ?? 0) > 0) {
                Invoice::updateById($invoice['id'], [
                    'discount_cents' => (int) $quote['discount_cents'],
                    'discount_label' => $quote['discount_label'] ?? null,
                ]);
                $invoice = (new InvoiceService())->recomputeTotals($invoice['id']);
            }

            Quote::updateById($quoteId, ['converted_invoice_id' => $invoice['id']]);

            return $invoice;
        });
    }

    public function decline(int|string $quoteId, ?string $reason = null): ?array
    {
        $quote = Quote::findOrFail($quoteId);

        // Only a live quote declines, and only once — an accepted quote has
        // already become an invoice and can't be walked back from here.
        if (! Quote::isAcceptable($quote)) {
            return null;
        }

        $claimed = Database::instance()->affecting(
            'UPDATE quotes SET status = ?, declined_at = ?, decline_reason = ?, updated_at = ? WHERE id = ? AND status = ?',
            [Quote::STATUS_DECLINED, now(), $reason ?: null, now(), $quoteId, Quote::STATUS_SENT]
        );

        return $claimed === 0 ? null : Quote::find($quoteId);
    }

    /** The invoice an already-accepted quote produced, if it has one yet. */
    protected function convertedInvoice(?array $quote): ?array
    {
        if (! $quote || empty($quote['converted_invoice_id'])) {
            return null;
        }

        return Invoice::find($quote['converted_invoice_id']);
    }
}
