#!/usr/bin/env sh
# Pre-deploy command for the WEB service ONLY. Runs once per deploy, before the
# new version starts serving, so migrations apply exactly once (not on every
# service). Set RAILPACK_SKIP_MIGRATIONS=1 on every service so Railpack's own
# start-container.sh does NOT also migrate.
set -e

echo "==> Running database migrations"
php artisan migrate --force
