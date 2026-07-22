<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasCompany, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'value',
        'min_purchase_amount',
        'max_discount_amount',
        'apply_to',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'usage_limit',
        'used_count',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'decimal:3',
        'min_purchase_amount' => 'decimal:3',
        'max_discount_amount' => 'decimal:3',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_discounts', 'discount_id', 'product_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_discounts', 'discount_id', 'category_id');
    }

    public function scopeActive($query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }

    /**
     * Calculate discount amount for a given price.
     */
    public function calculateDiscount(float $price, int $quantity = 1): float
    {
        $subtotal = $price * $quantity;

        if ($this->min_purchase_amount && $subtotal < $this->min_purchase_amount) {
            return 0;
        }

        $discount = match ($this->type) {
            'percentage' => $subtotal * ($this->value / 100),
            'fixed' => $this->value * $quantity,
            default => 0,
        };

        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return max(0, $discount);
    }
}

