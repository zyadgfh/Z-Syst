<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->email(),
            'address' => fake()->optional()->address(),
            'date_of_birth' => fake()->optional()->date(),
            'gender' => fake()->randomElement(['male', 'female']),
            'blood_group' => fake()->optional()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'allergies' => fake()->optional()->words(2, true),
            'medical_history' => fake()->optional()->sentences(2, true),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => 1,
        ];
    }
}