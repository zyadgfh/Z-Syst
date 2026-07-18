<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Price History Model
 * 
 * Tracks all price changes for products for audit and analytics purposes
 */
class ProductPriceHistory extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_price_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'product_id',
        'price_type', // purchase, sales, wholesale
        'old_price',
        'new_price',
        'old_wholesale_price',
        'new_wholesale_price',
        'change_reason', // manual_update, supplier_update, promotion, system
        'changed_by',
        'effective_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_wholesale_price' => 'decimal:2',
        'new_wholesale_price' => 'decimal:2',
        'effective_date' => 'date',
    ];

    /**
     * Get the product this price history belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the company that owns this record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who changed the price.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the percentage change.
     */
    public function getPriceChangePercentAttribute(): float
    {
        if ($this->old_price > 0) {
            return round((($this->new_price - $this->old_price) / $this->old_price) * 100, 2);
        }
        return 0;
    }

    /**
     * Get human readable change direction.
     */
    public function getChangeDirectionAttribute(): string
    {
        if ($this->new_price > $this->old_price) {
            return 'increased';
        }
        if ($this->new_price < $this->old_price) {
            return 'decreased';
        }
        return 'unchanged';
    }
}