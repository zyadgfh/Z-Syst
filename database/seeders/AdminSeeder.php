<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        if (!User::where('email', 'admin@zsyst.com')->exists()) {
            $password = env('ADMIN_INITIAL_PASSWORD');
            if (! is_string($password) || strlen($password) < 16) {
                throw new \RuntimeException('ADMIN_INITIAL_PASSWORD must be configured with at least 16 characters.');
            }
            User::create([
                'name' => 'Admin',
                'email' => 'admin@zsyst.com',
                'password' => bcrypt($password),
                'role' => 'superadmin',
            ]);
        }
    }
}
