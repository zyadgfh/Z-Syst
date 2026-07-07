<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;

class DemoInventoryMovementsSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return;
        }

        if (! Schema::hasTable('inventory_movements') && ! Schema::hasTable('stock_movements')) {
            return;
        }

        $table = Schema::hasTable('inventory_movements') ? 'inventory_movements' : 'stock_movements';
        $drugs = DB::table('drugs')->where('company_id', $company->id)->get();

        foreach ($drugs as $drug) {
            DB::table($table)->insert([
                'company_id' => $company->id,
                'drug_id' => $drug->id,
                'quantity' => rand(1, 50),
                'type' => 'in',
                'reference' => 'seed-' . now()->format('YmdHis'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
