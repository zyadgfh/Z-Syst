<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->name(),
            'national_id' => fake()->unique()->numerify('############'),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->email(),
            'address' => fake()->address(),
            'medical_history' => fake()->optional()->text(),
            'allergies' => fake()->optional()->randomElement([
                'Penicillin',
                'Aspirin',
                'Sulfa drugs',
                'Latex',
                'Dust mites',
                'Pollen',
                'Pet dander',
            ]),
            'chronic_conditions' => fake()->optional()->randomElement([
                'Diabetes',
                'Hypertension',
                'Asthma',
                'Arthritis',
                'Heart disease',
            ]),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'notes' => fake()->optional()->text(),
        ];
    }

    public function child(): self
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-17 years', '-1 year'),
        ]);
    }

    public function elderly(): self
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-90 years', '-65 years'),
        ]);
    }

    public function withAllergies(): self
    {
        return $this->state(fn (array $attributes) => [
            'allergies' => fake()->randomElement([
                'Penicillin, Aspirin',
                'Sulfa drugs, Latex',
                'Dust mites, Pollen',
            ]),
        ]);
    }

    public function withChronicConditions(): self
    {
        return $this->state(fn (array $attributes) => [
            'chronic_conditions' => fake()->randomElement([
                'Diabetes, Hypertension',
                'Asthma, Arthritis',
                'Heart disease, Diabetes',
            ]),
        ]);
    }
}
