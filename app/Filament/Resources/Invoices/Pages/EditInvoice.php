<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceService;
use App\Support\Money;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    /** Sync subtotal/total from the saved line items. */
    protected function afterSave(): void
    {
        app(InvoiceService::class)->recomputeTotals($this->record);
    }

    /**
     * Money casts hydrate as objects, which numeric TextInputs cannot fill;
     * convert to major-unit decimal strings before the form receives them.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['subtotal', 'tax', 'total', 'amount_paid'] as $key) {
            if (($data[$key] ?? null) instanceof Money) {
                $data[$key] = number_format($data[$key]->major(), 2, '.', '');
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
