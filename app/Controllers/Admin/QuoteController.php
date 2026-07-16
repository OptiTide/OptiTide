<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Service;
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

        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        $clientNames = array_column(Client::all(), 'business_name', 'id');

        $query = Quote::query()->orderBy('id', 'desc');
        if ($status !== '' && isset(Quote::STATUSES[$status])) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            // Match the quote number OR any client whose business name contains
            // the term (resolved client_id → business_name, then filtered by id).
            $like = '%' . $search . '%';
            $matchingClientIds = [];
            foreach ($clientNames as $clientId => $name) {
                if (stripos((string) $name, $search) !== false) {
                    $matchingClientIds[] = $clientId;
                }
            }

            if ($matchingClientIds !== []) {
                $placeholders = implode(', ', array_fill(0, count($matchingClientIds), '?'));
                $query->whereRaw(
                    '(number LIKE ? OR client_id IN (' . $placeholders . '))',
                    array_merge([$like], array_values($matchingClientIds))
                );
            } else {
                $query->whereLike('number', $like);
            }
        }

        $result = $query->paginate(20, $request->integer('page', 1));

        return $this->view('admin.quotes.index', [
            'title'        => 'Quotes',
            'result'       => $result,
            'status'       => $status,
            'search'       => $search,
            'client_names' => $clientNames,
            'stats'        => $this->indexStats(),
        ]);
    }

    /**
     * KPI figures for the quotes list header.
     *
     * @return array<string,mixed>
     */
    protected function indexStats(): array
    {
        $currency = config('company.currency', 'AUD');

        // "Open" is what's genuinely still winnable — a sent quote whose expiry
        // has passed is dead money and must not inflate the pipeline.
        $open = 0;
        foreach (Quote::query()->where('status', Quote::STATUS_SENT)->get() as $quote) {
            if (! Quote::hasExpired($quote)) {
                $open += (int) $quote['total_cents'];
            }
        }

        $accepted = Quote::query()->where('status', Quote::STATUS_ACCEPTED)->sum('total_cents');

        return [
            'open'           => money($open, $currency),
            'accepted'       => money($accepted, $currency),
            'draft_count'    => Quote::query()->where('status', Quote::STATUS_DRAFT)->count(),
            'accepted_count' => Quote::query()->where('status', Quote::STATUS_ACCEPTED)->count(),
        ];
    }

    public function create(Request $request): Response
    {
        $this->guard();

        return $this->view('admin.quotes.form', [
            'title'     => 'New Quote',
            'quote'     => null,
            'items'     => [],
            'clients'   => Client::query()->orderBy('business_name')->get(),
            'services'  => Service::active(),
            'preselect' => $request->query('client_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $this->guard();

        $data = $this->validate($request, [
            'client_id'  => 'required|exists:clients,id',
            'issue_date' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'notes'      => 'nullable|max:2000',
            'terms'      => 'nullable|max:5000',
            'discount'   => 'nullable|numeric|min:0',
            'discount_label' => 'nullable|max:160',
        ]);

        $items = $this->parseItems($request);
        if ($items === []) {
            Session::flash('error', 'Add at least one line item.');

            return $this->back();
        }

        $quote = $this->quotes->create([
            'client_id'      => $data['client_id'],
            'issue_date'     => $data['issue_date'] ?: today(),
            'expires_at'     => $data['expires_at'] ?: $this->quotes->defaultExpiry(),
            'notes'          => $data['notes'] ?? null,
            'terms'          => $data['terms'] ?? null,
            'discount_cents' => Money::fromDollars($request->input('discount', 0))->minorUnits,
            'discount_label' => $data['discount_label'] ?? null,
            'status'         => Quote::STATUS_DRAFT,
        ], $items);

        AuditLog::record('quote.created', 'quote', $quote['id'], ['number' => $quote['number'] ?? null]);

        if ($request->input('action') === 'save_send') {
            $this->quotes->send($quote['id']);
            AuditLog::record('quote.sent', 'quote', $quote['id'], ['number' => $quote['number'] ?? null]);
            Session::flash('success', "Quote {$quote['number']} created and emailed.");
        } else {
            Session::flash('success', "Quote {$quote['number']} created as a draft.");
        }

        return $this->redirect(route('admin.quotes.show', ['id' => $quote['id']]));
    }

    public function show(Request $request, string $id): Response
    {
        $this->guard();
        $quote = Quote::findOrFail($id);

        return $this->view('admin.quotes.show', [
            'title'   => 'Quote ' . $quote['number'],
            'quote'   => $quote,
            'client'  => Client::find($quote['client_id']),
            'items'   => Quote::items($id),
            'invoice' => $quote['converted_invoice_id'] ? Invoice::find($quote['converted_invoice_id']) : null,
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $this->guard();
        $quote = Quote::findOrFail($id);

        if (! $this->isEditable($quote)) {
            Session::flash('error', 'Accepted or declined quotes cannot be edited.');

            return $this->redirect(route('admin.quotes.show', ['id' => $id]));
        }

        return $this->view('admin.quotes.form', [
            'title'    => 'Edit ' . $quote['number'],
            'quote'    => $quote,
            'items'    => Quote::items($id),
            'clients'  => Client::query()->orderBy('business_name')->get(),
            'services' => Service::active(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $this->guard();
        $quote = Quote::findOrFail($id);

        // An accepted quote is the evidence of what the client agreed to — it is
        // frozen once it has become an invoice.
        if (! $this->isEditable($quote)) {
            $this->abort(403, 'This quote can no longer be edited.');
        }

        $data = $this->validate($request, [
            'client_id'  => 'required|exists:clients,id',
            'issue_date' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'notes'      => 'nullable|max:2000',
            'terms'      => 'nullable|max:5000',
            'discount'   => 'nullable|numeric|min:0',
            'discount_label' => 'nullable|max:160',
        ]);

        $items = $this->parseItems($request);
        if ($items === []) {
            Session::flash('error', 'Add at least one line item.');

            return $this->back();
        }

        $data['discount_cents'] = Money::fromDollars($request->input('discount', 0))->minorUnits;

        $this->quotes->update($id, $data, $items);
        AuditLog::record('quote.updated', 'quote', $id, ['number' => $quote['number'] ?? null]);
        Session::flash('success', 'Quote updated.');

        return $this->redirect(route('admin.quotes.show', ['id' => $id]));
    }

    public function send(Request $request, string $id): Response
    {
        $this->guard();
        $quote = Quote::findOrFail($id);

        if (Quote::isConverted($quote)) {
            Session::flash('error', 'This quote has already been accepted.');

            return $this->redirect(route('admin.quotes.show', ['id' => $id]));
        }

        $this->quotes->send($id);
        AuditLog::record('quote.sent', 'quote', $id, ['number' => $quote['number'] ?? null]);
        Session::flash('success', "Quote {$quote['number']} emailed to the client.");

        return $this->redirect(route('admin.quotes.show', ['id' => $id]));
    }

    public function pdf(Request $request, string $id): Response
    {
        $this->guard();
        $quote = Quote::findOrFail($id);

        return Response::file((new QuotePdf())->render($id), $quote['number'] . '.pdf', 'application/pdf');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->guard();
        $this->authorize(Auth::isAdmin(), 'Only an administrator can delete quotes.');
        $quote = Quote::findOrFail($id);

        Quote::deleteById($id);
        AuditLog::record('quote.deleted', 'quote', $id, ['number' => $quote['number'] ?? null]);
        Session::flash('status', 'Quote deleted.');

        return $this->redirect(route('admin.quotes.index'));
    }

    /** A quote is only editable while it is still a draft or an unactioned send. */
    protected function isEditable(array $quote): bool
    {
        return in_array($quote['status'], [Quote::STATUS_DRAFT, Quote::STATUS_SENT], true)
            && ! Quote::isConverted($quote);
    }

    protected function guard(): void
    {
        if (! Features::enabled('quotes')) {
            $this->abort(404, 'Quotes are not enabled.');
        }
    }

    /** @return array<int,array<string,mixed>> */
    protected function parseItems(Request $request): array
    {
        $rows = $request->input('items', []);
        if (! is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $description = trim((string) ($row['description'] ?? ''));
            $unitCents = Money::fromDollars($row['unit_price'] ?? 0)->minorUnits;
            if ($description === '' && $unitCents === 0) {
                continue;
            }
            $items[] = [
                'description'      => $description,
                'quantity'         => max(1, (int) ($row['quantity'] ?? 1)),
                'unit_price_cents' => $unitCents,
                'service_id'       => ! empty($row['service_id']) ? (int) $row['service_id'] : null,
            ];
        }

        return $items;
    }
}
