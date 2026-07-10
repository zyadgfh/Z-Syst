<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanSubscribe extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'price',
        'notes',
        'plan_id',
        'duration',
        'gateway_id',
        'business_id',
        'payment_status',
        'allow_multibranch',
        'addon_domain_limit',
        'subdomain_limit',
    ];

    protected $casts = [
        'notes' => 'json',
        'duration' => 'integer',
        'price' => 'double',
        'plan_id' => 'integer',
        'business_id' => 'integer',
        'gateway_id' => 'integer',
        'allow_multibranch' => 'integer',
        'addon_domain_limit' => 'integer',
        'subdomain_limit' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
    }
}
