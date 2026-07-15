<?php

namespace App\Filament\Client\Widgets;

use App\Enums\OrderState;
use App\Models\Order;
use App\Support\Money;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

/**
 * WHMCS-style "your services" panel — the client's most recent projects with
 * their client-facing status.
 */
class RecentOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Your recent projects';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::where('user_id', Auth::id())->latest('created_at'))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10])
            ->emptyStateHeading('No projects yet')
            ->emptyStateDescription('Your orders will appear here once you place one.')
            ->emptyStateIcon('heroicon-o-rocket-launch')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (OrderState $state): string => $state->clientFacingLabel()),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge(),
                TextColumn::make('total')
                    ->formatStateUsing(fn (?Money $state): ?string => $state?->format()),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime()
                    ->since(),
            ]);
    }
}
