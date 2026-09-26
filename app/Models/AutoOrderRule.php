<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoOrderRule extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'product_id',
        'enabled',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'safety_stock',
        'lead_time_days',
        'min_order_qty',
        'max_order_qty',
        'order_multiple',
        'preferred_supplier_id',
        'auto_approve',
        'meta',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_approve' => 'boolean',
        'meta' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'preferred_supplier_id');
    }
}
