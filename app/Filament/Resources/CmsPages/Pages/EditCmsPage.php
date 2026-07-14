<?php

namespace App\Filament\Resources\CmsPages\Pages;

use App\Filament\Resources\CmsPages\CmsPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsPage extends EditRecord
{
    protected static string $resource = CmsPageResource::class;

    /**
     * spatie translatable attributes hydrate as per-locale arrays
     * (`{"en":"…"}`), which a plain TextInput/RichEditor can't fill (the editor
     * 500s on the array). Flatten to the current-locale string for the form;
     * saving writes back through spatie, which preserves the other locales.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['title', 'body'] as $key) {
            if (is_array($data[$key] ?? null)) {
                $data[$key] = $this->record->getTranslation($key, app()->getLocale());
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
