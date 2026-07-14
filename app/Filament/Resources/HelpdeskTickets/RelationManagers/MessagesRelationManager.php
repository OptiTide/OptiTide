<?php

namespace App\Filament\Resources\HelpdeskTickets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversation';

    // Render eagerly — a support thread should be visible immediately.
    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')->default(fn () => Auth::id()),
            Textarea::make('body')
                ->label('Message')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Toggle::make('is_internal')
                ->label('Internal note (hidden from the client)')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('user.name')->label('From')->badge(),
                TextColumn::make('body')->wrap(),
                IconColumn::make('is_internal')->label('Internal')->boolean(),
                TextColumn::make('created_at')->label('Sent')->since(),
            ])
            ->headerActions([
                CreateAction::make()->label('Reply'),
            ]);
    }
}
