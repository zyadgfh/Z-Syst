<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrderItem - table `purchase_order_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseOrderItem extends Model
{
    use TenantScoped;
    use HasFactory;


    protected $table = 'purchase_order_items';

    /**
     * `PurchaseOrder`-derived tenant key: this table's business_id is NOT NULL
     * in the live Supabase schema, but bulk inserts and queue jobs run without an
     * authenticated user (TenantScoped only fills from auth()). Derive it from the
     * parent row so the constraint can never be hit.
     * NOTE: hand-added — tools/generate-models.py must preserve this method.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->business_id) && $model->purchase_order_id) {
                $model->business_id = PurchaseOrder::where('id', $model->purchase_order_id)->value('business_id');
            }
        });
    }

    protected $fillable = [
        'business_id',
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'pending_quantity',
        'unit_price',
        'discount',
        'tax',
        'total',
        'notes',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'pending_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
