#!/bin/sh
# One-shot deploy work, run by render.yaml's preDeployCommand in a separate
# container BEFORE the new release takes traffic. If any step fails, Render
# aborts the deploy and the previous release keeps serving.
#
# Only put work here whose effects live OUTSIDE this container — i.e. the
# external database. Anything written to bootstrap/cache or public/ happens in
# the one-off container and is thrown away with it; that work lives in
# entrypoint.sh instead.
set -eu

cd /var/www/html

echo "== deploy: artisan =="
php artisan --version

# Without APP_KEY the app boots but cannot encrypt cookies, sessions or the
# encrypted casts, so every authenticated request fails in a confusing way.
# Fail the deploy instead of shipping that.
if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is empty. Set it on the Render service:" >&2
    echo "       php artisan key:generate --show   (store the base64: value)" >&2
    exit 1
fi

echo "== deploy: migrations =="
# Production was baselined against these migrations on 2026-11-10 — the
# public.migrations ledger records all 116 as applied — so this is normally a
# no-op that only picks up migrations added since the previous deploy.
#
# --isolated takes a cache lock so two instances cannot migrate concurrently.
# With CACHE_DRIVER=database that lock is a row in public.cache_locks — i.e.
# genuinely shared across every instance and every container, not per-filesystem
# as it would be with CACHE_DRIVER=file. This is why the driver choice in
# render.yaml matters for deploy safety, not only for runtime.
php artisan migrate --force --isolated

echo "== deploy: ready =="
