
# 🏗️ المرحلة 1: إصلاح الأساس (Foundation Fixes)

> ⏱️ **المدة المتوقعة**: 1-2 أسبوع
> 🎯 **الهدف**: بناء أساس متين قبل إضافة أي ميزة صيدلية

## 📋 Checklist المرحلة

### ✅ المهمة 1.1: إصلاح الهجرات الأساسية المفقودة

**الملفات المطلوبة:**
- `database/migrations/xxxx_xx_xx_000000_create_companies_table.php`
- `database/migrations/xxxx_xx_xx_000001_create_branches_table.php`
- `database/migrations/xxxx_xx_xx_000002_create_departments_table.php`
- `database/migrations/xxxx_xx_xx_000003_create_users_table.php`

**تعليمات التنفيذ:**
1. افحص الهجرات الموجودة أولاً
2. إذا كانت الهجرات الحالية تُعدّل فقط (ALTER)، أنشئ CREATE migrations منفصلة
3.
app/Http/Controllers/Api/V1/Auth/
├── LoginController.php
├── RegisterController.php
├── LogoutController.php
├── ForgotPasswordController.php
├── ResetPasswordController.php
└── UserProfileController.php

**الكود المطلوب:**
```php
// LoginController.php
public function login(LoginRequest $request): JsonResponse
{
    $credentials = $request->validated();
    
    if (!Auth::attempt($credentials)) {
        throw new InvalidCredentialsException();
    }
    
    $user = Auth::user();
    $token = $user->createToken('api-token')->plainTextToken;
    
    // تسجيل النشاط
    ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'login',
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);
    
    // تحديث آخر تسجيل دخول
    $user->update([
        'last_login_at' => now(),
        'last_login_ip' => $request->ip(),
    ]);
    
    return response()->json([
        'success' => true,
        'data' => [
            'user' => UserResource::make($user),
            'token' => $token,
        ]
    ]);
}

Routes:

// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [LoginController::class, 'login']);
    Route::post('/auth/register', [RegisterController::class, 'register']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [LogoutController::class, 'logout']);
        Route::get('/auth/user', [UserProfileController::class, 'show']);
        Route::put('/auth/user', [UserProfileController::class, 'update']);
    });
});

✅ المهمة 1.3: إضافة Multi-Tenant Data Scoping
الملف المطلوب:
app/Support/Traits/BelongsToCompany.php

<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootedBelongsToCompany(): void
    {
        // تعيين company_id تلقائياً عند الإنشاء
        static::creating(function ($model) {
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
        
        // Global Scope لعزل البيانات
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    auth()->user()->company_id
                );
            }
        });
    }
    
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
    
    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}

التطبيق على كل النماذج:

class Product extends Model
{
    use BelongsToCompany;
    // ...
}

✅ المهمة 1.4: إضافة API Versioning
الهيكل:

routes/
└── api/
    ├── v1/
    │   ├── auth.php
    │   ├── products.php
    │   ├── inventory.php
    │   └── ...
    └── api.php (router الرئيسي)

routes/api.php:
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    require __DIR__ . '/api/v1/auth.php';
    
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        require __DIR__ . '/api/v1/products.php';
        require __DIR__ . '/api/v1/inventory.php';
        // ... باقي الملفات
    });
});

✅ المهمة 1.5: بناء Exception Handler موحد
الملفات المطلوبة:
app/Exceptions/
├── BaseApiException.php
├── ProductNotFoundException.php
├── InsufficientStockException.php
├── InvalidCredentialsException.php
├── DuplicateBarcodeException.php
└── ...

BaseApiException:

<?php

namespace App\Exceptions;

use Exception;

abstract class BaseApiException extends Exception
{
    protected string $errorCode;
    protected array $errorData = [];
    
    public function __construct(
        string $message = '',
        string $errorCode = '',
        array $errorData = [],
        int $code = 400
    ) {
        parent::__construct($message, $code);
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }
    
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'data' => $this->errorData,
        ], $this->code);
    }
}

🧪 اختبارات المرحلة 1

// tests/Feature/Auth/LoginTest.php
public function test_user_can_login_with_valid_credentials()
{
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);
    
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['user', 'token'],
        ]);
}

public function test_user_cannot_login_with_invalid_credentials()
{
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'wrong@example.com',
        'password' => 'wrongpass',
    ]);
    
    $response->assertStatus(401);
}

✅ معايير القبول (Acceptance Criteria)
كل الهجرات الأساسية تعمل بدون أخطاء
php artisan migrate:fresh --seed ينجح
تسجيل الدخول/الخروج يعمل
كل API محمي بـ auth:sanctum
Global Scope يمنع الوصول لبيانات شركات أخرى
Exception Handler يرجع responses موحدة
كل endpoint له Test واحد على الأقل
Coverage 80%+ للمرحلة 1
⚠️ تحذيرات
❌ لا تبدأ المرحلة 2 قبل إكمال هذه المرحلة
❌ لا تعدل الكود الموجود في RBAC, Branch Limits
✅ اختبر كل خطوة قبل الانتقال للتالية
✅ وثّق كل قرار في تعليقات الكود


---

## 📄 الملف 7: `14_ANTI_DUPLICATION.md`

```markdown
# 🚫 بروتوكول منع التكرار (Anti-Duplication Protocol)

