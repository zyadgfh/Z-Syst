<?php

namespace Modules\ZSyst\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ZSyst\App\Models\Drug;
use Modules\ZSyst\App\Models\InventoryItem;

class PosTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample drugs with barcodes
        $drugs = [
            [
                'name' => 'Paracetamol 500mg',
                'scientific_name' => 'Acetaminophen',
                'barcode' => '1234567890123',
                'generic_name' => 'Paracetamol',
                'strength' => '500mg',
                'form' => 'Tablet',
                'manufacturer' => 'PharmaCorp',
                'purchase_price' => 3.50,
                'sale_price' => 5.99,
                'wholesale_price' => 4.50,
                'stock_alert' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Amoxicillin 250mg',
                'scientific_name' => 'Amoxicillin',
                'barcode' => '9876543210987',
                'generic_name' => 'Amoxicillin',
                'strength' => '250mg',
                'form' => 'Capsule',
                'manufacturer' => 'MediLife',
                'purchase_price' => 8.00,
                'sale_price' => 12.50,
                'wholesale_price' => 10.00,
                'stock_alert' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Ibuprofen 400mg',
                'scientific_name' => 'Ibuprofen',
                'barcode' => '4567890123456',
                'generic_name' => 'Ibuprofen',
                'strength' => '400mg',
                'form' => 'Tablet',
                'manufacturer' => 'HealthPlus',
                'purchase_price' => 5.00,
                'sale_price' => 8.75,
                'wholesale_price' => 6.50,
                'stock_alert' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Vitamin C 1000mg',
                'scientific_name' => 'Ascorbic Acid',
                'barcode' => '7890123456789',
                'generic_name' => 'Vitamin C',
                'strength' => '1000mg',
                'form' => 'Tablet',
                'manufacturer' => 'VitaHealth',
                'purchase_price' => 8.00,
                'sale_price' => 15.00,
                'wholesale_price' => 12.00,
                'stock_alert' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Aspirin 100mg',
                'scientific_name' => 'Acetylsalicylic Acid',
                'barcode' => '3456789012345',
                'generic_name' => 'Aspirin',
                'strength' => '100mg',
                'form' => 'Tablet',
                'manufacturer' => 'AspirinCo',
                'purchase_price' => 2.50,
                'sale_price' => 4.50,
                'wholesale_price' => 3.50,
                'stock_alert' => 25,
                'is_active' => true,
            ],
        ];

        foreach ($drugs as $drugData) {
            $drug = Drug::firstOrCreate(
                ['barcode' => $drugData['barcode']],
                $drugData
            );

            // Create inventory items for each drug
            if ($drug->inventoryItems()->count() === 0) {
                InventoryItem::create([
                    'drug_id' => $drug->id,
                    'batch_number' => 'BATCH-' . strtoupper(substr($drug->barcode, 0, 4)),
                    'expiry_date' => now()->addYears(2),
                    'quantity_on_hand' => rand(20, 100),
                    'unit_cost' => $drug->purchase_price,
                    'location' => 'A1-SHELF-' . rand(1, 10),
                ]);
            }
        }

        $this->command->info('POS test data seeded successfully!');
    }
}