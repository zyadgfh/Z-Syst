<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * StockTransferItem Model
 *
 * Represents an individual item in a stock transfer.
 * Tracks requested, sent, and received quantities for each product.
 *
 * @property int $id
 * @property int $stock_transfer_id
 * @property int $product_id
 * @property int|null $product_stock_id
 * @property float $quantity_requested
 * @property float $quantity_sent
 * @property float $quantity_received
 * @property string|null $batch_number
 * @property Carbon|null $expiry_date
 * @property float $unit_cost
 * @property float $total_cost
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StockTransferItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'product_stock_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'batch_number',
        'expiry_date',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_sent' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'expiry_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the stock transfer that owns the item.
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    /**
     * Get the product being transferred.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the specific stock record being transferred.
     */
    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }

    /**
     * Calculate the remaining quantity to be sent.
     */
    public function getRemainingToSendAttribute(): float
    {
        return max(0, $this->quantity_requested - $this->quantity_sent);
    }

    /**
     * Calculate the remaining quantity to be received.
     */
    public function getRemainingToReceiveAttribute(): float
    {
        return max(0, $this->quantity_sent - $this->quantity_received);
    }

    /**
     * Check if the item has been fully sent.
     */
    public function isFullySent(): bool
    {
        return $this->quantity_sent >= $this->quantity_requested;
    }

    /**
     * Check if the item has been fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity_sent;
    }

    /**
     * Check if there are any discrepancies in quantities.
     */
    public function hasDiscrepancy(): bool
    {
        return $this->quantity_requested !== $this->quantity_sent
            || $this->quantity_sent !== $this->quantity_received;
    }
}
