# syntax=docker/dockerfile:1
# =============================================================================
# OptiTide production image — FrankenPHP + PHP 8.3.
#
# ONE image runs all four process types (web / worker / scheduler / reverb);
# docker-compose.yaml starts each with a different command. Coolify builds this
# from the repo root.
# =============================================================================

# ---- Stage 1: compile front-end assets (Vite / Tailwind v4) -----------------
FROM node:22-bookworm-slim AS frontend
WORKDIR /app

# VITE_* values are compiled INTO the browser bundle, so they must be present
# now, at build time (not runtime). Coolify: set these as compose build args.
ARG VITE_APP_NAME=OptiTide
ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV VITE_APP_NAME=$VITE_APP_NAME \
    VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage 2: application image --------------------------------------------
FROM dunglas/frankenphp:php8.3-bookworm AS app

# PHP extensions. intl/bcmath/zip are hard composer requirements; pdo_pgsql is
# for the managed Postgres; redis covers REDIS_CLIENT=phpredis if drivers are
# flipped; gd/pcntl/opcache are for image handling, queue signal-handling and
# performance. install-php-extensions ships in the FrankenPHP image.
RUN install-php-extensions \
        intl bcmath zip pdo_pgsql redis gd pcntl opcache \
    && apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-optitide.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Install PHP deps first for better layer caching. --no-scripts: artisan is NOT
# run at build (runtime env/secrets don't exist yet). package:discover,
# filament:assets and the config/route/view caches run in the entrypoint once
# Coolify has injected the environment.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --no-progress --prefer-dist

# App source + compiled front-end assets.
COPY . .
COPY --from=frontend /app/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && mkdir -p storage/framework/sessions storage/framework/views \
                storage/framework/cache storage/logs bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8080
ENTRYPOINT ["entrypoint"]
# Default command = the web server. worker/scheduler/reverb override this.
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
