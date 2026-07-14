<?php

namespace App\Filament\Client\Resources\Orders\Schemas;

use App\Enums\OrderState;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

/**
 * Client-facing order details, shown in the read-only view modal. Internal
 * pipeline stages (AI generation, QA) are masked behind client-safe labels.
 */
class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_number')
                    ->label('Order'),
                TextEntry::make('state')
                    ->label('Project status')
                    ->badge()
                    ->formatStateUsing(fn (OrderState $state): string => $state->clientFacingLabel()),
                TextEntry::make('payment_status')
                    ->badge(),
                TextEntry::make('total')
                    ->formatStateUsing(fn (?Money $state): ?string => $state?->format()),
                TextEntry::make('placed_at')
                    ->dateTime(),
            ]);
    }
}
