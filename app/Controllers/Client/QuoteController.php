<?php

namespace App\Controllers\Client;

use App\Core\Auth;
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
use App\Support\Money;

class QuoteController extends Controller
{
    public function __construct(protected QuoteService $quotes = new QuoteService())
    {
    }

    public function index(Request $request): Response
    {
        $this->guard();

        $clientId = Auth::clientId();
        $currency = config('company.currency', 'AUD');
        $status = (string) $request->query('status', '');

        // A draft is internal — the client only ever sees an issued quote.
        $all = $clientId
            ? array_values(array_filter(
                Quote::forClient($clientId),
                fn (array $quote) => $quote['status'] !== Quote::STATUS_DRAFT
            ))
            : [];

        $open = 0;
        $accepted = 0;
        foreach ($all as $quote) {
            if (Quote::isAcceptable($quote)) {
                $open += (int) $quote['total_cents'];
            }
            if ($quote['status'] === Quote::STATUS_ACCEPTED) {
                $accepted += (int) $quote['total_cents'];
            }
        }

        $quotes = $status !== '' && isset(Quote::STATUSES[$status])
            ? array_values(array_filter($all, fn ($q) => Quote::displayStatus($q) === $status))
            : $all;

        return $this->view('client.quotes.index', [
            'title'    => 'Quotes',
            'quotes'   => $quotes,
            'status'   => $status,
            'open'     => new Money($open, $currency),
            'accepted' => new Money($accepted, $currency),
            'count'    => count($all),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $this->guard();
        $quote = $this->ownedQuote($id);

        return $this->view('client.quotes.show', [
            'title'   => 'Quote ' . $quote['number'],
            'quote'   => $quote,
            'client'  => Client::find($quote['client_id']),
            'items'   => Quote::items($quote['id']),
            'invoice' => $quote['converted_invoice_id'] ? Invoice::find($quote['converted_invoice_id']) : null,
        ]);
    }

    public function pdf(Request $request, string $id): Response
    {
        $this->guard();
        $quote = $this->ownedQuote($id);

        return Response::file((new QuotePdf())->render($quote['id']), $quote['number'] . '.pdf', 'application/pdf');
    }

    public function accept(Request $request, string $id): Response
    {
        $this->guard();
        $quote = $this->ownedQuote($id);

        $invoice = $this->quotes->accept($quote['id']);

        if (! $invoice) {
            $this->flash('error', Quote::hasExpired($quote)
                ? 'This quote has expired. Please contact us for an updated quote.'
                : 'This quote can no longer be accepted.');

            return $this->redirectRoute('portal.quotes.show', ['id' => $quote['id']]);
        }

        AuditLog::record('quote.converted', 'quote', $quote['id'], [
            'number'  => $quote['number'],
            'invoice' => $invoice['number'] ?? null,
        ]);

        $this->flash('success', "Thanks — quote {$quote['number']} is accepted. Invoice {$invoice['number']} is ready below.");

        return $this->redirectRoute('portal.quotes.show', ['id' => $quote['id']]);
    }

    public function decline(Request $request, string $id): Response
    {
        $this->guard();
        $quote = $this->ownedQuote($id);

        $reason = trim((string) $request->input('reason')) ?: null;

        if (! $this->quotes->decline($quote['id'], $reason)) {
            $this->flash('error', 'This quote can no longer be declined.');

            return $this->redirectRoute('portal.quotes.show', ['id' => $quote['id']]);
        }

        AuditLog::record('quote.declined', 'quote', $quote['id'], ['number' => $quote['number']]);
        $this->flash('status', 'Quote declined. Thanks for letting us know.');

        return $this->redirectRoute('portal.quotes.show', ['id' => $quote['id']]);
    }

    /**
     * Re-scope every lookup to the signed-in client (IDOR guard). An id from the
     * request is never trusted — it only selects within this client's own rows.
     */
    protected function ownedQuote(string $id): array
    {
        $quote = Quote::findOrFail($id);

        if ((string) $quote['client_id'] !== (string) Auth::clientId()
            || $quote['status'] === Quote::STATUS_DRAFT) {
            $this->abort(404, 'Quote not found.');
        }

        return $quote;
    }

    protected function guard(): void
    {
        if (! Features::enabled('quotes')) {
            $this->abort(404, 'Quotes are not enabled.');
        }
    }
}
