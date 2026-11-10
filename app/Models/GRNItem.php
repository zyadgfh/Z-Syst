<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GRNItem extends Model
{
    use TenantScoped;
    use HasFactory;

    protected $table = 'grn_items';

    /**
     * `grn_items.business_id` is NOT NULL in the live Supabase schema, but
     * GRNService::addItem() creates rows without it and queue/console contexts
     * have no authenticated user for TenantScoped to copy from. Derive the
     * tenant from the parent GRN so the constraint can never be violated.
     */
    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if (empty($item->business_id) && $item->grn_id) {
                $item->business_id = GoodsReceivedNote::where('id', $item->grn_id)->value('business_id');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
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
        // Explicit FK: Str::snake('GRNItem') yields 'g_r_n_item', so the
        // inferred key would be quality_checks.g_r_n_item_id.
        return $this->hasMany(QualityCheck::class, 'grn_item_id');
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
