<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'product_id',
        'quantity',
        'status',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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
     * Scope for status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending transfers
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed transfers
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled transfers
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if transfer can be completed
     */
    public function canBeCompleted(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $fromWarehouse = $this->fromWarehouse;

        return $fromWarehouse && $fromWarehouse->hasSufficientStock($this->product_id, $this->quantity);
    }

    /**
     * Complete the transfer
     */
    public function complete(): bool
    {
        return \DB::transaction(function () {
            $transfer = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($transfer->status !== 'pending' || $transfer->quantity <= 0
                || $transfer->from_warehouse_id == $transfer->to_warehouse_id) {
                return false;
            }

            $warehouses = Warehouse::where('business_id', $transfer->business_id)
                ->whereIn('id', [$transfer->from_warehouse_id, $transfer->to_warehouse_id])->count();
            $productExists = Product::where('business_id', $transfer->business_id)->whereKey($transfer->product_id)->exists();
            if ($warehouses !== 2 || ! $productExists) {
                return false;
            }

            $source = WarehouseStock::where('business_id', $transfer->business_id)
                ->where('warehouse_id', $transfer->from_warehouse_id)
                ->where('product_id', $transfer->product_id)->lockForUpdate()->first();
            if (! $source || $source->quantity < $transfer->quantity) {
                return false;
            }

            $destination = WarehouseStock::firstOrCreate([
                'business_id' => $transfer->business_id,
                'warehouse_id' => $transfer->to_warehouse_id,
                'product_id' => $transfer->product_id,
            ]);
            $source->decrement('quantity', $transfer->quantity);
            $destination->increment('quantity', $transfer->quantity);
            $transfer->update(['status' => 'completed']);
            $this->setAttribute('status', 'completed');

            return true;
        });
    }

    /** Cancel only a pending transfer while holding its row lock. */
    public function cancel(): bool
    {
        return \DB::transaction(function () {
            $transfer = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($transfer->status !== 'pending') {
                return false;
            }
            $transfer->update(['status' => 'cancelled']);
            $this->setAttribute('status', 'cancelled');

            return true;
        });
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => __('Pending'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            default => ucfirst($this->status),
        };
    }
}
