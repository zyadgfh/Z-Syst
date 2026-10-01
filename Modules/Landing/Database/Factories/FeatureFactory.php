<?php

namespace Modules\Landing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Landing\App\Models\Feature;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'bg_color' => fake()->hexColor(),
            'image' => null,
            'status' => 1,
        ];
    }
}
