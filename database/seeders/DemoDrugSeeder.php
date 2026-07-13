<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Drug;
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

        if (! \Illuminate\Support\Facades\Schema::hasTable('drugs')) {
            // Create minimal drugs table for tests if migrations didn't run
            \Illuminate\Support\Facades\Schema::create('drugs', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->uuid('uuid')->nullable();
                $table->string('name');
                $table->string('generic_name')->nullable();
                $table->string('barcode')->nullable();
                $table->string('manufacturer')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        $names = [
            'Paracetamol 500mg',
            'Amoxicillin 500mg',
            'Ibuprofen 200mg',
            'Cetirizine 10mg',
            'Omeprazole 20mg',
        ];

        foreach ($names as $name) {
            \Illuminate\Support\Facades\DB::table('drugs')->updateOrInsert(
                ['company_id' => $company->id, 'barcode' => Str::slug($name)],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'generic_name' => $name,
                    'manufacturer' => 'DemoPharma',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
