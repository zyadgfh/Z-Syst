<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds public.app_users rows.
 *
 * Path B removed the local credential columns: there is no email / password /
 * remember_token in app_users (identity lives in auth.users). A factory row is
 * therefore a *profile*; authentication for it must exist in GoTrue, which is
 * why tests should stub the provider rather than rely on a password here.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'business_id' => Business::factory(),
            'name' => fake()->name(),
            'role' => 'staff',
            'status' => 'active',
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => 'superadmin', 'business_id' => null]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
