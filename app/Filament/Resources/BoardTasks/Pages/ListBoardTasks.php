<?php

namespace App\Filament\Resources\BoardTasks\Pages;

use App\Filament\Resources\BoardTasks\BoardTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBoardTasks extends ListRecords
{
    protected static string $resource = BoardTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
