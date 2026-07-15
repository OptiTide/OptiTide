<?php

namespace App\Filament\Client\Resources\HelpdeskTickets;

use App\Filament\Client\Resources\HelpdeskTickets\Pages\CreateHelpdeskTicket;
use App\Filament\Client\Resources\HelpdeskTickets\Pages\ListHelpdeskTickets;
use App\Filament\Client\Resources\HelpdeskTickets\Pages\ViewHelpdeskTicket;
use App\Filament\Client\Resources\HelpdeskTickets\RelationManagers\MessagesRelationManager;
use App\Filament\Client\Resources\HelpdeskTickets\Schemas\HelpdeskTicketForm;
use App\Filament\Client\Resources\HelpdeskTickets\Tables\HelpdeskTicketsTable;
use App\Models\HelpdeskTicket;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HelpdeskTicketResource extends Resource
{
    protected static ?string $model = HelpdeskTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $navigationLabel = 'Support';

    protected static string | \UnitEnum | null $navigationGroup = 'Support';

    protected static ?string $modelLabel = 'support ticket';

    /** Clients only ever see their own tickets. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Schema $schema): Schema
    {
        return HelpdeskTicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('subject')->columnSpanFull(),
            TextEntry::make('order.order_number')->label('Service')->placeholder('General enquiry'),
            TextEntry::make('status')->badge(),
            TextEntry::make('priority')->badge(),
            TextEntry::make('created_at')->label('Opened')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return HelpdeskTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHelpdeskTickets::route('/'),
            'create' => CreateHelpdeskTicket::route('/create'),
            'view' => ViewHelpdeskTicket::route('/{record}'),
        ];
    }
}
