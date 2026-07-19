<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory Model - Alias for ProductStock (for backward compatibility)
 */
class Inventory extends Model
{
    protected $table = 'product_stocks';

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

    /**
     * Get the product this inventory belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the company that owns this inventory.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}