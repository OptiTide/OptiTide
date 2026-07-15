<?php

namespace App\Filament\Client\Widgets;

use App\Enums\ContractStatus;
use App\Enums\OrderState;
use App\Enums\TicketStatus;
use App\Models\Contract;
use App\Models\HelpdeskTicket;
use App\Models\Invoice;
use App\Models\Order;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * WHMCS-style client-area overview: at-a-glance account status. All figures are
 * scoped to the signed-in client.
 */
class AccountOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = Auth::id();

        $activeProjects = Order::where('user_id', $userId)
            ->where('state', '!=', OrderState::Delivered)
            ->count();

        // Balance due = total − amount_paid across open (sent/overdue) invoices.
        $balance = new Money((int) Invoice::where('user_id', $userId)
            ->open()
            ->where('currency', 'AUD')
            ->sum(DB::raw('total - amount_paid')));

        $openTickets = HelpdeskTicket::where('user_id', $userId)
            ->whereIn('status', [TicketStatus::Open, TicketStatus::Pending])
            ->count();

        $toSign = Contract::where('user_id', $userId)
            ->where('status', ContractStatus::Pending)
            ->count();

        return [
            Stat::make('Active projects', $activeProjects)
                ->description('In progress')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('primary'),
            Stat::make('Balance due', $balance->format())
                ->description($balance->isZero() ? 'All paid — thank you' : 'Unpaid invoices')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($balance->isZero() ? 'success' : 'warning'),
            Stat::make('Open tickets', $openTickets)
                ->description('Support requests')
                ->descriptionIcon('heroicon-m-lifebuoy')
                ->color($openTickets > 0 ? 'info' : 'gray'),
            Stat::make('Agreements to sign', $toSign)
                ->description($toSign > 0 ? 'Action needed' : 'Nothing pending')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($toSign > 0 ? 'danger' : 'gray'),
        ];
    }
}
