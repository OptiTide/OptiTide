<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (string $name) => TextInput::make($name)
            ->required()
            ->numeric()
            ->minValue(0)
            ->rule('decimal:0,2')
            ->step(0.01)
            ->prefix('$')
            ->formatStateUsing(fn ($state) => $state instanceof Money ? number_format($state->major(), 2, '.', '') : $state)
            ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100));

        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Client')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                // order_number is system-generated; pipeline state changes only
                // through the enforced transition action, never via this form.
                TextInput::make('order_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
                Select::make('payment_status')
                    ->options(PaymentStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('AUD')
                    ->maxLength(3),
                $money('subtotal')->default(0),
                $money('total')->default(0),
                DateTimePicker::make('placed_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
