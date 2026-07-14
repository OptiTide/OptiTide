<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\OrderState;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\HelpdeskTicket;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgencyStatsOverview extends StatsOverviewWidget
{
    /** Financial stats are for the agency owner, not VAs. */
    public static function canView(): bool
    {
        return Auth::user()?->role === UserRole::Admin;
    }

    protected function getStats(): array
    {
        // Sums are constrained to AUD so mixed-currency rows can't be
        // mislabelled; partial payments on open invoices count as collected.
        $revenue = new Money((int) (
            Invoice::where('status', InvoiceStatus::Paid)->where('currency', 'AUD')->sum('total')
            + Invoice::open()->where('currency', 'AUD')->sum('amount_paid')
        ));
        $outstanding = new Money(
            (int) Invoice::open()->where('currency', 'AUD')->sum(DB::raw('total - amount_paid'))
        );

        return [
            Stat::make('Revenue collected', $revenue->format())
                ->description('Paid invoices + partial payments (AUD)')
                ->color('success'),
            Stat::make('Outstanding invoices', $outstanding->format())
                ->description(Invoice::open()->where('currency', 'AUD')->count().' awaiting payment')
                ->color('warning'),
            Stat::make('Orders in pipeline', (string) Order::whereNot('state', OrderState::Delivered)->count())
                ->description(Order::where('state', OrderState::ClientReview)->count().' awaiting client review')
                ->color('info'),
            Stat::make('New leads', (string) Lead::where('status', LeadStatus::New)->count())
                ->description('Unactioned enquiries')
                ->color('primary'),
            Stat::make('Open tickets', (string) HelpdeskTicket::whereIn('status', [TicketStatus::Open, TicketStatus::Pending])->count())
                ->description('Helpdesk queue')
                ->color('danger'),
        ];
    }
}
