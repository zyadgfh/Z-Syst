<?php

namespace Modules\Landing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Landing\Models\Feature;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition()
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'icon' => fake()->randomElement(['heroicon-o-star', 'heroicon-o-heart', 'heroicon-o-bolt']),
            'status' => 1,
        ];
    }
}
