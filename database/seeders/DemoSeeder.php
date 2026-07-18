<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate([
            'slug' => 'demo-pharmacy',
        ], [
            'name' => 'Demo Pharmacy',
        ]);

        $user = User::firstOrCreate([
            'email' => 'demo@local',
        ], [
            'name' => 'Demo Admin',
            'password' => Hash::make('secret'),
            'company_id' => $company->id,
            'status' => true,
        ]);

        // assign super-admin role if Spatie exists; create role if missing
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin']);
        }

        if (method_exists($user, 'assignRole')) {
            try {
                $user->assignRole('super-admin');
            } catch (\Throwable $e) {
                // ignore if role assignment fails
            }
        }
    }
}
