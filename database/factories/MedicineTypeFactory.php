<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MedicineType;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicineTypeFactory extends Factory
{
    protected $model = MedicineType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'company_id' => Company::factory(),
            'status' => fake()->boolean(90),
        ];
    }
}
