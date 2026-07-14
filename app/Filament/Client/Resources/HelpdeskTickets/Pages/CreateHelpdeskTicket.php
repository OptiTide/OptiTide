<?php

namespace App\Filament\Client\Resources\HelpdeskTickets\Pages;

use App\Filament\Client\Resources\HelpdeskTickets\HelpdeskTicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHelpdeskTicket extends CreateRecord
{
    protected static string $resource = HelpdeskTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // The form's message field becomes the ticket's opening message.
        $message = $this->data['message'] ?? null;

        if (filled($message)) {
            $this->record->messages()->create([
                'user_id' => Auth::id(),
                'body' => $message,
                'is_internal' => false,
            ]);
        }
    }
}
