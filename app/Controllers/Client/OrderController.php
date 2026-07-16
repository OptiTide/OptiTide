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
use App\Services\Billing\DiscountService;
use App\Services\Billing\InstallmentService;
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
        $category = ($svc['category_id'] ?? null) ? (ServiceCategory::find($svc['category_id'])['slug'] ?? null) : null;

        // Show any automatic sale up front, so the price on this screen matches
        // what they'll actually be charged.
        $sale = (new DiscountService())->saleForService($svc);
        $saleAmount = $sale ? (new DiscountService())->amountFor($sale, (int) $svc['price_cents']) : 0;

        return $this->view('client.order.show', [
            'title'      => 'Confirm Order',
            'service'    => $svc,
            'line'       => ServiceCategory::find($svc['category_id']),
            'total'      => $total,
            'gst'        => Gst::component($total),
            'recurring'  => $svc['billing_type'] === Service::BILLING_RECURRING,
            'plans'      => (new InstallmentService())->plansFor($category),
            'sale'       => $sale,
            'saleAmount' => new Money($saleAmount, $svc['currency']),
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

        $category = ($svc['category_id'] ?? null) ? (ServiceCategory::find($svc['category_id'])['slug'] ?? null) : null;
        $installments = new InstallmentService();
        $plan = $installments->resolvePlan($category, (string) $request->input('plan', ''));
        $schedule = $installments->schedule((int) $svc['price_cents'], $plan);
        $yearly = $schedule['months'] >= 12;
        $recurring = $svc['billing_type'] === Service::BILLING_RECURRING;

        // Resolve the discount against what's ACTUALLY being charged (yearly
        // hosting bills 12 months), not the headline monthly price.
        $discounts = new DiscountService();
        $chargeCents = array_sum(array_column($schedule['rows'], 'amount_cents'));
        $resolved = $discounts->resolveForService(
            (string) $request->input('discount_code', ''),
            $svc,
            $clientId,
            $chargeCents
        );

        if ($resolved['error'] !== null) {
            $this->flash('error', $resolved['error']);

            return $this->redirect(route('portal.order.show', ['service' => $svc['id']]));
        }

        // Engagement attributes (yearly hosting bills 12 months as one yearly cycle).
        $interval = $svc['interval'];
        $engPrice = (int) $svc['price_cents'];
        $next = $recurring ? date('Y-m-d', strtotime('+' . Service::intervalMonths($svc['interval']) . ' months')) : null;
        if ($recurring && $yearly) {
            $interval = Service::INTERVAL_YEARLY;
            $engPrice = (int) $svc['price_cents'] * 12;
            $next = date('Y-m-d', strtotime('+12 months'));
        }
        $engAttrs = [
            'client_id'         => $clientId,
            'service_id'        => $svc['id'],
            'label'             => $svc['name'],
            'billing_type'      => $svc['billing_type'],
            'interval'          => $interval,
            'price_cents'       => $engPrice,
            'currency'          => $svc['currency'],
            'status'            => ClientService::STATUS_ACTIVE,
            'started_at'        => today(),
            'next_invoice_date' => $next,
        ];

        // Instalment / hardship plans are approval-gated: create the engagement and
        // a pending request, but issue NO invoices until an admin approves.
        if ($installments->requiresApproval($category, $plan)) {
            $engagement = (new ProjectService())->createEngagement($engAttrs);
            \App\Models\InstallmentRequest::create([
                'client_id'     => $clientId,
                'service_id'    => $svc['id'],
                'engagement_id' => $engagement['id'],
                'category'      => $category,
                'plan_key'      => $plan['key'],
                'price_cents'   => (int) $svc['price_cents'],
                'status'        => \App\Models\InstallmentRequest::STATUS_PENDING,
            ]);

            if (\App\Models\ProjectIntake::questionsFor($category)) {
                $this->flash('success', 'Order received! Tell us about your project — we\'ll review your payment plan and be in touch to confirm it.');

                return $this->redirect(route('portal.intake.show', ['engagement' => $engagement['id']]));
            }

            $this->flash('success', 'Thanks! Your payment plan request has been submitted for approval — we\'ll confirm it shortly.');

            return $this->redirectRoute('portal.services');
        }

        $invoices = new InvoiceService();

        $result = Database::instance()->transaction(function () use ($svc, $clientId, $invoices, $discounts, $resolved, $recurring, $yearly, $schedule, $engAttrs) {
            $engagement = (new ProjectService())->createEngagement($engAttrs);

            // Split the discount across the scheduled invoices in proportion to
            // each, so the parts always sum to exactly the discount.
            $parts = $discounts->allocate(
                $resolved['amount_cents'],
                array_column($schedule['rows'], 'amount_cents')
            );
            $label = $resolved['discount']
                ? \App\Models\Discount::label($resolved['discount']) . ' — ' . $resolved['discount']['name']
                : null;

            // One invoice per scheduled installment.
            $created = [];
            $count = count($schedule['rows']);
            foreach ($schedule['rows'] as $i => $row) {
                $desc = $svc['name'];
                if ($yearly) {
                    $desc .= ' — 12 months';
                } elseif ($recurring) {
                    $desc .= ' — first period';
                }
                if ($row['label'] !== '') {
                    $desc .= ' (' . $row['label'] . ')';
                }
                if ($count > 1) {
                    $desc .= ' [' . ($i + 1) . ' of ' . $count . ']';
                }

                $invoice = $invoices->create([
                    'client_id'  => $clientId,
                    'status'     => Invoice::STATUS_SENT,
                    'issue_date' => today(),
                    'due_date'   => date('Y-m-d', strtotime('+' . $row['due_days'] . ' days')),
                    'notes'      => 'Ordered online via the client portal. Thanks for choosing ' . config('company.legal_name', 'OptiTide') . '.',
                ], [
                    ['description' => $desc, 'quantity' => 1, 'unit_price_cents' => $row['amount_cents'], 'service_id' => $svc['id']],
                ]);

                // Line items stay at full price so the invoice shows the real
                // value and the saving as its own line; totals are re-derived
                // with GST coming off the DISCOUNTED figure.
                if (($parts[$i] ?? 0) > 0) {
                    Invoice::updateById($invoice['id'], [
                        'discount_cents' => $parts[$i],
                        'discount_id'    => $resolved['discount']['id'] ?? null,
                        'discount_label' => $label,
                    ]);
                    $invoice = $invoices->recomputeTotals($invoice['id']);
                }

                $created[] = $invoice;
            }

            // Claim the use once per order, against the first invoice. Inside the
            // transaction so a failed order never burns a use.
            if ($resolved['discount'] && $resolved['amount_cents'] > 0) {
                $discounts->redeem($resolved['discount'], $resolved['amount_cents'], $clientId, $created[0]['id'] ?? null);
            }

            return ['engagement' => $engagement, 'invoices' => $created];
        });

        $firstInvoice = $result['invoices'][0];
        $n = count($result['invoices']);

        // If this service line has a project brief, collect it before payment.
        if (\App\Models\ProjectIntake::questionsFor($category)) {
            $this->flash('success', 'Order confirmed! Tell us a bit about your project, then complete payment.');

            return $this->redirect(route('portal.intake.show', ['engagement' => $result['engagement']['id']]) . '?invoice=' . $firstInvoice['id']);
        }

        $this->flash('success', $n > 1
            ? "Order confirmed — we've scheduled {$n} payments. Your first, invoice {$firstInvoice['number']}, is ready to pay now."
            : 'Order confirmed — invoice ' . $firstInvoice['number'] . ' is ready. Complete payment below to get started.');

        return $this->redirectRoute('portal.invoices.show', ['id' => $firstInvoice['id']]);
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
