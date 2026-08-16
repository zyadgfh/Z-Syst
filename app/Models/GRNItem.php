<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'product_id',
        'ordered_quantity',
        'received_quantity',
        'pending_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'rejection_reason',
        'condition',
        'batch_number',
        'expiry_date',
        'unit_cost',
    ];

    protected $casts = [
        'ordered_quantity' => 'integer',
        'received_quantity' => 'integer',
        'pending_quantity' => 'integer',
        'accepted_quantity' => 'integer',
        'rejected_quantity' => 'integer',
        'expiry_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
