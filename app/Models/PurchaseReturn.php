<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseReturn - table `purchase_returns` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseReturn extends Model
{
    protected $table = 'purchase_returns';

    protected $fillable = [
        'business_id',
        'purchase_id',
        'invoice_no',
        'return_date',
    ];

    protected $casts = [
        'purchase_id' => 'integer',
        'return_date' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }
}
