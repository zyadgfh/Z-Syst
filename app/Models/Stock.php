<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'product_id',
        'productStock',
        'batch_no',
        'expire_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
