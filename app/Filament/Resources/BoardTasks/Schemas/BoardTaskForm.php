<?php

namespace App\Filament\Resources\BoardTasks\Schemas;

use App\Enums\BoardTaskStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BoardTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(BoardTaskStatus::class)
                    ->default('backlog')
                    ->required(),
                Select::make('assigned_to')
                    ->label('Assigned to')
                    ->relationship(
                        'assignee',
                        'name',
                        modifyQueryUsing: fn ($query) => $query->whereIn('role', ['admin', 'va']),
                    ),
                Select::make('order_id')
                    ->label('Related order')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload(),
                DatePicker::make('due_date'),
            ]);
    }
}
