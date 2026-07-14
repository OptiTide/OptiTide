<?php

namespace App\Filament\Client\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable(),
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total')
                    ->formatStateUsing(fn (?Money $state): ?string => $state?->format()),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('invoices.download', $record))
                    ->openUrlInNewTab(),
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
