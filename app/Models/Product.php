<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Model - Enhanced Unified Pharmacy Product/Medicine Model
 *
 * Represents medications/products in the pharmacy system.
 * This is the SINGLE source of truth for all product types (medicines, drugs, etc.)
 *
 * @property string $id UUID
 * @property string $company_id
 * @property string|null $branch_id
 * @property string|null $category_id
 * @property string|null $manufacturer_id
 * @property string|null $unit_id
 * @property string $name اسم المنتج (Product Name)
 * @property string|null $generic_name الاسم العلمي
 * @property string|null $brand_name الاسم التجاري
 * @property string|null $product_name اسم المنتج البديل
 * @property string|null $product_code كود المنتج (SKU)
 * @property string|null $barcode الباركود
 * @property string|null $internal_code الكود الداخلي
 * @property string|null $atc_code كود ATC التصنيفي
 * @property string|null $dosage_form شكل الجرعة
 * @property string|null $strength القوة/التركيز
 * @property string|null $unit_of_measure وحدة القياس
 * @property int $pack_size حجم العلبة
 * @property string|null $pack_unit وحدة التعبئة
 * @property bool $prescription_required يحتاج وصفة طبية
 * @property bool $is_controlled مادة خاضعة للرقابة
 * @property string|null $controlled_substance_schedule جدول المواد الخاضعة للرقابة
 * @property bool $requires_special_handling يحتاج معاملة خاصة
 * @property string|null $storage_conditions ظروف التخزين
 * @property int|null $shelf_life_months مدة الصلاحية بالأشهر
 * @property bool $is_splittable قابل للتجزئة
 * @property string|null $split_unit_id وحدة التجزئة
 * @property float|null $split_quantity كمية التجزئة
 * @property float $min_stock الحد الأدنى للمخزون
 * @property float $max_stock الحد الأقصى للمخزون
 * @property float $reorder_level نقطة إعادة الطلب
 * @property float $alert_qty كمية التنبيه
 * @property float $purchase_price سعر الشراء
 * @property float $sales_price سعر البيع
 * @property float $wholesale_price سعر الجملة
 * @property float $cost_price سعر التكلفة
 * @property string|null $usage_instructions تعليمات الاستخدام
 * @property string|null $side_effects الآثار الجانبية
 */
