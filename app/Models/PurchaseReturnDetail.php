<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseReturnDetail - table `purchase_return_details` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseReturnDetail extends Model
{
    protected $table = 'purchase_return_details';

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'purchase_return_id',
        'purchase_detail_id',
        'return_amount',
        'return_qty',
    ];

    protected $casts = [
        'purchase_return_id' => 'integer',
        'purchase_detail_id' => 'integer',
        'return_amount' => 'decimal:2',
        'return_qty' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseDetail::class, 'purchase_detail_id');
    }
}
