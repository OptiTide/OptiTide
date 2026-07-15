<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\Invoices\InvoicePdf;
use App\Services\Payments\PaymentManager;

/**
 * Public, token-addressed invoice pay page. The 48-char public_token is the
 * capability — no login required — so a client can pay straight from the email.
 * Void/draft invoices are never exposed here.
 */
class PayController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invoice = $this->resolve($token);
        $client = Client::find($invoice['client_id']);

        return $this->view('public.pay', [
            'title'        => 'Invoice ' . $invoice['number'],
            'invoice'      => $invoice,
            'client'       => $client,
            'items'        => Invoice::items($invoice['id']),
            'instructions' => (new PaymentManager())->instructionsFor($invoice),
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $invoice = $this->resolve($token);
        $pdf = (new InvoicePdf())->render($invoice['id']);

        return Response::file($pdf, $invoice['number'] . '.pdf', 'application/pdf');
    }

    protected function resolve(string $token): array
    {
        $invoice = Invoice::firstWhere('public_token', $token);

        if (! $invoice || in_array($invoice['status'], [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID], true)) {
            $this->abort(404, 'Invoice not found.');
        }

        return $invoice;
    }
}
