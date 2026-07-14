<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductCategory;
use App\Models\FormSchema;
use App\Support\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('category')
                    ->options(ProductCategory::class)
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                // Entered in dollars, stored as integer cents.
                TextInput::make('price')
                    ->label('Price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->rule('decimal:0,2')
                    ->step(0.01)
                    ->prefix('$')
                    ->formatStateUsing(fn ($state) => $state instanceof Money ? number_format($state->major(), 2, '.', '') : $state)
                    ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                TextInput::make('currency')
                    ->required()
                    ->default('AUD')
                    ->maxLength(3),
                Select::make('billing_interval')
                    ->label('Billing')
                    ->placeholder('One-time purchase')
                    ->options(['month' => 'Monthly subscription', 'year' => 'Yearly subscription']),
                TagsInput::make('features')
                    ->columnSpanFull(),
                Select::make('onboarding_form_key')
                    ->label('Onboarding form')
                    ->placeholder('None')
                    ->options(fn () => FormSchema::where('is_active', true)->pluck('name', 'key')->all()),
                TextInput::make('stripe_product_id'),
                TextInput::make('stripe_price_id'),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
