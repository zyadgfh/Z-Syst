<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\PlanSubscribe;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'plan_subscribe_id' => PlanSubscribe::factory(),
            'business_category_id' => BusinessCategory::factory(),
            'companyName' => fake()->company(),
            'address' => fake()->address(),
            'phoneNumber' => fake()->phoneNumber(),
            'pictureUrl' => fake()->imageUrl(400, 300, 'business', true, true),
            'will_expire' => fake()->dateTimeBetween('+30 days', '+2 years'),
            'subscriptionDate' => fake()->dateTimeBetween('-1 year', 'now'),
            'remainingShopBalance' => fake()->randomFloat(2, 0, 20000),
            'shopOpeningBalance' => fake()->randomFloat(2, 0, 20000),
        ];
    }
}
