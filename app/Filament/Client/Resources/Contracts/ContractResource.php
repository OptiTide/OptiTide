<?php

namespace App\Filament\Client\Resources\Contracts;

use App\Filament\Client\Resources\Contracts\Pages\ListContracts;
use App\Filament\Client\Resources\Contracts\Tables\ContractsTable;
use App\Models\Contract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $navigationLabel = 'Agreements';

    protected static string | \UnitEnum | null $navigationGroup = 'Services';

    protected static ?string $modelLabel = 'agreement';

    /** Clients only ever see their own agreements. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getEloquentQuery()->where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return ContractsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
        ];
    }
}
