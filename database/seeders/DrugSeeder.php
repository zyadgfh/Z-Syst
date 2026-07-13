<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Drug;
use Illuminate\Database\Seeder;

class DrugSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::factory()->create();

        Drug::factory()->count(12)->create([
            'company_id' => $company->id,
        ]);
    }
}
