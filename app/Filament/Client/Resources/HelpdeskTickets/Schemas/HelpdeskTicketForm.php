<?php

namespace App\Filament\Client\Resources\HelpdeskTickets\Schemas;

use App\Enums\TicketPriority;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class HelpdeskTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subject')
                    ->required()
                    ->maxLength(255),
                Select::make('order_id')
                    ->label('Which service is this about?')
                    ->placeholder('General enquiry')
                    ->options(fn (): array => Order::where('user_id', Auth::id())
                        ->pluck('order_number', 'id')
                        ->all()),
                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->default('normal')
                    ->required(),
                // Stored as the ticket's first TicketMessage, not a ticket column.
                Textarea::make('message')
                    ->label('How can we help?')
                    ->required()
                    ->dehydrated(false)
                    ->rows(6)
                    ->columnSpanFull(),
            ]);
    }
}
