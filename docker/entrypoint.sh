#!/bin/sh
# Container entrypoint for every OptiTide process type. Runs once at start, with
# Coolify's env already injected, then hands off (exec) to the service command.
#
# Why artisan caching happens HERE and not at image build time: config:cache
# resolves env() at the moment it runs, so caching at build (no secrets yet)
# would bake in empty values. Running it now captures the real runtime env.
set -e
cd /app

# Ensure the writable runtime dirs exist (defensive; also covers tmpfs mounts).
mkdir -p storage/framework/sessions storage/framework/views \
         storage/framework/cache storage/logs bootstrap/cache

# Package manifest + config/event caches are needed by all four services.
php artisan package:discover --ansi
php artisan config:cache
php artisan event:cache

# Web-only work: publish Filament assets, link storage, cache routes/views.
# Set PUBLISH_ASSETS=true on the web service only (see docker-compose.yaml).
if [ "${PUBLISH_ASSETS}" = "true" ]; then
    php artisan filament:assets --ansi
    php artisan storage:link 2>/dev/null || true
    php artisan route:cache
    php artisan view:cache
fi

# Migrations run exactly once per deploy, on the web service only, before it
# starts serving. Set RUN_MIGRATIONS=true on the web service only.
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "==> Running database migrations"
    php artisan migrate --force
fi

exec "$@"
