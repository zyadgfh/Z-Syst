<?php

namespace App\Models;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetails extends Model
{
    protected $table = 'sale_details';
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'price',
        'loss_profit',
        'batch_no',
        'quantities',
        'dose',
        'dose_code',
        'purchase_price',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
