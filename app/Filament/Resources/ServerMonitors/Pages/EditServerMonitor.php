<?php

namespace App\Filament\Resources\ServerMonitors\Pages;

use App\Filament\Resources\ServerMonitors\ServerMonitorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServerMonitor extends EditRecord
{
    protected static string $resource = ServerMonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
