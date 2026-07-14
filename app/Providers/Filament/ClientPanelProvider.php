<?php

namespace App\Providers\Filament;

use App\Enums\UserRole;
use App\Http\Middleware\EnsureOnboarded;
use App\Models\User;
use App\Support\SocialiteUserCreator;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('client')
            ->path('client')
            ->login()
            ->registration(\App\Filament\Client\Auth\Register::class)
            ->brandName('OptiTide')
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\Filament\Client\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\Filament\Client\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\Filament\Client\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->plugin(
                // Client-only social login. Each provider button is config-gated
                // via ->visible() so a button never appears (or dead-links)
                // without its OAuth credentials. Staff panel stays password-only.
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Continue with Google')
                            ->color(Color::Red)
                            ->visible(fn (): bool => filled(config('services.google.client_id'))),
                        Provider::make('github')
                            ->label('Continue with GitHub')
                            ->color(Color::Gray)
                            ->scopes(['user:email'])
                            ->visible(fn (): bool => filled(config('services.github.client_id'))),
                    ])
                    ->registration(true)
                    ->createUserUsing(
                        fn (string $provider, $oauthUser, $plugin) => app(SocialiteUserCreator::class)($provider, $oauthUser, $plugin),
                    )
                    // Staff are password-only. Reject any OAuth identity whose
                    // email belongs to a staff account BEFORE it can link/login —
                    // otherwise the email auto-link would authenticate a staff
                    // user on the shared web guard and hand them /admin access.
                    ->authorizeUserUsing(fn (FilamentSocialitePlugin $plugin, SocialiteUserContract $oauthUser): bool => ! User::query()
                        ->where('email', $oauthUser->getEmail())
                        ->where('role', '!=', UserRole::Client->value)
                        ->exists()),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // Force incomplete clients through the onboarding wizard before
                // they can use the rest of the portal.
                EnsureOnboarded::class,
            ]);
    }
}
