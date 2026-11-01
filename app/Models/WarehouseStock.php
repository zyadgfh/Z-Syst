<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WarehouseStock - table `warehouse_stocks` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class WarehouseStock extends Model
{
    protected $table = 'warehouse_stocks';

    protected $fillable = [
        'business_id',
        'warehouse_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
