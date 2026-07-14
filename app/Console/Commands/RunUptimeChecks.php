<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitorJob;
use App\Models\ServerMonitor;
use Illuminate\Console\Command;

/**
 * Fans out an uptime + SSL check job for every active server monitor. Run every
 * few minutes by the scheduler; also invokable manually.
 */
class RunUptimeChecks extends Command
{
    protected $signature = 'monitors:check';

    protected $description = 'Poll all active server monitors for uptime and TLS-certificate expiry';

    public function handle(): int
    {
        $count = 0;

        ServerMonitor::active()->each(function (ServerMonitor $monitor) use (&$count) {
            CheckMonitorJob::dispatch($monitor->id);
            $count++;
        });

        $this->info("Dispatched {$count} monitor check(s).");

        return self::SUCCESS;
    }
}
