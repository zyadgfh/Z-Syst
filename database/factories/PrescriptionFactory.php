<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'prescription_number' => 'RX-'.strtoupper($this->faker->bothify('????####')),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'branch_id' => Branch::factory(),
            'status' => $this->faker->randomElement(['pending', 'dispensed', 'partially_dispensed', 'cancelled', 'expired']),
            'prescribed_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('now', '+90 days')->format('Y-m-d'),
            'notes' => $this->faker->optional()->sentence(),
            'image_path' => null,
            'created_by' => User::factory(),
        ];
    }
}
