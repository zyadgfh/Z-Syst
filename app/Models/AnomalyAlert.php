<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class AnomalyAlert extends Model
{
    
    use TenantScoped;
public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $fillable = [
        'business_id', 'type', 'severity', 'title', 'details', 'user_id', 'is_resolved',
    ];

    protected $casts = ['details' => 'array', 'is_resolved' => 'boolean'];
}
