<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\StockAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAuditFactory extends Factory
{
    protected $model = StockAudit::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'audit_number' => 'SA-' . fake()->numerify('#####'),
            'audit_type' => fake()->randomElement(['periodic', 'manual', 'spot_check', 'financial']),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'audit_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => 'in_progress']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'completed_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
