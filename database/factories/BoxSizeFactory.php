<?php

namespace Database\Factories;

use App\Models\BoxSize;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoxSizeFactory extends Factory
{
    protected $model = BoxSize::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'company_id' => Company::factory(),
            'status' => fake()->boolean(90),
        ];
    }
}
