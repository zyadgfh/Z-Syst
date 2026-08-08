<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'subscriptionName' => 'Starter',
                'subscriptionPrice' => 29.99,
                'duration' => 30,
                'features' => json_encode([
                    'up to 50 products',
                    'up to 2 users',
                    'basic reporting',
                    'email support',
                ]),
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subscriptionName' => 'Professional',
                'subscriptionPrice' => 79.99,
                'duration' => 30,
                'features' => json_encode([
                    'unlimited products',
                    'up to 10 users',
                    'advanced reporting',
                    'priority support',
                    'multi-warehouse',
                ]),
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subscriptionName' => 'Enterprise',
                'subscriptionPrice' => 199.99,
                'duration' => 30,
                'features' => json_encode([
                    'unlimited products',
                    'unlimited users',
                    'custom reporting',
                    '24/7 phone support',
                    'multi-warehouse',
                    'API access',
                    'custom integrations',
                ]),
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('plans')->insert($plans);
    }
}
