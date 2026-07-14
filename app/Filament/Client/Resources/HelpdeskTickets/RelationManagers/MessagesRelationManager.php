<?php

namespace App\Filament\Client\Resources\HelpdeskTickets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
            Hidden::make('is_internal')->default(false),
            Textarea::make('body')
                ->label('Your reply')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Clients never see internal staff notes.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_internal', false))
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('user.name')->label('From')->badge(),
                TextColumn::make('body')->wrap(),
                TextColumn::make('created_at')->label('Sent')->since(),
            ])
            ->headerActions([
                CreateAction::make()->label('Reply'),
            ]);
    }
}
