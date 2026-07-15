<?php

namespace App\Filament\Client\Resources\Commissions;

use App\Filament\Client\Resources\Commissions\Pages\ListCommissions;
use App\Filament\Client\Resources\Commissions\Tables\CommissionsTable;
use App\Models\Commission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Referral earnings';

    protected static string | \UnitEnum | null $navigationGroup = 'Billing';

    protected static ?string $modelLabel = 'commission';

    /** A client only ever sees the commissions they earned as referrer. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('referrer_id', Auth::id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return CommissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissions::route('/'),
        ];
    }
}
