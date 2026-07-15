<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\Api\ApiCreditService;
use App\Services\Api\ApiKeyService;
use App\Services\Audit\AuditLog;
use App\Services\Invoices\InvoiceService;

class ApiCreditController extends Controller
{
    public function index(Request $request): Response
    {
        $client = Client::findOrFail(Auth::clientId());
        $credits = new ApiCreditService();

        return $this->view('client.api.index', [
            'title'       => 'API Credits',
            'client'      => $client,
            'balance'     => $credits->balance($client['id']),
            'ledger'      => $credits->ledger($client['id'], 30),
            'hasKey'      => ApiKeyService::hasKey($client),
            'maskedKey'   => ApiKeyService::masked($client),
            'newKey'      => Session::pull('_new_api_key'), // shown once, just after issue
            'packs'       => (array) config('api_credits.packs', []),
            'models'      => (array) config('api_credits.models', []),
            'pricing'     => (array) config('api_credits.pricing', []),
            'apiBase'     => rtrim(config('app.url', ''), '/') . '/api/v1',
        ]);
    }

    /** Issue or rotate the client's API key (plaintext shown once). */
    public function issueKey(Request $request): Response
    {
        $clientId = Auth::clientId();
        $rotating = ApiKeyService::hasKey(Client::findOrFail($clientId));

        $plain = (new ApiKeyService())->issue($clientId);
        Session::flash('_new_api_key', $plain);
        AuditLog::record($rotating ? 'api_key.rotated' : 'api_key.issued', 'client', $clientId);
        Session::flash('success', $rotating ? 'A new API key has been generated — your old key no longer works.' : 'Your API key is ready.');

        return $this->redirect(route('portal.api.index'));
    }

    public function revokeKey(Request $request): Response
    {
        $clientId = Auth::clientId();
        (new ApiKeyService())->revoke($clientId);
        AuditLog::record('api_key.revoked', 'client', $clientId);
        Session::flash('status', 'Your API key has been revoked.');

        return $this->redirect(route('portal.api.index'));
    }

    /** Buy credit: raise a payable invoice tagged as a top-up. */
    public function buy(Request $request): Response
    {
        $clientId = Auth::clientId();

        $min = (int) config('api_credits.min_topup_cents', 1000);
        $max = (int) config('api_credits.max_topup_cents', 500000);

        // Amount comes in dollars (a pack button or the custom field).
        $dollars = (float) $request->input('amount', 0);
        $cents = (int) round($dollars * 100);
        if ($cents < $min || $cents > $max) {
            Session::flash('error', 'Please choose an amount between $' . number_format($min / 100) . ' and $' . number_format($max / 100) . '.');

            return $this->redirect(route('portal.api.index'));
        }

        $invoice = (new InvoiceService())->create([
            'client_id'              => $clientId,
            'status'                 => Invoice::STATUS_SENT,
            'currency'               => config('company.currency', 'AUD'),
            'notes'                  => 'OptiTide API credit top-up. Credit is added automatically once this invoice is paid.',
            'api_credit_topup_cents' => $cents,
        ], [[
            'description'      => 'OptiTide API Credits ($' . number_format($cents / 100, 2) . ')',
            'quantity'         => 1,
            'unit_price_cents' => $cents,
        ]]);

        AuditLog::record('api_credit.purchase_invoiced', 'invoice', $invoice['id'], ['amount_cents' => $cents]);
        Session::flash('success', 'Invoice ' . $invoice['number'] . ' is ready — pay it and your credit is added automatically.');

        return $this->redirect(route('portal.invoices.show', ['id' => $invoice['id']]));
    }
}
