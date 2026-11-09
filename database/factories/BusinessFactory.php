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
            'company_name' => fake()->company(),
            'address' => fake()->address(),
            'phone_number' => fake()->phoneNumber(),
            'remaining_shop_balance' => fake()->randomFloat(2, 0, 10000),
            'shop_opening_balance' => fake()->randomFloat(2, 0, 10000),
        ];
    }
}
