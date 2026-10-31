<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product - table `products` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'business_id',
        'product_name',
        'category_id',
        'unit_id',
        'type_id',
        'manufacturer_id',
        'box_size_id',
        'purchase_without_tax',
        'purchase_with_tax',
        'profit_percent',
        'sales_price',
        'alert_qty',
        'wholesale_price',
        'product_code',
        'images',
        'tax_id',
        'tax_type',
        'meta',
        'barcode',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'unit_id' => 'integer',
        'type_id' => 'integer',
        'manufacturer_id' => 'integer',
        'box_size_id' => 'integer',
        'purchase_without_tax' => 'decimal:2',
        'purchase_with_tax' => 'decimal:2',
        'profit_percent' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'alert_qty' => 'integer',
        'wholesale_price' => 'decimal:2',
        'tax_id' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function boxSize(): BelongsTo
    {
        return $this->belongsTo(BoxSize::class, 'box_size_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function autoOrderRules(): HasMany
    {
        return $this->hasMany(AutoOrderRule::class, 'product_id');
    }

    public function autoOrderSuggestions(): HasMany
    {
        return $this->hasMany(AutoOrderSuggestion::class, 'product_id');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'product_id');
    }

    public function batchLots(): HasMany
    {
        return $this->hasMany(BatchLot::class, 'product_id');
    }

    public function controlledSubstancesLogs(): HasMany
    {
        return $this->hasMany(ControlledSubstancesLog::class, 'product_id');
    }

    public function fefoLogs(): HasMany
    {
        return $this->hasMany(FefoLog::class, 'product_id');
    }

    public function grnItems(): HasMany
    {
        return $this->hasMany(GrnItem::class, 'product_id');
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'product_id');
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class, 'product_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'product_id');
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'product_id');
    }

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'product_id');
    }

    public function recallEvents(): HasMany
    {
        return $this->hasMany(RecallEvent::class, 'product_id');
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class, 'product_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'product_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function stockTakeItems(): HasMany
    {
        return $this->hasMany(StockTakeItem::class, 'product_id');
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'product_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'product_id');
    }

    public function supplierInvoiceItems(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class, 'product_id');
    }

    public function traceabilityLogs(): HasMany
    {
        return $this->hasMany(TraceabilityLog::class, 'product_id');
    }

    public function warehouseStockBatchs(): HasMany
    {
        return $this->hasMany(WarehouseStockBatch::class, 'product_id');
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'product_id');
    }
}
