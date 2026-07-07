<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder as BaseSeeder;

class DatabaseSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CompanySeeder::class,
            BranchSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