class Product extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Basic Info
        'company_id',
        'branch_id',
        'category_id',
        'manufacturer_id',
        'unit_id',

        // Product Names
        'name',
        'generic_name',
        'brand_name',
        'product_name',

        // Identification
        'product_code', // SKU
        'barcode',
        'internal_code',
        'atc_code',

        // Pharmacy Specific
        'dosage_form',
        'strength',
        'unit_of_measure',
        'pack_size',
        'pack_unit',

        // Prescription & Control
        'prescription_required',
        'is_controlled',
        'controlled_substance_schedule',
        'requires_special_handling',

        // Storage
        'storage_conditions',
        'shelf_life_months',

        // Splittable (قابل للتجزئة)
        'is_splittable',
        'split_unit_id',
        'split_quantity',

        // Stock Management
        'min_stock',
        'max_stock',
        'reorder_level',
        'alert_qty',

        // Pricing
        'purchase_price',
        'purchase_without_tax',
        'purchase_with_tax',
        'sales_price',
        'selling_price',
        'wholesale_price',
        'cost_price',
        'price_currency',
        'tax_id',
        'tax_rate_id',
        'tax_rate',
        'profit_percent',
        'markup_percent',
        'discount_percentage',

        // Description
        'description',
        'usage_instructions',
        'side_effects',

        // Images & Media
        'images',
        'product_image',
        'image_path',
        'meta',
        'metadata',

        // Status
        'is_active',
        'is_taxable',
        'track_inventory',

        // Audit
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prescription_required' => 'boolean',
        'is_controlled' => 'boolean',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'track_inventory' => 'boolean',
        'requires_special_handling' => 'boolean',
        'is_splittable' => 'boolean',
        'pack_size' => 'integer',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'alert_qty' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'purchase_without_tax' => 'decimal:2',
        'purchase_with_tax' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'profit_percent' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'shelf_life_months' => 'integer',
        'split_quantity' => 'decimal:3',
        'storage_conditions' => 'json',
        'images' => 'json',
        'meta' => 'json',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values.
     *
     * @var array
     */
    protected $attributes = [
        'is_active' => true,
        'track_inventory' => true,
        'is_splittable' => false,
        'prescription_required' => false,
        'is_controlled' => false,
        'is_taxable' => true,
        'pack_size' => 1,
        'unit_of_measure' => 'piece',
        'price_currency' => 'EGP',
    ];

    // ────────────────────────────── Relationships ──────────────────────────────

    /**
     * Get the category for this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the manufacturer for this product.
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Get the unit for this product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the tax for this product.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the branch for this product.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the creator of this product.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of this product.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ────────────────────────────── Inventory/Stock ──────────────────────────────

    /**
     * Get all inventory records for this product (batches).
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_id', 'id');
    }

    /**
     * Get stock batches for this product (FEFO ready).
     */
    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    /**
     * Get active stock batches ordered by expiry (FEFO).
     */
    public function fefoBatches(): HasMany
    {
        return $this->stockBatches()
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc');
    }

    /**
     * Eager load common relationships on an existing model instance.
     */
    public function loadCommon(): static
    {
        return $this->load([
            'category:id,name,slug',
            'manufacturer:id,name',
            'unit:id,unitName,short_code',
        ]);
    }

    /**
     * Get product stocks (legacy compatibility).
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get stock movements for this product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get current available stock quantity.
     */
    public function getCurrentStockAttribute(): float
    {
        return (float) $this->inventory()->sum('quantity');
    }

    // ────────────────────────────── Variants ──────────────────────────────

    /**
     * Get product variants (different package sizes/formulations).
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    // ────────────────────────────── Pricing ──────────────────────────────

    /**
     * Get product price history.
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    /**
     * Get product prices (tiered pricing).
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    // ────────────────────────────── Drug Interactions ──────────────────────────────

    /**
     * Get drug interactions where this product is product A.
     */
    public function drugInteractionsA(): HasMany
    {
        return $this->hasMany(DrugInteraction::class, 'product_a_id');
    }

    /**
     * Get drug interactions where this product is product B.
     */
    public function drugInteractionsB(): HasMany
    {
        return $this->hasMany(DrugInteraction::class, 'product_b_id');
    }

    /**
     * Get all drug interactions for this product.
     */
    public function drugInteractions(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'drug_interactions', 'product_a_id', 'product_b_id')
            ->withPivot(['interaction_level', 'description', 'clinical_effects', 'management'])
            ->as('interaction');
    }

    /**
     * Get dangerous drug interactions (severe or contraindicated).
     */
    public function dangerousInteractions(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'drug_interactions', 'product_a_id', 'product_b_id')
            ->wherePivotIn('interaction_level', ['severe', 'contraindicated'])
            ->withPivot(['interaction_level', 'description', 'clinical_effects', 'management'])
            ->as('interaction');
    }

    // ────────────────────────────── Sales & Purchases ──────────────────────────────

    /**
     * Get sale items for this product.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get sales for this product (through sale items).
     */
    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'sale_items')
            ->withPivot(['quantity', 'unit_price', 'total'])
            ->withTimestamps();
    }

    /**
     * Get purchase order items for this product.
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get GRN items for this product.
     */
    public function grnItems(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }

    /**
     * Get prescription items for this product.
     */
    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    // ────────────────────────────── Insurance ──────────────────────────────

    /**
     * Get insurance plans covering this product.
     */
    public function insurancePlans(): BelongsToMany
    {
        return $this->belongsToMany(InsurancePlan::class, 'insurance_plan_product')
            ->withPivot(['coverage_percentage', 'co_pay', 'price_override'])
            ->withTimestamps();
    }

    // ────────────────────────────── Helpers ──────────────────────────────

    /**
     * Calculate current stock quantity.
     */
    public function getCurrentStock(): float
    {
        return (float) $this->inventory()->sum('quantity');
    }

    /**
     * Calculate current stock via stock_batches (more accurate with FEFO).
     */
    public function getAvailableStock(): float
    {
        return (float) $this->stockBatches()
            ->where('status', 'active')
            ->sum('quantity_available');
    }

    /**
     * Check if product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->getCurrentStock() <= 0;
    }

    /**
     * Check if product has any expired batches.
     */
    public function hasExpiredBatches(): bool
    {
        return $this->inventory()
            ->where('expiry_date', '<', now()->toDateString())
            ->where('quantity', '>', 0)
            ->exists();
    }

    /**
     * Check if product needs reorder (low stock).
     */
    public function needsReorder(): bool
    {
        return $this->getCurrentStock() <= $this->reorder_level;
    }

    /**
     * Check if this is a controlled substance.
     */
    public function isControlledSubstance(): bool
    {
        return $this->is_controlled || !empty($this->controlled_substance_schedule);
    }

    /**
     * Check if product requires prescription.
     */
    public function isPrescriptionOnly(): bool
    {
        return $this->prescription_required;
    }

    /**
     * Get the display name (generic + brand format).
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->brand_name && $this->generic_name) {
            return "{$this->brand_name} ({$this->generic_name})";
        }
        return $this->name ?? $this->generic_name ?? $this->brand_name ?? 'N/A';
    }

    /**
     * Get the full product title with strength.
     */
    public function getFullTitleAttribute(): string
    {
        $title = $this->display_name;
        if ($this->strength) {
            $title .= " - {$this->strength}";
        }
        if ($this->dosage_form) {
            $title .= " ({$this->dosage_form})";
        }
        return $title;
    }

    /**
     * Calculate profit margin percentage.
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price > 0) {
            return round((($this->sales_price - $this->cost_price) / $this->cost_price) * 100, 2);
        }
        return 0;
    }

    /**
     * Get the storage condition label in Arabic.
     */
    public function getStorageLabelAttribute(): string
    {
        return match ($this->storage_conditions) {
            'room_temp' => 'درجة حرارة الغرفة',
            'refrigerated' => 'تبريد (2-8°C)',
            'frozen' => 'تجميد',
            'cold_chain' => 'سلسلة تبريد',
            default => $this->storage_conditions ?? 'قياسي',
        };
    }

    /**
     * Get the dosage form label in Arabic.
     */
    public function getDosageFormLabelAttribute(): string
    {
        $labels = [
            'tablet' => 'أقراص',
            'capsule' => 'كبسول',
            'syrup' => 'شراب',
            'injection' => 'حقن',
            'cream' => 'كريم',
            'ointment' => 'مرهم',
            'drops' => 'قطرة',
            'inhaler' => 'بخاخ',
            'suppository' => 'لبوس',
            'powder' => 'مسحوق',
            'solution' => 'محلول',
            'suspension' => 'معلق',
            'ampoule' => 'أمبولة',
            'vial' => 'فيال',
            'patch' => 'لصاقة',
            'gel' => 'جل',
        ];
        return $labels[$this->dosage_form] ?? $this->dosage_form ?? 'غير محدد';
    }

    /**
     * Get the controlled substance schedule label.
     */
    public function getControlledScheduleLabelAttribute(): string
    {
        return match ($this->controlled_substance_schedule) {
            '1' => 'جدول أول - مخدر',
            '2' => 'جدول ثاني - مخدر',
            '3' => 'جدول ثالث - مؤثر عقلي',
            '4' => 'جدول رابع - مؤثر عقلي',
            '5' => 'جدول خامس - مراقب',
            default => 'غير خاضع للرقابة',
        };
    }

    // ────────────────────────────── Scopes ──────────────────────────────

    /**
     * Scope: Search products across multiple fields.
     */
    public function scopeSearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('generic_name', 'ilike', "%{$search}%")
                ->orWhere('brand_name', 'ilike', "%{$search}%")
                ->orWhere('barcode', 'ilike', "%{$search}%")
                ->orWhere('product_code', 'ilike', "%{$search}%")
                ->orWhere('product_name', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%")
                ->orWhere('sku', 'ilike', "%{$search}%");
        });
    }

    /**
     * Scope: Filter by barcode (exact match).
     */
    public function scopeByBarcode($query, string $barcode): void
    {
        $query->where('barcode', $barcode);
    }

    /**
     * Scope: Filter active products only.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope: Filter by prescription requirement.
     */
    public function scopePrescriptionRequired($query, bool $required = true): void
    {
        $query->where('prescription_required', $required);
    }

    /**
     * Scope: Filter controlled substances only.
     */
    public function scopeControlled($query): void
    {
        $query->where('is_controlled', true)
            ->orWhereNotNull('controlled_substance_schedule');
    }

    /**
     * Scope: Filter by dosage form.
     */
    public function scopeByDosageForm($query, string $dosageForm): void
    {
        $query->where('dosage_form', $dosageForm);
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeByCategory($query, string $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by manufacturer.
     */
    public function scopeByManufacturer($query, string $manufacturerId): void
    {
        $query->where('manufacturer_id', $manufacturerId);
    }

    /**
     * Scope: Filter products with low stock (at or below reorder level).
     */
    public function scopeLowStock($query): void
    {
        $query->whereHas('inventory', function ($q) {
            $q->selectRaw('COALESCE(SUM(quantity), 0) as total_qty')
                ->havingRaw('total_qty <= products.reorder_level');
        })->orWhere(function ($q) {
            $q->whereDoesntHave('inventory')
                ->where('reorder_level', '>', 0);
        });
    }

    /**
     * Scope: Filter out-of-stock products.
     */
    public function scopeOutOfStock($query): void
    {
        $query->whereDoesntHave('inventory', function ($q) {
            $q->where('quantity', '>', 0);
        });
    }

    /**
     * Scope: Filter products expiring within given days.
     */
    public function scopeExpiringSoon($query, int $days = 90): void
    {
        $query->whereHas('inventory', function ($q) use ($days) {
            $q->whereBetween('expiry_date', [now(), now()->addDays($days)])
                ->where('quantity', '>', 0);
        });
    }

    /**
     * Scope: Filter already expired products.
     */
    public function scopeExpired($query): void
    {
        $query->whereHas('inventory', function ($q) {
            $q->where('expiry_date', '<', now()->toDateString())
                ->where('quantity', '>', 0);
        });
    }

    /**
     * Scope: Filter by price range.
     */
    public function scopePriceBetween($query, float $min, float $max): void
    {
        $query->whereBetween('sales_price', [$min, $max]);
    }

    /**
     * Scope: Filter by unit of measure.
     */
    public function scopeByUnit($query, string $unit): void
    {
        $query->where('unit_of_measure', $unit);
    }

    /**
     * Scope: Products with complete info (has barcode and price).
     */
    public function scopeComplete($query): void
    {
        $query->whereNotNull('barcode')
            ->where('sales_price', '>', 0);
    }

    /**
     * Scope: Eager load common relationships.
     */
    public function scopeWithCommon($query): void
    {
        $query->with([
            'category:id,name,slug',
            'manufacturer:id,name',
            'unit:id,unitName,short_code',
        ]);
    }

    /**
     * Scope: Eager load stock info.
     */
    public function scopeWithStock($query): void
    {
        $query->with([
            'inventory' => function ($q) {
                $q->selectRaw('product_id, SUM(quantity) as total_quantity')
                    ->groupBy('product_id');
            },
            'stockBatches' => function ($q) {
                $q->where('status', 'active')
                    ->where('quantity_available', '>', 0)
                    ->orderBy('expiry_date', 'asc');
            },
        ]);
    }

    // ────────────────────────────── Boot ──────────────────────────────

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            // Auto-generate product_code if not provided
            if (empty($product->product_code)) {
                $product->product_code = static::generateProductCode();
            }
            // Sync name with product_name if name not provided
            if (empty($product->name) && !empty($product->product_name)) {
                $product->name = $product->product_name;
            }
            if (empty($product->name) && !empty($product->generic_name)) {
                $product->name = $product->generic_name;
            }
            // Track inventory by default
            if ($product->track_inventory === null) {
                $product->track_inventory = true;
            }
        });
    }

    /**
     * Generate a unique product code.
     */
    protected static function generateProductCode(): string
    {
        $prefix = 'PRD-';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . $timestamp . '-' . $random;
    }
}
