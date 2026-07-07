<?php

namespace Database\Seeders;

use App\Models\Drug;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDrugSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return;
        }

        $names = [
            'Paracetamol 500mg',
            'Amoxicillin 500mg',
            'Ibuprofen 200mg',
            'Cetirizine 10mg',
            'Omeprazole 20mg',
        ];

        foreach ($names as $name) {
            Drug::updateOrCreate(
                ['company_id' => $company->id, 'barcode' => Str::slug($name)],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'generic_name' => $name,
                    'manufacturer' => 'DemoPharma',
                ]
            );
        }
    }
}
