<?php

namespace App\Filament\Resources\ServerMonitors\Tables;

use App\Enums\MonitorStatus;
use App\Jobs\CheckMonitorJob;
use App\Models\ServerMonitor;
use App\Services\MonitorService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServerMonitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->limit(40)
                    ->url(fn (ServerMonitor $record) => $record->url, shouldOpenInNewTab: true)
                    ->color('primary'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('consecutive_failures')
                    ->label('Fails')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('ssl_expires_at')
                    ->label('SSL expires')
                    ->dateTime()
                    ->placeholder('—')
                    ->description(fn (ServerMonitor $record) => ($d = $record->sslDaysRemaining()) !== null
                        ? ($d < 0 ? abs($d).' days ago' : "in {$d} days")
                        : null)
                    ->color(fn (ServerMonitor $record) => ($d = $record->sslDaysRemaining()) !== null && $d <= 14 ? 'danger' : null),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_checked_at')
                    ->label('Last checked')
                    ->since()
                    ->placeholder('never'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MonitorStatus::class),
            ])
            ->recordActions([
                Action::make('checkNow')
                    ->label('Check now')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (ServerMonitor $record): void {
                        // Run synchronously for immediate feedback in the UI.
                        app(MonitorService::class)->check($record);
                        Notification::make()
                            ->title("Checked {$record->name}: ".$record->fresh()->status->getLabel())
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('checkAll')
                    ->label('Check all now')
                    ->icon('heroicon-o-bolt')
                    ->action(function (): void {
                        $count = 0;
                        ServerMonitor::active()->each(function (ServerMonitor $monitor) use (&$count) {
                            CheckMonitorJob::dispatch($monitor->id);
                            $count++;
                        });
                        Notification::make()->title("Dispatched {$count} check(s)")->success()->send();
                    }),
            ]);
    }
}
