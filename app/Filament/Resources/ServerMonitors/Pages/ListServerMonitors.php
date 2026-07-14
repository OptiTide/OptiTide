<?php

namespace App\Filament\Resources\ServerMonitors\Pages;

use App\Filament\Resources\ServerMonitors\ServerMonitorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServerMonitors extends ListRecords
{
    protected static string $resource = ServerMonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
