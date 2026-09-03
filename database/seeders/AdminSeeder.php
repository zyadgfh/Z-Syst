<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        if (!User::where('email', 'admin@zsyst.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@zsyst.com',
                'password' => bcrypt(env('ADMIN_INITIAL_PASSWORD', 'Admin@Secure2026!')),
                'role' => 'superadmin',
            ]);
        }
    }
}
