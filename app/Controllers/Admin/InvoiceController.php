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
use App\Services\Invoices\InvoicePdf;
use App\Services\Invoices\InvoiceService;
use App\Services\Payments\PaymentManager;
use App\Support\Money;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoices = new InvoiceService())
    {
    }

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $query = Invoice::query()->orderBy('id', 'desc');
        if ($status !== '' && isset(Invoice::STATUSES[$status])) {
            $query->where('status', $status);
        }

        $page = $request->integer('page', 1);
        $result = $query->paginate(20, $page);

        return $this->view('admin.invoices.index', [
            'title'        => 'Invoices',
            'result'       => $result,
            'status'       => $status,
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.invoices.form', [
            'title'      => 'New invoice',
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
        Session::flash('success', "Invoice {$invoice['number']} emailed to the client.");

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    public function recordPayment(Request $request, string $id): Response
    {
        $invoice = Invoice::findOrFail($id);

        $data = $this->validate($request, [
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:payid,payoneer,manual',
            'reference' => 'nullable|max:120',
            'paid_at'   => 'nullable|date',
        ]);

        $this->invoices->recordPayment(
            $id,
            Money::fromDollars($data['amount'], $invoice['currency'])->minorUnits,
            $data['method'],
            $data['reference'] ?? null,
            $data['paid_at'] ?: today(),
            Auth::id()
        );

        Session::flash('success', 'Payment recorded.');

        return $this->redirect(route('admin.invoices.show', ['id' => $id]));
    }

    public function void(Request $request, string $id): Response
    {
        Invoice::findOrFail($id);
        $this->invoices->void($id);
        Session::flash('status', 'Invoice voided.');

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
