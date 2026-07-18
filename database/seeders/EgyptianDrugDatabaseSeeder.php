<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Egyptian Drug Database Seeder
 * 
 * Seeds the database with common Egyptian medications
 */
class EgyptianDrugDatabaseSeeder extends Seeder
{
    /**
     * List of common Egyptian medications.
     */
    protected array $medications = [
        // Pain Relief & Anti-inflammatory
        [
            'generic_name' => 'Paracetamol',
            'brand_names' => ['Panadol', 'Rheumox', 'Calpol'],
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Pain Relief'],
        ],
        [
            'generic_name' => 'Ibuprofen',
            'brand_names' => ['Brufen', 'Nurofen', 'Advil'],
            'strength' => '400mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Pain Relief', 'Anti-inflammatory'],
        ],
        [
            'generic_name' => 'Diclofenac',
            'brand_names' => ['Voltaren', 'Flector'],
            'strength' => '50mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Anti-inflammatory'],
        ],
        
        // Antibiotics
        [
            'generic_name' => 'Amoxicillin',
            'brand_names' => ['Amoxil', 'Trimox', 'Flemoxin'],
            'strength' => '500mg',
            'dosage_form' => 'Capsule',
            'categories' => ['Antibiotics'],
        ],
        [
            'generic_name' => 'Ciprofloxacin',
            'brand_names' => ['Cipro', 'Ciprodar', 'Floxcin'],
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Antibiotics'],
        ],
        [
            'generic_name' => 'Azithromycin',
            'brand_names' => ['Zithromax', 'Azithrocin'],
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Antibiotics'],
        ],
        
        // Antihistamines
        [
            'generic_name' => 'Cetirizine',
            'brand_names' => ['Zyrtec', 'Cetrizine', 'Zyrtec-D'],
            'strength' => '10mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Allergy'],
        ],
        [
            'generic_name' => 'Loratadine',
            'brand_names' => ['Claritin', 'Alavert'],
            'strength' => '10mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Allergy'],
        ],
        
        // Respiratory
        [
            'generic_name' => 'Salbutamol',
            'brand_names' => ['Ventolin', 'Ventolair', 'Salbutamol'],
            'strength' => '2mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Respiratory'],
        ],
        [
            'generic_name' => 'Budesonide',
            'brand_names' => ['Pulmicort', 'Budecort'],
            'strength' => '100mcg',
            'dosage_form' => 'Inhaler',
            'categories' => ['Respiratory'],
        ],
        
        // Cardiovascular
        [
            'generic_name' => 'Atenolol',
            'brand_names' => ['Tenormin', 'Atenex'],
            'strength' => '50mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Cardiovascular'],
        ],
        [
            'generic_name' => 'Losartan',
            'brand_names' => ['Cozaar', 'Hypertensid'],
            'strength' => '50mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Cardiovascular'],
        ],
        
        // Vitamins & Supplements
        [
            'generic_name' => 'Vitamin C',
            'brand_names' => ['Ascorbic Acid', 'Vitamin C', 'C-Max'],
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Vitamins'],
        ],
        [
            'generic_name' => 'Multivitamin',
            'brand_names' => ['Centrum', 'Supradyn', 'Multi-Vit'],
            'strength' => 'One tablet daily',
            'dosage_form' => 'Tablet',
            'categories' => ['Vitamins'],
        ],
        
        // Gastrointestinal
        [
            'generic_name' => 'Omeprazole',
            'brand_names' => ['Losec', 'Omeprazole', 'Protonix'],
            'strength' => '20mg',
            'dosage_form' => 'Capsule',
            'categories' => ['Gastrointestinal'],
        ],
        [
            'generic_name' => 'Ranitidine',
            'brand_names' => ['Zantac', 'Ranitidine'],
            'strength' => '150mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Gastrointestinal'],
        ],
        
        // Diabetes
        [
            'generic_name' => 'Metformin',
            'brand_names' => ['Glucophage', 'Metformin', 'Glucosal'],
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Diabetes'],
        ],
        [
            'generic_name' => 'Glibenclamide',
            'brand_names' => ['Glyburide', 'Glibenclamide'],
            'strength' => '5mg',
            'dosage_form' => 'Tablet',
            'categories' => ['Diabetes'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = 1; // Default company

        DB::transaction(function () use ($companyId) {
            foreach ($this->medications as $medication) {
                // Get or create categories
                $categoryIds = [];
                foreach ($medication['categories'] as $categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName, 'company_id' => $companyId],
                        ['company_id' => $companyId, 'is_active' => true]
                    );
                    $categoryIds[] = $category->id;
                }

                // Create main category
                $mainCategory = Category::firstOrCreate(
                    ['name' => $medication['categories'][0], 'company_id' => $companyId],
                    ['company_id' => $companyId, 'is_active' => true]
                );

                // Create product for each brand
                foreach ($medication['brand_names'] as $brandName) {
                    $product = Product::create([
                        'company_id' => $companyId,
                        'category_id' => $mainCategory->id,
                        'generic_name' => $medication['generic_name'],
                        'brand_name' => $brandName,
                        'product_name' => $brandName,
                        'strength' => $medication['strength'],
                        'dosage_form' => $medication['dosage_form'],
                        'product_code' => 'EGY-' . strtoupper(substr($medication['generic_name'], 0, 3)) . '-' . rand(10, 99),
                        'prescription_required' => true,
                        'is_active' => true,
                        'track_inventory' => true,
                        'reorder_level' => 10,
                    ]);

                    // Add variants (different package sizes)
                    foreach ([10, 20, 30] as $count) {
                        ProductVariant::create([
                            'company_id' => $companyId,
                            'product_id' => $product->id,
                            'variant_name' => "{$count} Tablets",
                            'unit_quantity' => $count,
                            'is_default' => $count === 10,
                        ]);
                    }
                }
            }
        });

        $this->command->info('✅ Egyptian Drug Database seeded successfully!');
    }
}