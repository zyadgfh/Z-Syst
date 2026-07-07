<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;

class DemoPricingTiersSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return;
        }

        if (! Schema::hasTable('drug_prices')) {
            return;
        }

        $drugs = DB::table('drugs')->where('company_id', $company->id)->get();
        foreach ($drugs as $drug) {
            $hasTier = Schema::hasColumn('drug_prices', 'tier');
            $baseWhere = ['company_id' => $company->id, ($hasTier ? 'drug_id' : 'drug_id') => $drug->id, 'branch_id' => null];

            if ($hasTier) {
                DB::table('drug_prices')->updateOrInsert(array_merge($baseWhere, ['tier' => 'retail']), ['price' => rand(100, 1000) / 10, 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now()]);
                DB::table('drug_prices')->updateOrInsert(array_merge($baseWhere, ['tier' => 'wholesale']), ['price' => rand(80, 900) / 10, 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now()]);
            } else {
                // fallback: create a single price row when no tier-like column exists
                DB::table('drug_prices')->updateOrInsert($baseWhere, ['price' => rand(100, 1000) / 10, 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
