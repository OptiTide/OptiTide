<?php

namespace App\Filament\Pages;

use App\Services\Whm\WhmClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;
use Throwable;

/**
 * Staff-only backup + server health dashboard. Lists the archives that
 * spatie/laravel-backup has written to the backup disk, health-checks them
 * against the configured age/size thresholds, and surfaces WHM server status
 * (or "not configured" when the fail-closed NullWhmClient is bound).
 */
class BackupMonitor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Backup monitor';

    protected static ?string $title = 'Backups & server health';

    protected string $view = 'filament.pages.backup-monitor';

    // Backups + server/WHM status are admin-only infrastructure.
    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    /** @var array<int, array{name: string, size_mb: float, age: string, last_modified: string}> */
    public array $backups = [];

    public bool $healthy = false;

    public ?string $healthMessage = null;

    public ?string $diskError = null;

    public bool $whmConfigured = false;

    /** @var array{load: array<int, float>, version: ?string}|null */
    public ?array $whmStatus = null;

    public ?string $whmError = null;

    public function mount(): void
    {
        $this->loadState();
    }

    protected function loadState(): void
    {
        $this->loadBackups();
        $this->loadWhm();
    }

    protected function loadBackups(): void
    {
        // spatie/laravel-backup nests destination + name under a top-level
        // `backup` key; monitor_backups is genuinely top-level.
        $disk = config('backup.backup.destination.disks.0', 'local');
        $name = config('backup.backup.name');
        $checks = config('backup.monitor_backups.0.health_checks', []);
        $maxAgeDays = (int) ($checks[MaximumAgeInDays::class] ?? 1);
        $maxSizeMb = (int) ($checks[MaximumStorageInMegabytes::class] ?? 5000);

        if (blank($name)) {
            $this->diskError = 'Backup archive name is not configured (config/backup.php → backup.name).';
            $this->backups = [];
            $this->healthy = false;

            return;
        }

        try {
            $storage = Storage::disk($disk);

            $files = collect($storage->files($name))
                ->filter(fn (string $path) => str_ends_with($path, '.zip'))
                ->map(fn (string $path) => [
                    'name' => basename($path),
                    'size_mb' => round($storage->size($path) / 1_048_576, 2),
                    'modified' => Carbon::createFromTimestamp($storage->lastModified($path)),
                ])
                ->sortByDesc(fn (array $b) => $b['modified']->timestamp)
                ->values();
        } catch (Throwable $e) {
            $this->diskError = "Could not read the backup disk [{$disk}]: {$e->getMessage()}";
            $this->backups = [];
            $this->healthy = false;
            $this->healthMessage = null;

            return;
        }

        $this->backups = $files->map(fn (array $b) => [
            'name' => $b['name'],
            'size_mb' => $b['size_mb'],
            'age' => $b['modified']->diffForHumans(),
            'last_modified' => $b['modified']->toDayDateTimeString(),
        ])->all();

        if ($files->isEmpty()) {
            $this->healthy = false;
            $this->healthMessage = 'No backups have been created yet. Run one to get started.';

            return;
        }

        $newestOk = $files->first()['modified']->greaterThan(now()->subDays($maxAgeDays));
        $totalMb = $files->sum('size_mb');
        $sizeOk = $totalMb <= $maxSizeMb;

        $this->healthy = $newestOk && $sizeOk;
        $this->healthMessage = match (true) {
            $this->healthy => 'Backups are healthy.',
            ! $newestOk => "The most recent backup is older than {$maxAgeDays} day(s).",
            default => "Total backup size ({$totalMb} MB) exceeds the {$maxSizeMb} MB threshold.",
        };
    }

    protected function loadWhm(): void
    {
        $whm = app(WhmClient::class);
        $this->whmConfigured = $whm->isConfigured();

        if (! $this->whmConfigured) {
            return;
        }

        try {
            $this->whmStatus = $whm->serverStatus();
        } catch (Throwable $e) {
            $this->whmError = $e->getMessage();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runBackup')
                ->label('Run backup now')
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->modalDescription('This queues a full backup job. It runs on the queue worker and may take a few minutes.')
                ->action(function (): void {
                    // Queue it — a synchronous backup would zip the whole app in
                    // the request cycle.
                    Artisan::queue('backup:run');

                    Notification::make()
                        ->title('Backup queued')
                        ->body('A backup job has been dispatched to the queue.')
                        ->success()
                        ->send();
                }),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->loadState()),
        ];
    }
}
