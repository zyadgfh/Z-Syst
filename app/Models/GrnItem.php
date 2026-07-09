<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnItem extends Model
{
    use HasFactory;

    protected $table = 'grn_items';

    protected $fillable = [
        'grn_id',
        'product_id',
        'batch_number',
        'quantity_received',
        'expiry_date',
        'manufacturing_date',
        'unit_cost',
        'rack_location',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
    ];

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
