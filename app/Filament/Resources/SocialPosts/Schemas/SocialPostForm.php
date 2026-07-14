<?php

namespace App\Filament\Resources\SocialPosts\Schemas;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SocialPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship('client', 'name'),
                Select::make('platform')
                    ->options(SocialPlatform::class)
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('image_prompt')
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->image(),
                Select::make('status')
                    ->options(SocialPostStatus::class)
                    ->default('pending_review')
                    ->required(),
                DateTimePicker::make('scheduled_for'),
                DateTimePicker::make('published_at'),
                TextInput::make('external_id'),
                Textarea::make('error')
                    ->columnSpanFull(),
            ]);
    }
}
