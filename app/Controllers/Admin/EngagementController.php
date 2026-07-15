<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Support\Money;

class EngagementController extends Controller
{
    public function store(Request $request, string $clientId): Response
    {
        $client = Client::findOrFail($clientId);
        $data = $this->engagementData($request);
        $data['client_id'] = $client['id'];

        ClientService::create($data);
        Session::flash('success', 'Service added to client.');

        return $this->redirect(route('admin.clients.show', ['id' => $client['id']]) . '#services');
    }

    public function update(Request $request, string $id): Response
    {
        $engagement = ClientService::findOrFail($id);
        ClientService::updateById($id, $this->engagementData($request));
        Session::flash('success', 'Service updated.');

        return $this->redirect(route('admin.clients.show', ['id' => $engagement['client_id']]) . '#services');
    }

    public function destroy(Request $request, string $id): Response
    {
        $engagement = ClientService::findOrFail($id);
        ClientService::deleteById($id);
        Session::flash('status', 'Service removed.');

        return $this->redirect(route('admin.clients.show', ['id' => $engagement['client_id']]) . '#services');
    }

    protected function engagementData(Request $request): array
    {
        $data = $this->validate($request, [
            'label'             => 'required|max:160',
            'service_id'        => 'nullable|exists:services,id',
            'billing_type'      => 'required|in:one_off,recurring',
            'interval'          => 'nullable|in:monthly,quarterly,yearly',
            'price'             => 'required|numeric|min:0',
            'status'            => 'required|in:active,paused,cancelled',
            'next_invoice_date' => 'nullable|date',
        ]);

        $recurring = $data['billing_type'] === Service::BILLING_RECURRING;

        return [
            'label'             => $data['label'],
            'service_id'        => ! empty($data['service_id']) ? (int) $data['service_id'] : null,
            'billing_type'      => $data['billing_type'],
            'interval'          => $recurring ? ($data['interval'] ?: Service::INTERVAL_MONTHLY) : null,
            'price_cents'       => Money::fromDollars($data['price'])->minorUnits,
            'currency'          => config('company.currency', 'AUD'),
            'status'            => $data['status'],
            'next_invoice_date' => $recurring ? ($data['next_invoice_date'] ?: date('Y-m-d', strtotime('+1 month'))) : null,
        ];
    }
}
