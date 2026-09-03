<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'stock_transfer_id',
        'user_id',
        'action',
        'from_warehouse_id',
        'to_warehouse_id',
        'product_id',
        'quantity',
        'from_stock_before',
        'from_stock_after',
        'to_stock_before',
        'to_stock_after',
        'notes',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to filter by business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to filter by transfer.
     */
    public function scopeForTransfer($query, int $transferId)
    {
        return $query->where('stock_transfer_id', $transferId);
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
