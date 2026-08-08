# Z-Syst Security Rules

## 🔐 معايير الأمان العامة

### المبادئ الأساسية
1. **Principle of Least Privilege:** منح الحد الأدنى من الصلاحيات
2. **Defense in Depth:** طبقات متعددة من الحماية
3. **Secure by Default:** الإعدادات الافتراضية آمنة
4. **Fail Securely:** الفشل بطريقة آمنة

---

## 🏢 Tenant Isolation

### عزل المستأجرين (Tenant Isolation)

#### Global Scopes على جميع النماذج
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

#### Tenant Access Check Middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantAccessCheck
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $resource = $request->route('business') ?? $request->route('product');

        if ($resource && $resource->business_id !== $user->business_id) {
            abort(403, 'Access denied: Resource belongs to different tenant');
        }

        return $next($request);
    }
}
```

---

## 👤 Authentication & Authorization

### Authentication
```php
// Force HTTPS in production
if (app()->environment('production')) {
    URL::forceScheme('https');
}

// Secure session cookies
'session' => [
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
],
```

### Authorization
```php
// Use Spatie Permission
public function __construct()
{
    $this->middleware('permission:products-create')->only('store');
    $this->middleware('permission:products-read')->only('index', 'show');
    $this->middleware('permission:products-update')->only('edit', 'update');
    $this->middleware('permission:products-delete')->only('destroy');
}
```

### Role-Based Access Control
```php
// Define roles
$adminRole = Role::create(['name' => 'admin']);
$staffRole = Role::create(['name' => 'staff']);

// Assign permissions
$adminRole->givePermissionTo(['products-create', 'products-read', 'products-update', 'products-delete']);
$staffRole->givePermissionTo(['products-read', 'products-update']);
```

---

## 🔒 Input Validation

### Always Validate Input
```php
// ✅ Good
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
    ]);
    
    User::create($validated);
}

// ❌ Bad - No validation
public function store(Request $request)
{
    User::create($request->all());
}
```

### Form Request Validation
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('products-create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ];
    }
}
```

---

## 🛡️ SQL Injection Prevention

### Use Parameterized Queries
```php
// ✅ Good - Eloquent automatically parameterizes
$products = Product::where('name', $request->name)->get();

// ✅ Good - Manual parameterization
$products = DB::select(
    'SELECT * FROM products WHERE name = ?',
    [$request->name]
);

// ❌ Bad - Vulnerable to SQL injection
$products = DB::select(
    "SELECT * FROM products WHERE name = '{$request->name}'"
);
```

---

## 🎭 XSS Prevention

### Escape Output
```php
// ✅ Good - Blade automatically escapes
{{ $user->name }}

// ✅ Good - Manual escaping
{!! htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') !!}

// ❌ Bad - Unescaped output
{!! $user->name !!}
```

### Sanitize Input
```php
// ✅ Good - Strip tags
$cleanInput = strip_tags($request->input('description'));

// ✅ Good - Use HTML Purifier for rich text
$cleanHtml = Purifier::clean($request->input('content'));
```

---

## 🔑 Password Security

### Strong Password Requirements
```php
'password' => [
    'required',
    'min:8',
    'regex:/[a-z]/',      // Must contain lowercase
    'regex:/[A-Z]/',      // Must contain uppercase
    'regex:/[0-9]/',      // Must contain number
    'regex:/[@$!%*#?&]/', // Must contain special char
],
```

### Password Hashing
```php
// ✅ Good - Laravel automatically hashes
User::create([
    'password' => Hash::make($request->password),
]);

// ❌ Bad - Storing plain text
User::create([
    'password' => $request->password,
]);
```

---

## 📦 File Upload Security

### Validate File Types
```php
$request->validate([
    'file' => 'required|mimes:jpg,png,pdf|max:10240', // Max 10MB
]);
```

### Sanitize File Names
```php
$fileName = time() . '_' . Str::slug($request->file->getClientOriginalName());
```

### Store Outside Public Directory
```php
// ✅ Good - Store in storage/app
$path = $request->file->store('uploads', 'local');

// ❌ Bad - Store in public directory
$path = $request->file->move(public_path('uploads'), $fileName);
```

---

## 🔗 CSRF Protection

### Enable CSRF Protection
```php
// All forms should include CSRF token
<form method="POST">
    @csrf
    <!-- Form fields -->
</form>
```

### API Exceptions
```php
// For API routes, use token-based auth
Route::middleware('auth:api')->group(function () {
    // API routes without CSRF
});
```

