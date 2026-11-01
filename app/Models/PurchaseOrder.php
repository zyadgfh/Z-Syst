<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrder - table `purchase_orders` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'business_id',
        'supplier_id',
        'po_number',
        'status',
        'priority',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'terms',
        'internal_notes',
        'notes',
        'approved_at',
        'sent_at',
        'cancelled_at',
        'rejected_at',
        'rejection_reason',
        'is_active',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'expected_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
