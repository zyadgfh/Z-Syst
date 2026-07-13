<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug();

        return [
            'name' => $slug,
            'slug' => $slug,
            'description' => fake()->sentence(),
            'guard_name' => 'web',
            'color_badge' => fake()->hexColor(),
            'priority' => fake()->numberBetween(0, 100),
            'is_system' => false,
            'status' => true,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
