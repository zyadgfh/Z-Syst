<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class OrderItem extends Model
{
    use HasCompany;

    protected $fillable = ['order_id','company_id','product_id','drug_id','quantity','price'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
