<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TraceabilityLog;
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
        if (! $this->canBeCompleted()) {
            return false;
        }

        return \DB::transaction(function () {
            // Decrease stock from source warehouse
            $fromStock = WarehouseStock::where([
                'warehouse_id' => $this->from_warehouse_id,
                'product_id' => $this->product_id,
            ])->first();

            if (! $fromStock || ! $fromStock->decrease($this->quantity)) {
                return false;
            }

            // Increase stock in destination warehouse
            $toStock = WarehouseStock::firstOrCreate([
                'warehouse_id' => $this->to_warehouse_id,
                'product_id' => $this->product_id,
                'business_id' => $this->business_id,
            ]);

            $toStock->increase($this->quantity);

            // Update transfer status
            $this->update(['status' => 'completed']);

            // Log traceability for batch-level tracking
            TraceabilityLog::create([
                'business_id' => $this->business_id,
                'product_id' => $this->product_id,
                'from_warehouse_id' => $this->from_warehouse_id,
                'to_warehouse_id' => $this->to_warehouse_id,
                'type' => 'transfer',
                'quantity' => $this->quantity,
                'user_id' => $this->user_id ?? auth()->id(),
                'notes' => "Stock transfer #{$this->id} completed",
            ]);

            return true;
        });
    }

    /**
     * Cancel the transfer
     */
    public function cancel(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update(['status' => 'cancelled']);
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
