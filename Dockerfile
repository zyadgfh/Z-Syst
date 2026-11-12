# syntax=docker/dockerfile:1
#
# Production image for the Z-Syst Laravel API + Blade admin.
#
# One container runs nginx and php-fpm under supervisord, because Render gives a
# service exactly one process to supervise. The same image also serves the
# scheduler (render.yaml's cron service) by overriding the start command — see
# docker/entrypoint.sh.
#
# Deliberate choices, so they are not "cleaned up" later:
#   * No `php artisan config:cache` / `route:cache` here. Build time has no
#     environment, so baking config would freeze empty values. Caches are built
#     at boot by docker/deploy.sh, which runs with the real env.
#   * `route:cache` is never run at all: routes/web.php registers closure routes
#     (e.g. /dashboard), which Artisan refuses to serialize. See RUNBOOK §7.
#   * ext-redis is installed even though nothing uses Redis yet, so switching
#     CACHE/SESSION/QUEUE to the Valkey instance (RUNBOOK §7, docker-compose.
#     valkey.yml) needs no image change. The composer set has no predis, so the
#     PHP extension is the only way to talk to Redis from this app.

# ---------------------------------------------------------------------------
# Stage 1 — dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

# composer.json/lock first: this layer is reused until dependencies change.
COPY composer.json composer.lock ./

# --no-scripts: package post-install scripts run Artisan, which needs APP_KEY.
# --no-autoloader: the application source is not here yet.
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev

# ---------------------------------------------------------------------------
# Stage 2 — runtime
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS runtime

# Extensions the application actually needs:
#   pdo_pgsql  Supabase/Postgres (the only database driver used in production)
#   bcmath     money and dosage arithmetic in the POS and pricing services
#   gd         barryvdh/laravel-dompdf receipts and image handling
#   zip        maatwebsite/excel imports/exports (PhpSpreadsheet)
#   intl       locale-aware number and date formatting
#   exif       image orientation on uploads
#   pcntl      queue workers and signal handling in schedule:run
#   sockets    php-fpm / supervisor signalling
#   redis      Valkey (see the header note)
#   opcache    production opcode cache
# mbstring, xml, dom, simplexml, xmlreader, xmlwriter, ctype, fileinfo, iconv,
# openssl, session, tokenizer and zlib are already in the official image.
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        postgresql-dev; \
    apk add --no-cache \
        bash \
        curl \
        fcgi \
        icu-libs \
        libzip \
        nginx \
        postgresql-libs \
        supervisor; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j "$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        sockets \
        zip; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del .build-deps; \
    php -m

# nginx and php-fpm both run as root supervisors with www-data workers. The
# stock alpine nginx package ships a config that binds :80 and needs /var/log
# paths we do not use; docker/nginx.conf.template replaces it.
RUN rm -f /etc/nginx/http.d/default.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/99-z-syst.ini
# Replaces the image's pool config: clear_env must be no or the workers never
# see Render's environment variables. See the file header.
COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/entrypoint.sh docker/deploy.sh /usr/local/bin/

COPY --from=vendor --chown=root:root /app /var/www/html

WORKDIR /var/www/html

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/deploy.sh && \
    mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/testing \
             storage/framework/views \
             storage/app/public \
             storage/logs \
             bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Render injects $PORT (10000 by default); nginx listens on it.
ENV PORT=10000 \
    APP_ROLE=web

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS "http://127.0.0.1:${PORT}/api/up" || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Default: serve HTTP. render.yaml overrides this for the scheduler.
CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]
