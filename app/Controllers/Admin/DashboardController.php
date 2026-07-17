<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ChatConversation;
use App\Models\Client;
use App\Models\Commission;
use App\Models\InstallmentRequest;
use App\Models\Invoice;
use App\Models\JobApplication;
use App\Models\Meeting;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Ticket;
use App\Support\Features;
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

        // Compare against the DATE, never date . ' 00:00:00'. paid_at is mixed:
        // an auto-stamped payment stores a datetime, but a staff-recorded one
        // stores the date alone (the admin form validates it as `date`). String
        // compared, '2026-07-01' < '2026-07-01 00:00:00' — so the time suffix
        // silently dropped every payment taken on the first day of the period.
        $paidThisMonth = Payment::query()
            ->where('paid_at', '>=', date('Y-m-01'))
            ->sum('amount_cents');

        $paidThisWeek = Payment::query()
            ->where('paid_at', '>=', date('Y-m-d', strtotime('monday this week')))
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
            'actions'         => $this->actionQueue(),
            // Day one: no clients AND no invoices means the numbers above are all
            // zero and say nothing — show the setup path instead of empty tables.
            'is_fresh'        => Client::query()->count() === 0 && Invoice::query()->count() === 0,
            'recent'          => $recent,
            'client_names'    => $clientNames,
            'top_outstanding' => $topOutstanding,
            'recent_payments' => $recentPayments,
        ]);
    }

    /**
     * The "what needs me today?" queue: everything waiting on a decision, newest
     * pain first. Each row carries the URL of the exact list it counts — a figure
     * you can't click is a dead end. Zero-count rows are dropped, so the queue is
     * a to-do list rather than a wall of noughts.
     *
     * Feature-gated rows are omitted when the feature is off: those screens 404,
     * so counting them would send the owner to a dead page.
     *
     * @return array<int,array{count:int,label:string,url:string,icon:string,tone:string}>
     */
    protected function actionQueue(): array
    {
        $rows = [];

        $add = function (int $count, string $label, string $url, string $icon, string $tone) use (&$rows): void {
            if ($count > 0) {
                $rows[] = [
                    'count' => $count,
                    'label' => $label,
                    'url'   => $url,
                    'icon'  => $icon,
                    'tone'  => $tone,
                ];
            }
        };

        $add(
            Invoice::query()->where('status', Invoice::STATUS_OVERDUE)->count(),
            'Overdue Invoices',
            route('admin.invoices.index') . '?status=' . Invoice::STATUS_OVERDUE,
            'bi-exclamation-triangle',
            'danger'
        );

        $add(
            Invoice::query()->where('status', Invoice::STATUS_DRAFT)->count(),
            'Draft Invoices Not Yet Sent',
            route('admin.invoices.index') . '?status=' . Invoice::STATUS_DRAFT,
            'bi-file-earmark',
            'warning'
        );

        $add(
            Ticket::query()->where('status', Ticket::STATUS_OPEN)->count(),
            'Support Tickets Awaiting a Reply',
            route('admin.tickets.index') . '?status=' . Ticket::STATUS_OPEN,
            'bi-life-preserver',
            'primary'
        );

        $add(
            count(InstallmentRequest::pending()),
            'Payment Plans Awaiting Approval',
            route('admin.installments.index'),
            'bi-hourglass-split',
            'warning'
        );

        if (Features::enabled('quotes')) {
            // A sent quote past its expiry is dead money, not a live decision —
            // and `?status=sent` wouldn't list it back anyway (the column still
            // reads "sent" until something stamps it). Count only live ones.
            $liveQuotes = 0;
            foreach (Quote::query()->where('status', Quote::STATUS_SENT)->get() as $quote) {
                if (! Quote::hasExpired($quote)) {
                    $liveQuotes++;
                }
            }

            $add(
                $liveQuotes,
                'Quotes Awaiting a Response',
                route('admin.quotes.index') . '?status=' . Quote::STATUS_SENT,
                'bi-file-earmark-text',
                'info'
            );
        }

        if (Features::enabled('meetings')) {
            $add(
                Meeting::query()->where('status', Meeting::STATUS_REQUESTED)->count(),
                'Meetings Awaiting Confirmation',
                route('admin.meetings.index'),
                'bi-calendar-event',
                'warning'
            );
        }

        if (Features::enabled('live_chat')) {
            $add(
                ChatConversation::query()->where('status', ChatConversation::STATUS_OPEN)->count(),
                'Open Live Chats',
                route('admin.chat.index'),
                'bi-chat-dots',
                'info'
            );
        }

        if (Features::enabled('careers')) {
            $add(
                JobApplication::countNew(),
                'New Job Applications',
                route('admin.careers.applications') . '?status=' . JobApplication::STATUS_NEW,
                'bi-person-plus',
                'primary'
            );
        }

        // Commissions are admin-only (CommissionController re-checks), so staff
        // must not be pointed at a screen that will refuse them.
        if (Features::enabled('affiliate') && Auth::isAdmin()) {
            $add(
                Commission::query()->where('status', Commission::STATUS_PENDING)->count(),
                'Commissions Awaiting Approval',
                route('admin.commissions.index'),
                'bi-cash-stack',
                'info'
            );
        }

        return $rows;
    }
}
