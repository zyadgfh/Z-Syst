<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse - table `warehouses` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'location',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    public function warehouseStockBatchs(): HasMany
    {
        return $this->hasMany(WarehouseStockBatch::class, 'warehouse_id');
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'warehouse_id');
    }
}
