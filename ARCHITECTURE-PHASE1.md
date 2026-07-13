# ARCHITECTURE-PHASE1.md

# Z-Syst Phase 1 Architecture — Products Module

## 1. الهدف من المرحلة الأولى

المرحلة الأولى تركز على بناء الوحدة الأساسية الأولى من النظام: إدارة المنتجات والأدوية، مع الالتزام بالمتطلبات التالية:

- بنية Laravel واضحة ومناسبة للإنتاج
- عزل صارم بين المستأجرين (Tenant Isolation)
- فصل منطق العمل عن Controllers
- استخدام Service Layer + Actions + DTOs + Form Requests
- دعم عربي كامل و RTL لاحقًا
- اختبارات شاملة لكل مكون أساسي

## 2. ما الذي سيتم بناؤه في هذه المرحلة

### الوحدات الأساسية
- Products
- Categories
- Manufacturers

### الإضافات الأساسية
- CRUD كامل للمنتجات
- بحث متقدم بالاسم/الباركود/الاسم العلمي
- فلترة حسب الفئة أو الشركة المصنعة
- دعم الاستيراد والتصدير لاحقًا
- التحقق من تكرار الباركود داخل الشركة
- دعم tenant-aware queries

## 3. المبادئ المعمارية الأساسية

### 3.1 Tenant Scoping
كل نموذج مرتبط بالمنتجات يجب أن يكون tenant-aware.

- يجب استخدام trait مثل HasCompany
- يجب أن تكون جميع الاستعلامات الخاصة بالمنتجات مرتبطة ب company_id الحالي
- لا يُسمح بالوصول عبر الشركات

### 3.2 Service Layer
لا يُسمح بكتابة منطق الأعمال داخل Controllers.

جميع العمليات مثل:
- إنشاء منتج
- تحديث منتج
- حذف منتج
- البحث
- الاستيراد

يجب أن تتم عبر Services.

### 3.3 DTOs
يجب استخدام DTOs بدل تمرير arrays الخام إلى Services.

### 3.4 Form Requests
كل endpoint يجب أن يملك Request مخصص للـ validation.

### 3.5 Transactions
العمليات التي تتضمن أكثر من خطوة أو أكثر من جدول يجب أن تتم داخل transaction.

### 3.6 Soft Deletes
لا يُسمح بحذف فعلي من قاعدة البيانات.

### 3.7 Audit Logging
كل عملية إنشاء/تعديل/حذف يجب تسجيلها في activity logs.

---

## 4. هيكل المجلدات المقترح

```text
app/
├── Actions/
│   └── Products/
│       ├── CreateProduct.php
│       ├── UpdateProduct.php
│       └── DeleteProduct.php
├── DTOs/
│   ├── ProductDTO.php
│   ├── CreateProductDTO.php
│   └── UpdateProductDTO.php
├── Http/
│   ├── Controllers/Api/V1/Products/
│   │   ├── ProductController.php
│   │   └── CategoryController.php
│   └── Requests/Products/
│       ├── StoreProductRequest.php
│       ├── UpdateProductRequest.php
│       └── SearchProductRequest.php
├── Models/
│   ├── Product.php
│   ├── Category.php
│   └── Manufacturer.php
├── Services/
│   └── Product/
│       ├── ProductService.php
│       └── ProductSearchService.php
├── Policies/
│   └── ProductPolicy.php
└── Events/
    └── Product/
        ├── ProductCreated.php
        └── ProductUpdated.php
```

---

## 5. قاعدة البيانات

### 5.1 الجدول: products

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('generic_name')->nullable();
    $table->string('brand_name')->nullable();
    $table->string('barcode')->nullable()->index();
    $table->string('sku')->nullable()->unique();
    $table->foreignId('category_id')->nullable()->constrained('categories');
    $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers');
    $table->string('dosage_form')->nullable();
    $table->string('strength')->nullable();
    $table->string('unit_of_measure')->default('piece');
    $table->boolean('prescription_required')->default(false);
    $table->string('controlled_substance_schedule')->nullable();
    $table->string('storage_conditions')->nullable();
    $table->integer('min_stock_level')->default(0);
    $table->integer('reorder_point')->default(0);
    $table->integer('max_stock_level')->default(0);
    $table->decimal('cost_price', 12, 2)->default(0);
    $table->decimal('selling_price', 12, 2)->default(0);
    $table->decimal('tax_rate', 5, 2)->default(0);
    $table->decimal('discount_percentage', 5, 2)->default(0);
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    $table->json('side_effects')->nullable();
    $table->json('interactions')->nullable();
    $table->json('contraindications')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->foreignId('company_id')->constrained('companies');
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

