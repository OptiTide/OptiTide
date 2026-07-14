<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Staff-only tracking settings. Only stores format-validated IDs (never raw
 * <script>); the <x-analytics> component re-validates before rendering into
 * fixed templates.
 */
class ManageAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Analytics & tracking';

    protected string $view = 'filament.pages.manage-analytics';

    // Tracking settings are admin-only.
    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public string $ga4 = '';

    public string $gtm = '';

    public string $pixel = '';

    protected function rules(): array
    {
        return [
            'ga4' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,20}$/'],
            'gtm' => ['nullable', 'string', 'regex:/^GTM-[A-Z0-9]{4,20}$/'],
            'pixel' => ['nullable', 'string', 'regex:/^[0-9]{5,20}$/'],
        ];
    }

    protected array $validationAttributes = [
        'ga4' => 'GA4 measurement ID',
        'gtm' => 'GTM container ID',
        'pixel' => 'Meta pixel ID',
    ];

    public function mount(): void
    {
        $this->ga4 = Setting::get('ga4_measurement_id') ?? '';
        $this->gtm = Setting::get('gtm_container_id') ?? '';
        $this->pixel = Setting::get('meta_pixel_id') ?? '';
    }

    public function save(): void
    {
        $this->validate();

        Setting::put('ga4_measurement_id', $this->ga4 !== '' ? $this->ga4 : null);
        Setting::put('gtm_container_id', $this->gtm !== '' ? $this->gtm : null);
        Setting::put('meta_pixel_id', $this->pixel !== '' ? $this->pixel : null);

        Notification::make()->title('Analytics settings saved')->success()->send();
    }
}
