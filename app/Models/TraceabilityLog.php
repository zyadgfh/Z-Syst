<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraceabilityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_lot_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'type',
        'quantity',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for transfers
     */
    public function scopeTransfers($query)
    {
        return $query->where('type', 'transfer');
    }

    /**
     * Scope for sales
     */
    public function scopeSales($query)
    {
        return $query->where('type', 'sale');
    }

    /**
     * Scope for purchases
     */
    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for specific batch/lot
     */
    public function scopeForBatchLot($query, $batchLotNumber)
    {
        return $query->where('batch_lot_number', $batchLotNumber);
    }

    /**
     * Get movement path as readable string
     */
    public function getMovementPathAttribute(): string
    {
        $from = $this->fromWarehouse ? $this->fromWarehouse->name : 'External';
        $to = $this->toWarehouse ? $this->toWarehouse->name : 'External';
        
        return "{$from} → {$to}";
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'transfer' => __('Transfer'),
            'sale' => __('Sale'),
            'purchase' => __('Purchase'),
            'adjustment' => __('Adjustment'),
            'recall' => __('Recall'),
            default => ucfirst($this->type),
        };
    }
}
