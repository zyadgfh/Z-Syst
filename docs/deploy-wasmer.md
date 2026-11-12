# Deploying Z-Syst (Laravel app) to Wasmer Edge

Status: **experimental / demo-only** for this project. Wasmer Edge runs PHP as a
single `php -S` process against a **read-only** package filesystem, with no queue
workers, no scheduler and no subprocess spawning. It is a reasonable place to
show the storefront/API to a reviewer; it is not a production home for a
multi-tenant pharmacy SaaS (see [Limitations](#5-limitations-and-what-breaks)).

Everything below matches the "Create app · Wasmer" import flow
(`wasmer.io/apps/create?github=zyadgfh/z-syst&intent=laravel`).

---

## 1. The three command fields

| Field | Value |
| --- | --- |
| **Install Command** | `composer install --no-dev --optimize-autoloader --prefer-dist` |
| **Build Command** | `php artisan optimize:clear` |
| **Start Command** | `php -S 0.0.0.0:8080 -t public` |
| **Enable Database** | **Off** if you keep Supabase (recommended). **On → PostgreSQL** only if you want Wasmer to provision a fresh DB. |

**Why no `npm run build`:** no Blade view or module view uses `@vite`; the
frontend assets under `public/assets` are committed. `npm run build` in this
repo is the Vercel static build (`vite.config.vercel.js` → `dist/`), which the
Laravel app never reads. If you later switch the views to `@vite`, add
`npm ci && npm run build:laravel` to the build command.

**Why `php artisan optimize:clear`:** do **not** run `config:cache` in the build.
The cached config bakes in the build container's absolute paths, which will not
match `/app` at runtime. If you must cache, cache and then rewrite:

```bash
php artisan config:cache && sed -i "s|$(pwd)|/app|g" bootstrap/cache/*.php
```

**Why the start command works:** PHP's built-in server serves existing files from
`public/` and falls back to the nearest `index.php` for extension-less paths, so
Laravel routes such as `/admin/dashboard` reach `public/index.php`. URLs that
contain a dot (e.g. `/storage/x.pdf`) are treated as static files.

If the deployment reports "no listening port", retry with
`php -S 127.0.0.1:8080 -t public`. Wasmer's own Laravel guide uses
`localhost:8080`, which is equivalent for their proxy.

---

## 2. Environment variables

Copy `.env.wasmer.example`, fill the `<PLACEHOLDER>`s, and paste it into the
"Paste a .env above to populate the form" box (or add the same keys later under
the app's **Settings → Secrets**).

The drivers there are chosen for a read-only filesystem:

| Key | Value | Why |
| --- | --- | --- |
| `SESSION_DRIVER` | `database` | `file` needs a writable `storage/framework/sessions`; the `sessions` table migration exists. |
| `CACHE_DRIVER` | `array` | There is no `cache` table migration, and no Redis/Valkey on Edge. Per-instance, fine for read-mostly traffic. |
| `LOG_CHANNEL` | `errorlog` | `config/logging.php` maps `errorlog` (type `0`) to `php://stderr`. |
| `VIEW_COMPILED_PATH` | `/app/storage/framework/views` | Blade must compile somewhere writable — the volume. |
| `DB_SSLMODE` | `require` | Wasmer-managed Postgres and Supabase both require TLS. |
| `FILESYSTEM_DISK` | `s3` | `public/` and the app code are read-only after deploy. |

Repo config supports this out of the box now:

- `config/database.php` — pgsql reads `DB_SSLMODE` (default `prefer`) and falls
  back to `DB_NAME` when `DB_DATABASE` is unset, so Wasmer's injected
  `DB_HOST`/`DB_PORT`/`DB_NAME`/`DB_USERNAME`/`DB_PASSWORD` work as-is.
- `config/session.php` — `SESSION_FILES_PATH` can relocate the file session store.

---

## 3. Read-only filesystem: mount a volume

Only volumes are writable on Wasmer Edge. Laravel writes to `storage/` (compiled
views, logs, barcode images in `storage/app/public`, Firebase credentials,
backups), so mount a volume over it. If you deploy with the CLI, put this in
`app.yaml` (the Git import flow generates an equivalent file for you):

```yaml
kind: wasmer.io/App.v0
name: z-syst-api
owner: zyadgfh
package: .
scaling:
  mode: single_concurrency      # php -S is single-process
volumes:
  - name: storage
    mount: /app/storage
capabilities:
  instaboot:
    requests:
      - path: /
# Uncomment to let Wasmer provision PostgreSQL instead of using Supabase:
#  database:
#    engine: postgres
```

The volume starts **empty**, and it hides the packaged `storage/` tree. Create
the directories once over SFTP (enable the SSH/SFTP capability, see
<https://docs.wasmer.io/edge/learn/ssh/sftp>):

```bash
mkdir -p framework/views framework/cache/data framework/sessions logs app/public app/private app/backups
touch installed          # clears the /login -> /install gate (see below)
```

Until `installed` exists, `/login` redirects to `/install`
(`app/Http/Middleware/SetLocale.php`).

### Custom php.ini (memory for dompdf/Excel)

Wasmer's PHP CLI defaults to a 128 MB memory limit, which is tight for
`barryvdh/laravel-dompdf` and `maatwebsite/excel`. Add `config/php.ini`:

```ini
memory_limit = 512M
upload_max_filesize = 20M
post_max_size = 25M
opcache.validate_timestamps = 0
```

and reference it from a repo `wasmer.toml` (this replaces the auto-generated
package config, so keep the `[fs]` mapping):

```toml
[dependencies]
"php/php" = "=8.3.4"

[fs]
"/app/" = "."
"/config" = "config"

[[command]]
name = "run"
module = "php/php:php"
runner = "wasi"

[command.annotations.wasi]
main-args = ["-t", "/app/public", "-S", "0.0.0.0:8080"]
env = ["PHPRC=/config/"]
```

---

## 4. Database migrations

There is no shell in the runtime, so run migrations from your machine against
the target database (or via the app's SSH capability):

```bash
cp .env.wasmer.example .env.wasmer        # fill the DB_* values
set -a; . ./.env.wasmer; set +a
php artisan migrate --force
```

91 migrations run in one go — do it before the first deploy so the app boots
against a complete schema. The `sessions` table is required by
`SESSION_DRIVER=database`.

---

## 5. Limitations (and what breaks)

| Area | Behaviour on Wasmer Edge |
| --- | --- |
| HTTP server | `php -S` PHP **development** server: one request at a time per instance, no OPcache tuning, no FastCGI. Keep `scaling.mode: single_concurrency`. |
| Filesystem | Package files read-only; only volumes writable. Any un-volumed write (`storage/logs` from the `errors`/`security`/`audit` channels, `public/uploads`, installer state) throws. |
| Queues / scheduler | Nothing runs `queue:work` or `schedule:run`. `QUEUE_CONNECTION=database` keeps jobs but they never drain; OTP mail, backups and report exports will not run. |
| Subprocesses | WASIX has no reliable `exec`; anything shelling out (`BackupService` zips, PDF tools) must be reworked or moved. |
| Uploads | Route to object storage (`FILESYSTEM_DISK=s3`); barcode images written under `storage/app/public` need the volume. |
| Proxies / URLs | `App\Http\Middleware\TrustProxies::$proxies` is `null`, so Laravel cannot see Wasmer's `X-Forwarded-*` headers. Set it to `'*'` (or to Wasmer's edge ranges) or `asset()`/redirect URLs will be `http://`. |
| Installer | `safiull/laravel-installer` is installed via composer but not registered in `config/app.php`; the `/install` route + `storage/installed` marker gate `/login`. |

**Recommendation:** keep production on Render/a VPS (or a Laravel-focused host)
where you get Nginx + PHP-FPM, workers, cron and persistent disk. Use Wasmer Edge
for a public demo of the storefront/landing pages, not for the admin workload.
