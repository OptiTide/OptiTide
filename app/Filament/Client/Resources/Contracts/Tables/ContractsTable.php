<?php

namespace App\Filament\Client\Resources\Contracts\Tables;

use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('signed_at')
                    ->dateTime()
                    ->placeholder('Not yet signed')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Issued')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('sign')
                    ->label('Review & sign')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->url(fn (Contract $record): string => route('contracts.sign.show', $record))
                    ->visible(fn (Contract $record): bool => ! $record->isSigned()),
                Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Contract $record): string => route('contracts.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Contract $record): bool => $record->isSigned()),
            ]);
    }
}
