<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Services\Audit\AuditLog;
use App\Services\Invoices\InvoicePdf;
use App\Services\Invoices\InvoiceService;
use App\Services\Payments\PaymentManager;
use App\Support\Money;

class InvoiceController extends Controller
{
    /**
     * Pseudo-status for the list filter: everything issued but not settled
     * (sent + overdue). It is what "Outstanding" on the dashboard counts, and no
     * single real status matches it — without this the headline money figure
     * could only link to a list that disagreed with it.
     */
    public const FILTER_OUTSTANDING = 'outstanding';

    public function __construct(protected InvoiceService $invoices = new InvoiceService())
    {
    }

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        $clientNames = array_column(Client::all(), 'business_name', 'id');

        $query = Invoice::query()->orderBy('id', 'desc');
        if ($status === self::FILTER_OUTSTANDING) {
            $query->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE]);
        } elseif ($status !== '' && isset(Invoice::STATUSES[$status])) {
            $query->where('status', $status);
        } else {
            // Normalise an unknown value to "All" so the view can't highlight a
            // filter button that isn't actually applied.
            $status = '';
        }

        if ($search !== '') {
            // Match the invoice number OR any client whose business name contains
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

        $page = $request->integer('page', 1);
        $result = $query->paginate(20, $page);

        return $this->view('admin.invoices.index', [
            'title'        => 'Invoices',
            'result'       => $result,
            'status'       => $status,
            'search'       => $search,
            'client_names' => $clientNames,
            'stats'        => $this->indexStats(),
        ]);
    }

    /**
     * KPI figures for the invoices list header.
     *
     * @return array<string,mixed>
     */
    protected function indexStats(): array
    {
        $currency = config('company.currency', 'AUD');
        $outstandingStatuses = [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE];

        $billed = Invoice::query()->whereIn('status', $outstandingStatuses)->sum('total_cents');
        $collected = Invoice::query()->whereIn('status', $outstandingStatuses)->sum('amount_paid_cents');

        $monthStart = date('Y-m-01');
        $paidThisMonth = Payment::query()->where('paid_at', '>=', $monthStart)->sum('amount_cents');

        return [
            'outstanding'     => money(max(0, $billed - $collected), $currency),
            'paid_this_month' => money($paidThisMonth, $currency),
            'draft_count'     => Invoice::query()->where('status', Invoice::STATUS_DRAFT)->count(),
            'overdue_count'   => Invoice::query()->where('status', Invoice::STATUS_OVERDUE)->count(),
        ];
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.invoices.form', [
            'title'      => 'New Invoice',
            'invoice'    => null,
            'items'      => [],
            'clients'    => Client::query()->orderBy('business_name')->get(),
            'services'   => Service::active(),
            'preselect'  => $request->query('client_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'client_id'  => 'required|exists:clients,id',
            'issue_date' => 'nullable|date',
            'due_date'   => 'nullable|date',
            'notes'      => 'nullable|max:2000',
            'payoneer_link' => 'nullable|url',
        ]);

        $items = $this->parseItems($request);
        if ($items === []) {
            Session::flash('error', 'Add at least one line item.');

            return $this->back();
        }

        $invoice = $this->invoices->create([
            'client_id'     => $data['client_id'],
            'issue_date'    => $data['issue_date'] ?: today(),
            'due_date'      => $data['due_date'] ?: date('Y-m-d', strtotime('+14 days')),
            'notes'         => $data['notes'] ?? null,
            'payoneer_link' => $data['payoneer_link'] ?? null,
            'status'        => Invoice::STATUS_DRAFT,
        ], $items);

        AuditLog::record('invoice.created', 'invoice', $invoice['id'], ['number' => $invoice['number'] ?? null]);

        if ($request->input('action') === 'save_send') {
            $this->invoices->send($invoice['id']);
            Session::flash('success', "Invoice {$invoice['number']} created and emailed.");
        } else {
            Session::flash('success', "Invoice {$invoice['number']} created as a draft.");
        }

        return $this->redirect(route('admin.invoices.show', ['id' => $invoice['id']]));
    }

    public function show(Request $request, string $id): Response
    {
        $invoice = Invoice::findOrFail($id);

        return $this->view('admin.invoices.show', [
            'title'        => 'Invoice ' . $invoice['number'],
            'invoice'      => $invoice,
            'client'       => Client::find($invoice['client_id']),
            'items'        => Invoice::items($id),
            'payments'     => Payment::forInvoice($id),
            'instructions' => (new PaymentManager())->instructionsFor($invoice),
            'methods'      => (new PaymentManager())->methods(),
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $invoice = Invoice::findOrFail($id);

        if (in_array($invoice['status'], [Invoice::STATUS_PAID, Invoice::STATUS_VOID], true)) {
            Session::flash('error', 'Paid or void invoices cannot be edited.');

            return $this->redirect(route('admin.invoices.show', ['id' => $id]));
        }

        return $this->view('admin.invoices.form', [
            'title'    => 'Edit ' . $invoice['number'],
            'invoice'  => $invoice,
            'items'    => Invoice::items($id),
            'clients'  => Client::query()->orderBy('business_name')->get(),
            'services' => Service::active(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $invoice = Invoice::findOrFail($id);
        if (in_array($invoice['status'], [Invoice::STATUS_PAID, Invoice::STATUS_VOID], true)) {
            $this->abort(403, 'This invoice can no longer be edited.');
        }

        $data = $this->validate($request, [
            'client_id'  => 'required|exists:clients,id',
            'issue_date' => 'nullable|date',
            'due_date'   => 'nullable|date',
            'notes'      => 'nullable|max:2000',
            'payoneer_link' => 'nullable|url',
        ]);

        $items = $this->parseItems($request);
        if ($items === []) {
            Session::flash('error', 'Add at least one line item.');

            return $this->back();
        }

        $this->invoices->update($id, $data, $items);
        Session::flash('success', 'Invoice updated.');

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    public function send(Request $request, string $id): Response
    {
        $invoice = Invoice::findOrFail($id);
        $this->invoices->send($id);
        AuditLog::record('invoice.sent', 'invoice', $invoice['id'] ?? $id, ['number' => $invoice['number'] ?? null]);
        Session::flash('success', "Invoice {$invoice['number']} emailed to the client.");

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    public function recordPayment(Request $request, string $id): Response
    {
        $invoice = Invoice::findOrFail($id);

        $data = $this->validate($request, [
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:payid,skrill,paypal,payoneer,manual',
            'reference' => 'nullable|max:120',
            'paid_at'   => 'nullable|date',
        ]);

        $payment = $this->invoices->recordPayment(
            $id,
            Money::fromDollars($data['amount'], $invoice['currency'])->minorUnits,
            $data['method'],
            $data['reference'] ?? null,
            $data['paid_at'] ?: today(),
            Auth::id()
        );

        // E-mail the client a receipt.
        $client = Client::find($invoice['client_id']);
        if ($client && ! empty($client['email'])) {
            \App\Services\Mail\Mail::to($client['email'], $client['business_name'])
                ->subject('Payment received — invoice ' . $invoice['number'])
                ->view('emails.payment-receipt', [
                    'invoice' => Invoice::find($id),
                    'payment' => $payment,
                    'client'  => $client,
                ])
                ->send();
        }

        AuditLog::record('invoice.payment_recorded', 'invoice', $id, []);
        Session::flash('success', 'Payment recorded and receipt e-mailed.');

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    public function void(Request $request, string $id): Response
    {
        Invoice::findOrFail($id);
        $this->invoices->void($id);
        AuditLog::record('invoice.voided', 'invoice', $id);
        Session::flash('status', 'Invoice voided.');

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    /** Staff (non-admin) can ask for a late fee to be waived; an admin approves. */
    public function requestLateFeeWaiver(Request $request, string $id): Response
    {
        Invoice::findOrFail($id);
        (new \App\Services\Billing\LateFeeService())->requestWaiver($id, Auth::id());
        Session::flash('status', 'Waiver requested — an administrator will review it.');

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    /** Admin approval that removes the late fee (the "admin will approve" step). */
    public function waiveLateFee(Request $request, string $id): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only an administrator can waive a late fee.');
        Invoice::findOrFail($id);
        (new \App\Services\Billing\LateFeeService())->waive($id, trim((string) $request->input('reason')) ?: null);
        Session::flash('success', 'Late fee waived and removed from the invoice.');

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    public function pdf(Request $request, string $id): Response
    {
        $invoice = Invoice::findOrFail($id);

        return Response::file((new InvoicePdf())->render($id), $invoice['number'] . '.pdf', 'application/pdf');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only an administrator can delete invoices.');
        Invoice::findOrFail($id);
        Invoice::deleteById($id);
        AuditLog::record('invoice.deleted', 'invoice', $id);
        Session::flash('status', 'Invoice deleted.');

        return $this->redirect(route('admin.invoices.index'));
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
