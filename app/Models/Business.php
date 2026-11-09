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
        'company_name',
        'address',
        'phone_number',
        'picture_url',
        'will_expire',
        'subscription_date',
        'remaining_shop_balance',
        'shop_opening_balance',
        'status',
        'store_slug',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    
    public function getCompanyNameAttribute(): ?string { return $this->attributes['company_name'] ?? null; }
    public function setCompanyNameAttribute($v): void { $this->attributes['company_name'] = $v; }
    public function getPhoneNumberAttribute(): ?string { return $this->attributes['phone_number'] ?? null; }
    public function setPhoneNumberAttribute($v): void { $this->attributes['phone_number'] = $v; }
    public function getPictureUrlAttribute(): ?string { return $this->attributes['picture_url'] ?? null; }
    public function setPictureUrlAttribute($v): void { $this->attributes['picture_url'] = $v; }
    public function getSubscriptionDateAttribute() { return $this->attributes['subscription_date'] ?? null; }
    public function setSubscriptionDateAttribute($v): void { $this->attributes['subscription_date'] = $v; }
    public function getRemainingShopBalanceAttribute() { return $this->attributes['remaining_shop_balance'] ?? null; }
    public function setRemainingShopBalanceAttribute($v): void { $this->attributes['remaining_shop_balance'] = $v; }
    public function getShopOpeningBalanceAttribute() { return $this->attributes['shop_opening_balance'] ?? null; }
    public function setShopOpeningBalanceAttribute($v): void { $this->attributes['shop_opening_balance'] = $v; }

    protected $casts = [
        'status' => 'boolean',
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
}
