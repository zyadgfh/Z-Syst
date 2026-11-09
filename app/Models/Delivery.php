<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class Delivery extends Model
{
    
    use TenantScoped;
protected $fillable = [
        'business_id', 'sale_id', 'store_order_id', 'courier_user_id', 'courier_name',
        'customer_name', 'customer_phone', 'address', 'fee', 'status', 'assigned_at',
        'delivered_at', 'notes',
    ];

    protected $casts = ['fee' => 'float', 'assigned_at' => 'datetime', 'delivered_at' => 'datetime'];
}
