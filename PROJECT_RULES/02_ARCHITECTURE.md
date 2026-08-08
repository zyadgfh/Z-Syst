# Z-Syst Architecture Guidelines

## 🏗️ البنية المعمارية العامة

### المبادئ الأساسية
1. **Separation of Concerns:** فصل المسؤوليات بين الطبقات
2. **Single Responsibility:** كل فئة/دالة لها مسؤولية واحدة
3. **Dependency Injection:** استخدام DI Container
4. **Service Layer Pattern:** منطق العمل في Services
5. **Repository Pattern:** الوصول للبيانات عبر Repositories (اختياري)

---

## 📁 هيكل المشروع

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # Controllers للمشرفين
│   │   ├── Api/             # API Controllers
│   │   └── Auth/            # Authentication Controllers
│   ├── Middleware/          # Middleware
│   ├── Requests/            # Form Requests
│   └── Kernel.php
├── Models/                  # Eloquent Models
├── Services/                # Business Logic
├── Providers/               # Service Providers
├── Exceptions/              # Custom Exceptions
└── Helpers/                 # Helper Functions

Modules/                     # Modular Structure
├── Landing/
│   ├── App/
│   │   ├── Http/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   └── routes/
└── ...

config/                      # Configuration Files
database/
├── migrations/              # Database Migrations
├── seeders/                # Database Seeders
└── factories/              # Model Factories

resources/
├── views/                  # Blade Templates
├── lang/                   # Language Files
└── assets/                 # Frontend Assets

routes/
├── web.php                 # Web Routes
├── api.php                 # API Routes
└── admin.php               # Admin Routes
```

---

## 🎯 Service Layer Pattern

### متى تستخدم Service؟
- منطق عمل معقد
- عمليات تتضمن عدة models
- عمليات تحتاج transactions
- عمليات قابلة لإعادة الاستخدام

### مثال على Service
```php
<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SaleService
{
    /**
     * Create sale with inventory deduction
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::create($data);
            
            // Update product stock
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $product->decrement('stock', $item['quantity']);
            }
            
            return $sale;
        });
    }
}
```

---

## 🗄️ Database Architecture

### عزل المستأجرين (Tenant Isolation)

#### Global Scopes
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('business', function ($query) {
            if (auth()->check() && auth()->user()->business_id) {
                $query->where('business_id', auth()->user()->business_id);
            }
        });
    }
}
```

#### Tenant Middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->business_id) {
            session(['tenant_id' => auth()->user()->business_id]);
        }
        
        return $next($request);
    }
}
```

---

## 🔌 API Architecture

### RESTful API Standards
- استخدام HTTP methods بشكل صحيح (GET, POST, PUT, DELETE)
- JSON response format
- معايير خطأ موحدة
- Pagination
- Filtering & Sorting

### Response Format
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful",
    "meta": {
        "page": 1,
        "per_page": 15,
        "total": 100
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

---

## 🧊 Model Architecture

### Model Best Practices
- Relationships معرفة في Models
- Accessors & Mutators للتحويل
- Scopes للqueries الشائعة
- Events لتتبع التغييرات
- Casting للdata types

### مثال Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'stock',
        'business_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->where('stock', '<=', 10);
    }
}
```

---

## 🎮 Controller Architecture

### Controller Best Practices
- Controllers رقيقة (Thin Controllers)
- منطق العمل في Services
- Validation في Form Requests
- لا Database queries مباشرة

### مثال Controller
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $product = $this->productService->createProduct($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ]);
    }
}
```

---

## 🔐 Security Architecture

### Middleware Stack
```php
// Verify CSRF
'web' => [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],

// API with token auth
'api' => [
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],

// Admin with tenant check
'admin' => [
    'auth',
    'verified',
    \App\Http\Middleware\TenantContextMiddleware::class,
    \App\Http\Middleware\TenantAccessCheck::class,
],
```

---

## 📊 Caching Strategy

### Redis Cache Usage
```php
// Cache product data
Cache::remember("products:{$businessId}", 3600, function () use ($businessId) {
    return Product::where('business_id', $businessId)->get();
});

// Cache invalidation
Cache::forget("products:{$businessId}");
```

### Cache Keys Pattern
- `business:{id}:products` - Products for business
- `business:{id}:statistics` - Business statistics
- `user:{id}:permissions` - User permissions
- `plan:{id}:features` - Plan features

---

## 🔄 Queue Architecture

### Queue Jobs
```php
<?php

namespace App\Jobs;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReceiptPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Receipt $receipt
    ) {}

    public function handle()
    {
        // Generate PDF logic
    }
}
```

---

## 🧪 Testing Architecture

### Test Structure
```
tests/
├── Unit/
│   ├── Services/
│   └── Models/
├── Feature/
│   ├── Api/
│   └── Admin/
└── TestCase.php
```

### Test Best Practices
- Unit tests للbusiness logic
- Feature tests للAPI endpoints
- Database transactions لكل test
- Factories لtest data

---

## 📝 Logging Architecture

### Log Channels
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

### Audit Logging
```php
use App\Services\AuditService;

// Log action
$auditService->log('created', $model);
$auditService->logUpdated($model, $oldValues, $newValues);
$auditService->logDeleted($model);
```

---

## 🌐 Environment Configuration

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=laravel

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

FORCE_HTTPS=true
```

---

## 🚀 Deployment Architecture

### Production Setup
- Laravel Forge أو Vapor
- AWS أو DigitalOcean
- Redis caching
- CDN for assets
- Load balancer
- SSL certificates

---

## 📊 Monitoring & Observability

### Tools
- **Sentry:** Error tracking
- **New Relic:** Performance monitoring
- **Laravel Telescope:** Debugging
- **Laravel Horizon:** Queue monitoring

### Metrics to Track
- Response time
- Error rate
- Queue throughput
- Database queries
- Cache hit rate

---

## 🔧 Development Workflow

### Git Workflow
```
main (production)
  ↑
develop (staging)
  ↑
feature/* (development)
```

### Commit Message Format
```
type(scope): subject

type: feat, fix, docs, style, refactor, test, chore
scope: module name
subject: short description

Example:
feat(products): add bulk import functionality
fix(auth): resolve token expiration issue
docs(readme): update installation guide
```

---

## 📚 Resources

### Laravel Documentation
- [Laravel Docs](https://laravel.com/docs)
- [Laravel Package Development](https://laravel.com/docs/packages)

### Best Practices
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Clean Code in Laravel](https://github.com/robertbasic/clean-code-in-laravel)

---

**آخر تحديث:** 2026-08-07
