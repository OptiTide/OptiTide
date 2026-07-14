<?php

namespace App\Filament\Resources\Commissions\Tables;

use App\Enums\CommissionStatus;
use App\Models\Commission;
use App\Services\CommissionService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->searchable(),
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->formatStateUsing(fn (?Money $state) => $state?->format())
                    ->sortable(),
                TextColumn::make('rate_basis_points')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format($state / 100, 2), '0'), '.').'%'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('settled_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CommissionStatus::class),
            ])
            ->recordActions([
                // Four-eyes: a staff member can never action a commission where
                // they are the referrer (defence-in-depth — staff can't be
                // referrers anyway, per ReferralService).
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Commission $record) => $record->status === CommissionStatus::Pending && $record->referrer_id !== Auth::id())
                    ->action(function (Commission $record): void {
                        app(CommissionService::class)->approve($record);
                        Notification::make()->title('Commission approved')->success()->send();
                    }),
                Action::make('markPaid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-banknotes')
                    ->requiresConfirmation()
                    // Only an Approved commission is paid out — Credited is a
                    // terminal state (taken as account credit instead of cash).
                    ->visible(fn (Commission $record) => $record->status === CommissionStatus::Approved && $record->referrer_id !== Auth::id())
                    ->action(function (Commission $record): void {
                        app(CommissionService::class)->markPaid($record);
                        Notification::make()->title('Commission marked as paid')->success()->send();
                    }),
            ]);
    }
}
