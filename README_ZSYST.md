# Z-Syst — Phase 1: Setup Notes

This file documents Phase‑1 setup: migrations, tenant enforcement, and quick run instructions.

1) Run migrations

Place the migration files (already added) under `database/migrations` and run:

```bash
php artisan migrate
```

If starting fresh (will drop data):

```bash
php artisan migrate:fresh --seed
```

2) Tenant enforcement (how it works)

- Models that should be tenant-scoped must use the `HasCompany` trait (e.g. `use App\Traits\HasCompany;`).
- The trait adds the `App\Scopes\TenantScope` global scope which applies `WHERE company_id = {current}` automatically.
- Set the current tenant by providing `X-Company-Id` header, or ensure authenticated users have `company_id` set. The `TenantMiddleware` binds the current company id into the container (`tenant.company_id`).

3) Kernel registration

Register the middleware in `app/Http/Kernel.php` (for API routes):

```php
protected $middlewareGroups = [
    'api' => [
        // ...
        \App\Http\Middleware\TenantMiddleware::class,
    ],
];
```

4) Using models

Example model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class Drug extends Model
{
    use HasCompany;

    protected $fillable = ['company_id','name','generic_name','barcode'];
}
```

5) Notes and recommendations

- Defense-in-depth: keep DB foreign keys and unique constraints scoped by `company_id`.
- For console tasks (migrations, seeds), bind a default tenant when running tenant-aware seeding.
- Consider adding a `TenantManager` service if you need richer tenant resolution (subdomain, token, etc.).
 - A `TenantManager` service is provided (`App\Services\TenantManager`). It resolves tenant by `X-Company-Id` header, authenticated user, or route model binding. The `TenantMiddleware` uses it to bind `tenant.company_id` into the container.

Usage example (manual bind):

```php
app(\App\Services\TenantManager::class)->bindTenant($company);
```

Host/subdomain resolution

 - To support tenant subdomains (e.g., `demo.example.com`), set a `slug` value on the `companies` table for each tenant (e.g., `demo`). `TenantManager` will map the first host segment to `companies.slug` and bind the matching tenant automatically.
 - For local testing, add an entry like `127.0.0.1 demo.localhost` to your hosts file and visit `http://demo.localhost:8000`.
