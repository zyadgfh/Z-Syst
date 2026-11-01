<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SubscriptionPlan - table `subscription_plans` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'trial_days',
        'is_active',
        'is_default',
        'features',
        'limits',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'features' => 'array',
        'limits' => 'array',
        'sort_order' => 'integer',
    ];
}
