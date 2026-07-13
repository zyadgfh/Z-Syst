<?php

namespace Database\Factories;

use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\Factory;

class OptionFactory extends Factory
{
    protected $model = Option::class;

    public function definition(): array
    {
        return [
            'key' => fake()->randomElement(['general', 'payment', 'notifications', 'appearance']),
            'value' => ['setting' => fake()->word(), 'enabled' => fake()->boolean()],
            'status' => fake()->boolean(90),
        ];
    }
}
