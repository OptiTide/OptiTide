<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /** Sync subtotal/total from the saved line items. */
    protected function afterCreate(): void
    {
        app(InvoiceService::class)->recomputeTotals($this->record);
    }
}
