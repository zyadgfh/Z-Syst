<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeAttendanceFactory extends Factory
{
    protected $model = EmployeeAttendance::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'check_in_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'check_out_at' => $this->faker->dateTimeBetween('now', '+8 hours'),
            'check_in_method' => $this->faker->randomElement(['qr', 'manual', 'biometric']),
            'check_in_qr_token' => 'ATTENDANCE_' . $this->faker->numberBetween(1000, 9999) . '_' . strtoupper($this->faker->unique()->regexify('[A-Z]{8}')),
            'ip_address' => $this->faker->ipv4,
            'location' => $this->faker->city,
            'notes' => $this->faker->sentence,
        ];
    }

    /**
     * Indicate that the attendance is open (checked in but not out)
     */
    public function open(): self
    {
        return $this->state(fn (array $attributes) => [
            'check_out_at' => null,
        ]);
    }
}