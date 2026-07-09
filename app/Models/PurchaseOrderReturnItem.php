<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_return_id',
        'product_id',
        'quantity_returned',
        'unit_cost',
        'total',
        'batch_number',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchaseOrderReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
