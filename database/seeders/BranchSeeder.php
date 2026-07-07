<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function ($company) {
            $branchCount = $company->is_unlimited_branches
                ? rand(5, 15)
                : min(rand(1, 10), $company->max_branches);

            Branch::factory()->count($branchCount)->create(['company_id' => $company->id]);
        });
    }
}
