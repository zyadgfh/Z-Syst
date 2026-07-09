<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoBranchStockSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return;
        }

        if (! Schema::hasTable('branches') || ! Schema::hasTable('product_stocks')) {
            return;
        }

        $branches = DB::table('branches')->where('company_id', $company->id)->get();
        $drugs = DB::table('drugs')->where('company_id', $company->id)->get();

        foreach ($branches as $branch) {
            foreach ($drugs as $drug) {
                $productIdCol = Schema::hasColumn('product_stocks', 'drug_id') ? 'drug_id' : (Schema::hasColumn('product_stocks', 'product_id') ? 'product_id' : null);
                if (! $productIdCol) {
                    continue;
                }

                $targetId = $drug->id;
                if ($productIdCol === 'product_id' && Schema::hasTable('products')) {
                    $product = DB::table('products')->where('barcode', $drug->barcode)->first();
                    if (! $product) {
                        continue;
                    }
                    $targetId = $product->id;
                }

                DB::table('product_stocks')->updateOrInsert([
                    'company_id' => $company->id,
                    $productIdCol => $targetId,
                    'branch_id' => $branch->id,
                ], [
                    'quantity' => rand(5, 100),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
