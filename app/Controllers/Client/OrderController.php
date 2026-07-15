<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Invoices\InvoiceService;
use App\Services\Projects\ProjectService;
use App\Support\Gst;
use App\Support\Money;

/**
 * Self-service ordering for clients. Placing an order automatically creates the
 * engagement (the client's active service/project) AND a first tax invoice they
 * can pay straight away — no staff step required. The catalogue and pricing come
 * from the same Service rows the public site and admin use.
 */
class OrderController extends Controller
{
    /** In-portal storefront: every active package grouped by service line. */
    public function index(Request $request): Response
    {
        return $this->view('client.order.index', [
            'title'    => 'Order a Service',
            'packages' => $this->packages(),
        ]);
    }

    /** Confirm screen for a single package before it's ordered. */
    public function show(Request $request, string $service): Response
    {
        $svc = $this->orderableService($service);
        $total = new Money((int) $svc['price_cents'], $svc['currency']);

        return $this->view('client.order.show', [
            'title'      => 'Confirm Order',
            'service'    => $svc,
            'line'       => ServiceCategory::find($svc['category_id']),
            'total'      => $total,
            'gst'        => Gst::component($total),
            'recurring'  => $svc['billing_type'] === Service::BILLING_RECURRING,
        ]);
    }

    /** Place the order: create the engagement + a payable invoice atomically. */
    public function place(Request $request, string $service): Response
    {
        $svc = $this->orderableService($service);

        $clientId = Auth::clientId();
        if (! $clientId) {
            $this->flash('error', 'Your login is not linked to a client account yet. Please contact us and we\'ll set it up.');

            return $this->redirectRoute('portal.dashboard');
        }

        $invoices = new InvoiceService();

        $invoice = Database::instance()->transaction(function () use ($svc, $clientId, $invoices) {
            $recurring = $svc['billing_type'] === Service::BILLING_RECURRING;

            // Creates the engagement, stamps a JOB- reference and drops a card on
            // the matching delivery board automatically.
            (new ProjectService())->createEngagement([
                'client_id'         => $clientId,
                'service_id'        => $svc['id'],
                'label'             => $svc['name'],
                'billing_type'      => $svc['billing_type'],
                'interval'          => $svc['interval'],
                'price_cents'       => (int) $svc['price_cents'],
                'currency'          => $svc['currency'],
                'status'            => ClientService::STATUS_ACTIVE,
                'started_at'        => today(),
                'next_invoice_date' => $recurring
                    ? date('Y-m-d', strtotime('+' . Service::intervalMonths($svc['interval']) . ' months'))
                    : null,
            ]);

            $label = $recurring
                ? $svc['name'] . ' — ' . (Service::INTERVALS[$svc['interval']] ?? 'Recurring') . ' subscription (first period)'
                : $svc['name'];

            return $invoices->create([
                'client_id'  => $clientId,
                'status'     => Invoice::STATUS_SENT,
                'issue_date' => today(),
                'notes'      => 'Ordered online via the client portal. Thanks for choosing ' . config('company.legal_name', 'OptiTide') . '.',
            ], [
                ['description' => $label, 'quantity' => 1, 'unit_price_cents' => (int) $svc['price_cents'], 'service_id' => $svc['id']],
            ]);
        });

        $this->flash('success', 'Order confirmed — invoice ' . $invoice['number'] . ' is ready. Complete payment below to get started.');

        return $this->redirectRoute('portal.invoices.show', ['id' => $invoice['id']]);
    }

    /**
     * Load an active, priced service or 404. Custom/quote plans (price 0) are not
     * self-orderable — they go through the contact/quote flow instead.
     */
    protected function orderableService(string $id): array
    {
        $service = Service::find($id);

        if (! $service || (int) ($service['active'] ?? 0) !== 1 || (int) $service['price_cents'] <= 0) {
            $this->abort(404, 'That package is not available to order online.');
        }

        return $service;
    }

    /** Active packages grouped by service line (custom/quote plans included). */
    protected function packages(): array
    {
        $services = Service::active();
        $groups = [];

        foreach (ServiceCategory::ordered() as $line) {
            $plans = array_values(array_filter(
                $services,
                fn ($s) => (string) $s['category_id'] === (string) $line['id']
            ));
            usort($plans, function ($a, $b) {
                $pa = (int) $a['price_cents'];
                $pb = (int) $b['price_cents'];
                if (($pa === 0) !== ($pb === 0)) {
                    return $pa === 0 ? 1 : -1;
                }

                return $pa <=> $pb;
            });
            if ($plans !== []) {
                $groups[] = ['line' => $line, 'plans' => $plans];
            }
        }

        return $groups;
    }
}
