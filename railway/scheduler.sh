#!/usr/bin/env sh
# Start command for the SCHEDULER service. schedule:work is a long-running
# foreground process that invokes `schedule:run` every 60s — Railway's native
# cron has a 5-minute floor, and several OptiTide schedules need finer cadence
# (monitors:check every 5m, social:distribute-due every 15m).
set -e
exec php artisan schedule:work
