<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Supplier;
use App\Models\SupplierRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierRatingFactory extends Factory
{
    protected $model = SupplierRating::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'business_id' => Business::factory(),
            'rating' => fake()->randomFloat(1, 1, 5),
            'category' => fake()->randomElement(['quality', 'delivery', 'price', 'communication']),
            'review' => fake()->optional(0.7)->sentence(),
            'rated_by' => User::factory(),
            'rated_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
