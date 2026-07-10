<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\AffiliateAddon\App\Models\Affiliate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'plan_subscribe_id',
        'business_category_id',
        'companyName',
        'address',
        'phoneNumber',
        'pictureUrl',
        'will_expire',
        'subscriptionDate',
        'remainingShopBalance',
        'shopOpeningBalance',
        'vat_name',
        'vat_no',
        'affiliator_id',
        'email',
        'status',
        'meta'
    ];

    public function enrolled_plan()
    {
        return $this->belongsTo(PlanSubscribe::class, 'plan_subscribe_id');
    }

    public function category()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliator_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'plan_subscribe_id' => 'integer',
        'business_category_id' => 'integer',
        'remainingShopBalance' => 'double',
        'shopOpeningBalance' => 'double',
        'status' => 'integer',
        'meta' => 'json',
    ];
}
