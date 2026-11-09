<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class JournalEntry extends Model
{
    
    use TenantScoped;
public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $fillable = [
        'business_id', 'entry_date', 'description', 'source', 'source_id',
        'amount', 'lines', 'user_id',
    ];

    protected $casts = ['lines' => 'array', 'amount' => 'float', 'entry_date' => 'date'];
}
