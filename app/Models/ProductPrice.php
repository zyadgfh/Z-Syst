<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    use HasCompany, HasFactory, HasUuids, SoftDeletes;

    protected $table = 'product_prices';

    protected $fillable = [
        'company_id',
        'product_id',
        'product_variant_id',
        'tier_name',
        'tier_label',
        'price',
        'min_quantity',
        'max_quantity',
        'customer_group_id',
        'is_default',
        'start_date',
        'end_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'min_quantity' => 'decimal:3',
        'max_quantity' => 'decimal:3',
        'is_default' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Scope active price tiers.
     */
    public function scopeActive($query): void
    {
        $query->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }

    /**
     * Get best price for a given quantity.
     */
    public static function getBestPrice(string $productId, float $quantity, ?string $variantId = null): float
    {
        $query = self::where('product_id', $productId)
            ->where('is_default', false)
            ->whereNull('end_date')
            ->orderBy('min_quantity', 'desc')
            ->where('min_quantity', '<=', $quantity);

        if ($variantId) {
            $query->where(fn ($q) => $q->whereNull('product_variant_id')->orWhere('product_variant_id', $variantId));
        }

        $tierPrice = $query->first();

        if ($tierPrice) {
            return $tierPrice->price;
        }

        // Fallback to default price
        $default = self::where('product_id', $productId)
            ->where('is_default', true)
            ->whereNull('product_variant_id')
            ->first();

        return $default?->price ?? 0;
    }
}

