<?php

namespace App\Filament\Resources\HelpdeskTickets\Pages;

use App\Filament\Resources\HelpdeskTickets\HelpdeskTicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHelpdeskTicket extends CreateRecord
{
    protected static string $resource = HelpdeskTicketResource::class;
}
