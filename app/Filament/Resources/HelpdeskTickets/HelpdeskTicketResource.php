<?php

namespace App\Filament\Resources\HelpdeskTickets;

use App\Filament\Resources\HelpdeskTickets\Pages\CreateHelpdeskTicket;
use App\Filament\Resources\HelpdeskTickets\Pages\EditHelpdeskTicket;
use App\Filament\Resources\HelpdeskTickets\Pages\ListHelpdeskTickets;
use App\Filament\Resources\HelpdeskTickets\Schemas\HelpdeskTicketForm;
use App\Filament\Resources\HelpdeskTickets\Tables\HelpdeskTicketsTable;
use App\Models\HelpdeskTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HelpdeskTicketResource extends Resource
{
    protected static ?string $model = HelpdeskTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return HelpdeskTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HelpdeskTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHelpdeskTickets::route('/'),
            'create' => CreateHelpdeskTicket::route('/create'),
            'edit' => EditHelpdeskTicket::route('/{record}/edit'),
        ];
    }
}
