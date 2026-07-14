<?php

namespace App\Filament\Client\Resources\Orders\Pages;

use App\Filament\Client\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    // Orders are created through checkout, never manually by clients.
}
