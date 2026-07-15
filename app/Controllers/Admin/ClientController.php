<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\ClientApp;
use App\Models\ClientService;
use App\Models\CreditTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Services\Billing\CreditService;
use App\Support\Money;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $status = (string) $request->query('status', '');
        if (! in_array($status, [Client::STATUS_ACTIVE, Client::STATUS_SUSPENDED, Client::STATUS_ARCHIVED], true)) {
            $status = '';
        }

        $query = Client::query()->orderBy('business_name');
        if ($search !== '') {
            $query->whereLike('business_name', "%$search%");
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        $clients = $query->get();
        $currency = config('company.currency', 'AUD');

        // Outstanding balance per client (unpaid issued invoices).
        $balances = [];
        foreach (Invoice::query()->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->get() as $invoice) {
            $balances[$invoice['client_id']] = ($balances[$invoice['client_id']] ?? 0)
                + (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
        }

        // Total paid per client (from recorded payments).
        $paid = [];
        foreach (Payment::query()->get() as $payment) {
            $paid[$payment['client_id']] = ($paid[$payment['client_id']] ?? 0)
                + (int) $payment['amount_cents'];
        }

        // Clients with at least one OVERDUE invoice (for the red flag).
        $overdue = [];
        foreach (Invoice::query()->where('status', Invoice::STATUS_OVERDUE)->get() as $invoice) {
            $overdue[$invoice['client_id']] = true;
        }

        // One-line summary reflecting the current filter.
        $outstandingTotal = 0;
        foreach ($clients as $client) {
            $outstandingTotal += (int) ($balances[$client['id']] ?? 0);
        }

        return $this->view('admin.clients.index', [
            'title'            => 'Clients',
            'clients'          => $clients,
            'balances'         => $balances,
            'paid'             => $paid,
            'currency'         => $currency,
            'search'           => $search,
            'status'           => $status,
            'overdue'          => $overdue,
            'outstandingTotal' => $outstandingTotal,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.clients.form', ['title' => 'New Client', 'client' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->clientData($request);
        $client = Client::create($data);
        Session::flash('success', 'Client created.');

        return $this->redirect(route('admin.clients.show', ['id' => $client['id']]));
    }

    public function show(Request $request, string $id): Response
    {
        $client = Client::findOrFail($id);

        return $this->view('admin.clients.show', [
            'title'       => $client['business_name'],
            'client'      => $client,
            'engagements' => ClientService::forClient($id),
            'invoices'    => Invoice::query()->where('client_id', $id)->orderBy('id', 'desc')->get(),
            'services'    => Service::active(),
            'intakes'     => \App\Models\ProjectIntake::forClient($id),
            'credit_txns' => CreditTransaction::forClient($id),
            'apps'        => ClientApp::forClient($id),
        ]);
    }

    public function storeApp(Request $request, string $id): Response
    {
        Client::findOrFail($id);
        $data = $this->validate($request, [
            'name'        => 'required|max:120',
            'url'         => 'required|url|max:300',
            'environment' => 'nullable|max:40',
        ]);

        ClientApp::create([
            'client_id'   => $id,
            'name'        => $data['name'],
            'url'         => $data['url'],
            'environment' => $data['environment'] ?: null,
            'status'      => 'live',
        ]);
        Session::flash('success', 'App linked to client.');

        return $this->redirect(route('admin.clients.show', ['id' => $id]) . '#apps');
    }

    public function destroyApp(Request $request, string $id): Response
    {
        $app = ClientApp::findOrFail($id);
        ClientApp::deleteById($id);
        Session::flash('status', 'App unlinked.');

        return $this->redirect(route('admin.clients.show', ['id' => $app['client_id']]) . '#apps');
    }

    public function addCredit(Request $request, string $id): Response
    {
        $client = Client::findOrFail($id);
        $data = $this->validate($request, [
            'amount' => 'required|numeric',
            'reason' => 'nullable|max:200',
        ]);

        $cents = Money::fromDollars(abs((float) $data['amount']), config('company.currency', 'AUD'))->minorUnits;
        if ((float) $data['amount'] < 0) {
            $cents = -$cents;
        }

        (new CreditService())->add($id, $cents, $cents >= 0 ? 'add' : 'adjust', $data['reason'] ?? null, Auth::id());
        Session::flash('success', 'Account credit updated.');

        return $this->redirect(route('admin.clients.show', ['id' => $id]) . '#credit');
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->view('admin.clients.form', [
            'title'  => 'Edit Client',
            'client' => Client::findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        Client::findOrFail($id);
        Client::updateById($id, $this->clientData($request, $id));
        Session::flash('success', 'Client updated.');

        return $this->redirect(route('admin.clients.show', ['id' => $id]));
    }

    public function destroy(Request $request, string $id): Response
    {
        Client::findOrFail($id);
        // Archive rather than hard-delete so invoice history is never lost.
        Client::updateById($id, ['status' => Client::STATUS_ARCHIVED]);
        Session::flash('status', 'Client archived.');

        return $this->redirect(route('admin.clients.index'));
    }

    protected function clientData(Request $request, ?string $id = null): array
    {
        $ignore = $id ? ",$id" : '';

        $data = $this->validate($request, [
            'business_name'    => 'required|max:160',
            'contact_name'     => 'nullable|max:120',
            'email'            => "nullable|email|unique:clients,email$ignore",
            'phone'            => 'nullable|max:40',
            'abn'              => 'nullable|max:20',
            'address_line1'    => 'nullable|max:180',
            'address_locality' => 'nullable|max:120',
            'address_region'   => 'nullable|max:60',
            'address_postcode' => 'nullable|max:12',
            'status'           => 'nullable|in:active,archived',
        ], ['business_name' => 'Business name', 'abn' => 'ABN']);

        // Let the NOT NULL default stand when no status was submitted.
        if (($data['status'] ?? null) === null) {
            unset($data['status']);
        }

        return $data;
    }
}