### 5.2 الجدول: categories

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('company_id')->constrained('companies');
    $table->timestamps();
    $table->softDeletes();
});
```

### 5.3 الجدول: manufacturers

```php
Schema::create('manufacturers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('contact_person')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->text('address')->nullable();
    $table->string('website')->nullable();
    $table->string('logo')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('company_id')->constrained('companies');
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 6. نماذج البيانات

### 6.1 Model: Product

```php
class Product extends Model
{
    use HasCompany, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'generic_name', 'brand_name', 'barcode', 'sku',
        'category_id', 'manufacturer_id', 'dosage_form', 'strength',
        'unit_of_measure', 'prescription_required', 'controlled_substance_schedule',
        'storage_conditions', 'min_stock_level', 'reorder_point', 'max_stock_level',
        'cost_price', 'selling_price', 'tax_rate', 'discount_percentage',
        'description', 'image', 'side_effects', 'interactions', 'contraindications',
        'is_active', 'is_featured', 'company_id', 'created_by', 'updated_by'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }
}
```

### 6.2 Model: Category

```php
class Category extends Model
{
    use HasCompany, SoftDeletes;

    protected $fillable = ['name', 'slug', 'parent_id', 'description', 'image', 'sort_order', 'is_active', 'company_id'];
}
```

### 6.3 Model: Manufacturer

```php
class Manufacturer extends Model
{
    use HasCompany, SoftDeletes;

    protected $fillable = ['name', 'contact_person', 'email', 'phone', 'address', 'website', 'logo', 'is_active', 'company_id'];
}
```

---

## 7. Service Layer

### 7.1 ProductService

المسؤولية:
- إنشاء منتج
- تحديث منتج
- حذف منتج
- البحث
- التحقق من التكرار
- تشغيل الأحداث

```php
class ProductService
{
    public function create(CreateProductDTO $dto): Product
    {
        // validate barcode uniqueness within company
        // create product
        // fire event
        // return product
    }

    public function update(Product $product, UpdateProductDTO $dto): Product
    {
        // update data
        // fire event
        // return updated product
    }

    public function search(SearchProductDTO $dto): LengthAwarePaginator
    {
        // apply search filters and pagination
    }
}
```

### 7.2 ProductSearchService

المسؤولية:
- البحث السريع بناءً على:
  - الاسم التجاري
  - الاسم العلمي
  - الباركود
  - SKU

---

## 8. DTOs

### 8.1 CreateProductDTO

```php
final class CreateProductDTO
{
    public function __construct(
        public string $name,
        public ?string $genericName,
        public ?string $brandName,
        public ?string $barcode,
        public ?string $sku,
        public ?int $categoryId,
        public ?int $manufacturerId,
        public string $dosageForm,
        public ?string $strength,
        public string $unitOfMeasure,
        public bool $prescriptionRequired,
        public ?string $controlledSubstanceSchedule,
        public ?string $storageConditions,
        public int $minStockLevel,
        public int $reorderPoint,
        public int $maxStockLevel,
        public float $costPrice,
        public float $sellingPrice,
        public float $taxRate,
        public float $discountPercentage,
        public ?string $description,
        public ?string $image,
        public int $companyId,
        public ?int $createdBy,
    ) {}
}
```

---

## 9. Form Requests

### 9.1 StoreProductRequest

```php
class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:100'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'dosage_form' => ['required', 'string'],
            'prescription_required' => ['boolean'],
        ];
    }
}
```

### 9.2 SearchProductRequest

```php
class SearchProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

---

## 10. API Routes

```php
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::get('products/search', [ProductController::class, 'search']);
    Route::post('products/import', [ProductController::class, 'import']);
    Route::get('products/export', [ProductController::class, 'export']);

    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/tree', [CategoryController::class, 'tree']);

    Route::apiResource('manufacturers', ManufacturerController::class);
});
```

---

## 11. Policies

### ProductPolicy

- viewAny
- view
- create
- update
- delete

التحقق يعتمد على صلاحية المستخدم داخل الشركة الحالية.

---

## 12. Testing Strategy

### Unit Tests
- ProductService creates product correctly
- Duplicate barcode throws exception
- Search returns filtered results

### Feature Tests
- Authenticated user can create a product
- User cannot access another company's products
- Product update works correctly

### Example

```php
public function test_can_create_product(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/products', [
        'name' => 'Paracetamol',
        'selling_price' => 10.5,
        'dosage_form' => 'tablet',
    ]);

    $response->assertStatus(201);
}
```

---

## 13. Implementation Order

1. Create migrations for products, categories, manufacturers
2. Create models and tenant-aware traits
3. Create DTOs and Form Requests
4. Create services and actions
5. Create controllers and routes
6. Add policies and authorization checks
7. Add tests
8. Validate with API smoke tests

---

## 14. Notes

- هذا الملف يمثل الأساس المعماري للمرحلة الأولى فقط.
- لا يتم البدء بالوحدات التالية قبل إكمال هذه المرحلة بشكل كامل.
- التركيز الأساسي الآن هو بناء Products module بشكل صحيح ومتوافق مع tenant isolation.
