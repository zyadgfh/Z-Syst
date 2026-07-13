<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'specialization' => fake()->randomElement(['General', 'Cardiology', 'Dermatology', 'Pediatrics']),
            'license_number' => fake()->bothify('LIC-#####'),
            'clinic_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'is_active' => fake()->boolean(90),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