---

## 🚫 Rate Limiting

### Configure Rate Limiting
```php
// config/throttle.php
'admin' => [
    'throttle:60,1', // 60 requests per minute
],

'api' => [
    'throttle:1000,60', // 1000 requests per hour
],
```

### Custom Rate Limiter
```php
RateLimiter::for('uploads', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});
```

---

## 🔍 Security Headers

### HTTP Security Headers
```php
// middleware/SecurityHeaders.php
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('X-XSS-Protection', '1; mode=block');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
$response->headers->set('Content-Security-Policy', "default-src 'self'");
```

---

## 📊 Audit Logging

### Log All Critical Operations
```php
<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public function log(string $action, ?Model $model = null, array $data = []): AuditLog
    {
        return AuditLog::create([
            'business_id' => auth()->user()?->business_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $data['description'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Log Sensitive Operations
```php
// Log user login
$auditService->logLogin($user);

// Log data changes
$auditService->logUpdated($model, $oldValues, $newValues);

// Log deletions
$auditService->logDeleted($model);
```

---

## 🔐 API Security

### API Authentication
```php
// Use Sanctum for API auth
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

### API Rate Limiting
```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### IP Whitelist (Optional)
```php
// config/security.php
'api' => [
    'enable_ip_whitelist' => env('API_IP_WHITELIST_ENABLED', false),
    'ip_whitelist' => explode(',', env('API_IP_WHITELIST', '')),
],
```

---

## 🗄️ Database Security

### Database Credentials
```env
# .env (Never commit to version control)
DB_PASSWORD=your_secure_password
```

### Encrypted Secrets
```php
// Encrypt sensitive data
$encrypted = encrypt($sensitiveData);

// Decrypt when needed
$decrypted = decrypt($encrypted);
```

### Read-Only Connections
```php
// For reporting, use read-only replica
'connections' => [
    'mysql_read' => [
        'read' => [
            'host' => ['read-replica-1'],
        ],
        'write' => [
            'host' => ['primary-db'],
        ],
    ],
],
```

---

## 🌐 HTTPS Enforcement

### Force HTTPS
```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### HSTS Header
```php
$response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
```

---

## 🔍 Security Testing

### Security Headers Test
```php
public function test_security_headers_are_present()
{
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
}
```

### CSRF Protection Test
```php
public function test_csrf_protection()
{
    $response = $this->post('/products', ['name' => 'Test']);

    $response->assertStatus(419); // CSRF token mismatch
}
```

### Authorization Test
```php
public function test_unauthorized_user_cannot_delete_product()
{
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('products.destroy', $product));

    $response->assertStatus(403);
}
```

---

## 🚨 Common Vulnerabilities

### OWASP Top 10 Prevention

1. **Injection:** Use parameterized queries
2. **Broken Authentication:** Strong passwords, MFA
3. **Sensitive Data Exposure:** Encrypt at rest and in transit
4. **XML External Entities:** Disable XML external entities
5. **Broken Access Control:** Proper authorization checks
6. **Security Misconfiguration:** Secure defaults
7. **Cross-Site Scripting (XSS):** Escape output
8. **Insecure Deserialization:** Validate serialized data
9. **Using Components with Known Vulnerabilities:** Keep dependencies updated
10. **Insufficient Logging & Monitoring:** Comprehensive audit logs

---

## 📦 Dependency Security

### Update Dependencies Regularly
```bash
composer update
npm update
```

### Security Scanning
```bash
# PHP dependencies
composer audit

# JavaScript dependencies
npm audit
```

---

## 🔧 Security Checklist

### Pre-Deployment Checklist
- [ ] All environment variables set
- [ ] APP_DEBUG = false
- [ ] HTTPS enabled
- [ ] Database credentials secure
- [ ] API keys not in code
- [ ] CORS configured correctly
- [ ] Rate limiting enabled
- [ ] Audit logging enabled
- [ ] CSRF protection enabled
- [ ] Security headers configured
- [ ] Dependencies updated
- [ ] No hardcoded secrets
- [ ] File upload validation
- [ ] Input validation on all forms
- [ ] SQL injection prevention
- [ ] XSS prevention

---

## 📚 Resources

### Security Resources
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security](https://laravel.com/docs/security)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)

### Security Tools
- [Laravel Security Checker](https://github.com/enlightn/security-checker)
- [SonarQube](https://www.sonarqube.org/)
- [Burp Suite](https://portswigger.net/burp)

---

**آخر تحديث:** 2026-08-07
