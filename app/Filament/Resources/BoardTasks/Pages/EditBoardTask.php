<?php

namespace App\Filament\Resources\BoardTasks\Pages;

use App\Filament\Resources\BoardTasks\BoardTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBoardTask extends EditRecord
{
    protected static string $resource = BoardTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
