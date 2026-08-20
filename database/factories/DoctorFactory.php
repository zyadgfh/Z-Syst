<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->name(),
            'license_number' => fake()->unique()->numerify('DR-#####'),
            'specialization' => fake()->randomElement([
                'General Practice',
                'Cardiology',
                'Dermatology',
                'Neurology',
                'Pediatrics',
                'Orthopedics',
                'Internal Medicine',
                'Family Medicine',
            ]),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
            'clinic_name' => fake()->company(),
            'clinic_address' => fake()->address(),
            'signature_image' => null,
            'is_active' => true,
            'notes' => fake()->optional()->text(),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withSignature(): self
    {
        return $this->state(fn (array $attributes) => [
            'signature_image' => 'signatures/' . fake()->uuid() . '.png',
        ]);
    }
}
