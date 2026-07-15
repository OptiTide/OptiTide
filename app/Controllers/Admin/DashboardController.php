<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $currency = config('company.currency', 'AUD');
        $outstanding = 0;
        $overdueCount = 0;

        foreach (Invoice::query()->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->get() as $invoice) {
            $outstanding += (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
            if ($invoice['status'] === Invoice::STATUS_OVERDUE) {
                $overdueCount++;
            }
        }

        $paidThisMonth = Payment::query()
            ->where('paid_at', '>=', date('Y-m-01') . ' 00:00:00')
            ->sum('amount_cents');

        $recent = Invoice::query()->orderBy('id', 'desc')->limit(8)->get();
        $clientNames = array_column(Client::all(), 'business_name', 'id');

        return $this->view('admin.dashboard', [
            'title'        => 'Dashboard',
            'stats'        => [
                'clients'     => Client::query()->where('status', Client::STATUS_ACTIVE)->count(),
                'outstanding' => new Money($outstanding, $currency),
                'overdue'     => $overdueCount,
                'paid_month'  => new Money($paidThisMonth, $currency),
            ],
            'recent'       => $recent,
            'client_names' => $clientNames,
        ]);
    }
}
