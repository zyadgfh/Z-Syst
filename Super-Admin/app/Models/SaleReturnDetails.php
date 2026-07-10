<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnDetails extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'sale_return_id',
        'sale_detail_id',
        'return_amount',
        'return_qty',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    public function saleDetail()
    {
        return $this->belongsTo(SaleDetails::class, 'sale_detail_id');
    }

    protected $casts = [
        'business_id' => 'integer',
        'sale_return_id' => 'integer',
        'sale_detail_id' => 'integer',
        'return_amount' => 'double',
        'return_qty' => 'double',
    ];
}
