<?php

namespace App\Filament\Resources\FormSchemas;

use App\Filament\Resources\FormSchemas\Pages\CreateFormSchema;
use App\Filament\Resources\FormSchemas\Pages\EditFormSchema;
use App\Filament\Resources\FormSchemas\Pages\ListFormSchemas;
use App\Filament\Resources\FormSchemas\Schemas\FormSchemaForm;
use App\Filament\Resources\FormSchemas\Tables\FormSchemasTable;
use App\Models\FormSchema;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FormSchemaResource extends Resource
{
    protected static ?string $model = FormSchema::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FormSchemaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormSchemasTable::configure($table);
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
            'index' => ListFormSchemas::route('/'),
            'create' => CreateFormSchema::route('/create'),
            'edit' => EditFormSchema::route('/{record}/edit'),
        ];
    }
}
