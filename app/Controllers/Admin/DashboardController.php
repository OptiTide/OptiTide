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
        $balanceByClient = [];

        foreach (Invoice::query()->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->get() as $invoice) {
            $balance = (int) $invoice['total_cents'] - (int) $invoice['amount_paid_cents'];
            $outstanding += $balance;
            if ($invoice['status'] === Invoice::STATUS_OVERDUE) {
                $overdueCount++;
            }
            if ($balance > 0) {
                $clientId = (int) $invoice['client_id'];
                $balanceByClient[$clientId] = ($balanceByClient[$clientId] ?? 0) + $balance;
            }
        }

        $paidThisMonth = Payment::query()
            ->where('paid_at', '>=', date('Y-m-01') . ' 00:00:00')
            ->sum('amount_cents');

        $paidThisWeek = Payment::query()
            ->where('paid_at', '>=', date('Y-m-d', strtotime('monday this week')) . ' 00:00:00')
            ->sum('amount_cents');

        $recent = Invoice::query()->orderBy('id', 'desc')->limit(8)->get();
        $clientNames = array_column(Client::all(), 'business_name', 'id');

        arsort($balanceByClient);
        $topOutstanding = [];
        foreach (array_slice($balanceByClient, 0, 5, true) as $clientId => $balance) {
            $topOutstanding[] = [
                'client_id'     => $clientId,
                'business_name' => $clientNames[$clientId] ?? '—',
                'balance'       => new Money($balance, $currency),
            ];
        }

        $recentPayments = [];
        foreach (Payment::query()->orderBy('paid_at', 'desc')->limit(5)->get() as $payment) {
            $recentPayments[] = [
                'business_name' => $clientNames[$payment['client_id']] ?? '—',
                'amount'        => new Money((int) $payment['amount_cents'], $payment['currency'] ?? $currency),
                'method'        => $payment['method'],
                'paid_at'       => $payment['paid_at'],
            ];
        }

        return $this->view('admin.dashboard', [
            'title'           => 'Dashboard',
            'stats'           => [
                'clients'     => Client::query()->where('status', Client::STATUS_ACTIVE)->count(),
                'outstanding' => new Money($outstanding, $currency),
                'overdue'     => $overdueCount,
                'paid_month'  => new Money($paidThisMonth, $currency),
                'paid_week'   => new Money($paidThisWeek, $currency),
            ],
            'recent'          => $recent,
            'client_names'    => $clientNames,
            'top_outstanding' => $topOutstanding,
            'recent_payments' => $recentPayments,
        ]);
    }
}
