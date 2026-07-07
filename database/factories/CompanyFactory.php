<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'max_branches' => fake()->numberBetween(1, 20),
            'is_unlimited_branches' => fake()->boolean(20),
            'default_branch_limit' => fake()->numberBetween(1, 20),
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_branches' => null,
            'is_unlimited_branches' => true,
        ]);
    }

    public function withBranchCount(int $count): static
    {
        return $this->afterCreating(function ($company) use ($count) {
            Branch::factory()->count($count)->create(['company_id' => $company->id]);
        });
    }
}
