<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_id',
        'business_id',
        'supplier_item_code',
        'supplier_barcode',
        'supplier_purchase_price',
        'supplier_currency',
        'lead_time_days',
        'minimum_order_quantity',
        'is_preferred',
        'notes',
    ];

    protected $casts = [
        'supplier_purchase_price' => 'double',
        'lead_time_days' => 'integer',
        'minimum_order_quantity' => 'integer',
        'is_preferred' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopePreferred($query)
    {
        return $query->where('is_preferred', true);
    }
}
