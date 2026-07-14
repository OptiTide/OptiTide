<?php

namespace App\Filament\Client\Resources\Orders\Tables;

use App\Enums\OrderState;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // Auto-refresh so "Design In Progress" advances to the review action
            // once async generation completes, without a manual reload.
            ->poll('15s')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('state')
                    ->label('Project status')
                    ->badge()
                    ->formatStateUsing(fn (OrderState $state): string => $state->clientFacingLabel()),
                TextColumn::make('payment_status')
                    ->badge(),
                TextColumn::make('total')
                    ->formatStateUsing(fn (?Money $state): ?string => $state?->format()),
                TextColumn::make('placed_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                // Prompts the client to complete the schema-driven brief while
                // the order is awaiting it.
                Action::make('brief')
                    ->label('Complete project brief')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('warning')
                    ->url(fn (Order $record): string => route('brief.show', $record))
                    ->visible(fn (Order $record): bool => $record->needsIntake()),
                // Visual proofing of the AI mockup once it reaches the client.
                Action::make('proof')
                    ->label('Review your design')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Order $record): string => route('proofing.show', $record))
                    ->visible(fn (Order $record): bool => $record->state === OrderState::ClientReview),
                // Money objects are not Livewire-serializable; flatten them to
                // integer cents before the view modal's fill data is snapshot.
                ViewAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => array_map(
                        fn ($value) => $value instanceof Money ? $value->amount : $value,
                        $data,
                    )),
            ]);
    }
}
