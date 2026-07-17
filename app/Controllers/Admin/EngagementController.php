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

        // Stamps a JOB- reference and auto-creates the delivery board card.
        (new \App\Services\Projects\ProjectService())->createEngagement($data);
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
            // started_at existed in the table but no form or validator ever exposed
            // it, so the start date was stamped at creation and frozen forever.
            'started_at'        => 'nullable|date',
            'ends_at'           => 'nullable|date',
        ]);

        $recurring = $data['billing_type'] === Service::BILLING_RECURRING;

        $started = trim((string) ($data['started_at'] ?? ''));
        $ends = trim((string) ($data['ends_at'] ?? ''));

        // An engagement that ends before it starts is a typo, not a business rule.
        if ($started !== '' && $ends !== '' && $ends < $started) {
            throw new \App\Core\Exceptions\ValidationException([
                'ends_at' => 'The end date is before the start date.',
            ]);
        }

        $attrs = [
            'label'             => $data['label'],
            'service_id'        => ! empty($data['service_id']) ? (int) $data['service_id'] : null,
            'billing_type'      => $data['billing_type'],
            'interval'          => $recurring ? ($data['interval'] ?: Service::INTERVAL_MONTHLY) : null,
            'price_cents'       => Money::fromDollars($data['price'])->minorUnits,
            'currency'          => config('company.currency', 'AUD'),
            'status'            => $data['status'],
            'next_invoice_date' => $recurring ? ($data['next_invoice_date'] ?: date('Y-m-d', strtotime('+1 month'))) : null,
            // Blank clears the end date (back to open-ended) rather than being
            // ignored, so a date set by mistake can actually be removed.
            'ends_at'           => $ends !== '' ? $ends : null,
        ];

        // Only overwrite started_at when a date was supplied — an empty field must not
        // wipe the real start date of an existing engagement.
        if ($started !== '') {
            $attrs['started_at'] = $started;
        }

        return $attrs;
    }
}
