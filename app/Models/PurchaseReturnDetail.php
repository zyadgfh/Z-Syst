<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'purchase_return_id',
        'purchase_detail_id',
        'product_id',
        'return_amount',
        'return_qty',
        'unit_price',
        'discount',
        'tax',
        'credit_amount',
        'reason',
        'batch_no',
    ];

    // Timestamps enabled (migration now has created_at/updated_at)

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function purchaseDetail()
    {
        return $this->belongsTo(PurchaseDetails::class, 'purchase_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
