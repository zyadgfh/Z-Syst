<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'company_code',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'will_expire' => 'datetime',
        'subscriptionDate' => 'datetime',
        'remainingShopBalance' => 'decimal:2',
        'shopOpeningBalance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function enrolled_plan()
    {
        return $this->belongsTo(PlanSubscribe::class, 'plan_subscribe_id');
    }

    public function category()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'business_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'company_id');
    }

    public function paymentGateways()
    {
        return $this->hasMany(CompanyPaymentGateway::class, 'company_id');
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'company_id');
    }
}
