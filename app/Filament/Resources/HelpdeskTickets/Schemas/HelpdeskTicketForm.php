<?php

namespace App\Filament\Resources\HelpdeskTickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HelpdeskTicketForm
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
                TextInput::make('subject')
                    ->required(),
                Select::make('status')
                    ->options(TicketStatus::class)
                    ->default('open')
                    ->required(),
                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->default('normal')
                    ->required(),
                Select::make('assigned_to')
                    ->label('Assigned to')
                    ->relationship(
                        'assignee',
                        'name',
                        modifyQueryUsing: fn ($query) => $query->whereIn('role', ['admin', 'va']),
                    ),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
