<?php

namespace Modules\Landing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Landing\App\Models\Feature;

class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition()
    {
        return [
            'title' => fake()->sentence(),
            'bg_color' => fake()->hexColor(),
            'status' => 1,
        ];
    }
}
