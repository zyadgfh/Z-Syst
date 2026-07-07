# Z-Syst Enterprise Laravel 12 Application

A production-grade, enterprise-level Laravel 12 application with advanced Branch Limit Management System, optimized for high traffic, large databases, and thousands of concurrent users.

## Features

### Branch Limit Management System
- Configurable maximum branch limits per company
- Unlimited branch mode support
- Real-time validation and enforcement
- Admin override capabilities
- Subscription integration
- Notifications at 80%, 90%, and 100% usage
- Bulk operations support
- Comprehensive API endpoints
- Dashboard metrics and analytics
- Caching for optimal performance

### Performance Optimizations
- Database query optimization with eager loading
- Strategic indexing on critical columns
- Cached branch counts to reduce database load
- Efficient pagination for large datasets
- Service layer for centralized logic
- Queue system for background jobs
- Rate limiting for API protection

### Security
- Super Admin authorization checks
- Policy-based access control
- Form request validation
- API rate limiting
- SQL injection protection via Eloquent ORM
- XSS and CSRF protection
- Sanctum API authentication

### Scalability
- Horizontal scaling support
- Shared cache configuration (Redis ready)
- Queue workers for async processing
- Load balancer compatible
- CDN support structure
- Distributed file storage ready

### PharmaSync Product Feature List
For the complete pharmacy-focused offering, see [docs/pharmasync-feature-list.md](docs/pharmasync-feature-list.md).

## Public Pages
- `/` — PharmaSync marketing landing page.
- `/features` — Full PharmaSync feature list page.
- `/docs` — PharmaSync documentation index page.

## System Requirements

- PHP >= 8.2
- MySQL >= 8.0 or PostgreSQL >= 14.0
- Redis (for cache and queue)
- Composer
- Node.js & NPM (for frontend assets)
- Apache/Nginx with mod_rewrite

## Installation

### 1. Clone Repository
```bash
git clone https://github.com/ZyadGamal/Z-Syst.git
cd Z-Syst
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Configure `.env` file:
```env
APP_NAME=Z-Syst
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pharmacy_db
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

### 4. Database Migration
```bash
php artisan migrate
php artisan db:seed
```

### 5. Storage Link
```bash
php artisan storage:link
```

### 6. Queue Worker (Production)
```bash
php artisan queue:work --daemon
```

Or use Supervisor for persistent workers:
```ini
[program:z-syst-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopasgroup=true
killasgroup=true
```

## Configuration

### Branch Limit Settings
Located in `config/branch-limit.php`:
- `warning_threshold`: 80 (percentage)
- `critical_threshold`: 90 (percentage)
- `cache_ttl`: 300 (seconds)
- `stats_cache_ttl`: 600 (seconds)
- `api_rate_limit`: 60 (requests per minute)

### Scheduled Tasks
Defined in `app/Console/Kernel.php`:
```php
$schedule->command('branches:check-limits')->dailyAt('00:00');
$schedule->command('cache:clear')->hourly();
```

Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## API Documentation

### Authentication
All API endpoints require Sanctum authentication:
```bash
Authorization: Bearer {token}
```

### Endpoints

#### List All Companies
```http
GET /api/admin/companies
```

Response:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Acme Corp",
            "max_branches": 10,
            "is_unlimited_branches": false,
            "branches_count": 7,
            "branch_usage_percentage": 70.0
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 25
    }
}
```

#### Update Company Branch Limit
```http
PUT /api/admin/companies/{company}/branch-limit
Content-Type: application/json

{
    "max_branches": 25,
    "is_unlimited_branches": false
}
```

Response:
```json
{
    "message": "Branch limit updated successfully.",
    "data": {
        "id": 1,
        "max_branches": 25,
        "is_unlimited_branches": false
    }
}
```

#### Bulk Update Limits
```http
POST /api/admin/companies/bulk-update-branch-limits
Content-Type: application/json

{
    "updates": [
        {
            "company_id": 1,
            "max_branches": 25,
            "is_unlimited_branches": false
        },
        {
            "company_id": 2,
            "max_branches": null,
            "is_unlimited_branches": true
        }
    ]
}
```

Response:
```json
{
    "message": "Bulk update completed.",
    "results": [
        {
            "company_id": 1,
            "status": "success",
            "message": "Branch limit updated."
        }
    ]
}
```

#### Check Branch Availability
```http
GET /api/admin/companies/{company}/branch-availability
```

Response:
```json
{
    "company_id": 1,
    "max_branches": 10,
    "is_unlimited": false,
    "current_branches": 7,
    "remaining": 3,
    "usage_percentage": 70.0,
    "can_create": true,
    "is_near_limit": true,
    "is_at_limit": false
}
```

## Usage

### Creating a Branch with Limit Enforcement
```php
use App\Services\BranchLimitService;

public function store(Request $request, BranchLimitService $branchLimitService)
{
    $company = auth()->user()->company;
    
    // Enforce limit before creation
    $branchLimitService->enforceBeforeCreate($company);
    
    // Create branch
    $branch = Branch::create($request->validated());
    
    // Increment cached count
    $branchLimitService->incrementBranchCount($company);
    
    return response()->json($branch, 201);
}
```

### Middleware Usage
Apply to routes to enforce limits:
```php
Route::middleware([
    \App\Http\Middleware\EnforceBranchLimit::class
])->group(function () {
    Route::post('/branches', [BranchController::class, 'store']);
});
```

### Event-Driven Subscription Changes
```php
use App\Events\SubscriptionChanged;

event(new SubscriptionChanged($company));

// Listener automatically resets branch limit to plan default
```

## Testing

Run all tests:
```bash
php artisan test
```

Run specific test:
```bash
php artisan test --filter=BranchLimitTest
```

Coverage report:
```bash
php artisan test --coverage
```

## Performance Monitoring

### Metrics to Monitor
- Cache hit rate (target: >95%)
- API response time (target: <200ms)
- Database query count (target: <10 per request)
- Queue job processing time
- Memory usage per request
- Slow query log (threshold: >100ms)

### Logging
All operations are logged to `storage/logs/laravel.log`:
- Branch limit changes
- API access attempts
- Slow queries
- Queue failures
- Notification delivery

## Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure Redis for cache and sessions
- [ ] Set up queue workers
- [ ] Configure Horizon (optional)
- [ ] Set up Telescope for debugging
- [ ] Configure backups
- [ ] Enable HTTPS
- [ ] Set up CDN
- [ ] Configure monitoring (New Relic, Datadog, etc.)

### Docker Deployment (Optional)
```bash
# Build image
docker build -t z-syst .

# Run container
docker run -d \
  -p 8000:8000 \
  --env-file .env \
  z-syst
```

## Maintenance

### Regular Tasks
1. Monitor cache hit rates weekly
2. Review slow query log monthly
3. Archive old branches quarterly
4. Audit admin actions monthly
5. Update dependencies quarterly

### Scaling Guidelines
- **1,000 users**: Single server with Redis
- **10,000 users**: 2 app servers + Redis + Load Balancer
- **100,000 users**: 5+ app servers + Redis Cluster + Read replicas + CDN

## Troubleshooting

### Cache Issues
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Queue Issues
```bash
# Restart workers
php artisan queue:restart

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## License

Proprietary - Z-Syst. All rights reserved.

## Support

For support, email support@z-syst.com or create an issue in the repository.

## Changelog

### v1.0.0 (2024-01-01)
- Initial release with Branch Limit Management System
- Enterprise-grade performance optimizations
- Comprehensive API with rate limiting
- Queue system integration
- Security hardening
- Full test coverage
- Complete documentation"# Z-Syst" 
