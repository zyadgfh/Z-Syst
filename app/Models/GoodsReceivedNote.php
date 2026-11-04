<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GoodsReceivedNote - table `goods_received_notes` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class GoodsReceivedNote extends Model
{
    use SoftDeletes;

    protected $table = 'goods_received_notes';

    protected $fillable = [
        'business_id',
        'purchase_order_id',
        'supplier_id',
        'grn_number',
        'received_date',
        'location',
        'status',
        'notes',
        'verified_at',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'supplier_id' => 'integer',
        'received_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
