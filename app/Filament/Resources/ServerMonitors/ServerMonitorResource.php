<?php

namespace App\Filament\Resources\ServerMonitors;

use App\Filament\Resources\ServerMonitors\Pages\CreateServerMonitor;
use App\Filament\Resources\ServerMonitors\Pages\EditServerMonitor;
use App\Filament\Resources\ServerMonitors\Pages\ListServerMonitors;
use App\Filament\Resources\ServerMonitors\Schemas\ServerMonitorForm;
use App\Filament\Resources\ServerMonitors\Tables\ServerMonitorsTable;
use App\Models\ServerMonitor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServerMonitorResource extends Resource
{
    protected static ?string $model = ServerMonitor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $navigationLabel = 'Server monitors';

    public static function form(Schema $schema): Schema
    {
        return ServerMonitorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServerMonitorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServerMonitors::route('/'),
            'create' => CreateServerMonitor::route('/create'),
            'edit' => EditServerMonitor::route('/{record}/edit'),
        ];
    }
}
