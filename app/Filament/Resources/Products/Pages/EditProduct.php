<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Support\Money;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Money casts hydrate as objects, which numeric TextInputs cannot fill;
     * convert to major-unit decimal strings before the form receives them.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['price'] ?? null) instanceof Money) {
            $data['price'] = number_format($data['price']->major(), 2, '.', '');
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
