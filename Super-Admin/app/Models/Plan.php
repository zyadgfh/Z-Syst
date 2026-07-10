<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'duration',
        'offerPrice',
        'subscriptionName',
        'subscriptionPrice',
        'visibility',
        'features',
        'affiliate_commission',
        'allow_multibranch',
        'addon_domain_limit',
        'subdomain_limit',
    ];

    public function planSubscribes()
    {
        return $this->hasMany(PlanSubscribe::class, 'plan_id');
    }


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'features' => 'json',
        'duration' => 'integer',
        'offerPrice' => 'double',
        'status' => 'integer',
        'visibility' => 'json',
        'subscriptionPrice' => 'double',
        'allow_multibranch' => 'integer',
        'addon_domain_limit' => 'integer',
        'subdomain_limit' => 'integer',
    ];
}
