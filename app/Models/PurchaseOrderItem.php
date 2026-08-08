<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'pending_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the purchase order that owns the item.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product that owns the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if item is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Check if item is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        return $this->received_quantity > 0 && $this->received_quantity < $this->quantity;
    }

    /**
     * Get remaining quantity.
     */
    public function getRemainingQuantity(): int
    {
        return $this->quantity - $this->received_quantity;
    }

    /**
     * Calculate total.
     */
    public function calculateTotal(): void
    {
        $this->total = ($this->unit_price * $this->quantity) - $this->discount + $this->tax;
        $this->save();
    }

    /**
     * Update received quantity.
     */
    public function updateReceivedQuantity(int $quantity): void
    {
        $this->received_quantity += $quantity;
        $this->pending_quantity = $this->quantity - $this->received_quantity;
        $this->save();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->pending_quantity = $item->quantity;
            $item->calculateTotal();
        });

        static::updating(function ($item) {
            if ($item->isDirty('quantity') || $item->isDirty('unit_price') || $item->isDirty('discount') || $item->isDirty('tax')) {
                $item->calculateTotal();
            }
        });
    }
}
