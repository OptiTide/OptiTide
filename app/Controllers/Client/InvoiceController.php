<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\Invoices\InvoicePdf;
use App\Services\Payments\PaymentManager;
use App\Support\Money;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();
        $currency = config('company.currency', 'AUD');
        $status = (string) $request->query('status', '');

        $all = $clientId
            ? Invoice::query()->where('client_id', $clientId)
                ->where('status', '!=', Invoice::STATUS_DRAFT)
                ->orderBy('id', 'desc')->get()
            : [];

        $outstanding = 0;
        $paid = 0;
        foreach ($all as $invoice) {
            $paid += (int) $invoice['amount_paid_cents'];
            if (in_array($invoice['status'], [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE], true)) {
                $outstanding += (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
            }
        }

        $invoices = $status !== '' && isset(Invoice::STATUSES[$status])
            ? array_values(array_filter($all, fn ($i) => $i['status'] === $status))
            : $all;

        return $this->view('client.invoices.index', [
            'title'       => 'Invoices',
            'invoices'    => $invoices,
            'status'      => $status,
            'outstanding' => new Money($outstanding, $currency),
            'paid'        => new Money($paid, $currency),
            'count'       => count($all),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $invoice = $this->ownedInvoice($id);

        return $this->view('client.invoices.show', [
            'title'        => 'Invoice ' . $invoice['number'],
            'invoice'      => $invoice,
            'client'       => Client::find($invoice['client_id']),
            'items'        => Invoice::items($id),
            'instructions' => (new PaymentManager())->instructionsFor($invoice),
        ]);
    }

    public function pdf(Request $request, string $id): Response
    {
        $invoice = $this->ownedInvoice($id);

        return Response::file((new InvoicePdf())->render($id), $invoice['number'] . '.pdf', 'application/pdf');
    }

    /** Scope every lookup to the signed-in client (IDOR guard). */
    protected function ownedInvoice(string $id): array
    {
        $invoice = Invoice::findOrFail($id);

        if ((string) $invoice['client_id'] !== (string) Auth::clientId()
            || $invoice['status'] === Invoice::STATUS_DRAFT) {
            $this->abort(404, 'Invoice not found.');
        }

        return $invoice;
    }
}
