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

        if (! $clientId) {
            return $this->view('client.dashboard', [
                'title'       => 'Dashboard',
                'outstanding' => Money::zero($currency),
                'services'    => 0,
                'recent'      => [],
            ]);
        }

        $outstanding = 0;
        foreach (Invoice::query()->where('client_id', $clientId)->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->get() as $invoice) {
            $outstanding += (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
        }

        return $this->view('client.dashboard', [
            'title'       => 'Dashboard',
            'outstanding' => new Money($outstanding, $currency),
            'services'    => ClientService::query()->where('client_id', $clientId)->where('status', 'active')->count(),
            'recent'      => Invoice::query()->where('client_id', $clientId)
                ->where('status', '!=', Invoice::STATUS_DRAFT)
                ->orderBy('id', 'desc')->limit(6)->get(),
        ]);
    }
}
