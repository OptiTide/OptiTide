<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                // Required on create; leave blank on edit to keep the current password.
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state)),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default('client')
                    ->required()
                    // Prevent self-demotion: an admin can't change their own
                    // role (which would lock them out of admin abilities). The
                    // disabled field isn't dehydrated, so the value is preserved.
                    ->disabled(fn (?User $record): bool => $record !== null && $record->is(Auth::user()))
                    ->helperText(fn (?User $record): ?string => $record !== null && $record->is(Auth::user())
                        ? 'You cannot change your own role.'
                        : null),
                TextInput::make('company_name'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('locale')
                    ->required()
                    ->default('en'),
                TextInput::make('preferred_currency')
                    ->required()
                    ->default('AUD')
                    ->maxLength(3),
                TextInput::make('referral_code')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
                Select::make('referred_by')
                    ->label('Referred by')
                    ->relationship('referrer', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
