<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetails extends Model
{
    use TenantScoped;
    use HasFactory;

    protected $table = 'purchase_details';

    /**
     * `purchase_details.business_id` is NOT NULL in the live Supabase schema.
     * See SaleDetails::booted() for the reasoning.
     */
    protected static function booted(): void
    {
        static::creating(function (self $detail) {
            if (empty($detail->business_id) && $detail->purchase_id) {
                $detail->business_id = Purchase::where('id', $detail->purchase_id)->value('business_id');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'purchase_id',
        'product_id',
        'purchase_without_tax',
        'purchase_with_tax',
        'profit_percent',
        'sales_price',
        'wholesale_price',
        'quantities',
        'batch_no',
        'expire_date',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
