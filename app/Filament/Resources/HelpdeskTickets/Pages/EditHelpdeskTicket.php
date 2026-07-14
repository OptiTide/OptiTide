<?php

namespace App\Filament\Resources\HelpdeskTickets\Pages;

use App\Filament\Resources\HelpdeskTickets\HelpdeskTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHelpdeskTicket extends EditRecord
{
    protected static string $resource = HelpdeskTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
