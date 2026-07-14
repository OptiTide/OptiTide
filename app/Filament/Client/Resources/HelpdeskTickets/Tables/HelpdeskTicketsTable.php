<?php

namespace App\Filament\Client\Resources\HelpdeskTickets\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HelpdeskTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('order.order_number')
                    ->label('Service')
                    ->placeholder('General'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('priority')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Opened')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
