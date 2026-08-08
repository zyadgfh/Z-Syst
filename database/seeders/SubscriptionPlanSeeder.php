<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small pharmacies',
                'price' => 29.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_default' => true,
                'features' => [
                    'Up to 3 branches',
                    'Up to 1000 products',
                    'Basic inventory management',
                    'Barcode printing',
                    'Standard reports',
                    'Email support',
                ],
                'limits' => [
                    'branches' => 3,
                    'products' => 1000,
                    'users' => 5,
                    'transactions' => 1000,
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing pharmacy chains',
                'price' => 79.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_default' => false,
                'features' => [
                    'Up to 10 branches',
                    'Up to 5000 products',
                    'Advanced inventory management',
                    'Barcode printing',
                    'Advanced reports',
                    'Priority support',
                    'API access',
                    'Multi-location stock',
                ],
                'limits' => [
                    'branches' => 10,
                    'products' => 5000,
                    'users' => 20,
                    'transactions' => 10000,
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large pharmacy networks',
                'price' => 199.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 30,
                'is_active' => true,
                'is_default' => false,
                'features' => [
                    'Unlimited branches',
                    'Unlimited products',
                    'Advanced inventory management',
                    'Barcode printing',
                    'Advanced reports',
                    '24/7 support',
                    'API access',
                    'Multi-location stock',
                    'Custom integrations',
                    'Dedicated account manager',
                    'SLA guarantee',
                ],
                'limits' => [
                    'branches' => null, // unlimited
                    'products' => null, // unlimited
                    'users' => null, // unlimited
                    'transactions' => null, // unlimited
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'Starter Yearly',
                'slug' => 'starter-yearly',
                'description' => 'Starter plan with 20% discount',
                'price' => 287.90,
                'currency' => 'USD',
                'billing_cycle' => 'yearly',
                'trial_days' => 14,
                'is_active' => true,
                'is_default' => false,
                'features' => [
                    'Up to 3 branches',
                    'Up to 1000 products',
                    'Basic inventory management',
                    'Barcode printing',
                    'Standard reports',
                    'Email support',
                ],
                'limits' => [
                    'branches' => 3,
                    'products' => 1000,
                    'users' => 5,
                    'transactions' => 1000,
                ],
                'sort_order' => 4,
            ],
            [
                'name' => 'Professional Yearly',
                'slug' => 'professional-yearly',
                'description' => 'Professional plan with 20% discount',
                'price' => 767.90,
                'currency' => 'USD',
                'billing_cycle' => 'yearly',
                'trial_days' => 14,
                'is_active' => true,
                'is_default' => false,
                'features' => [
                    'Up to 10 branches',
                    'Up to 5000 products',
                    'Advanced inventory management',
                    'Barcode printing',
                    'Advanced reports',
                    'Priority support',
                    'API access',
                    'Multi-location stock',
                ],
                'limits' => [
                    'branches' => 10,
                    'products' => 5000,
                    'users' => 20,
                    'transactions' => 10000,
                ],
                'sort_order' => 5,
            ],
            [
                'name' => 'Enterprise Yearly',
                'slug' => 'enterprise-yearly',
                'description' => 'Enterprise plan with 20% discount',
                'price' => 1919.90,
                'currency' => 'USD',
                'billing_cycle' => 'yearly',
                'trial_days' => 30,
                'is_active' => true,
                'is_default' => false,
                'features' => [
                    'Unlimited branches',
                    'Unlimited products',
                    'Advanced inventory management',
                    'Barcode printing',
                    'Advanced reports',
                    '24/7 support',
                    'API access',
                    'Multi-location stock',
                    'Custom integrations',
                    'Dedicated account manager',
                    'SLA guarantee',
                ],
                'limits' => [
                    'branches' => null,
                    'products' => null,
                    'users' => null,
                    'transactions' => null,
                ],
                'sort_order' => 6,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
