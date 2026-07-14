<?php

namespace App\Filament\Client\Resources\HelpdeskTickets\Pages;

use App\Filament\Client\Resources\HelpdeskTickets\HelpdeskTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpdeskTickets extends ListRecords
{
    protected static string $resource = HelpdeskTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
