<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EtaInvoice extends Model
{
    protected $fillable = [
        'business_id', 'sale_id', 'submission_uuid', 'long_id', 'status',
        'request_payload', 'response_payload', 'error_message', 'submitted_at',
    ];

    public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $casts = [
        'request_payload' => 'array', 'response_payload' => 'array',
        'submitted_at' => 'datetime',
    ];
}
