<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSetupSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'Demo Company'],
            ['max_branches' => 5, 'is_unlimited_branches' => false]
        );

        $branch = Branch::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'Main Branch',
        ], [
            'address' => 'Headquarters',
            'phone' => null,
            'is_active' => true,
        ]);

        $admin = User::where('email', 'admin@example.com')->first();

        if ($admin) {
            $admin->company_id = $company->id;
            $admin->branch_id = $branch->id;
            $admin->save();
        }
    }
}
