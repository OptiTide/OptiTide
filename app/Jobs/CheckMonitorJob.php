<?php

namespace App\Jobs;

use App\Models\ServerMonitor;
use App\Services\MonitorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs one uptime + SSL check for a single monitor. One job per monitor keeps a
 * slow/hanging host from blocking the rest of the fleet. Async on the database
 * queue in dev/prod (sync in tests).
 */
class CheckMonitorJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $monitorId) {}

    public function handle(MonitorService $monitor): void
    {
        $record = ServerMonitor::find($this->monitorId);

        if ($record === null || ! $record->is_active) {
            return;
        }

        $monitor->check($record);
    }
}
