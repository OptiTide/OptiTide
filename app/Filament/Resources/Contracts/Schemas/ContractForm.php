<?php

namespace App\Filament\Resources\Contracts\Schemas;

use App\Enums\ContractStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
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
                TextInput::make('title')
                    ->required(),
                Select::make('template_key')
                    ->label('Agreement type')
                    ->options([
                        'service_agreement' => 'Service Agreement',
                        'hosting_agreement' => 'Hosting Services Agreement',
                    ])
                    ->default('service_agreement')
                    ->required(),
                Select::make('status')
                    ->options(ContractStatus::class)
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Set automatically when the client signs.'),
            ]);
    }
}
