<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Enums\BlogStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('excerpt')
                    ->columnSpanFull(),
                RichEditor::make('body')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(BlogStatus::class)
                    ->default('draft')
                    ->required(),
                DateTimePicker::make('scheduled_for')
                    ->helperText('Scheduled articles are published automatically by the daily cron.'),
                DateTimePicker::make('published_at'),
                Select::make('author_id')
                    ->relationship('author', 'name'),
                KeyValue::make('meta')
                    ->label('SEO metadata')
                    ->keyLabel('Field')
                    ->valueLabel('Value')
                    ->columnSpanFull(),
                Toggle::make('is_ai_generated')
                    ->disabled()
                    ->helperText('Set automatically by the AI blog pipeline.'),
            ]);
    }
}
