<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return;
        }

        // Seed drug prices if table exists; detect column names
        if (Schema::hasTable('drug_prices')) {
            $drugs = DB::table('drugs')->where('company_id', $company->id)->get();
            $priceTargetCol = Schema::hasColumn('drug_prices', 'drug_id') ? 'drug_id' : (Schema::hasColumn('drug_prices', 'product_id') ? 'product_id' : null);
            foreach ($drugs as $drug) {
                if (! $priceTargetCol) {
                    continue;
                }

                $targetId = $drug->id;
                // If the prices table expects a product_id, attempt to map to products table
                if ($priceTargetCol === 'product_id' && Schema::hasTable('products')) {
                    $product = DB::table('products')
                        ->where('barcode', $drug->barcode)
                        ->orWhere('name', $drug->name)
                        ->first();
                    if (! $product) {
                        // no mapping available; skip to avoid FK violations
                        continue;
                    }
                    $targetId = $product->id;
                }

                DB::table('drug_prices')->updateOrInsert([
                    'company_id' => $company->id,
                    $priceTargetCol => $targetId,
                    'branch_id' => null,
                ], [
                    'price' => rand(100, 1000) / 10,
                    'currency' => 'USD',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Seed product stocks if table exists; detect column names
        if (Schema::hasTable('product_stocks')) {
            $drugs = DB::table('drugs')->where('company_id', $company->id)->get();
            $stockTargetCol = Schema::hasColumn('product_stocks', 'drug_id') ? 'drug_id' : (Schema::hasColumn('product_stocks', 'product_id') ? 'product_id' : null);
            foreach ($drugs as $drug) {
                if (! $stockTargetCol) {
                    continue;
                }

                $targetId = $drug->id;
                if ($stockTargetCol === 'product_id' && Schema::hasTable('products')) {
                    $product = DB::table('products')
                        ->where('barcode', $drug->barcode)
                        ->orWhere('name', $drug->name)
                        ->first();
                    if (! $product) {
                        // attempt to create a minimal product mapping so stocks/prices can be seeded
                        try {
                            $productId = DB::table('products')->insertGetId([
                                'company_id' => $company->id,
                                'name' => $drug->name,
                                'barcode' => $drug->barcode,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $product = (object) ['id' => $productId];
                        } catch (\Exception $e) {
                            // failed to create product mapping; skip
                            continue;
                        }
                    }
                    $targetId = $product->id;
                }

                DB::table('product_stocks')->updateOrInsert([
                    'company_id' => $company->id,
                    $stockTargetCol => $targetId,
                    'branch_id' => null,
                ], [
                    'quantity' => rand(10, 200),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
