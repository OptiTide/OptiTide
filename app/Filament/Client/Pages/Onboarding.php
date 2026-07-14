<?php

namespace App\Filament\Client\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;

/**
 * First-login profile wizard. A client is forced here by EnsureOnboarded until
 * they complete it; completing stamps `onboarded_at`, which satisfies the
 * spatie/laravel-onboard step and releases the guard. Collects only the
 * user's own fields via Auth::user() — no record-selecting property, so no IDOR.
 * Hidden from navigation (it's a gate, not a menu item).
 */
class Onboarding extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $title = 'Welcome — complete your profile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.client.pages.onboarding';

    #[Validate('required|string|max:255')]
    public string $company_name = '';

    #[Validate('nullable|string|max:32')]
    public string $phone = '';

    public function mount(): void
    {
        $user = Auth::user();

        // Already done — don't show the gate again.
        if ($user->hasCompletedOnboarding()) {
            $this->redirect(Dashboard::getUrl(panel: 'client'));

            return;
        }

        $this->company_name = $user->company_name ?? '';
        $this->phone = $user->phone ?? '';
    }

    public function save(): void
    {
        $data = $this->validate();

        Auth::user()->update([
            'company_name' => $data['company_name'],
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'onboarded_at' => now(),
        ]);

        Notification::make()
            ->title('Welcome to OptiTide!')
            ->body('Your profile is all set.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl(panel: 'client'));
    }
}
