<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Support\Money;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();
        $currency = config('company.currency', 'AUD');

        $zero = Money::zero($currency);
        $view = [
            'title'        => 'Dashboard',
            'outstanding'  => $zero,
            'paid'         => $zero,
            'services'     => 0,
            'overdue'      => 0,
            'next_due'     => null,
            'next_renewal' => null,
            'recent'       => [],
        ];

        if (! $clientId) {
            return $this->view('client.dashboard', $view);
        }

        $invoices = Invoice::query()
            ->where('client_id', $clientId)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->orderBy('id', 'desc')->get();

        $outstanding = 0;
        $paid = 0;
        $overdue = 0;
        foreach ($invoices as $invoice) {
            $paid += (int) $invoice['amount_paid_cents'];
            if (in_array($invoice['status'], [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE], true)) {
                $outstanding += (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
            }
            if ($invoice['status'] === Invoice::STATUS_OVERDUE) {
                $overdue++;
            }
        }

        $view['outstanding'] = new Money($outstanding, $currency);
        $view['paid'] = new Money($paid, $currency);
        $view['overdue'] = $overdue;
        $view['services'] = ClientService::query()->where('client_id', $clientId)->where('status', 'active')->count();
        $view['recent'] = array_slice($invoices, 0, 6);

        $view['next_due'] = Invoice::query()
            ->where('client_id', $clientId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->orderBy('due_date', 'asc')->first();

        $view['next_renewal'] = ClientService::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->where('billing_type', 'recurring')
            ->whereNotNull('next_invoice_date')
            ->orderBy('next_invoice_date', 'asc')->first();

        return $this->view('client.dashboard', $view);
    }
}
