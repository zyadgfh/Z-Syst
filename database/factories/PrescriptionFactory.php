<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Doctor;
use App\Models\Party;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'party_id' => Party::factory(),
            'image' => fake()->imageUrl(),
            'prescription_number' => 'RX-' . fake()->unique()->numerify('######'),
            'status' => fake()->randomElement(['pending', 'used']),
            'review_status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'expires_at' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'patient_name' => fake()->name(),
            'patient_phone' => fake()->phoneNumber(),
            'doctor_name' => fake()->name(),
            'doctor_license' => fake()->numerify('DR-#####'),
            'max_refills' => fake()->numberBetween(0, 5),
            'refill_count' => fake()->numberBetween(0, 3),
            'refill_expiry_date' => fake()->dateTimeBetween('+2 months', '+1 year'),
            'is_controlled_substance' => fake()->boolean(10),
            'schedule' => fake()->optional()->randomElement(['I', 'II', 'III', 'IV', 'V']),
            'notes' => fake()->optional()->text(),
        ];
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'review_status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function used(): self
    {
        return $this->state(fn () => [
            'status' => 'used',
            'review_status' => 'approved',
            'used_at' => now(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expires_at' => fake()->dateTimeBetween('-2 months', '-1 week'),
        ]);
    }

    public function controlledSubstance(): self
    {
        return $this->state(fn () => [
            'is_controlled_substance' => true,
            'schedule' => fake()->randomElement(['II', 'III', 'IV', 'V']),
        ]);
    }

    public function withRefills(): self
    {
        return $this->state(fn () => [
            'max_refills' => fake()->numberBetween(1, 5),
            'refill_count' => 0,
        ]);
    }
}
