<?php

namespace App\Models;

use Database\Factories\ProductStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStock extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ProductStockFactory::new();
    }

    protected $fillable = [
        'company_id',
        'product_id',
        'branch_id',
        'quantity',
        'reorder_level',
        'reorder_quantity',
        'batch_number',
        'expiry_date',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function transferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
