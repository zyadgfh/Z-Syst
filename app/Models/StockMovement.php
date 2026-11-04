<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockMovement - table `stock_movements` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class StockMovement extends Model
{
    protected $table = 'stock_movements';

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'product_id',
        'warehouse_id',
        'stock_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'batch_no',
        'expire_date',
        'unit_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'stock_id' => 'integer',
        'quantity' => 'integer',
        'reference_id' => 'integer',
        'expire_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
