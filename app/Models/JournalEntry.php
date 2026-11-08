<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $fillable = [
        'business_id', 'entry_date', 'description', 'source', 'source_id',
        'amount', 'lines', 'user_id',
    ];

    protected $casts = ['lines' => 'array', 'amount' => 'float', 'entry_date' => 'date'];
}
