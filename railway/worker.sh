#!/usr/bin/env sh
# Start command for the WORKER service. `exec` so SIGTERM reaches the worker for
# a graceful shutdown on redeploy. --max-time recycles the process hourly so a
# leaked resource or a code update is picked up.
set -e
exec php artisan queue:work --tries=3 --backoff=10 --sleep=3 --max-time=3600
