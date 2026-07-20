
# 🏗️ أنماط البنية المعمارية

## 📐 البنية الطبقة (Layered Architecture)

HTTP Request
↓
[Routes] → api.php (versioned)
↓
[Middleware] → auth:sanctum, tenant, rbac, throttle
↓
[Controller] → thin, only orchestration
↓
[Form Request] → validation & authorization
↓
[Service Layer] → business logic
↓
[Repository] → data access (optional for complex queries)
↓
[Model] → Eloquent, relationships, scopes
↓
[Database]

## 📁 هيكل المجلدات المطلوب
افضل PHP LARAVEL منسق


## 🎯 Service Layer Pattern (إلزامي)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Products\Services;

use App\Domain\Products\Models\Product;
use App\Domain\Products\DTOs\CreateProductDTO;
use App\Domain\Products\Events\ProductCreated;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    /**
     * إنشاء منتج جديد مع كل العمليات المرتبطة
     */
    public function createProduct(CreateProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            // 1. التحقق من عدم تكرار الباركود
            $this->validateBarcodeUnique($dto->barcode);
            
            // 2. إنشاء المنتج
            $product = $this->repository->create($dto->toArray());
            
            // 3. إطلاق الأحداث
            event(new ProductCreated($product));
            
            // 4. مسح الكاش المرتبط
            cache()->forget("products.company.{$product->company_id}");
            
            return $product->load('category', 'manufacturer');
        });
    }

    private function validateBarcodeUnique(string $barcode): void
    {
        if (Product::where('barcode', $barcode)->exists()) {
            throw new DuplicateBarcodeException($barcode);
        }
    }
}

🎯 Repository Pattern (للاستعلامات المعقدة)

<?php

namespace App\Domain\Products\Repositories;

use App\Domain\Products\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function findLowStock(int $companyId, int $limit = 50): Collection
    {
        return Product::where('company_id', $companyId)
            ->whereColumn('current_stock', '<=', 'reorder_point')
            ->where('is_active', true)
            ->with(['category', 'branchStocks'])
            ->limit($limit)
            ->get();
    }
}

🎯 DTO Pattern (لنقل البيانات)


<?php

namespace App\Domain\Products\DTOs;

class CreateProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $genericName,
        public readonly string $barcode,
        public readonly int $categoryId,
        public readonly float $costPrice,
        public readonly float $sellingPrice,
        // ... باقي الحقول
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            genericName: $data['generic_name'] ?? null,
            barcode: $data['barcode'],
            categoryId: (int) $data['category_id'],
            costPrice: (float) $data['cost_price'],
            sellingPrice: (float) $data['selling_price'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'generic_name' => $this->genericName,
            'barcode' => $this->barcode,
            'category_id' => $this->categoryId,
            'cost_price' => $this->costPrice,
            'selling_price' => $this->sellingPrice,
        ];
    }
}

🎯 Enum Pattern (Laravel 9+)

<?php

namespace App\Support\Enums;

enum StockMovementType: string
{
    case IN = 'in';
    case OUT = 'out';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';
    case RETURN = 'return';
    case DAMAGE = 'damage';
    case EXPIRED = 'expired';

    public function affectsStock(): int
    {
        return match($this) {
            self::IN, self::RETURN => 1,
            self::OUT, self::DAMAGE, self::EXPIRED => -1,
            self::TRANSFER, self::ADJUSTMENT => 0,
        };
    }
}

🎯 Trait Pattern (لإعادة استخدام الكود)


<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootedBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && !$model->company_id) {
                $model->company_id = auth()->user()->company_id;
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });
    }
}

🎯 Event-Driven Architecture


// Event
class SaleCompleted
{
    public function __construct(public Sale $sale) {}
}

// Listeners
class DeductStockOnSale
{
    public function handle(SaleCompleted $event): void
    {
        InventoryService::deductForSale($event->sale);
    }
}

class UpdateCustomerLoyalty
{
    public function handle(SaleCompleted $event): void
    {
        if ($event->sale->customer) {
            LoyaltyService::addPoints($event->sale);
        }
    }
}

class NotifyLowStock
{
    public function handle(SaleCompleted $event): void
    {
        LowStockAlertService::check($event->sale);
    }
}

// EventServiceProvider
protected $listen = [
    SaleCompleted::class => [
        DeductStockOnSale::class,
        UpdateCustomerLoyalty::class,
        NotifyLowStock::class,
    ],
];

🎯 Cache Strategy


class ProductRepository
{
    public function findById(int $id): ?Product
    {
        return cache()->remember(
            "products.{$id}",
            now()->addHour(),
            fn() => Product::with(['category', 'manufacturer'])->find($id)
        );
    }

    public function clearCache(Product $product): void
    {
        cache()->forget("products.{$product->id}");
        cache()->forget("products.company.{$product->company_id}");
        cache()->tags(['products'])->flush();
    }
}


