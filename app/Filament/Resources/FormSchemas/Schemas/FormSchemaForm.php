<?php

namespace App\Filament\Resources\FormSchemas\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FormSchemaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Stable identifier referenced by products, e.g. basic_onboarding.'),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                // The schema is stored as JSON; edited here as raw JSON text.
                Textarea::make('schema')
                    ->required()
                    ->rows(20)
                    ->rules([
                        'json',
                        // rule('json') alone accepts literal `null` and scalars,
                        // which would corrupt the array-cast column.
                        fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail) {
                            if (! is_string($value) || ! is_array(json_decode($value, true))) {
                                $fail('The schema must be a JSON object or array, e.g. {"fields": [...]}.');
                            }
                        },
                    ])
                    ->formatStateUsing(fn ($state) => is_string($state) ? $state : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->dehydrateStateUsing(fn (string $state) => json_decode($state, true))
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
