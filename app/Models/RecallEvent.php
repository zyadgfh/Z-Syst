<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RecallEvent - table `recall_events` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class RecallEvent extends Model
{
    protected $table = 'recall_events';

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_lot_number',
        'reason',
        'initiated_at',
        'resolved_at',
        'status',
        'description',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'initiated_at' => 'date',
        'resolved_at' => 'date',
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
