<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManufacturerFactory extends Factory
{
    protected $model = Manufacturer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'company_id' => Company::factory(),
            'description' => fake()->sentence(),
            'status' => fake()->boolean(90),
        ];
    }
}
