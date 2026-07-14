<?php

namespace App\Filament\Resources\BoardTasks\Pages;

use App\Filament\Resources\BoardTasks\BoardTaskResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBoardTask extends CreateRecord
{
    protected static string $resource = BoardTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