> ⚠️ **هذا الملف يجب قراءته قبل كتابة أي كود جديد**

## 🔍 خطوات الفحص قبل كتابة أي كود

### الخطوة 1: فحص الملفات الموجودة
```bash
# قبل إنشاء أي ملف جديد، افحص:
find app -name "*.php" | xargs grep -l "اسم_الدالة_أو_الكلاس"
# 🚫 بروتوكول منع التكرار (Anti-Duplication Protocol)

> ⚠️ **هذا الملف يجب ق
الخطوة 2: فحص الـ Services الموجودة
هل يوجد Service يغطي هذه الوظيفة؟
هل يمكن توسيع Service موجود بدلاً من إنشاء جديد؟
الخطوة 3: فحص الـ Traits
هل هذه الوظيفة مشتركة بين عدة Models؟
هل يمكن تحويلها إلى Trait؟
الخطوة 4: فحص الـ Helpers
هل هذه دالة مساعدة عامة؟
هل يجب وضعها في app/Support/Helpers/؟
📋 قائمة الـ Duplicates الشائعة وكيفية تجنبها
❌ خطأ: تكرار Validation Rules

// ❌ خطأ
class StoreProductRequest extends FormRequest {
    public function rules() {
        return [
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode',
            // ...
        ];
    }
}

class UpdateProductRequest extends FormRequest {
    public function rules() {
        return [
            'name' => 'required|string|max:255',  // مكرر!
            'barcode' => 'required|string|unique:products,barcode,' . $this->product->id,
            // ...
        ];
    }
}

✅ صحيح: استخدام Rule Classes

// app/Rules/ProductRules.php
class ProductRules
{
    public static function store(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['required', 'string', new UniqueBarcode()],
            'category_id' => ['required', 'exists:categories,id'],
            // ...
        ];
    }
    
    public static function update(Product $product): array
    {
        return array_merge(self::store(), [
            'barcode' => ['required', 'string', new UniqueBarcode($product->id)],
        ]);
    }
}

// الآن في Requests:
class StoreProductRequest extends FormRequest {
    public function rules() { return ProductRules::store(); }
}

class UpdateProductRequest extends FormRequest {
    public function rules() { return ProductRules::update($this->product); }
}

❌ خطأ: تكرار استعلامات Eloquent

// ❌ في Controllers مختلفة
$products = Product::where('company_id', auth()->user()->company_id)
    ->where('is_active', true)
    ->where('current_stock', '>', 0)
    ->with(['category', 'manufacturer'])
    ->get();

    ✅ صحيح: استخدام Scopes

// في Model Product
class Product extends Model {
    public function scopeAvailable($query) {
        return $query->where('is_active', true)
                    ->where('current_stock', '>', 0);
    }
    
    public function scopeWithRelations($query) {
        return $query->with(['category', 'manufacturer']);
    }
}

// الاستخدام:
$products = Product::available()->withRelations()->get();

❌ خطأ: تكرار Business Logic

// ❌ في Controllers مختلفة
public function createSale(Request $request) {
    // 50 سطر من منطق البيع...
}

public function createPrescriptionSale(Request $request) {
    // نفس الـ 50 سطر مع اختلافات بسيطة...
}

✅ صحيح: استخدام Service Layer

class SaleService {
    public function createSale(CreateSaleDTO $dto): Sale {
        // منطق البيع الأساسي
    }
    
    public function createPrescriptionSale(CreateSaleDTO $dto, Prescription $prescription): Sale {
        $sale = $this->createSale($dto);
        $this->linkPrescription($sale, $prescription);
        return $sale;
    }
}


📊 Checklist قبل Commit
هل بحثت عن كود مشابه موجود؟
هل يمكن إعادة استخدام Service موجود؟
هل يمكن تحويل الكود إلى Trait؟
هل يمكن استخدام Scope بدلاً من Query مكرر؟
هل الـ Validation Rules معادة الاستخدام؟
هل الـ Response Format موحد؟
هل الـ Error Handling موحد؟
🛠️ أدوات كشف التكرار
استخدام PHPStan

composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app --level 5

استخدام PMD/PHPMD

composer require --dev phpmd/phpmd
./vendor/bin/phpmd app text cleancode,codesize,unusedcode

استخدام Laravel Pint (لتنسيق موحد)

composer require --dev laravel/pint
./vendor/bin/pint

📝 أمثلة على الأنماط القابلة لإعادة الاستخدام
1. CRUD Service Pattern

abstract class BaseService {
    protected Model $model;
    
    public function __construct(Model $model) {
        $this->model = $model;
    }
    
    public function getAll(array $filters = []): Collection { }
    public function getById(int $id): Model { }
    public function create(array $data): Model { }
    public function update(Model $model, array $data): Model { }
    public function delete(Model $model): bool { }
}

2. Repository Pattern

abstract class BaseRepository {
    protected Model $model;
    
    public function find(int $id): ?Model { }
    public function create(array $data): Model { }
    public function update(int $id, array $data): Model { }
    public function delete(int $id): bool { }
}

3. Resource Pattern

abstract class BaseResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

