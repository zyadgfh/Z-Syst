<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Supplier;
use App\Models\SupplierRating;
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
            'category' => fake()->randomElement(['quality', 'delivery', 'price', 'service']),
            'review' => fake()->sentence(),
            'rated_by' => null,
            'rated_at' => now(),
        ];
    }
}
