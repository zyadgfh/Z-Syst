<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GrnItem - table `grn_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class GrnItem extends Model
{
    use TenantScoped;

    protected $table = 'grn_items';

    /**
     * `GoodsReceivedNote`-derived tenant key: this table's business_id is NOT NULL
     * in the live Supabase schema, but bulk inserts and queue jobs run without an
     * authenticated user (TenantScoped only fills from auth()). Derive it from the
     * parent row so the constraint can never be hit.
     * NOTE: hand-added — tools/generate-models.py must preserve this method.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->business_id) && $model->grn_id) {
                $model->business_id = GoodsReceivedNote::where('id', $model->grn_id)->value('business_id');
            }
        });
    }

    protected $fillable = [
        'business_id',
        'grn_id',
        'product_id',
        'ordered_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'batch_number',
        'expiry_date',
        'purchase_price',
        'notes',
    ];

    protected $casts = [
        'grn_id' => 'integer',
        'product_id' => 'integer',
        'ordered_quantity' => 'integer',
        'received_quantity' => 'integer',
        'accepted_quantity' => 'integer',
        'rejected_quantity' => 'integer',
        'expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
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
