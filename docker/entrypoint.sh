#!/bin/sh
# Runs on EVERY container start, for both roles (web and cron), so it must stay
# fast and idempotent.
#
# Why the caches are built here and not in deploy.sh: Render runs
# `preDeployCommand` in a *separate* one-off container. Only the mounted disk
# (/var/www/html/storage) survives from it. config:cache and package:discover
# write to bootstrap/cache, which lives in the container filesystem, so building
# them in preDeploy would produce a cache that the serving container never sees.
# Migrations, by contrast, write to the external database — those belong in
# deploy.sh.
set -eu

APP_ROLE="${APP_ROLE:-web}"
PORT="${PORT:-10000}"

cd /var/www/html

# Render mounts a persistent disk over /var/www/html/storage. A mount hides
# whatever the image put at that path, so the skeleton Laravel needs has to be
# recreated on every boot — a missing storage/framework/views is a hard 500 on
# the first rendered page.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/app/public \
    storage/logs \
    bootstrap/cache

# nginx and the php-fpm pool both run workers as www-data and write logs,
# compiled views and cached config.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# composer.json's post-autoload-dump does not run package:discover, so without
# this bootstrap/cache/packages.php is missing and auto-discovered providers
# (Sanctum, Spatie Permission, dompdf, Excel, laravel-modules) never register.
php artisan package:discover --ansi

# Bake the real environment into bootstrap/cache/config.php. Safe here because
# configuration comes from Render's environment variables, which stay readable
# through env() (clear_env = no in docker/php-fpm-pool.conf, variables_order =
# EGPCS in docker/php.ini). This matters: the codebase calls env() directly in
# 451 places outside config/, including the admin system-settings view.
#
# Never `route:cache` — routes/web.php has closure routes (e.g. /dashboard) that
# Artisan refuses to serialize, so the command aborts. See RUNBOOK §7.
php artisan config:cache

if [ "$APP_ROLE" = "web" ]; then
    # Render assigns the port; nginx listens on whatever this instance got.
    # Only ${PORT} is substituted, leaving nginx's own variables alone.
    envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
    nginx -t

    # Precompile Blade so the first request after a deploy does not pay for it.
    # Written under storage/, i.e. on the disk, so it survives restarts too.
    php artisan view:cache

    # public/storage is not committed and lives in the container filesystem, so
    # the symlink must be recreated each boot; 13 call sites serve uploads
    # through Storage::url()/asset('storage') and would 404 without it.
    php artisan storage:link
fi

# The scheduler is not a long-running process: render.yaml runs it as a cron
# service that starts a container per tick, so hand over to the start command
# and let the container exit when the tick finishes.
if [ "$APP_ROLE" = "cron" ]; then
    exec php artisan schedule:run
fi

exec "$@"
