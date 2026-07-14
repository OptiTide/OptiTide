<?php

namespace App\Filament\Client\Resources\Invoices\Schemas;

use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

/** Client-facing invoice details, shown in the read-only view modal. */
class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (string $name) => TextEntry::make($name)
            ->formatStateUsing(fn (?Money $state): ?string => $state?->format());

        return $schema
            ->components([
                TextEntry::make('invoice_number')
                    ->label('Invoice'),
                TextEntry::make('order.order_number')
                    ->label('Related order')
                    ->placeholder('—'),
                TextEntry::make('status')
                    ->badge(),
                $money('subtotal'),
                $money('tax')->label('Tax (GST)'),
                $money('total'),
                $money('amount_paid'),
                TextEntry::make('due_date')
                    ->date(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('Not yet paid'),
            ]);
    }
}
