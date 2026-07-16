<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\Audit\AuditLog;
use App\Services\Quotes\QuotePdf;
use App\Services\Quotes\QuoteService;
use App\Support\Features;

/**
 * Public, token-addressed quote page. The 48-char public_token is the
 * capability — no login required — so a client can accept straight from the
 * email. Draft quotes are never exposed here.
 */
class QuoteController extends Controller
{
    public function __construct(protected QuoteService $quotes = new QuoteService())
    {
    }

    public function show(Request $request, string $token): Response
    {
        $quote = $this->resolve($token);

        return $this->view('public.quote', [
            'title'   => 'Quote ' . $quote['number'],
            'quote'   => $quote,
            'client'  => Client::find($quote['client_id']),
            'items'   => Quote::items($quote['id']),
            'invoice' => $quote['converted_invoice_id'] ? Invoice::find($quote['converted_invoice_id']) : null,
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $quote = $this->resolve($token);

        return Response::file((new QuotePdf())->render($quote['id']), $quote['number'] . '.pdf', 'application/pdf');
    }

    public function accept(Request $request, string $token): Response
    {
        $quote = $this->resolve($token);

        $invoice = $this->quotes->accept($quote['id']);

        if (! $invoice) {
            $this->flash('error', Quote::hasExpired($quote)
                ? 'This quote has expired. Please contact us for an updated quote.'
                : 'This quote can no longer be accepted.');

            return $this->redirectRoute('quote.show', ['token' => $token]);
        }

        // No signed-in actor on the public link — the audit row records the
        // client the token belongs to, not a user.
        AuditLog::record('quote.converted', 'quote', $quote['id'], [
            'number'  => $quote['number'],
            'invoice' => $invoice['number'] ?? null,
            'via'     => 'public_link',
        ]);

        $this->flash('success', "Thanks — quote {$quote['number']} is accepted. Invoice {$invoice['number']} is ready below.");

        return $this->redirectRoute('quote.show', ['token' => $token]);
    }

    public function decline(Request $request, string $token): Response
    {
        $quote = $this->resolve($token);

        $reason = trim((string) $request->input('reason')) ?: null;

        if (! $this->quotes->decline($quote['id'], $reason)) {
            $this->flash('error', 'This quote can no longer be declined.');

            return $this->redirectRoute('quote.show', ['token' => $token]);
        }

        AuditLog::record('quote.declined', 'quote', $quote['id'], [
            'number' => $quote['number'],
            'via'    => 'public_link',
        ]);

        $this->flash('status', 'Quote declined. Thanks for letting us know.');

        return $this->redirectRoute('quote.show', ['token' => $token]);
    }

    protected function resolve(string $token): array
    {
        if (! Features::enabled('quotes')) {
            $this->abort(404, 'Quote not found.');
        }

        $quote = Quote::firstWhere('public_token', $token);

        if (! $quote || $quote['status'] === Quote::STATUS_DRAFT) {
            $this->abort(404, 'Quote not found.');
        }

        return $quote;
    }
}
