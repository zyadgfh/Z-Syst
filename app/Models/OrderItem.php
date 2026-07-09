<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasCompany;

    protected $fillable = ['order_id', 'company_id', 'product_id', 'drug_id', 'quantity', 'price'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
