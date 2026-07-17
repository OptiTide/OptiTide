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
use App\Models\Quote;
use App\Models\Service;
use App\Models\Ticket;
use App\Services\Audit\AuditLog;
use App\Services\Billing\CreditService;
use App\Support\Features;
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
        $apps = ClientApp::forClient($id);

        return $this->view('admin.clients.show', [
            'title'            => $client['business_name'],
            'client'           => $client,
            'engagements'      => ClientService::forClient($id),
            'invoices'         => Invoice::query()->where('client_id', $id)->orderBy('id', 'desc')->get(),
            'services'         => Service::active(),
            'intakes'          => \App\Models\ProjectIntake::forClient($id),
            'credit_txns'      => CreditTransaction::forClient($id),
            'apps'             => $apps,
            'app_engagements'  => ClientApp::engagementMap($apps),
            // Both already existed per-client but were only reachable from their
            // own top-level list — you had to know the client's name and search
            // for it. Empty arrays when the feature is off so the view can't link
            // to a screen that 404s.
            'quotes'           => Features::enabled('quotes') ? Quote::forClient($id) : [],
            'tickets'          => Ticket::forClient($id),
        ]);
    }

    public function storeApp(Request $request, string $id): Response
    {
        Client::findOrFail($id);

        ClientApp::create($this->appData($request, $id) + [
            'client_id' => $id,
            'status'    => 'live',
        ]);
        AuditLog::record('client.app_linked', 'client', $id);
        Session::flash('success', 'App linked to client.');

        return $this->redirect(route('admin.clients.show', ['id' => $id]) . '#apps');
    }

    public function updateApp(Request $request, string $id): Response
    {
        $app = ClientApp::findOrFail($id);
        $data = $this->appData($request, (string) $app['client_id']);
        ClientApp::updateById($id, $data);
        AuditLog::record('client.app_updated', 'client', $app['client_id'], [
            'app_id'       => $id,
            'billing_type' => $data['billing_type'],
        ]);
        Session::flash('success', 'App updated.');

        return $this->redirect(route('admin.clients.show', ['id' => $app['client_id']]) . '#apps');
    }

    /**
     * Validated app fields, billing included. An app never bills itself: a
     * recurring app must name the engagement RecurringBiller already invoices,
     * and only a one-off carries its own price. Anything else is dropped to
     * NULL so a stale figure can't survive a billing-type change and show the
     * client a price nothing will invoice.
     */
    protected function appData(Request $request, string $clientId): array
    {
        $data = $this->validate($request, [
            'name'          => 'required|max:120',
            'url'           => 'required|url|max:300',
            'environment'   => 'nullable|max:40',
            'billing_type'  => 'required|in:none,one_off,recurring',
            'price'         => 'nullable|numeric|min:0',
            'engagement_id' => 'nullable|exists:client_services,id',
        ], ['engagement_id' => 'Engagement', 'billing_type' => 'Billing']);

        $type = $data['billing_type'];
        $engagementId = ! empty($data['engagement_id']) ? (int) $data['engagement_id'] : null;

        // The engagement must belong to this client — otherwise a tampered id
        // would show one client another client's price.
        if ($engagementId !== null) {
            $engagement = ClientService::find($engagementId);
            if (! $engagement || (string) $engagement['client_id'] !== $clientId) {
                $this->abort(404, 'Engagement not found.');
            }
        }

        if ($type === ClientApp::BILLING_RECURRING && $engagementId === null) {
            $this->abort(422, 'Pick the engagement this app is billed under, or set it to not billed.');
        }

        return [
            'name'          => $data['name'],
            'url'           => $data['url'],
            'environment'   => $data['environment'] ?: null,
            'billing_type'  => $type,
            'price_cents'   => $type === ClientApp::BILLING_ONE_OFF
                ? Money::fromDollars($data['price'] ?? 0)->minorUnits
                : null,
            'currency'      => config('company.currency', 'AUD'),
            // client_apps.interval is intentionally never written — the linked
            // engagement owns the interval, so the column stays NULL.
            'engagement_id' => $type === ClientApp::BILLING_RECURRING ? $engagementId : null,
        ];
    }

    public function destroyApp(Request $request, string $id): Response
    {
        $app = ClientApp::findOrFail($id);
        ClientApp::deleteById($id);
        AuditLog::record('client.app_unlinked', 'client', $app['client_id'], ['app_id' => $id]);
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
        AuditLog::record('credit.adjusted', 'client', $id, ['amount_cents' => $cents, 'reason' => $data['reason'] ?? null]);
        Session::flash('success', 'Account credit updated.');

        return $this->redirect(route('admin.clients.show', ['id' => $id]) . '#credit');
    }

    /** Manually grant or deduct API credit (e.g. comped credit or a correction). */
    public function adjustApiCredit(Request $request, string $id): Response
    {
        Client::findOrFail($id);
        $data = $this->validate($request, [
            'amount' => 'required|numeric',
            'reason' => 'nullable|max:200',
        ]);

        $cents = Money::fromDollars(abs((float) $data['amount']), config('company.currency', 'AUD'))->minorUnits;
        if ((float) $data['amount'] < 0) {
            $cents = -$cents;
        }

        (new \App\Services\Api\ApiCreditService())->adjust($id, $cents, $data['reason'] ?? null, Auth::id());
        Session::flash('success', 'API credit updated.');

        return $this->redirect(route('admin.clients.show', ['id' => $id]) . '#apicredit');
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
        AuditLog::record('client.archived', 'client', $id);
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
