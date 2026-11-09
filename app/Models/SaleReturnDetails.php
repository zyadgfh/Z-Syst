<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class SaleReturnDetails extends Model
{
    
    use TenantScoped;
protected $table = 'sale_return_details';
    use HasFactory;

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
}
