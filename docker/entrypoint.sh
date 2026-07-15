#!/bin/sh
set -e
cd /var/www/html

mkdir -p storage/logs storage/framework/sessions storage/framework/cache storage/invoices database
chown -R www-data:www-data storage database || true

# Wait for the database, then migrate (idempotent). Never blocks boot forever.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    i=0
    until php bin/console migrate 2>/tmp/migrate.log; do
        i=$((i + 1))
        if [ "$i" -ge 15 ]; then
            echo "FATAL: migrations failed after $i attempts — refusing to start." >&2
            cat /tmp/migrate.log >&2
            # Exit non-zero so the deploy fails visibly instead of serving a
            # broken/partial schema.
            exit 1
        fi
        echo "Waiting for database… ($i)"
        sleep 3
    done
fi

if [ "${SEED_ON_BOOT:-false}" = "true" ]; then
    php bin/console seed || true
fi

exec "$@"
