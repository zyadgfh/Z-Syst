<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class GatewayTransaction extends Model
{
    
    use TenantScoped;
protected $fillable = [
        'business_id', 'gateway', 'purpose', 'payable_id', 'amount', 'currency',
        'status', 'reference', 'payload', 'paid_at',
    ];

    public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $casts = ['amount' => 'float', 'payload' => 'array', 'paid_at' => 'datetime'];
}
