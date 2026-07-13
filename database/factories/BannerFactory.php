<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'imageUrl' => fake()->imageUrl(1200, 400, 'business', true, true),
            'status' => fake()->boolean(90),
        ];
    }
}
