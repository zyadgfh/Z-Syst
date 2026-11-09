<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class StoreOrder extends Model
{
    
    use TenantScoped;
protected $fillable = [
        'business_id', 'order_number', 'customer_name', 'customer_phone', 'address',
        'fulfillment', 'status', 'total', 'items', 'sale_id', 'delivery_id', 'notes',
    ];

    public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $casts = ['items' => 'array', 'total' => 'float'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}
