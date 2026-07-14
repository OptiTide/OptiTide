<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily follow-up engine: mark overdue invoices + send escalating reminders.
Schedule::command('invoices:process-overdue')->dailyAt('09:00')->withoutOverlapping();

// SEO/SMM engine: publish scheduled blog articles + distribute approved social posts.
Schedule::command('blogs:publish-due')->hourly()->withoutOverlapping();
Schedule::command('social:distribute-due')->everyFifteenMinutes()->withoutOverlapping();

// Server ops: poll uptime + SSL for every active monitor, and run/prune backups.
Schedule::command('monitors:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
