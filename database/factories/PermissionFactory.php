<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'guard_name' => 'web',
            'module' => fake()->randomElement(['Dashboard', 'Users', 'Roles', 'Branches', 'Products', 'Sales', 'Reports']),
            'group' => fake()->randomElement(['Management', 'Operations', 'Reporting']),
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => true,
        ];
    }

    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
