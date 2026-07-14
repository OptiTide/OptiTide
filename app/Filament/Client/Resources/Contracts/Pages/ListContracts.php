<?php

namespace App\Filament\Client\Resources\Contracts\Pages;

use App\Filament\Client\Resources\Contracts\ContractResource;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    // Agreements are issued by the agency; clients review, sign, and download.
}
