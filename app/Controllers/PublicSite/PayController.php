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

    /** Hand off to Skrill Quick Checkout via an auto-submitting form. */
    public function skrill(Request $request, string $token): Response
    {
        $invoice = $this->resolve($token);
        $email = trim((string) config('payments.gateways.skrill.merchant_email', ''));
        if ($email === '') {
            $this->abort(404, 'Skrill is not configured.');
        }

        $balance = max(0, (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents']);
        $params = [
            'pay_to_email'         => $email,
            'transaction_id'       => (string) $invoice['number'],
            'amount'               => number_format($balance / 100, 2, '.', ''),
            'currency'             => (string) $invoice['currency'],
            'language'             => 'EN',
            'recipient_description' => (string) config('company.legal_name', 'OptiTide'),
            'detail1_description'  => 'Invoice',
            'detail1_text'         => (string) $invoice['number'],
            'return_url'           => url('pay/' . $token),
            'cancel_url'           => url('pay/' . $token),
        ];

        $inputs = '';
        foreach ($params as $k => $v) {
            $inputs .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
        }

        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Redirecting to Skrill…</title>'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"></head>'
            . '<body onload="document.forms[0].submit()" style="font-family:system-ui,sans-serif;text-align:center;padding:4rem 1rem;color:#0D1530">'
            . '<p>Redirecting you to Skrill to complete your payment…</p>'
            . '<form action="https://pay.skrill.com" method="post">' . $inputs
            . '<noscript><button type="submit" style="background:#FF6A00;color:#fff;border:0;border-radius:8px;padding:.7rem 1.4rem;font-size:1rem">Continue to Skrill</button></noscript>'
            . '</form></body></html>';

        return Response::make($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
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
