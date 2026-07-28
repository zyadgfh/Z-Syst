<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'business_category_id' => BusinessCategory::factory(),
            'companyName' => fake()->company(),
            'address' => fake()->address(),
            'phoneNumber' => fake()->phoneNumber(),
            'remainingShopBalance' => fake()->randomFloat(2, 0, 10000),
            'shopOpeningBalance' => fake()->randomFloat(2, 0, 10000),
        ];
    }
}