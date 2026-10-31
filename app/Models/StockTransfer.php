<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockTransfer - table `stock_transfers` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class StockTransfer extends Model
{
    protected $table = 'stock_transfers';

    protected $fillable = [
        'business_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'product_id',
        'quantity',
        'status',
        'notes',
        'user_id',
        'idempotency_key',
        'completed_at',
    ];

    protected $casts = [
        'from_warehouse_id' => 'integer',
        'to_warehouse_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
