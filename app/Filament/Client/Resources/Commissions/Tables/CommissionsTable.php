<?php

namespace App\Filament\Client\Resources\Commissions\Tables;

use App\Enums\CommissionStatus;
use App\Models\Commission;
use App\Services\CommissionService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->formatStateUsing(fn (?Money $state) => $state?->format())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Earned')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                // The referrer chooses to take an approved commission as account
                // credit (the credit-vs-payout choice); payout is admin-driven.
                Action::make('applyAsCredit')
                    ->label('Take as credit')
                    ->icon('heroicon-o-wallet')
                    ->requiresConfirmation()
                    ->modalDescription('Apply this commission to your account credit balance instead of a cash payout.')
                    ->visible(fn (Commission $record) => $record->status === CommissionStatus::Approved)
                    ->action(function (Commission $record): void {
                        app(CommissionService::class)->applyAsCredit($record);
                        Notification::make()->title('Applied to your account credit')->success()->send();
                    }),
            ]);
    }
}
