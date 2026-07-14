<?php

namespace App\Filament\Resources\BoardTasks;

use App\Filament\Resources\BoardTasks\Pages\CreateBoardTask;
use App\Filament\Resources\BoardTasks\Pages\EditBoardTask;
use App\Filament\Resources\BoardTasks\Pages\ListBoardTasks;
use App\Filament\Resources\BoardTasks\Schemas\BoardTaskForm;
use App\Filament\Resources\BoardTasks\Tables\BoardTasksTable;
use App\Models\BoardTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BoardTaskResource extends Resource
{
    protected static ?string $model = BoardTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BoardTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BoardTasksTable::configure($table);
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
            'index' => ListBoardTasks::route('/'),
            'create' => CreateBoardTask::route('/create'),
            'edit' => EditBoardTask::route('/{record}/edit'),
        ];
    }
}
