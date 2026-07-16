# OptiTide CRM — production image for Coolify (PHP 8.3 + Apache).
FROM php:8.3-apache

# --- System + PHP extensions ------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
        libsqlite3-dev libpq-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_pgsql pdo_mysql pdo_sqlite gd zip intl opcache \
    && rm -rf /var/lib/apt/lists/*

# --- Apache: docroot = public/, allow .htaccess ----------------------------
RUN a2enmod rewrite headers
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# --- Composer ---------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# App source.
COPY . .

# Writable runtime dirs.
RUN mkdir -p storage/logs storage/framework/sessions storage/framework/cache storage/invoices database \
    && chown -R www-data:www-data storage database \
    && chmod -R 775 storage database

# Production opcache tuning.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Uploads. PHP defaults to upload_max_filesize=2M, which is BELOW the CV limit
# the careers form advertises (App\Support\Upload::MAX_BYTES = 5M) — without
# this, a 3MB CV is rejected by PHP before our own check ever runs.
# post_max_size must exceed upload_max_filesize to leave room for the other
# fields, or the whole POST is discarded and $_POST arrives empty.
RUN { \
        echo 'upload_max_filesize=5M'; \
        echo 'post_max_size=8M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
    CMD php -r '$c=@file_get_contents("http://127.0.0.1/health"); exit(str_contains((string)$c,"ok")?0:1);'

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
