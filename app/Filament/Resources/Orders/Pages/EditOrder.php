<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Support\Money;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Money casts hydrate as objects, which numeric TextInputs cannot fill;
     * convert to major-unit decimal strings before the form receives them.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['subtotal', 'total'] as $key) {
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
