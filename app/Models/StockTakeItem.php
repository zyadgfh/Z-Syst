<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockTakeItem - table `stock_take_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class StockTakeItem extends Model
{
    protected $table = 'stock_take_items';

    protected $fillable = [
        'business_id',
        'stock_take_id',
        'product_id',
        'stock_id',
        'system_quantity',
        'counted_quantity',
        'variance',
        'notes',
    ];

    protected $casts = [
        'stock_take_id' => 'integer',
        'product_id' => 'integer',
        'stock_id' => 'integer',
        'system_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'variance' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class, 'stock_take_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
