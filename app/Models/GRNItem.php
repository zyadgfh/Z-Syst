<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GRNItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'datetime',
        'purchase_price' => 'decimal:2',
    ];

    /**
     * Get the GRN for the item.
     */
    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    /**
     * Get the product for the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the quality checks for the item.
     */
    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class);
    }

    /**
     * Calculate total amount.
     */
    public function getTotalAttribute(): float
    {
        return $this->accepted_quantity * $this->purchase_price;
    }

    /**
     * Calculate pending quantity.
     */
    public function getPendingQuantityAttribute(): int
    {
        return $this->ordered_quantity - $this->received_quantity;
    }

    /**
     * Calculate acceptance rate.
     */
    public function getAcceptanceRateAttribute(): float
    {
        if ($this->received_quantity === 0) return 0;
        return ($this->accepted_quantity / $this->received_quantity) * 100;
    }
}
