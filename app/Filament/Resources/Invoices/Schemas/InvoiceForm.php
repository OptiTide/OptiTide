<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Support\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        // Dollars in the form, integer cents in the DB. NOTE: no ->numeric() —
        // it installs a NumberStateCast that floatval()s the incoming state and
        // fatals on our Money value objects during edit hydration. We get the
        // same UX + validation with inputMode + explicit rules instead.
        $money = fn (string $name) => TextInput::make($name)
            ->rules(['numeric', 'min:0', 'decimal:0,2'])
            ->inputMode('decimal')
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
                Select::make('order_id')
                    ->label('Related order')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload(),
                TextInput::make('invoice_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
                Select::make('status')
                    ->options(InvoiceStatus::class)
                    ->default('draft')
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('AUD')
                    ->maxLength(3),

                // Line items; subtotal + total are recomputed from these on save.
                Repeater::make('items')
                    ->relationship()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        $money('unit_price')->label('Unit price')->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->addActionLabel('Add line item')
                    // The stored line total is a Money value object with no form
                    // field, so it can't live in Livewire's serialized state.
                    // Drop it on load — it's recomputed from qty × unit price on save.
                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                        unset($data['total']);

                        return $data;
                    })
                    // Compute each item's total (qty × unit price) + currency.
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::withLineTotal($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::withLineTotal($data)),

                // GST is derived (10% of subtotal) by InvoiceService::recomputeTotals
                // on save — read-only here, never hand-entered.
                $money('tax')
                    ->label('GST')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Calculated automatically at 10% on save.'),
                $money('amount_paid')->label('Amount paid')->default(0),
                DatePicker::make('due_date'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    /** @param array<string, mixed> $data */
    protected static function withLineTotal(array $data): array
    {
        $data['total'] = (int) ($data['unit_price'] ?? 0) * (int) ($data['quantity'] ?? 1);
        $data['currency'] ??= 'AUD';

        return $data;
    }
}
