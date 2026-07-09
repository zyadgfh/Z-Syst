<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'max_users',
        'max_branches',
        'max_products',
        'max_transactions_monthly',
        'has_advanced_reports',
        'has_insurance_integration',
        'has_api_access',
        'has_priority_support',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'max_users' => 'integer',
        'max_branches' => 'integer',
        'max_products' => 'integer',
        'max_transactions_monthly' => 'integer',
        'has_advanced_reports' => 'boolean',
        'has_insurance_integration' => 'boolean',
        'has_api_access' => 'boolean',
        'has_priority_support' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }
}
