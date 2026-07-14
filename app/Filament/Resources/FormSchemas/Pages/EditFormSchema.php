<?php

namespace App\Filament\Resources\FormSchemas\Pages;

use App\Filament\Resources\FormSchemas\FormSchemaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFormSchema extends EditRecord
{
    protected static string $resource = FormSchemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
