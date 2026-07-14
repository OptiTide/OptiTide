<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('company'),
                TextInput::make('website_url')
                    ->url(),
                Select::make('source')
                    ->options([
                        'contact_form' => 'Contact form',
                        'seo_audit' => 'Instant SEO audit',
                        'referral' => 'Referral',
                        'manual' => 'Manually added',
                    ])
                    ->default('contact_form')
                    ->required(),
                Select::make('status')
                    ->options(LeadStatus::class)
                    ->default('new')
                    ->required(),
                Textarea::make('message')
                    ->columnSpanFull(),
                TextInput::make('seo_report_path')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Set automatically by the SEO audit pipeline.'),
                Select::make('assigned_to')
                    ->label('Assigned to')
                    ->relationship(
                        'assignee',
                        'name',
                        modifyQueryUsing: fn ($query) => $query->whereIn('role', ['admin', 'va']),
                    ),
            ]);
    }
}
