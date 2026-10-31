<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TraceabilityLog - table `traceability_logs` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class TraceabilityLog extends Model
{
    protected $table = 'traceability_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_lot_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'type',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'from_warehouse_id' => 'integer',
        'to_warehouse_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
