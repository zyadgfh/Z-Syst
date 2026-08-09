<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@z-syst.com',
            'password' => Hash::make('SuperAdmin123!'),
            'role' => 'superadmin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Test Admin User
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Test Staff User
        $staff = User::create([
            'name' => 'Test Staff',
            'email' => 'staff@test.com',
            'password' => Hash::make('Staff123!'),
            'role' => 'staff',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->command->info('Users seeded successfully');
    }
}
