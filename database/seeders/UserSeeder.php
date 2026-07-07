<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
            ]
        );

        // create additional demo users only if they don't already exist
        $existingCount = User::count();
        if ($existingCount < 20) {
            User::factory()->count(10)->create();
        }
    }
}
